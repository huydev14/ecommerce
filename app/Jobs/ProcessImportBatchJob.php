<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Models\ImportProductRow;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\StockMovement;
use DragonCode\Support\Facades\Helpers\Str;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

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

        $targetWarehouseId = $batch->warehouse_id;

        ImportProductRow::where('import_batch_id', $this->batchId)
            ->where('status', 'valid')
            ->chunkById(1000, function ($rows) use ($targetWarehouseId) {
                $productsToInsert = [];
                $variantsToInsert = [];

                foreach ($rows as $row) {
                    $payload = is_array($row->data) ? $row->data : json_decode($row->data, true);
                    $productPayload = $payload['product'] ?? [];
                    $variantPayload = $payload['variant'] ?? [];
                    $stockPayload = $payload['stock'] ?? [];

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

                    $variantPayload['_import_quantity'] = $stockPayload['quantity'] ?? 0;
                    $variantsToInsert[$slug] = $variantPayload;
                }

                try {
                    DB::transaction(function () use ($productsToInsert, $variantsToInsert, $targetWarehouseId) {

                        // Bulk insert products
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
                                    'attributes' => $variantPayload['attributes'] ? json_encode(json_decode($variantPayload['attributes'], true)) : null,
                                    'sku' => $variantPayload['sku'],
                                    'price' => $variantPayload['price'],
                                    'compare_at_price' => $variantPayload['compare_at_price'] ?? null,
                                    'cost_price' => $variantPayload['cost_price'] ?? null,
                                    'unit_id' => $variantPayload['unit_id'] ?? null,
                                    'tax_id' => $variantPayload['tax_id'] ?? null,
                                    'is_active' => $variantPayload['is_active'] ?? true,

                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];
                            }
                        }

                        // Bulk insert product variants
                        if (!empty($finalVariants)) {
                            ProductVariant::insert($finalVariants);
                        }

                        // --- Handle stock increment and log logic ------------------
                        $skus = array_column($finalVariants, 'sku');
                        $insertedVariants = ProductVariant::whereIn('sku', $skus)->get(['id', 'sku'])->keyBy('sku');

                        $stocksToInsert = [];
                        $movementsDataMap = [];

                        foreach ($variantsToInsert as $slug => $variantPayload) {
                            $sku = $variantPayload['sku'];
                            $variant = $insertedVariants->get($sku);
                            $importQuantity = $variantPayload['_import_quantity'] ?? 0;

                            if ($variant && $importQuantity > 0 && $targetWarehouseId) {

                                $stocksToInsert[] = [
                                    'product_variant_id' => $variant->id,
                                    'warehouse_id' => $targetWarehouseId,
                                    'quantity' => $importQuantity,
                                    'reserved_quantity' => 0,
                                    'low_stock_threshold' => 10,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];

                                $movementsDataMap[$variant->id] = $importQuantity;
                            }
                        }

                        if (!empty($stocksToInsert)) {
                            Stock::insert($stocksToInsert);

                            $insertedStocks = Stock::where('warehouse_id', $targetWarehouseId)
                                ->whereIn('product_variant_id', array_keys($movementsDataMap))
                                ->get(['id', 'product_variant_id'])
                                ->keyBy('product_variant_id');

                            $movementsToInsert = [];

                            foreach ($movementsDataMap as $variantId => $importQuantity) {
                                $stock = $insertedStocks->get($variantId);

                                if ($stock) {
                                    $movementsToInsert[] = [
                                        'stock_id' => $stock->id,
                                        'type' => 'in',
                                        'quantity_changed' => $importQuantity,
                                        'quantity_after' => $importQuantity,
                                        'note' => 'Nhập tồn kho đầu kỳ từ file Excel',
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ];
                                }
                            }
                            if (!empty($movementsToInsert)) {
                                StockMovement::insert($movementsToInsert);
                            }
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