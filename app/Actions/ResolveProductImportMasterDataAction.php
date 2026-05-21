<?php

namespace App\Actions;

use App\Imports\TempProductsImport;
use App\Models\Category;
use App\Models\ImportProductRow;
use App\Models\Tax;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResolveProductImportMasterDataAction
{
    public function execute(int $batchId): array
    {
        $rows = ImportProductRow::where('import_batch_id', $batchId)
            ->where('status', 'error')
            ->get();

        if ($rows->isEmpty()) {
            return [
                'units' => 0,
                'taxes' => 0,
                'categories' => 0,
                'resolved_rows' => 0,
            ];
        }

        $payloads = $rows->mapWithKeys(fn($row) => [$row->id => $this->payload($row->data)]);

        return DB::transaction(function () use ($rows, $payloads) {
            $createdUnits = $this->resolveUnits($payloads->all());
            $createdTaxes = $this->resolveTaxes($payloads->all());
            $createdCategories = $this->resolveCategories($payloads->all());

            $unitMap = $this->unitMap($payloads->all());
            $taxMap = $this->taxMap($payloads->all());
            $categoryMap = $this->categoryMap();

            $resolvedRows = 0;

            foreach ($rows as $row) {
                $payload = $payloads[$row->id];
                $retainedErrorCodes = array_values(array_diff($payload['error_codes'] ?? [], [
                    'missing_category',
                    'missing_unit',
                    'missing_tax',
                ]));

                $this->patchPayload($payload, $unitMap, $taxMap, $categoryMap);

                [$errors, $errorCodes] = TempProductsImport::validatePayload($payload);
                $errorCodes = array_values(array_unique(array_merge($errorCodes, $retainedErrorCodes)));

                $payload['error_codes'] = $errorCodes;
                $hasErrors = !empty($errors) || !empty($retainedErrorCodes);

                $row->update([
                    'data' => $payload,
                    'status' => $hasErrors ? 'error' : 'valid',
                    'error_message' => $hasErrors ? ($errors ? implode(' ', $errors) : $row->error_message) : null,
                ]);

                if (!$hasErrors) {
                    $resolvedRows++;
                }
            }

            return [
                'units' => $createdUnits,
                'taxes' => $createdTaxes,
                'categories' => $createdCategories,
                'resolved_rows' => $resolvedRows,
            ];
        });
    }

    private function resolveUnits(array $payloads): int
    {
        $names = collect($payloads)
            ->map(fn($payload) => $this->normalizeName($payload['variant']['unit_name'] ?? null))
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

        Unit::insert($missing->map(fn($name) => [
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());

        return $missing->count();
    }

    private function resolveTaxes(array $payloads): int
    {
        $rates = collect($payloads)
            ->map(fn($payload) => $this->normalizeRate($payload['variant']['tax'] ?? null))
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

        Tax::insert($missing->map(fn($rate) => [
            'name' => 'Thuế VAT ' . $rate . '%',
            'rate' => $rate,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());

        return $missing->count();
    }

    private function resolveCategories(array $payloads): int
    {
        $parentNames = collect($payloads)
            ->map(fn($payload) => $this->normalizeName($payload['product']['parent_category_name'] ?? null))
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

        $childrenToCreate = collect($payloads)
            ->map(function ($payload) use ($parents) {
                $parentName = $this->normalizeName($payload['product']['parent_category_name'] ?? null);
                $childName = $this->normalizeName($payload['product']['sub_category_name'] ?? null);

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

        Category::insert($missingChildren->map(fn($item) => [
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

        return $created + $missingChildren->count();
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

        Category::insert($missing->map(fn($name) => [
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

        return $missing->count();
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

    private function unitMap(array $payloads): array
    {
        $names = collect($payloads)
            ->map(fn($payload) => $this->normalizeName($payload['variant']['unit_name'] ?? null))
            ->filter()
            ->unique()
            ->all();

        return empty($names) ? [] : Unit::whereIn('name', $names)
            ->pluck('id', 'name')
            ->mapWithKeys(fn($id, $name) => [$this->normalizeName($name) => $id])
            ->toArray();
    }

    private function taxMap(array $payloads): array
    {
        $rates = collect($payloads)
            ->map(fn($payload) => $this->normalizeRate($payload['variant']['tax'] ?? null))
            ->filter()
            ->unique()
            ->all();

        return empty($rates) ? [] : Tax::whereIn('rate', $rates)
            ->pluck('id', 'rate')
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

    private function payload($data): array
    {
        return is_array($data) ? $data : json_decode($data, true);
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
