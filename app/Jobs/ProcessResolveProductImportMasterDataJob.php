<?php

namespace App\Jobs;

use App\Actions\ResolveProductImportMasterDataAction;
use App\Models\ImportBatch;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessResolveProductImportMasterDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    protected int $batchId;
    /**
     * Create a new job instance.
     */
    public function __construct(int $batchId)
    {
        $this->batchId = $batchId;
    }

    /**
     * Execute the job.
     */
    public function handle(ResolveProductImportMasterDataAction $action): void
    {
        try {
            $batch = ImportBatch::find($this->batchId);

            if (! $batch) {
                return;
            }

            $progress = $batch->master_data_resolution_result ?? [];
            $previousStatus = $progress['previous_status'] ?? $batch->status;

            $result = $action->execute($this->batchId);

            $remainingErrorRows = $batch->rows()->where('status', 'error')->count();
            $nextStatus = $previousStatus === 'preview_ready' ? 'ready' : $previousStatus;

            if ($remainingErrorRows === 0 && $previousStatus === 'completed_with_errors') {
                $nextStatus = 'ready';
            }

            $batch->update([
                'master_data_resolution_result' => array_merge($progress, $result, [
                    'status' => 'completed',
                    'remaining_error_rows' => $remainingErrorRows,
                    'finished_at' => now()->toDateTimeString(),
                ]),
                'status' => $nextStatus,
            ]);
        } catch (\Exception $e) {
            Log::error("Lỗi khi xử lý Batch ID: {$this->batchId}", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $batch = ImportBatch::find($this->batchId);
            $progress = $batch?->master_data_resolution_result ?? [];
            $previousStatus = $progress['previous_status'] ?? 'ready';

            ImportBatch::whereKey($this->batchId)->update([
                'master_data_resolution_result' => [
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                    'finished_at' => now()->toDateTimeString(),
                ],
                'status' => $previousStatus === 'preview_ready' ? 'ready' : $previousStatus,
            ]);

            $this->fail($e);
        }
    }
}
