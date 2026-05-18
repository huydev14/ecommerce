<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\ImportBatch;
use App\Models\ImportProductRow;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessImportBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batchId;

    /**
     * Create a new job instance.
     */
    public function __construct($batchId)
    {
        $this->batchId = $batchId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $batch = ImportBatch::find($this->batchId);
        if (!$batch) {
            return;
        }

        $batch->update(['status' => 'importing']);

        ImportProductRow::where('import_batch_id', $this->batchId)
            ->where('status', 'valid')
            ->chunkById(1000, function ($rows) {
                $categoryNames = $rows->map(function ($row) {
                    return $row->data['category_name'] ?? null;
                })->filter()->unique()->toArray();

                $categoriesMap = Category::whereIn('name', $categoryNames)
                    ->pluck('id', 'name')
                    ->toArray();

                $productsToInsert = [];

                foreach ($rows as $row) {
                    $payload = $row->data;
                    $categoryName = $payload['category_name'] ?? null;

                    $categoryId = $categoriesMap[$categoryNames] ?? null;

                    if (!$categoryId && $categoryName) {
                        $newCategory = Category::create(['name' => $categoryName]);
                        $categoryId = $newCategory->id;
                        $categoriesMap[$categoryName] = $categoryId;
                    }

                    $productsToInsert[] = [
                        'name'        => $payload['name'],
                        'price'       => $payload['price'],
                        'category_id' => $categoryId,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }
                Product::insert($productsToInsert);
            });
            $batch->update(['status' => 'completed']);
            ImportProductRow::where('import_batch_id', $this->batchId)->delete();
    }
}
