<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ImportProductRow;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tax;
use App\Models\Unit;
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


    const EXCEL_COL_NAME = 'ten_san_pham';
    const EXCEL_COL_VARIANT = 'ten_bien_the';
    const EXCEL_COL_BRAND = 'nhan_hieu';
    const EXCEL_COL_UNIT = 'don_vi_tinh';
    const EXCEL_COL_CATEGORY = 'danh_muc';
    const EXCEL_COL_SUB_CATEGORY = 'danh_muc_con';
    const EXCEL_COL_SKU = 'ma_hang_sku';
    const EXCEL_COL_COST_PRICE = 'gia_mua';
    const EXCEL_COL_PRICE = 'gia_ban_khong_bao_gom_thue';
    const EXCEL_COL_TAX = 'thue_ap_dung';
    const EXCEL_COL_STATUS = 'trang_thai';
    const EXCEL_COL_STOCK = 'ton_kho';

    public function __construct($batchId)
    {
        $this->batchId = $batchId;
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty())
            return;

        $categoryData = $this->buildCategoryHierachyMap();

        $brandsMap = $this->nameMap(Brand::class, $rows->pluck(self::EXCEL_COL_BRAND));
        $unitsMap = $this->nameMap(Unit::class, $rows->pluck(self::EXCEL_COL_UNIT));
        $taxesMap = $this->rateMap($rows->pluck(self::EXCEL_COL_TAX));

        $dbProducts = Product::whereIn('name', $rows->pluck(self::EXCEL_COL_NAME)->filter()->toArray())
            ->pluck('name')->map(fn($v) => mb_strtolower(trim($v)))->flip()->toArray();

        $dbSkus = ProductVariant::whereIn('sku', $rows->pluck(self::EXCEL_COL_SKU)->filter()->toArray())
            ->pluck('sku')->map(fn($v) => mb_strtolower(trim($v)))->flip()->toArray();

        $rowsToInsert = [];

        foreach ($rows as $index => $row) {
            $payload = $this->normalizePayload($row, $categoryData, $brandsMap, $unitsMap, $taxesMap);
            $errors = $this->validatePayload($payload);
            $this->checkDuplicatesAndDatabase($payload, $dbProducts, $dbSkus, $errors);

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

    private function normalizePayload($row, array $categoryData, array $brandsMap, array $unitsMap, array $taxesMap): array
    {
        $cName = mb_strtolower(trim($row[self::EXCEL_COL_CATEGORY] ?? ''));
        $subCName = mb_strtolower(trim($row[self::EXCEL_COL_SUB_CATEGORY] ?? ''));
        $bName  = mb_strtolower(trim($row[self::EXCEL_COL_BRAND] ?? ''));
        $uName  = mb_strtolower(trim($row[self::EXCEL_COL_UNIT] ?? ''));
        $taxRaw = isset($row[self::EXCEL_COL_TAX]) ? trim($row[self::EXCEL_COL_TAX]) : null;
        $taxRateKey = $this->rateKey($taxRaw);

        $resolvedCategoryId = null;

        if ($cName !== '' && $subCName !== '') {
            $compositeKey = $cName . '|' . $subCName;
            $resolvedCategoryId = $categoryData['children'][$compositeKey] ?? null;
        } elseif ($cName !== '') {
            $resolvedCategoryId = $categoryData['parents'][$cName] ?? null;
        }

        $resolvedCategoryName = trim($row[self::EXCEL_COL_SUB_CATEGORY] ?? '') ?: trim($row[self::EXCEL_COL_CATEGORY] ?? '');

        // Mapping excel column to DB
        return [
            'product' => [

                'name' => trim($row[self::EXCEL_COL_NAME] ?? ''),
                'category_id' => $resolvedCategoryId,
                'category_name' => $resolvedCategoryName ?: null,
                'brand_id' => $brandsMap[$bName] ?? null,
                'brand_name' => trim($row[self::EXCEL_COL_BRAND] ?? '') ?: null,
                'status' => 'published',
            ],
            'variant' => [
                'sku' => trim($row[self::EXCEL_COL_SKU] ?? ''),
                'price' => $row[self::EXCEL_COL_PRICE] ?? 0,

                'cost_price' => $row[self::EXCEL_COL_COST_PRICE] ?? null,

                'unit_id' => $unitsMap[$uName] ?? null,
                'unit_name' => trim($row[self::EXCEL_COL_UNIT] ?? '') ?: null,

                'tax_id' => $taxesMap[$taxRateKey] ?? null,
                'tax' => $taxRaw !== '' ? $taxRaw : null,

                'attributes' => !empty($row[self::EXCEL_COL_VARIANT])
                    ? json_encode(['variant_name' => trim($row[self::EXCEL_COL_VARIANT])])
                    : null,

                'is_active' => true,
            ],
            'stock' => [
                'quantity' => $row[self::EXCEL_COL_STOCK] ?? 0
            ]
        ];
    }

    private function validatePayload(array $payload): array
    {
        $validator = Validator::make(
            array_merge($payload['product'], $payload['variant'], ['stock_quantity' => $payload['stock']['quantity']]),
            [
                'name' => ['required', 'string', 'min:2', 'max:255'],
                'category_id' => ['required', 'integer'],
                'sku' => ['required', 'string', 'max:100'],
                'price' => ['required', 'numeric', 'min:0'],
                'stock_quantity' => ['required', 'integer', 'min:0'],
                'cost_price' => ['nullable', 'numeric', 'min:0'],
                'unit_id' => ['nullable', 'integer', 'required_with:unit_name'],
                'tax_id' => ['nullable', 'integer', 'required_with:tax'],
            ],
            [
                'unit_id.required_with' => 'Đơn vị tính không tồn tại trên hệ thống.',
                'tax_id.required_with' => 'Thuế suất không tồn tại trên hệ thống.',
            ]
        );

        return $validator->errors()->all();
    }

    private function checkDuplicatesAndDatabase(array $payload, array $dbProducts, array $dbSkus, array &$errors): void
    {
        $pNameKey = mb_strtolower(trim($payload['product']['name']));
        $skuKey = mb_strtolower(trim($payload['variant']['sku']));

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

    private function rateMap(Collection $rates): array
    {
        $cleanRates = $rates->map(fn($r) => $this->rateKey($r))->filter()->unique()->toArray();
        if (empty($cleanRates))
            return [];

        return Tax::whereIn('rate', $cleanRates)
            ->pluck('id', 'rate')
            ->mapWithKeys(fn($id, $rate) => [$this->rateKey($rate) => $id])
            ->toArray();
    }

    private function rateKey($rate): ?string
    {
        if ($rate === null || $rate === '')
            return null;
        return is_numeric($rate) ? number_format((float) $rate, 2, '.', '') : null;
    }

    private function buildCategoryHierachyMap()
    {
        $categories = Category::all();

        $parentsMap = [];
        $childrenMap = [];

        foreach ($categories as $cat) {
            $nameKey = mb_strtolower(trim($cat->name));

            if (empty($cat->parent_id)) {
                $parentsMap[$nameKey] = $cat->id;
            } else {
                $parent = $categories->firstWhere('id', $cat->parent_id);
                if ($parent) {
                    $parentNameKey = mb_strtolower(trim($parent->name));

                    $compositeKey = $parentNameKey . '|' . $nameKey;
                    $childrenMap[$compositeKey] = $cat->id;
                }
            }
        }
        return [
            'parents' => $parentsMap,
            'children' => $childrenMap,
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
