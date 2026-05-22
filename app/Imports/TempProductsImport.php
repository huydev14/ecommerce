<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ImportBatch;
use App\Models\ImportProductRow;
use App\Models\ProductVariant;
use App\Models\Tax;
use App\Models\Unit;

use Illuminate\Support\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeImport;

class TempProductsImport implements ToCollection, WithChunkReading, WithHeadingRow, ShouldQueue, WithEvents
{
    protected $batchId;
    protected static array $seenSkus = [];

    const EXCEL_COL_NAME = 'ten_san_pham';
    const EXCEL_COL_VARIANT = 'ten_bien_the';
    const EXCEL_COL_BRAND = 'nhan_hieu';
    const EXCEL_COL_UNIT = 'don_vi_tinh';
    const EXCEL_COL_CATEGORY = 'danh_muc';
    const EXCEL_COL_SUB_CATEGORY = 'danh_muc_con';
    const EXCEL_COL_SKU = 'sku';
    const EXCEL_COL_COST_PRICE = 'gia_mua';
    const EXCEL_COL_PRICE = 'gia_ban_khong_bao_gom_thue';
    const EXCEL_COL_TAX = 'thue_ap_dung';
    const EXCEL_COL_STATUS = 'trang_thai';
    const EXCEL_COL_STOCK = 'ton_kho';

    public function __construct($batchId)
    {
        $this->batchId = $batchId;
        self::$seenSkus = [];
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty() || !$this->importBatchExists()) {
            return;
        }

        $categoryData = $this->buildCategoryHierachyMap();
        $brandsMap = $this->nameMap(Brand::class, $rows->pluck(self::EXCEL_COL_BRAND));
        $unitsMap = $this->nameMap(Unit::class, $rows->pluck(self::EXCEL_COL_UNIT));
        $taxesMap = $this->rateMap($rows->pluck(self::EXCEL_COL_TAX));

        $dbSkus = ProductVariant::whereIn('sku', $rows->pluck(self::EXCEL_COL_SKU)->filter()->toArray())
            ->pluck('sku')
            ->map(fn($value) => mb_strtolower(trim($value)))
            ->flip()
            ->toArray();

        $rowsToInsert = [];

        foreach ($rows as $index => $row) {
            $payload = $this->normalizePayload($row, $categoryData, $brandsMap, $unitsMap, $taxesMap);
            [$errors, $errorCodes] = self::validatePayload($payload);
            $this->checkDuplicatesAndDatabase($payload, $dbSkus, $errors, $errorCodes);

            // Cache missing metadata (for after import process handling)
            if (in_array('missing_category', $errorCodes)) {
                $parent = trim($payload['product']['parent_category_name'] ?? '');
                $child = trim($payload['product']['sub_category_name'] ?? '');

                if ($parent !== '' && $child !== '') {
                    Redis::sAdd("import_batch_{$this->batchId}_missing_categories", "{$parent}|{$child}");
                } elseif ($parent !== '') {
                    Redis::sAdd("import_batch_{$this->batchId}_missing_categories", $parent);
                } elseif (!empty($payload['product']['category_name'])) {
                    Redis::sAdd("import_batch_{$this->batchId}_missing_categories", trim($payload['product']['category_name']));
                }
            }
            if (in_array('missing_unit', $errorCodes)) {
                $unitName = $payload['variant']['unit_name'];
                if ($unitName) {
                    Redis::sAdd("import_batch_{$this->batchId}_missing_units", trim($unitName));
                }
            }
            if (in_array('missing_tax', $errorCodes)) {
                $taxRate = $payload['variant']['tax'];
                if ($taxRate !== null && $taxRate !== '') {
                    Redis::sAdd("import_batch_{$this->batchId}_missing_taxes", trim($taxRate));
                }
            }
            $payload['error_codes'] = array_values(array_unique($errorCodes));

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

        if (!$this->importBatchExists()) {
            return;
        }

        try {
            ImportProductRow::insert($rowsToInsert);
        } catch (QueryException $exception) {
            if (!$this->importBatchExists()) {
                return;
            }

            throw $exception;
        }

        $processed = ImportProductRow::where('import_batch_id', $this->batchId)->count();
        $total = ImportBatch::where('id', $this->batchId)->value('total_rows') ?: $processed;

        ImportBatch::where('id', $this->batchId)->update([
            'total_rows' => max($total, $processed),
        ]);
    }

