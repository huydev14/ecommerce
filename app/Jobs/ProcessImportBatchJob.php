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

    public function __construct($batchId)
    {
        $this->batchId = $batchId;
    }

    public function handle(): void
    {
        $batch = ImportBatch::find($this->batchId);
        if (!$batch) {
            return;
        }

        $totalRows = ImportProductRow::where('import_batch_id', $this->batchId)
            ->where('status', 'valid')
            ->count();
        $processedRows = 0;

        $this->updateImportProgress($batch, $processedRows, $totalRows, 'processing');

        $targetWarehouseId = $batch->warehouse_id;

        ImportProductRow::where('import_batch_id', $this->batchId)
            ->where('status', 'valid')
            ->chunkById(1000, function ($rows) use ($batch, $targetWarehouseId, &$processedRows, $totalRows) {
                $parsedRows = [];
                $rowIds = $rows->pluck('id')->all();

                foreach ($rows as $row) {
                    $parsedRows[$row->id] = is_array($row->data) ? $row->data : json_decode($row->data, true);
                }

                $productsToInsert = [];
                $variantsToInsert = [];

                foreach ($parsedRows as $payload) {
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
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $variantPayload['_import_quantity'] = $stockPayload['quantity'] ?? 0;
                    $variantsToInsert[$slug] = $variantPayload;
                }

                try {
                    DB::transaction(function () use ($productsToInsert, $variantsToInsert, $targetWarehouseId) {
                        Product::insert($productsToInsert);

                        $slugs = array_keys($variantsToInsert);
                        $insertedProducts = Product::whereIn('slug', $slugs)
                            ->pluck('id', 'slug');

                        $finalVariants = [];
                        foreach ($variantsToInsert as $slug => $variantPayload) {
                            if (!isset($insertedProducts[$slug]))
                                continue;

                            $finalVariants[] = [
                                'product_id' => $insertedProducts[$slug],
                                'attributes' => !empty($variantPayload['attributes']) ? json_encode(json_decode($variantPayload['attributes'], true)) : null,
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

                        ProductVariant::insert($finalVariants);

                        $skus = array_column($finalVariants, 'sku');
                        $insertedVariants = ProductVariant::whereIn('sku', $skus)
                            ->pluck('id', 'sku');

                        $stocksToInsert = [];
                        $movementsDataMap = [];

                        foreach ($variantsToInsert as $variantPayload) {
                            $variantId = $insertedVariants[$variantPayload['sku']] ?? null;
                            $importQuantity = $variantPayload['_import_quantity'] ?? 0;

                            if ($variantId && $importQuantity > 0 && $targetWarehouseId) {
                                $stocksToInsert[] = [
                                    'product_variant_id' => $variantId,
                                    'warehouse_id' => $targetWarehouseId,
                                    'quantity' => $importQuantity,
                                    'reserved_quantity' => 0,
                                    'low_stock_threshold' => 10,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];

                                $movementsDataMap[$variantId] = $importQuantity;
                            }
                        }

                        if (empty($stocksToInsert)) {
                            return;
                        }

                        Stock::insert($stocksToInsert);

                        $insertedStocks = Stock::where('warehouse_id', $targetWarehouseId)
                            ->whereIn('product_variant_id', array_keys($movementsDataMap))
                            ->pluck('id', 'product_variant_id');

                        $movementsToInsert = [];

                        foreach ($movementsDataMap as $variantId => $importQuantity) {
                            $stockId = $insertedStocks[$variantId] ?? null;

                            if ($stockId) {
                                $movementsToInsert[] = [
                                    'stock_id' => $stockId,
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
                    });

                    ImportProductRow::whereIn('id', $rowIds)->update(['status' => 'completed']);
                } catch (\Throwable $exception) {
                    ImportProductRow::whereIn('id', $rowIds)->update([
                        'status' => 'error',
                        'error_message' => 'Thêm sản phẩm vào hệ thống thất bại: ' . $exception->getMessage(),
                    ]);
                }

                $processedRows += count($rowIds);
                $this->updateImportProgress($batch, $processedRows, $totalRows, 'processing');
            });

        $failedRows = ImportProductRow::where('import_batch_id', $this->batchId)
            ->where('status', 'error')
            ->count();

        $this->updateImportProgress($batch, $processedRows, $totalRows, 'completed');
        $batch->update(['status' => $failedRows > 0 ? 'completed_with_errors' : 'completed']);
    }

    private function updateImportProgress(ImportBatch $batch, int $processedRows, int $totalRows, string $status): void
    {
        $result = $batch->master_data_resolution_result ?? [];

        $batch->update([
            'status' => 'importing',
            'master_data_resolution_result' => array_merge($result, [
                'import_progress' => [
                    'status' => $status,
                    'processed_rows' => $processedRows,
                    'total_rows' => $totalRows,
                    'percentage' => $totalRows > 0 ? min(100, (int) round(($processedRows / $totalRows) * 100)) : 100,
                    'updated_at' => now()->toDateTimeString(),
                ],
            ]),
        ]);
    }
}
