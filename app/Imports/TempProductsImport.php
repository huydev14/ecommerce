<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ImportProductRow;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TempProductsImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    protected $batchId;
    protected static array $seenProductNames = [];
    protected static array $seenSkus = [];
    protected static array $seenBarcodes = [];

    public function __construct($batchId)
    {
        $this->batchId = $batchId;
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty())
            return;

        $categoriesMap = $this->nameMap(Category::class, $rows->pluck('category_name'));
        $brandsMap = $this->nameMap(Brand::class, $rows->pluck('brand_name'));

        $dbProducts = Product::whereIn('name', $rows->pluck('product_name')
            ->filter()
            ->toArray())
            ->pluck('name')
            ->map(fn($v) => mb_strtolower(trim($v)))
            ->flip()
            ->toArray();
        $dbSkus = ProductVariant::whereIn('sku', $rows->pluck('sku')
            ->filter()
            ->toArray())
            ->pluck('sku')
            ->map(fn($v) => mb_strtolower(trim($v)))
            ->flip()
            ->toArray();
        $dbBarcodes = ProductVariant::whereIn('barcode', $rows->pluck('barcode')
            ->filter()
            ->toArray())
            ->pluck('barcode')
            ->map(fn($v) => mb_strtolower(trim($v)))
            ->flip()
            ->toArray();

        $rowsToInsert = [];

        foreach ($rows as $index => $row) {
            $payload = $this->normalizePayload($row, $categoriesMap, $brandsMap);
            $errors = $this->validatePayload($payload);
            $this->checkDuplicatesAndDatabase($payload, $dbProducts, $dbSkus, $dbBarcodes, $errors);

            $rowsToInsert[] = [
                'import_batch_id' => $this->batchId,
                'row_number' => $index + 2,
                'status' => empty($errors) ? 'valid' : 'error',
                'error_message' => empty($errors) ? null : implode(' ', $errors),
                'data' => json_encode($payload),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        ImportProductRow::insert($rowsToInsert);
    }

    private function normalizePayload($row, array $categoriesMap, array $brandsMap): array
    {
        $cName = mb_strtolower(trim($row['category_name'] ?? ''));
        $bName = mb_strtolower(trim($row['brand_name'] ?? ''));

        $isActive = $row['is_active'] ?? true;
        if (is_string($isActive)) {
            $isActive = in_array(strtolower(trim($isActive)), ['1', 'true', 'yes', 'y', 'active', 'on'], true);
        }

        return [
            'product' => [
                'name' => trim($row['product_name'] ?? ''),
                'description' => $row['description'] ?? null,
                'category_id' => $row['category_id'] ?? $categoriesMap[$cName] ?? null,
                'category_name' => $row['category_name'] ?? null,
                'brand_id' => $row['brand_id'] ?? $brandsMap[$bName] ?? null,
                'brand_name' => $row['brand_name'] ?? null,
                'status' => trim($row['status'] ?? 'draft'),
                'metadata' => $row['metadata'] ?? null,
            ],
            'variant' => [
                'sku' => trim($row['sku'] ?? ''),
                'barcode' => trim($row['barcode'] ?? '') ?: null,
                'price' => $row['price'] ?? 0,
                'compare_at_price' => $row['compare_at_price'] ?? null,
                'cost_price' => $row['cost_price'] ?? null,
                'attributes' => $row['attributes'] ?? null,
                'position' => $row['position'] ?? 0,
                'is_active' => (bool) $isActive,
            ],
        ];
    }

    private function validatePayload(array $payload): array
    {
        $validator = Validator::make(
            array_merge($payload['product'], $payload['variant']),
            [
                'name'        => ['required', 'string', 'min:2', 'max:255'],
                'category_id' => ['required', 'integer'],
                'status'      => ['required', 'in:draft,published,archived'],
                'sku'         => ['required', 'string', 'max:100'],
                'price'       => ['required', 'numeric', 'min:0'],
                'compare_at_price' => ['nullable', 'numeric', 'min:0'],
                'cost_price'  => ['nullable', 'numeric', 'min:0'],
                'metadata'    => ['nullable', 'json'],
                'attributes'  => ['nullable', 'json'],
                'position'    => ['nullable', 'integer', 'min:0'],
            ]
        );

        return $validator->errors()->all();
    }

    private function checkDuplicatesAndDatabase(array $payload, array $dbProducts, array $dbSkus, array $dbBarcodes, array &$errors): void
    {
        $pNameKey = mb_strtolower(trim($payload['product']['name']));
        $skuKey   = mb_strtolower(trim($payload['variant']['sku']));
        $barcodeKey = $payload['variant']['barcode'] ? mb_strtolower(trim($payload['variant']['barcode'])) : '';

        if ($pNameKey !== '') {
            if (isset(self::$seenProductNames[$pNameKey]))
                $errors[] = 'Tên sản phẩm bị trùng lặp trong file.';
            if (isset($dbProducts[$pNameKey]))
                $errors[] = 'Tên sản phẩm đã tồn tại trên hệ thống.';
            self::$seenProductNames[$pNameKey] = true;
        }

        if ($skuKey !== '') {
            if (isset(self::$seenSkus[$skuKey]))
                $errors[] = 'Mã SKU bị trùng lặp trong file.';
            if (isset($dbSkus[$skuKey]))
                $errors[] = 'Mã SKU đã tồn tại trên hệ thống.';
            self::$seenSkus[$skuKey] = true;
        }

        if ($barcodeKey !== '') {
            if (isset(self::$seenBarcodes[$barcodeKey]))
                $errors[] = 'Mã Barcode bị trùng lặp trong file.';
            if (isset($dbBarcodes[$barcodeKey]))
                $errors[] = 'Mã Barcode đã tồn tại trên hệ thống.';
            self::$seenBarcodes[$barcodeKey] = true;
        }
    }

    private function nameMap(string $modelClass, Collection $names): array
    {
        $cleanNames = $names->map(fn($n) => trim($n))->filter()->unique()->toArray();
        if (empty($cleanNames))
            return [];

        return $modelClass::whereIn('name', $cleanNames)
            ->pluck('id', 'name')
            ->mapWithKeys(fn($id, $name) => [mb_strtolower(trim($name)) => $id])
            ->toArray();
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}