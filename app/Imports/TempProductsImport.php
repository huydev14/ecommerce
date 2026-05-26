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
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            return;
        }

        $categoryData = $this->buildCategoryHierachyMap();
        $brandsMap = $this->nameMap(Brand::class, $rows->pluck(self::EXCEL_COL_BRAND));
        $unitsMap = $this->nameMap(Unit::class, $rows->pluck(self::EXCEL_COL_UNIT));
        $taxesMap = $this->rateMap($rows->pluck(self::EXCEL_COL_TAX));

        $chunkSkus = $rows->pluck(self::EXCEL_COL_SKU)
            ->map(fn($value) => mb_strtolower(trim((string) $value)))
            ->filter()
            ->toArray();

        $chunkSkuCounts = array_count_values($chunkSkus);

        $dbSkus = ProductVariant::whereIn('sku', $chunkSkus)->pluck('sku')
            ->map(fn($value) => mb_strtolower(trim($value)))
            ->flip()->toArray();

        $redisDuplicateSkus = $this->getDuplicateSkusFromRedis($chunkSkus);

        $rowsToInsert = [];

        $missingCategories = [];
        $missingBrands = [];
        $missingUnits = [];
        $missingTaxes = [];
        $rowsToInsert = [];

        foreach ($rows as $index => $row) {
            $payload = $this->normalizePayload($row, $categoryData, $brandsMap, $unitsMap, $taxesMap);

            // Validate data
            [$errors, $errorCodes] = self::validatePayload($payload);

            // Check duplicate
            $this->checkDuplicates($payload, $dbSkus, $chunkSkuCounts, $redisDuplicateSkus, $errors, $errorCodes);

            // Save missing metadata to array
            if (in_array('missing_category', $errorCodes)) {
                $missingCategories[] = $this->extractMissingCategory($payload);
            }
            if (in_array('missing_brand', $errorCodes)) {
                $brandName = trim($payload['product']['brand_name'] ?? '');
                if ($brandName !== '')
                    $missingBrands[] = $brandName;
            }
            if (in_array('missing_unit', $errorCodes)) {
                $unitName = trim($payload['variant']['unit_name'] ?? '');
                $missingUnits[] = $unitName !== '' ? $unitName : 'khác';
            }
            if (in_array('missing_tax', $errorCodes)) {
                $taxRate = $payload['variant']['tax'] ?? null;
                if ($taxRate !== null && $taxRate !== '')
                    $missingTaxes[] = trim((string) $taxRate);
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

        $this->bulkCacheMissingMetadata($missingCategories, $missingBrands, $missingUnits, $missingTaxes);

        $this->bulkCacheSeenSkus($chunkSkus);

        ImportProductRow::insert($rowsToInsert);

        $total = ImportBatch::where('id', $this->batchId)->value('total_rows');

        ImportBatch::where('id', $this->batchId)->update([
            'total_rows' => $total,
        ]);
    }

    private function normalizePayload($row, array $categoryData, array $brandsMap, array $unitsMap, array $taxesMap): array
    {
        $categoryName = trim($row[self::EXCEL_COL_CATEGORY] ?? '');
        if ($categoryName === '') {
            $categoryName = 'Other';
        }

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
        $errors = [];
        $masterDataCodes = [];

        $product = $payload['product'] ?? [];
        $variant = $payload['variant'] ?? [];
        $stock = $payload['stock'] ?? [];

        // 1. Name
        $name = $product['name'] ?? '';
        if ($name === '') {
            $errors[] = 'Tên sản phẩm là bắt buộc.';
        } elseif (mb_strlen((string) $name) < 2) {
            $errors[] = 'Tên sản phẩm phải có ít nhất 2 ký tự.';
        } elseif (mb_strlen((string) $name) > 255) {
            $errors[] = 'Tên sản phẩm không được vượt quá 255 ký tự.';
        }

        // 2. SKU
        $sku = $variant['sku'] ?? '';
        if ($sku === '') {
            $errors[] = 'Mã SKU là bắt buộc.';
        } elseif (mb_strlen((string) $sku) > 100) {
            $errors[] = 'Mã SKU không được vượt quá 100 ký tự.';
        }

        // 3. Price
        $price = $variant['price'] ?? null;
        if ($price === null || $price === '') {
            $errors[] = 'Giá bán là bắt buộc.';
        } elseif (!is_numeric($price)) {
            $errors[] = 'Giá bán phải là một số.';
        } elseif ((float) $price < 0) {
            $errors[] = 'Giá bán không được nhỏ hơn 0.';
        }

        // 4. Stock
        $qty = $stock['quantity'] ?? null;
        if ($qty === null || $qty === '') {
            $errors[] = 'Nhập tồn kho là bắt buộc.';
        } elseif (filter_var($qty, FILTER_VALIDATE_INT) === false) {
            $errors[] = 'Tồn kho phải là số nguyên.';
        } elseif ((int) $qty < 0 || (int) $qty > 999999) {
            $errors[] = 'Tồn kho không được phép âm.';
        }

        // 5. Cost price
        $cost = $variant['cost_price'] ?? null;
        if ($cost !== null && $cost !== '') {
            if (!is_numeric($cost)) {
                $errors[] = 'Giá mua phải là một số.';
            } elseif ((float) $cost < 0) {
                $errors[] = 'Giá mua không được nhỏ hơn 0.';
            }
        }

        // 6. Tax Rate & Master Data Tax
        $tax = $variant['tax'] ?? null;
        if ($tax !== null && $tax !== '') {
            if (!is_numeric($tax)) {
                $errors[] = 'Thuế suất phải là một số.';
            } elseif ((float) $tax < 0 || (float) $tax > 100) {
                $errors[] = 'Thuế suất chỉ cho phép từ 0 đến 100.';
            } elseif (empty($variant['tax_id'])) {
                // Nhập số thuế hợp lệ nhưng không tìm thấy ID trong DB
                $masterDataCodes[] = 'missing_tax';
                $errors[] = 'Thuế suất không tồn tại trên hệ thống.';
            }
        }

        // 7. Unit Master Data
        if (empty($variant['unit_id'])) {
            $masterDataCodes[] = 'missing_unit';
            if (!empty($variant['unit_name'])) {
                $errors[] = 'Đơn vị tính không tồn tại trên hệ thống.';
            }
        }

        // 8. Category Master Data
        if (empty($product['category_id'])) {
            $masterDataCodes[] = 'missing_category';

            if (empty($product['category_name']) && empty($product['parent_category_name'])) {
                $errors[] = 'Vui lòng nhập danh mục.';
            } else {
                $errors[] = 'Danh mục không tồn tại trên hệ thống.';
            }
        }

        // 9. Brand Master Data
        if (empty($product['brand_id']) && !empty($product['brand_name'])) {
            $masterDataCodes[] = 'missing_brand';
            $errors[] = 'Thương hiệu không tồn tại trên hệ thống.';
        }

        return [$errors, $masterDataCodes];
    }

    private function checkDuplicates(array $payload, array $dbSkus, array $chunkSkuCounts, array $redisDuplicateSkus, array &$errors, array &$errorCodes): void
    {
        $skuKey = mb_strtolower(trim($payload['variant']['sku'] ?? ''));
        if ($skuKey === '')
            return;

        // Check duplicates in DB
        if (isset($dbSkus[$skuKey])) {
            $errors[] = 'Mã SKU đã tồn tại trên hệ thống.';
            $errorCodes[] = 'duplicate_sku_in_database';
            return;
        }

        // Check duplicates in chunk
        if (($chunkSkuCounts[$skuKey] ?? 0) > 1 || isset($redisDuplicateSkus[$skuKey])) {
            $errors[] = 'Mã SKU bị trùng lặp trong file.';
            $errorCodes[] = 'duplicate_sku_in_file';
        }
    }

    private function getDuplicateSkusFromRedis(array $chunkSkus): array
    {
        $uniqueSkus = array_unique($chunkSkus);
        if (empty($uniqueSkus)) {
            return [];
        }

        $redisKey = "import_batch_{$this->batchId}_seen_skus";
        $duplicates = [];

        $results = Redis::pipeline(function ($pipe) use ($redisKey, $uniqueSkus) {
            foreach ($uniqueSkus as $sku) {
                $pipe->sismember($redisKey, $sku);
            }
        });

        foreach (array_values($uniqueSkus) as $index => $sku) {
            if ($results[$index]) {
                $duplicates[$sku] = true;
            }
        }

        return $duplicates;
    }

    private function bulkCacheSeenSkus(array $chunkSkus): void
    {
        $uniqueSkus = array_unique($chunkSkus);
        if (empty($uniqueSkus))
            {return;}

        $redisKey = "import_batch_{$this->batchId}_seen_skus";

        Redis::sAdd($redisKey, ...$uniqueSkus);
        Redis::expire($redisKey, 7200);
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

    private function extractMissingCategory(array $payload): string
    {
        $parent = trim($payload['product']['parent_category_name'] ?? '');
        $child = trim($payload['product']['sub_category_name'] ?? '');

        if ($parent !== '' && $child !== '') {
            return "{$parent}|{$child}";
        }

        return $parent !== '' ? $parent : 'Other';
    }

    private function bulkCacheMissingMetadata(array $categories, array $brands, array $units, array $taxes): void
    {
        $categories = array_unique($categories);
        $brands = array_unique($brands);
        $units = array_unique($units);
        $taxes = array_unique($taxes);

        if (empty($categories) && empty($brands) && empty($units) && empty($taxes)) {
            return;
        }

        Redis::pipeline(function ($pipe) use ($categories, $brands, $units, $taxes) {
            foreach ($categories as $item) {
                $pipe->sadd("import_batch_{$this->batchId}_missing_categories", $item);
            }
            foreach ($brands as $item) {
                $pipe->sadd("import_batch_{$this->batchId}_missing_brands", $item);
            }
            foreach ($units as $item) {
                $pipe->sadd("import_batch_{$this->batchId}_missing_units", $item);
            }
            foreach ($taxes as $item) {
                $pipe->sadd("import_batch_{$this->batchId}_missing_taxes", $item);
            }
        });
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                Redis::del("import_batch_{$this->batchId}_seen_skus");

                $sheetRows = $event->getReader()->getTotalRows();
                $totalRows = max(array_sum($sheetRows) - count($sheetRows), 0);

                ImportBatch::where('id', $this->batchId)->update([
                    'total_rows' => $totalRows,
                ]);
            },
            AfterImport::class => function (AfterImport $event) {

                $totalRows = ImportProductRow::where('import_batch_id', $this->batchId)->count();

                ImportBatch::where('id', $this->batchId)->update([
                    'status' => 'ready',
                    'total_rows' => $totalRows,
                ]);

                Cache::tags(["import_batch_{$this->batchId}"])->flush();
                Redis::del("import_batch_{$this->batchId}_seen_skus");
            },
        ];
    }
}
