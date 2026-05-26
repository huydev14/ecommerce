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
    /**
     * Error codes that this action can clear.
     */
    private const RESOLVABLE_CODES = [
        'missing_category',
        'missing_brand',
        'missing_unit',
        'missing_tax',
        'duplicate_sku_in_database',
    ];

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

        $this->updateResolutionProgress($batchId, 0, $totalRowsToResolve);

        // Bulk insert master data
        DB::transaction(function () use (
            &$createdCategories, &$createdBrands, &$createdUnits, &$createdTaxes,
            $missingCategories, $missingBrands, $missingUnits, $missingTaxes
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

        $unitMap     = $this->unitMap();
        $taxMap      = $this->taxMap();
        $brandMap    = $this->brandMap();
        $categoryMap = $this->categoryMap();

        $resolvedRowsCount = 0;
        $processedRows = 0;

        ImportProductRow::where('import_batch_id', $batchId)
            ->where('status', 'error')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (
                &$resolvedRowsCount, &$processedRows,
                $batchId, $totalRowsToResolve,
                $unitMap, $taxMap, $brandMap, $categoryMap
            ) {
                $updates = [];

                foreach ($rows as $row) {
                    $payload = is_array($row->data) ? $row->data : json_decode($row->data, true);

                    $retainedCodes = array_values(array_diff($payload['error_codes'] ?? [], self::RESOLVABLE_CODES));

                    $this->patchPayload($payload, $unitMap, $taxMap, $brandMap, $categoryMap);

                    [$errors, $newCodes] = TempProductsImport::validatePayload($payload);

                    // Drop master-data codes that patchPayload already fixed
                    $newCodes = array_values(array_diff($newCodes, self::RESOLVABLE_CODES));

                    $finalCodes = array_values(array_unique(array_merge($newCodes, $retainedCodes)));
                    $payload['error_codes'] = $finalCodes;

                    $hasErrors = !empty($errors) || !empty($retainedCodes);

                    $updates[] = [
                        'id'             => $row->id,
                        'import_batch_id'=> $row->import_batch_id,
                        'row_number'     => $row->row_number,
                        'data'           => json_encode($payload),
                        'status'         => $hasErrors ? 'error' : 'valid',
                        'error_message'  => $hasErrors ? (implode(' ', $errors) ?: $row->error_message) : null,
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
            'units'          => $createdUnits,
            'taxes'          => $createdTaxes,
            'brands'         => $createdBrands,
            'categories'     => $createdCategories,
            'resolved_rows'  => $resolvedRowsCount,
            'processed_rows' => $processedRows,
            'total_rows'     => $totalRowsToResolve,
            'percentage'     => $this->resolutionPercentage($processedRows, $totalRowsToResolve),
            'status'         => 'completed',
        ];
    }

    // -------------------------------------------------------------------------
    // Master-data creators
    // -------------------------------------------------------------------------

    private function resolveCategories(array $categoryStrings): int
    {
        $allRoots     = Category::whereNull('parent_id')->get(['id', 'name', 'slug']);
        $rootBySlug   = $allRoots->keyBy(fn($c) => Str::slug($this->normalizeName($c->name) ?? ''));

        $parentNames = collect($categoryStrings)
            ->map(fn($s) => $this->normalizeName(explode('|', $s)[0] ?? null))
            ->filter()->unique()->values();

        $created = 0;
        $usedSlugs = $allRoots->pluck('slug')->all();

        foreach ($parentNames as $parentName) {
            $slug = Str::slug($parentName);
            if (!$rootBySlug->has($slug)) {
                $uniqueSlug = $this->makeUniqueSlug($slug, $usedSlugs);
                Category::create([
                    'name'        => $parentName,
                    'slug'        => $uniqueSlug,
                    'parent_id'   => null,
                    'description' => null,
                    'icon'        => null,
                    'image'       => null,
                    'sort_order'  => 0,
                    'is_active'   => true,
                ]);
                $usedSlugs[] = $uniqueSlug;
                $created++;
                // Refresh map so children can find this parent
                $rootBySlug->put($slug, Category::whereNull('parent_id')->where('slug', $uniqueSlug)->first());
            }
        }

        // 2. Ensure every required child exists (match by parent-slug + child-slug)
        $childStrings = collect($categoryStrings)->filter(fn($s) => str_contains($s, '|'));

        foreach ($childStrings as $str) {
            $parts      = explode('|', $str);
            $parentNorm = $this->normalizeName($parts[0]);
            $childNorm  = $this->normalizeName($parts[1]);
            if (!$parentNorm || !$childNorm) { continue; }

            $parentSlug = Str::slug($parentNorm);
            $parent     = $rootBySlug->get($parentSlug);
            if (!$parent) { continue; }

            $childSlug = Str::slug($childNorm);

            // Match existing children by slug so accent/dash variants don't create duplicates
            $childExists = Category::where('parent_id', $parent->id)
                ->get(['id', 'name', 'slug'])
                ->first(fn($c) => Str::slug($this->normalizeName($c->name) ?? '') === $childSlug);

            if (!$childExists) {
                $uniqueChildSlug = $this->makeUniqueSlug(
                    Str::slug($parentNorm . '-' . $childNorm),
                    $usedSlugs
                );
                Category::create([
                    'name'        => $childNorm,
                    'slug'        => $uniqueChildSlug,
                    'parent_id'   => $parent->id,
                    'description' => null,
                    'icon'        => null,
                    'image'       => null,
                    'sort_order'  => 0,
                    'is_active'   => true,
                ]);
                $usedSlugs[] = $uniqueChildSlug;
                $created++;
            }
        }

        return $created;
    }

    private function resolveBrands(array $namesList): int
    {
        $names = collect($namesList)->map(fn($n) => $this->normalizeName($n))->filter()->unique()->values();
        if ($names->isEmpty()) { return 0; }

        $existingSlugs = Brand::pluck('slug')->all();
        $existing = Brand::pluck('name')
            ->map(fn($n) => $this->normalizeName($n))
            ->all();

        $missing = $names->diff($existing)->values();

        if ($missing->isEmpty()) {
            return 0;
        }

        return Brand::insertOrIgnore($missing->map(fn($name) => [
            'name'       => $name,
            'slug'       => $this->makeUniqueSlug(Str::slug($name), $existingSlugs),
            'logo'       => null,
            'website'    => null,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());
    }

    private function resolveUnits(array $namesList): int
    {
        $names = collect($namesList)->map(fn($n) => $this->normalizeName($n))->filter()->unique()->values();
        if ($names->isEmpty()) { return 0; }

        $existingModels = Unit::withTrashed()->whereIn('name', $names)->get(['id', 'name', 'deleted_at']);

        $idsToRestore = $existingModels->whereNotNull('deleted_at')->pluck('id');
        if ($idsToRestore->isNotEmpty()) {
            Unit::whereIn('id', $idsToRestore)->restore();
        }

        $existing = $existingModels->pluck('name')->map(fn($n) => $this->normalizeName($n))->all();
        $missing  = $names->diff($existing)->values();
        if ($missing->isEmpty()) { return 0; }

        return Unit::insertOrIgnore($missing->map(fn($name) => [
            'name'       => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());
    }

    private function resolveTaxes(array $ratesList): int
    {
        $rates = collect($ratesList)->map(fn($r) => $this->normalizeRate($r))->filter()->unique()->values();
        if ($rates->isEmpty()) { return 0; }

        $existingModels = Tax::withTrashed()->whereIn('rate', $rates)->get(['id', 'rate', 'deleted_at']);
        $existingModels->whereNotNull('deleted_at')->each->restore();

        $existing = $existingModels->pluck('rate')->map(fn($r) => $this->normalizeRate($r))->all();
        $missing  = $rates->diff($existing)->values();
        if ($missing->isEmpty()) { return 0; }

        return Tax::insertOrIgnore($missing->map(fn($rate) => [
            'name'       => 'Thuế VAT ' . $rate . '%',
            'rate'       => $rate,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());
    }

    // -------------------------------------------------------------------------
    // Lookup maps
    // -------------------------------------------------------------------------

    /**
     * Category map keyed by Str::slug so minor accent/dash differences
     */
    private function categoryMap(): array
    {
        $categories = Category::get(['id', 'name', 'parent_id'])->keyBy('id');
        $map = [];

        foreach ($categories as $cat) {
            $normName = $this->normalizeName($cat->name);
            $slugName = Str::slug($normName ?? '');

            if (empty($cat->parent_id)) {
                $map[$slugName] = $cat->id;
                continue;
            }

            $parent = $categories->get($cat->parent_id);
            if ($parent) {
                $parentSlug = Str::slug($this->normalizeName($parent->name) ?? '');
                $map[$parentSlug . '|' . $slugName] = $cat->id;
            }
        }

        return $map;
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

    // -------------------------------------------------------------------------
    // Payload patcher
    // -------------------------------------------------------------------------

    private function patchPayload(array &$payload, array $unitMap, array $taxMap, array $brandMap, array $categoryMap): void
    {
        $unitName   = $this->normalizeName($payload['variant']['unit_name'] ?? null);
        $taxRate    = $this->normalizeRate($payload['variant']['tax']       ?? null);
        $brandName  = $this->normalizeName($payload['product']['brand_name']          ?? null);
        $parentName = $this->normalizeName($payload['product']['parent_category_name'] ?? null);
        $childName  = $this->normalizeName($payload['product']['sub_category_name']    ?? null);

        // Unit
        if (empty($payload['variant']['unit_id'])) {
            $key = $unitName ?: $this->normalizeName('khác');
            $payload['variant']['unit_id']   = $unitMap[$key] ?? null;
            $payload['variant']['unit_name'] = $key;
        }

        // Tax
        if ($taxRate && empty($payload['variant']['tax_id'])) {
            $payload['variant']['tax_id'] = $taxMap[$taxRate] ?? null;
            $payload['variant']['tax']    = $taxRate;
        }

        // Brand
        if ($brandName && empty($payload['product']['brand_id'])) {
            $payload['product']['brand_id'] = $brandMap[$brandName] ?? null;
        }

        // Category
        if (empty($payload['product']['category_id'])) {
            if (!$parentName && !$childName) {
                // Set default if no category
                $other = Category::firstOrCreate(
                    ['name' => 'Other', 'parent_id' => null],
                    ['slug' => 'other', 'description' => 'Default category',
                     'icon' => null, 'image' => null, 'sort_order' => 0, 'is_active' => true]
                );
                $payload['product']['category_id']          = $other->id;
                $payload['product']['category_name']        = 'Other';
                $payload['product']['parent_category_name'] = 'Other';
                $payload['product']['sub_category_name']    = null;
                return;
            }
            $parentSlug = Str::slug($parentName ?? '');
            $slugKey    = $childName ? $parentSlug . '|' . Str::slug($childName) : $parentSlug;
            $categoryId = $categoryMap[$slugKey] ?? null;

            if ($categoryId) {
                $payload['product']['category_id']   = $categoryId;
                $payload['product']['category_name'] ??= ($childName ?? $parentName);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Utilities
    // -------------------------------------------------------------------------

    private function makeUniqueSlug(string $base, array &$usedSlugs): string
    {
        if (empty($base)) {
            $base = 'item-' . substr(md5(uniqid()), 0, 6);
        }

        $slug  = $base;
        $index = 2;
        while (in_array($slug, $usedSlugs, true)) {
            $slug = $base . '-' . $index++;
        }

        $usedSlugs[] = $slug;
        return $slug;
    }

    private function mergeUnique(array ...$lists): array
    {
        return collect($lists)
            ->flatten(1)
            ->map(fn($v) => is_scalar($v) ? trim((string) $v) : null)
            ->filter(fn($v) => $v !== null && $v !== '')
            ->unique()->values()->all();
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
        if ($rate === null || $rate === '') { return null; }
        return is_numeric($rate) ? number_format((float) $rate, 2, '.', '') : null;
    }

    private function updateResolutionProgress(int $batchId, int $processedRows, int $totalRows): void
    {
        $current = ImportBatch::whereKey($batchId)->value('master_data_resolution_result') ?? [];
        $current = is_array($current) ? $current : json_decode($current, true) ?? [];

        ImportBatch::whereKey($batchId)->update([
            'master_data_resolution_result' => array_merge($current, [
                'status'         => 'processing',
                'processed_rows' => $processedRows,
                'total_rows'     => $totalRows,
                'percentage'     => $this->resolutionPercentage($processedRows, $totalRows),
            ]),
        ]);
    }

    private function resolutionPercentage(int $processedRows, int $totalRows): int
    {
        if ($totalRows <= 0) { return 100; }
        return min(100, (int) round(($processedRows / $totalRows) * 100));
    }
}
