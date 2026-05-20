<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Models\ImportProductRow;
use App\Models\Product;
use App\Models\ProductVariant;
use DragonCode\Support\Facades\Helpers\Str;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

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
        if (!$batch)
            return;

        $batch->update(['status' => 'importing']);

        ImportProductRow::where('import_batch_id', $this->batchId)
            ->where('status', 'valid')
            ->chunkById(1000, function ($rows) {
                $productsToInsert = [];
                $variantsToInsert = [];

                foreach ($rows as $row) {
                    $payload = $row->data;
                    $productPayload = $payload['product'] ?? [];
                    $variantPayload = $payload['variant'] ?? [];

                    $slug = Str::slug($productPayload['name']) . '-' . uniqid();

                    $productsToInsert[] = [
                        'name' => $productPayload['name'],
                        'slug' => $slug,
                        'description' => $productPayload['description'] ?? null,
                        'category_id' => $productPayload['category_id'],
                        'brand_id' => $productPayload['brand_id'] ?? null,
                        'status' => $productPayload['status'],
                        'metadata' => $productPayload['metadata'] ? json_encode(json_decode($productPayload['metadata'], true)) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $variantsToInsert[$slug] = $variantPayload;
                }

                try {
                    DB::transaction(function () use ($productsToInsert, $variantsToInsert) {
                        Product::insert($productsToInsert);

                        $insertedProducts = Product::whereIn('slug', array_keys($variantsToInsert))
                            ->get(['id', 'slug'])
                            ->keyBy('slug');

                        $finalVariants = [];

                        foreach ($variantsToInsert as $slug => $variantPayload) {
                            $product = $insertedProducts->get($slug);

                            if ($product) {
                                $finalVariants[] = [
                                    'product_id' => $product->id,
                                    'sku'     => $variantPayload['sku'],
                                    'price'   => $variantPayload['price'],
                                    'compare_at_price' => $variantPayload['compare_at_price'] ?? null,
                                    'cost_price' => $variantPayload['cost_price'] ?? null,
                                    'unit_id' => $variantPayload['unit_id'] ?? null,
                                    'tax_id' => $variantPayload['tax_id'] ?? null,
                                    'attributes' => $variantPayload['attributes'] ? json_encode(json_decode($variantPayload['attributes'], true)) : null,
                                    'is_active'  => $variantPayload['is_active'] ?? true,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];
                            }
                        }

                        if (!empty($finalVariants)) {
                            ProductVariant::insert($finalVariants);
                        }
                    });
                } catch (\Throwable $exception) {
                    $rowIds = $rows->pluck('id')->toArray();
                    ImportProductRow::whereIn('id', $rowIds)->update([
                        'status' => 'error',
                        'error_message' => 'Bulk insert failed: ' . $exception->getMessage(),
                    ]);
                }
            });

        $failedRows = ImportProductRow::where('import_batch_id', $this->batchId)
            ->where('status', 'error')
            ->count();

        $batch->update(['status' => $failedRows > 0 ? 'completed_with_errors' : 'completed']);

        ImportProductRow::where('import_batch_id', $this->batchId)
            ->where('status', 'valid')
            ->delete();
    }
}
