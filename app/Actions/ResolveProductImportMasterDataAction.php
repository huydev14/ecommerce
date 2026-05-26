<?php

namespace App\Actions;

use App\Imports\TempProductsImport;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ImportBatch;
use App\Models\ImportProductRow;
use App\Models\Tax;
use App\Models\Unit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResolveProductImportMasterDataAction
{
    public function execute(int $batchId): array
    {
        $resolutionResult = ImportBatch::whereKey($batchId)->value('master_data_resolution_result') ?? [];
        $resolutionResult = is_array($resolutionResult) ? $resolutionResult : json_decode($resolutionResult, true) ?? [];
        $storedMissing = $resolutionResult['missing_master_data'] ?? [];

        $missingCategories = $this->mergeUnique($storedMissing['categories'] ?? []);
        $missingBrands = $this->mergeUnique($storedMissing['brands'] ?? []);
        $missingUnits = $this->mergeUnique($storedMissing['units'] ?? []);
        $missingTaxes = $this->mergeUnique($storedMissing['taxes'] ?? []);

        $createdCategories = 0;
        $createdBrands = 0;
        $createdUnits = 0;
        $createdTaxes = 0;
        $totalRowsToResolve = ImportProductRow::where('import_batch_id', $batchId)
            ->where('status', 'error')
            ->count();
        $processedRows = 0;

        $this->updateResolutionProgress($batchId, $processedRows, $totalRowsToResolve);

        // Bulk insert master data
        DB::transaction(function () use (
            &$createdCategories,
            &$createdBrands,
            &$createdUnits,
            &$createdTaxes,
            $missingCategories,
            $missingBrands,
            $missingUnits,
            $missingTaxes
        ) {
            if (!empty($missingUnits)) {
                $createdUnits = $this->resolveUnits($missingUnits);
            }
            if (!empty($missingTaxes)) {
                $createdTaxes = $this->resolveTaxes($missingTaxes);
            }
            if (!empty($missingBrands)) {
                $createdBrands = $this->resolveBrands($missingBrands);
            }
            if (!empty($missingCategories)) {
                $createdCategories = $this->resolveCategories($missingCategories);
            }
        });

        Cache::forget('category_map');

        // Update error batch rows
        $resolvedRowsCount = 0;

        $unitMap = $this->unitMap();
        $taxMap = $this->taxMap();
        $brandMap = $this->brandMap();
        $categoryMap = $this->categoryMap();

        ImportProductRow::where('import_batch_id', $batchId)
            ->where('status', 'error')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (
                $batchId,
                &$resolvedRowsCount,
                &$processedRows,
                $totalRowsToResolve,
                $unitMap,
                $taxMap,
                $brandMap,
                $categoryMap
            ) {

                $updates = [];

                foreach ($rows as $row) {
                    $payload = is_array($row->data) ? $row->data : json_decode($row->data, true);

                    // Remove error codes
                    $retainedErrorCodes = array_values(array_diff($payload['error_codes'] ?? [], [
                        'missing_category',
                        'missing_brand',
                        'missing_unit',
                        'missing_tax'
                    ]));

                    $this->patchPayload($payload, $unitMap, $taxMap, $brandMap, $categoryMap);

                    // Validate again
                    [$errors, $errorCodes] = TempProductsImport::validatePayload($payload);
                    $finalErrorCodes = array_values(array_unique(array_merge($errorCodes, $retainedErrorCodes)));
                    $payload['error_codes'] = $finalErrorCodes;

                    $hasErrors = !empty($errors) || !empty($retainedErrorCodes);

                    $updates[] = [
                        'id' => $row->id,
                        'import_batch_id' => $row->import_batch_id,
                        'row_number' => $row->row_number,
                        'data' => json_encode($payload),
                        'status' => $hasErrors ? 'error' : 'valid',
                        'error_message' => $hasErrors ? ($errors ? implode(' ', $errors) : $row->error_message) : null,
                    ];

                    if (!$hasErrors) {
                        $resolvedRowsCount++;
                    }
                }
                if (!empty($updates)) {
                    ImportProductRow::upsert($updates, ['id'], ['data', 'status', 'error_message']);
                }

                $processedRows += $rows->count();
                $this->updateResolutionProgress($batchId, $processedRows, $totalRowsToResolve);
            });

        return [
            'units' => $createdUnits,
            'taxes' => $createdTaxes,
            'brands' => $createdBrands,
            'categories' => $createdCategories,
            'resolved_rows' => $resolvedRowsCount,
            'processed_rows' => $processedRows,
            'total_rows' => $totalRowsToResolve,
            'percentage' => $this->resolutionPercentage($processedRows, $totalRowsToResolve),
            'status' => 'completed',
        ];
    }

    private function mergeUnique(array ...$lists): array
    {
        return collect($lists)
            ->flatten(1)
            ->map(fn($value) => is_scalar($value) ? trim((string) $value) : null)
            ->filter(fn($value) => $value !== null && $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function updateResolutionProgress(int $batchId, int $processedRows, int $totalRows): void
    {
        $current = ImportBatch::whereKey($batchId)->value('master_data_resolution_result') ?? [];
        $current = is_array($current) ? $current : json_decode($current, true) ?? [];

        ImportBatch::whereKey($batchId)->update([
            'master_data_resolution_result' => array_merge($current, [
                'status' => 'processing',
                'processed_rows' => $processedRows,
                'total_rows' => $totalRows,
                'percentage' => $this->resolutionPercentage($processedRows, $totalRows),
            ]),
        ]);
    }

    private function resolutionPercentage(int $processedRows, int $totalRows): int
    {
        if ($totalRows <= 0) {
            return 100;
        }

        return min(100, (int) round(($processedRows / $totalRows) * 100));
    }

    private function resolveBrands(array $namesList): int
    {
        $names = collect($namesList)
            ->map(fn($name) => $this->normalizeName($name))
            ->filter()
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return 0;
        }

        $existingModels = Brand::query()
            ->whereIn('name', $names)
            ->get(['id', 'name']);

        $existing = $existingModels
            ->pluck('name')
            ->map(fn($name) => $this->normalizeName($name))
            ->all();

        $missing = $names->diff($existing)->values();

        if ($missing->isEmpty()) {
            return 0;
        }

        return Brand::query()->insertOrIgnore($missing->map(fn($name) => [
            'name' => $name,
            'slug' => $this->uniqueBrandSlug($name),
            'logo' => null,
            'website' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());
    }

    private function resolveUnits(array $namesList): int
    {
        $names = collect($namesList)
            ->map(fn($name) => $this->normalizeName($name))
            ->filter()
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return 0;
        }

        $existingModels = Unit::withTrashed()
            ->whereIn('name', $names)
            ->get(['id', 'name', 'deleted_at']);

        $idsToRestore = $existingModels->whereNotNull('deleted_at')->pluck('id');
        if ($idsToRestore->isNotEmpty()) {
            Unit::whereIn('id', $idsToRestore)->restore();
        }

        $existing = $existingModels
            ->pluck('name')
            ->map(fn($name) => $this->normalizeName($name))
            ->all();

        $missing = $names->diff($existing)->values();

        if ($missing->isEmpty()) {
            return 0;
        }

        return Unit::query()->insertOrIgnore($missing->map(fn($name) => [
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());
    }

    private function resolveTaxes(array $ratesList): int
    {
        $rates = collect($ratesList)
            ->map(fn($rate) => $this->normalizeRate($rate))
            ->filter()
            ->unique()
            ->values();

        if ($rates->isEmpty()) {
            return 0;
        }

        $existingModels = Tax::withTrashed()
            ->whereIn('rate', $rates)
            ->get(['id', 'rate', 'deleted_at']);

        $existingModels->whereNotNull('deleted_at')->each->restore();

        $existing = $existingModels
            ->pluck('rate')
            ->map(fn($rate) => $this->normalizeRate($rate))
            ->all();

        $missing = $rates->diff($existing)->values();

        if ($missing->isEmpty()) {
            return 0;
        }

        return Tax::query()->insertOrIgnore($missing->map(fn($rate) => [
            'name' => 'Thuế VAT ' . $rate . '%',
            'rate' => $rate,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());
    }

    private function resolveCategories(array $categoryStrings): int
    {
        // 1. Extract all unique parent names from the strings
        $parentNames = collect($categoryStrings)
            ->map(function ($str) {
                $parts = explode('|', $str);
                return $this->normalizeName($parts[0] ?? null);
            })
            ->filter()
            ->unique()
            ->values();

        if ($parentNames->isEmpty()) {
            return 0;
        }

        $usedSlugs = [];
        $created = $this->ensureParentCategories($parentNames, $usedSlugs);

        $parents = Category::query()
            ->whereNull('parent_id')
            ->whereIn('name', $parentNames)
            ->get(['id', 'name', 'parent_id'])
            ->keyBy(fn($category) => $this->normalizeName($category->name));

        // 2. Extract children that need to be created
        $childrenToCreate = collect($categoryStrings)
            ->map(function ($str) use ($parents) {
                $parts = explode('|', $str);
                if (count($parts) < 2)
                    return null; // It's just a parent
    
                $parentName = $this->normalizeName($parts[0]);
                $childName = $this->normalizeName($parts[1]);

                if (!$parentName || !$childName || !$parents->has($parentName)) {
                    return null;
                }

                return [
                    'parent_id' => $parents[$parentName]->id,
                    'parent_name' => $parentName,
                    'name' => $childName,
                    'key' => $parentName . '|' . $childName,
                ];
            })
            ->filter()
            ->unique('key')
            ->values();

        if ($childrenToCreate->isEmpty()) {
            return $created;
        }

        $existingChildren = Category::query()
            ->whereIn('parent_id', $childrenToCreate->pluck('parent_id')->unique()->all())
            ->whereIn('name', $childrenToCreate->pluck('name')->unique()->all())
            ->get(['id', 'name', 'parent_id']);

        $existingKeys = $existingChildren
            ->map(fn($category) => $category->parent_id . '|' . $this->normalizeName($category->name))
            ->all();

        $missingChildren = $childrenToCreate
            ->reject(fn($item) => in_array($item['parent_id'] . '|' . $item['name'], $existingKeys, true))
            ->values();

        if ($missingChildren->isEmpty()) {
            return $created;
        }

        $createdChildren = Category::query()->insertOrIgnore($missingChildren->map(fn($item) => [
            'name' => $item['name'],
            'slug' => $this->uniqueCategorySlug($item['name'], $item['parent_name'], $usedSlugs),
            'description' => null,
            'parent_id' => $item['parent_id'],
            'icon' => null,
            'image' => null,
            'sort_order' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());

        return $created + $createdChildren;
    }

    private function ensureParentCategories($parentNames, array &$usedSlugs = []): int
    {
        $existingModels = Category::query()
            ->whereNull('parent_id')
            ->whereIn('name', $parentNames)
            ->get(['id', 'name', 'parent_id']);

        $existing = $existingModels
            ->pluck('name')
            ->map(fn($name) => $this->normalizeName($name))
            ->all();

        $missing = $parentNames->diff($existing)->values();

        if ($missing->isEmpty()) {
            return 0;
        }

        return Category::query()->insertOrIgnore($missing->map(fn($name) => [
            'name' => $name,
            'slug' => $this->uniqueCategorySlug($name, null, $usedSlugs),
            'description' => null,
            'parent_id' => null,
            'icon' => null,
            'image' => null,
            'sort_order' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());
    }

    private function patchPayload(array &$payload, array $unitMap, array $taxMap, array $brandMap, array $categoryMap): void
    {
        $unitName = $this->normalizeName($payload['variant']['unit_name'] ?? null);
        $taxRate = $this->normalizeRate($payload['variant']['tax'] ?? null);
        $brandName = $this->normalizeName($payload['product']['brand_name'] ?? null);
        $parentName = $this->normalizeName($payload['product']['parent_category_name'] ?? null);
        $childName = $this->normalizeName($payload['product']['sub_category_name'] ?? null);

        if (empty($payload['variant']['unit_id'])) {
            $unitKey = $unitName ?: $this->normalizeName('khác');
            $payload['variant']['unit_id'] = $unitMap[$unitKey] ?? null;
            $payload['variant']['unit_name'] = $unitKey;
        }

        if ($taxRate && empty($payload['variant']['tax_id'])) {
            $payload['variant']['tax_id'] = $taxMap[$taxRate] ?? null;
            $payload['variant']['tax'] = $taxRate;
        }

        if ($brandName && empty($payload['product']['brand_id'])) {
            $payload['product']['brand_id'] = $brandMap[$brandName] ?? null;
        }

        if (empty($payload['product']['category_id'])) {
            if (!$parentName && !$childName) {
                $otherCategory = Category::firstOrCreate(
                    ['name' => 'Other', 'parent_id' => null],
                    [
                        'slug' => 'other',
                        'description' => 'Default category',
                        'icon' => null,
                        'image' => null,
                        'sort_order' => 0,
                        'is_active' => true,
                    ]
                );

                $payload['product']['category_id'] = $otherCategory->id;
                $payload['product']['category_name'] = 'Other';
                $payload['product']['parent_category_name'] = 'Other';
                $payload['product']['sub_category_name'] = null;
                return;
            }

            $categoryKey = $childName ? $parentName . '|' . $childName : $parentName;
            $categoryId = $categoryMap[$categoryKey] ?? null;

            if ($categoryId) {
                $payload['product']['category_id'] = $categoryId;
                $payload['product']['category_name'] = $payload['product']['category_name'] ?? $categoryKey;
            }
        }
    }

    private function unitMap(): array
    {
        return Unit::pluck('id', 'name')
            ->mapWithKeys(fn($id, $name) => [$this->normalizeName($name) => $id])
            ->toArray();
    }

    private function taxMap(): array
    {
        return Tax::pluck('id', 'rate')
            ->mapWithKeys(fn($id, $rate) => [$this->normalizeRate($rate) => $id])
            ->toArray();
    }

    private function brandMap(): array
    {
        return Brand::pluck('id', 'name')
            ->mapWithKeys(fn($id, $name) => [$this->normalizeName($name) => $id])
            ->toArray();
    }

    private function categoryMap(): array
    {
        $categories = Category::get(['id', 'name', 'parent_id'])->keyBy('id');
        $map = [];

        foreach ($categories as $category) {
            $name = $this->normalizeName($category->name);

            if (empty($category->parent_id)) {
                $map[$name] = $category->id;
                continue;
            }

            $parent = $categories->get($category->parent_id);
            if ($parent) {
                $parentName = $this->normalizeName($parent->name);
                $map[$parentName . '|' . $name] = $category->id;
            }
        }

        return $map;
    }

    private function normalizeName($name): ?string
    {
        $name = mb_strtolower(trim((string) $name));
        $name = str_replace(['–', '—'], '-', $name);
        $name = preg_replace('/\s+/u', ' ', $name);
        $name = preg_replace('/\s*-\s*/u', '-', $name);
        $name = preg_replace('/\s*\/\s*/u', '/', $name);

        return $name !== '' ? $name : null;
    }

    private function normalizeRate($rate): ?string
    {
        if ($rate === null || $rate === '') {
            return null;
        }

        return is_numeric($rate) ? number_format((float) $rate, 2, '.', '') : null;
    }

    private function uniqueCategorySlug(string $name, ?string $scope = null, array &$usedSlugs = []): string
    {
        $base = Str::slug($scope ? $scope . '-' . $name : $name);

        if (empty($base)) {
            $base = 'category-' . substr(md5($name), 0, 6);
        }

        $slug = $base;
        $index = 2;

        while (in_array($slug, $usedSlugs) || Category::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $index;
            $index++;
        }

        $usedSlugs[] = $slug;
        return $slug;
    }

    private function uniqueBrandSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $index = 2;

        while (Brand::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $index;
            $index++;
        }

        return $slug;
    }
}
