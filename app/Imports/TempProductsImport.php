<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\ImportProductRow;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TempProductsImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    protected $batchId;
    protected $categoriesMap = [];

    public function __construct($batchId)
    {
        $this->batchId = $batchId;
    }
    public function collection(Collection $rows)
    {
        $categoryNames = $rows->pluck('category_name')
            ->filter()
            ->unique()
            ->toArray();

        $this->categoriesMap = Category::whereIn('name', $categoryNames)
            ->pluck('id', 'name')
            ->toArray();

        $rowsToInsert = [];

        foreach ($rows as $index => $row) {
            $name = $row['product_name'] ?? null;
            $price = $row['price'] ?? null;
            $categoryName = $row['category_name'] ?? null;

            $errors = [];
            if (empty($name)) $errors[] = "Tên sản phẩm trống.";
            if ($price < 0) $errors[] = "Giá không được âm.";
            if (empty($categoryName)) $errors[] = "Tên danh mục trống.";

            $isValid = empty($errors);

            $rowsToInsert[] = [
                'import_batch_id' => $this->batchId,
                'row_number' => $index + 2,
                'status' => $isValid ? 'valid' : 'error',
                'error_message' => $isValid ? null : implode(' ', $errors),
                'data' => json_encode([
                    'name' => $name,
                    'price' => $price,
                    'category_name' => $categoryName,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        ImportProductRow::insert($rowsToInsert);
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