    private function normalizePayload($row, array $categoryData, array $brandsMap, array $unitsMap, array $taxesMap): array
    {
        $categoryName = trim($row[self::EXCEL_COL_CATEGORY] ?? '');
        $subCategoryName = trim($row[self::EXCEL_COL_SUB_CATEGORY] ?? '');
        $categoryKey = mb_strtolower($categoryName);
        $subCategoryKey = mb_strtolower($subCategoryName);
        $brandKey = mb_strtolower(trim($row[self::EXCEL_COL_BRAND] ?? ''));
        $unitKey = mb_strtolower(trim($row[self::EXCEL_COL_UNIT] ?? ''));
        $taxRaw = isset($row[self::EXCEL_COL_TAX]) ? trim($row[self::EXCEL_COL_TAX]) : null;
        $taxRateKey = $this->rateKey($taxRaw);

        $resolvedCategoryId = null;

        if ($categoryKey !== '' && $subCategoryKey !== '') {
            $resolvedCategoryId = $categoryData['children'][$categoryKey . '|' . $subCategoryKey] ?? null;
        } elseif ($categoryKey !== '') {
            $resolvedCategoryId = $categoryData['parents'][$categoryKey] ?? null;
        }

        return [
            'product' => [
                'name' => trim($row[self::EXCEL_COL_NAME] ?? ''),
                'category_id' => $resolvedCategoryId,
                'category_name' => ($subCategoryName ?: $categoryName) ?: null,
                'parent_category_name' => $categoryName ?: null,
                'sub_category_name' => $subCategoryName ?: null,
                'brand_id' => $brandsMap[$brandKey] ?? null,
                'brand_name' => trim($row[self::EXCEL_COL_BRAND] ?? '') ?: null,
                'status' => 'published',
            ],
            'variant' => [
                'sku' => trim($row[self::EXCEL_COL_SKU] ?? ''),
                'price' => $row[self::EXCEL_COL_PRICE] ?? 0,
                'cost_price' => $row[self::EXCEL_COL_COST_PRICE] ?? null,
                'unit_id' => $unitsMap[$unitKey] ?? null,
                'unit_name' => $unitKey ?: null,
                'tax_id' => $taxesMap[$taxRateKey] ?? null,
                'tax' => $taxRateKey,
                'attributes' => !empty($row[self::EXCEL_COL_VARIANT])
                    ? json_encode(['variant_name' => trim($row[self::EXCEL_COL_VARIANT])])
                    : null,
                'is_active' => true,
            ],
            'stock' => [
                'quantity' => $row[self::EXCEL_COL_STOCK] ?? 0,
            ],
        ];
    }

    public static function validatePayload(array $payload): array
    {
        $validator = Validator::make(
            array_merge($payload['product'], $payload['variant'], ['stock_quantity' => $payload['stock']['quantity']]),
            [
                'name' => ['required', 'string', 'min:2', 'max:255'],
                'category_id' => ['nullable', 'integer'],
                'sku' => ['required', 'string', 'max:100'],
                'price' => ['required', 'numeric', 'min:0'],
                'stock_quantity' => ['required', 'integer', 'min:0', 'max:999999'],
                'cost_price' => ['nullable', 'numeric', 'min:0'],
                'unit_id' => ['nullable', 'integer', 'required_with:unit_name'],
                'tax_id' => ['nullable', 'integer', 'required_with:tax'],
                'tax' => ['nullable', 'numeric', 'min:0', 'max:100'],
            ],
            [
                'unit_id.required_with' => 'Đơn vị tính không tồn tại trên hệ thống.',
                'tax_id.required_with' => 'Thuế suất không tồn tại trên hệ thống.',
            ]
        );

        $errors = $validator->errors()->all();
        $masterDataCodes = self::missingMasterDataCodes($payload);

        if (in_array('missing_category', $masterDataCodes, true)) {
            $errors[] = 'Danh mục không tồn tại trên hệ thống.';
        } elseif (empty($payload['product']['category_id']) && empty($payload['product']['category_name'])) {
            $errors[] = 'Vui lòng nhập danh mục.';
        }

        return [$errors, $masterDataCodes];
    }

