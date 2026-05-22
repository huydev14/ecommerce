<?php

namespace App\Actions;

use App\Imports\TempProductsImport;
use App\Models\Category;
use App\Models\ImportProductRow;
use App\Models\Tax;
use App\Models\Unit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class ResolveProductImportMasterDataAction
{
    public function execute(int $batchId): array
    {
        // Read missing metadata from cache
        $missingCategories = Redis::sMembers("import_batch_{$batchId}_missing_categories") ?: [];
        $missingUnits = Redis::sMembers("import_batch_{$batchId}_missing_units") ?: [];
        $missingTaxes = Redis::sMembers("import_batch_{$batchId}_missing_taxes") ?: [];

        $createdCategories = 0;
        $createdUnits = 0;
        $createdTaxes = 0;

        // Bulk insert master data
        DB::transaction(function () use (&$createdCategories, &$createdUnits, &$createdTaxes, $missingCategories, $missingUnits, $missingTaxes) {
            if (!empty($missingUnits)) {
                $createdUnits = $this->resolveUnits($missingUnits);
            }
            if (!empty($missingTaxes)) {
                $createdTaxes = $this->resolveTaxes($missingTaxes);
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
        $categoryMap = $this->categoryMap();

        ImportProductRow::where('import_batch_id', $batchId)
            ->where('status', 'error')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$resolvedRowsCount, $unitMap, $taxMap, $categoryMap) {

                $updates = [];

                foreach ($rows as $row) {
                    $payload = is_array($row->data) ? $row->data : json_decode($row->data, true);

                    // Remove error codes
                    $retainedErrorCodes = array_values(array_diff($payload['error_codes'] ?? [], [
                        'missing_category',
                        'missing_unit',
                        'missing_tax'
                    ]));

                    $this->patchPayload($payload, $unitMap, $taxMap, $categoryMap);

                    // Validate lại lần cuối
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
            });

        Redis::del("import_batch_{$batchId}_missing_categories");
        Redis::del("import_batch_{$batchId}_missing_units");
        Redis::del("import_batch_{$batchId}_missing_taxes");

        return [
            'units' => $createdUnits,
            'taxes' => $createdTaxes,
            'categories' => $createdCategories,
            'resolved_rows' => $resolvedRowsCount,
        ];
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

        $existingModels->whereNotNull('deleted_at')->each->restore();

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

        $created = $this->ensureParentCategories($parentNames);

        $parents = Category::withTrashed()
            ->whereNull('parent_id')
            ->whereIn('name', $parentNames)
            ->get(['id', 'name', 'parent_id', 'deleted_at'])
            ->keyBy(fn($category) => $this->normalizeName($category->name));

        $parents->whereNotNull('deleted_at')->each->restore();

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

        $existingChildren = Category::withTrashed()
            ->whereIn('parent_id', $childrenToCreate->pluck('parent_id')->unique()->all())
            ->whereIn('name', $childrenToCreate->pluck('name')->unique()->all())
            ->get(['id', 'name', 'parent_id', 'deleted_at']);

        $existingChildren->whereNotNull('deleted_at')->each->restore();

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
            'slug' => $this->uniqueCategorySlug($item['name'], $item['parent_name']),
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

    private function ensureParentCategories($parentNames): int
    {
        $existingModels = Category::withTrashed()
            ->whereNull('parent_id')
            ->whereIn('name', $parentNames)
            ->get(['id', 'name', 'parent_id', 'deleted_at']);

        $existingModels->whereNotNull('deleted_at')->each->restore();

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
            'slug' => $this->uniqueCategorySlug($name),
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

    private function patchPayload(array &$payload, array $unitMap, array $taxMap, array $categoryMap): void
    {
        $unitName = $this->normalizeName($payload['variant']['unit_name'] ?? null);
        $taxRate = $this->normalizeRate($payload['variant']['tax'] ?? null);
        $parentName = $this->normalizeName($payload['product']['parent_category_name'] ?? null);
        $childName = $this->normalizeName($payload['product']['sub_category_name'] ?? null);

        if ($unitName && empty($payload['variant']['unit_id'])) {
            $payload['variant']['unit_id'] = $unitMap[$unitName] ?? null;
        }

        if ($taxRate && empty($payload['variant']['tax_id'])) {
            $payload['variant']['tax_id'] = $taxMap[$taxRate] ?? null;
            $payload['variant']['tax'] = $taxRate;
        }

        if (empty($payload['product']['category_id'])) {
            $categoryKey = $childName ? $parentName . '|' . $childName : $parentName;
            $payload['product']['category_id'] = $categoryMap[$categoryKey] ?? null;
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

    private function categoryMap(): array
    {
        $categories = Category::all();
        $map = [];

        foreach ($categories as $category) {
            $name = $this->normalizeName($category->name);

            if (empty($category->parent_id)) {
                $map[$name] = $category->id;
                continue;
            }

            $parent = $categories->firstWhere('id', $category->parent_id);
            if ($parent) {
                $map[$this->normalizeName($parent->name) . '|' . $name] = $category->id;
            }
        }

        return $map;
    }

    private function normalizeName($name): ?string
    {
        $name = mb_strtolower(trim((string) $name));

        return $name !== '' ? $name : null;
    }

    private function normalizeRate($rate): ?string
    {
        if ($rate === null || $rate === '') {
            return null;
        }

        return is_numeric($rate) ? number_format((float) $rate, 2, '.', '') : null;
    }

    private function uniqueCategorySlug(string $name, ?string $scope = null): string
    {
        $base = Str::slug($scope ? $scope . '-' . $name : $name);
        $slug = $base;
        $index = 2;

        while (Category::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $index;
            $index++;
        }

        return $slug;
    }
}
