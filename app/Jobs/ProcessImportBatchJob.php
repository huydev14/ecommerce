<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Models\ImportProductRow;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Str;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
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

                $groupedProducts = [];
                $groupedVariants = [];

                foreach ($parsedRows as $payload) {
                    $productPayload = $payload['product'] ?? [];
                    $variantPayload = $payload['variant'] ?? [];
                    $stockPayload = $payload['stock'] ?? [];

                    $identityKey = md5(
                        strtolower(trim($productPayload['name'])) . '|' .
                        $productPayload['category_id'] . '|' .
                        ($productPayload['brand_id'] ?? '')
                    );

                    if (!isset($groupedProducts[$identityKey])) {
                        $slug = Str::slug(trim($productPayload['name'])) . '-' . uniqid();

                        $groupedProducts[$identityKey] = [
                            'name' => trim($productPayload['name']),
                            'slug' => $slug,
                            'description' => $productPayload['description'] ?? null,
                            'category_id' => $productPayload['category_id'],
                            'brand_id' => $productPayload['brand_id'] ?? null,
                            'status' => $productPayload['status'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    $variantPayload['_import_quantity'] = $stockPayload['quantity'] ?? 0;
                    $groupedVariants[$identityKey][] = $variantPayload;
                }

                try {
                    DB::transaction(function () use ($groupedProducts, $groupedVariants, $targetWarehouseId) {
                        $productsToInsert = array_values($groupedProducts);
                        Product::insert($productsToInsert);

                        $slugs = array_column($productsToInsert, 'slug');
                        $insertedProducts = Product::whereIn('slug', $slugs)->pluck('id', 'slug')->toArray();

                        $finalVariants = [];
                        foreach ($groupedProducts as $identityKey => $productData) {
                            $slug = $productData['slug'];

                            if (!isset($insertedProducts[$slug]))
                                continue;
                            $productId = $insertedProducts[$slug];

                            foreach ($groupedVariants[$identityKey] as $variantPayload) {
                                $finalVariants[] = [
                                    'product_id' => $productId,
                                    'name' => $variantPayload['name'] ?? null,
                                    'sku' => $variantPayload['sku'],
                                    'price' => round($variantPayload['price']),
                                    'cost_price' => $variantPayload['cost_price'] ? round($variantPayload['cost_price']) : null,
                                    'unit_id' => $variantPayload['unit_id'] ?? null,
                                    'tax_id' => $variantPayload['tax_id'] ?? null,
                                    'is_active' => $variantPayload['is_active'] ?? true,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                    '_import_quantity' => $variantPayload['_import_quantity'],
                                ];
                            }
                        }

                        $variantsForDb = array_map(function ($variant) {
                            unset($variant['_import_quantity']);
                            return $variant;
                        }, $finalVariants);

                        ProductVariant::insert($variantsForDb);

                        $skus = array_column($variantsForDb, 'sku');
                        $insertedVariants = ProductVariant::whereIn('sku', $skus)->pluck('id', 'sku');

                        $stocksToInsert = [];
                        $movementsDataMap = [];

                        foreach ($finalVariants as $variantPayload) {
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

                    Cache::forget('api_new_arrivals');

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

    private function updateImportProgress(ImportBatch $batch, int $processedRows, int $totalRows, string $progressStatus): void
    {
        $result = $batch->master_data_resolution_result ?? [];

        $batch->update([
            'status' => $progressStatus === 'completed' ? $batch->status : 'importing',
            'master_data_resolution_result' => array_merge($result, [
                'import_progress' => [
                    'status' => $progressStatus,
                    'processed_rows' => $processedRows,
                    'total_rows' => $totalRows,
                    'percentage' => $totalRows > 0 ? min(100, (int) round(($processedRows / $totalRows) * 100)) : 100,
                    'updated_at' => now()->toDateTimeString(),
                ],
            ]),
        ]);
    }
}