    public static function missingMasterDataCodes(array $payload): array
    {
        $codes = [];
        $product = $payload['product'] ?? [];
        $variant = $payload['variant'] ?? [];

        if (empty($product['category_id']) && !empty($product['category_name'])) {
            $codes[] = 'missing_category';
        }

        if (empty($variant['unit_id']) && !empty($variant['unit_name'])) {
            $codes[] = 'missing_unit';
        }

        if (empty($variant['tax_id']) && isset($variant['tax']) && $variant['tax'] !== '') {
            $codes[] = 'missing_tax';
        }

        return $codes;
    }

    private function checkDuplicatesAndDatabase(array $payload, array $dbSkus, array &$errors, array &$errorCodes): void
    {
        $skuKey = mb_strtolower(trim($payload['variant']['sku']));
        if ($skuKey !== '') {
            // Check duplicate SKU in DB
            if (isset($dbSkus[$skuKey])) {
                $errors[] = 'Mã SKU đã tồn tại trên hệ thống.';
                $errorCodes[] = 'duplicate_sku_in_database';
            }

            // Check duplicate SKU in batch
            $cacheKey = "import_batch_{$this->batchId}_sku_{$skuKey}";
            $cacheTags = ["import_batch_{$this->batchId}"];

            if (isset(self::$seenSkus[$skuKey]) || Cache::tags($cacheTags)->has($cacheKey)) {
                $errors[] = 'Mã SKU bị trùng lặp trong file.';
                $errorCodes[] = 'duplicate_sku_in_file';
            } else {
                self::$seenSkus[$skuKey] = true;
                Cache::tags($cacheTags)->put($cacheKey, true, now()->addHours(1));
            }
        }
    }

    private function nameMap(string $modelClass, Collection $names): array
    {
        $cleanNames = $names->map(fn($name) => trim($name))->filter()->unique()->toArray();

        if (empty($cleanNames)) {
            return [];
        }

        return $modelClass::whereIn('name', $cleanNames)
            ->pluck('id', 'name')
            ->mapWithKeys(fn($id, $name) => [mb_strtolower(trim($name)) => $id])
            ->toArray();
    }

    private function rateMap(Collection $rates): array
    {
        $cleanRates = $rates->map(fn($rate) => $this->rateKey($rate))->filter()->unique()->toArray();

        if (empty($cleanRates)) {
            return [];
        }

        return Tax::whereIn('rate', $cleanRates)
            ->pluck('id', 'rate')
            ->mapWithKeys(fn($id, $rate) => [$this->rateKey($rate) => $id])
            ->toArray();
    }

    private function rateKey($rate): ?string
    {
        if ($rate === null || $rate === '') {
            return null;
        }

        return is_numeric($rate) ? number_format((float) $rate, 2, '.', '') : null;
    }

    private function buildCategoryHierachyMap(): array
    {
        return Cache::remember('category_map', 300, function () {
            $categories = Category::all();
            $parentsMap = [];
            $childrenMap = [];

            foreach ($categories as $category) {
                $nameKey = mb_strtolower(trim($category->name));

                if (empty($category->parent_id)) {
                    $parentsMap[$nameKey] = $category->id;
                    continue;
                }

                $parent = $categories->firstWhere('id', $category->parent_id);
                if ($parent) {
                    $childrenMap[mb_strtolower(trim($parent->name)) . '|' . $nameKey] = $category->id;
                }
            }

            return [
                'parents' => $parentsMap,
                'children' => $childrenMap,
            ];
        });
    }

    private function importBatchExists(): bool
    {
        return ImportBatch::whereKey($this->batchId)->exists();
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                if (!$this->importBatchExists()) {
                    return;
                }

                $sheetRows = $event->getReader()->getTotalRows();
                $totalRows = max(array_sum($sheetRows) - count($sheetRows), 0);

                ImportBatch::where('id', $this->batchId)->update([
                    'total_rows' => $totalRows,
                ]);
            },
            AfterImport::class => function (AfterImport $event) {
                if (!$this->importBatchExists()) {
                    return;
                }

                $totalRows = ImportProductRow::where('import_batch_id', $this->batchId)->count();

                ImportBatch::where('id', $this->batchId)->update([
                    'status' => 'ready',
                    'total_rows' => $totalRows,
                ]);

                Cache::tags(["import_batch_{$this->batchId}"])->flush();
            },
        ];
    }
}
