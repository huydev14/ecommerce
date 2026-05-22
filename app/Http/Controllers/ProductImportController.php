<?php

namespace App\Http\Controllers;

use App\Actions\ResolveProductImportMasterDataAction;
use App\Imports\TempProductsImport;
use App\Jobs\ProcessImportBatchJob;
use App\Models\ImportBatch;
use App\Models\ImportProductRow;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProductImportController extends Controller
{
    public function index()
    {
        $latestBatches = ImportBatch::with('user')
            ->latest()
            ->take(5)
            ->get();

        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('product-imports.index', compact('latestBatches', 'warehouses'));
    }

    public function downloadTemplate()
    {
        if (! Storage::disk('app_files')->exists('templates/product_import_sample.xlsx')) {
            abort(404, __('product_import.template_not_found'));
        }

        return Storage::disk('app_files')->download('templates/product_import_sample.xlsx');
    }

    public function uploadAndPreview(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls,csv|max:10240',
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $batch = ImportBatch::create([
            'user_id' => $request->user()->id,
            'status' => 'processing',
            'warehouse_id' => $request->warehouse_id,
        ]);

        Excel::queueImport(new TempProductsImport($batch->id), $request->file('excel_file'));

        return redirect()->route('product-imports.preview', $batch->id);
    }

    public function showPreview($batchId)
    {
        $batch = ImportBatch::findOrFail($batchId);
        $totalRows = ImportProductRow::where('import_batch_id', $batchId)->count();

        if ($batch->status === 'preview_ready') {
            $batch->update([
                'status' => 'ready',
                'total_rows' => $totalRows,
            ]);
            $batch->refresh();
        }

        $rows = ImportProductRow::where('import_batch_id', $batchId)
            ->orderByRaw("CASE WHEN status = 'error' THEN 0 ELSE 1 END")
            ->paginate(20);
        $validRows = ImportProductRow::where('import_batch_id', $batchId)->where('status', 'valid')->count();
        $errorRows = ImportProductRow::where('import_batch_id', $batchId)->where('status', 'error')->count();
        $missingMasterData = $this->missingMasterDataSummary((int) $batchId);
        $canResolveMasterData = in_array($batch->status, ['ready', 'completed_with_errors'], true);
        $canCancelImport = in_array($batch->status, ['processing', 'preview_ready', 'ready'], true);
        $canConfirmImport = in_array($batch->status, ['ready', 'preview_ready'], true);

        return view('product-imports.preview', compact('batch', 'rows', 'validRows', 'errorRows', 'missingMasterData', 'canResolveMasterData', 'canCancelImport', 'canConfirmImport'));
    }

    public function progress($batchId)
    {
        $batch = ImportBatch::findOrFail($batchId);
        $processedRows = ImportProductRow::where('import_batch_id', $batchId)->count();
        $totalRows = max((int) $batch->total_rows, $processedRows);

        return response()->json([
            'batchId' => (int) $batch->id,
            'processedRows' => $processedRows,
            'totalRows' => $totalRows,
            'status' => $batch->status,
            'isFinished' => in_array($batch->status, ['ready', 'completed', 'completed_with_errors'], true),
        ]);
    }

    public function confirmImport($batchId)
    {
        $batch = ImportBatch::findOrFail($batchId);

        if (!in_array($batch->status, ['ready', 'preview_ready'], true)) {
            return redirect()->back()->with('error', 'Trạng thái file không hợp lệ để import.');
        }

        ProcessImportBatchJob::dispatch($batch->id);

        return redirect()->route('products.index')
            ->with('success', 'Hệ thống đang đưa sản phẩm vào kho.');
    }

    public function cancelImport($batchId)
    {
        $batch = ImportBatch::findOrFail($batchId);

        if (! in_array($batch->status, ['processing', 'preview_ready', 'ready'], true)) {
            return redirect()->back()->with('error', __('product_import.cancel_invalid_status'));
        }

        DB::transaction(function () use ($batch) {
            $batch->rows()->delete();
            $batch->delete();
        });

        return redirect()
            ->route('product-imports.index')
            ->with('success', __('product_import.cancel_success'));
    }

    public function resolveMissingMasterData($batchId, ResolveProductImportMasterDataAction $action)
    {
        $batch = ImportBatch::findOrFail($batchId);

        if (!in_array($batch->status, ['ready', 'preview_ready', 'completed_with_errors'], true)) {
            return redirect()->back()->with('error', __('product_import.resolve_invalid_status'));
        }

        $result = $action->execute((int) $batch->id);

        return redirect()
            ->route('product-imports.preview', $batch->id)
            ->with('success', __('product_import.resolve_success', [
                'rows' => $result['resolved_rows'],
                'categories' => $result['categories'],
                'brands' => $result['brands'],
                'units' => $result['units'],
                'taxes' => $result['taxes'],
            ]));
    }

    private function missingMasterDataSummary(int $batchId): array
    {
        $summary = [
            'categories' => 0,
            'brands' => 0,
            'units' => 0,
            'taxes' => 0,
            'total' => 0,
        ];

        ImportProductRow::where('import_batch_id', $batchId)
            ->where('status', 'error')
            ->get(['data'])
            ->each(function (ImportProductRow $row) use (&$summary) {
                $codes = $row->data['error_codes'] ?? [];

                if (in_array('missing_category', $codes, true)) {
                    $summary['categories']++;
                }

                if (in_array('missing_brand', $codes, true)) {
                    $summary['brands']++;
                }

                if (in_array('missing_unit', $codes, true)) {
                    $summary['units']++;
                }

                if (in_array('missing_tax', $codes, true)) {
                    $summary['taxes']++;
                }
            });

        $summary['total'] = $summary['categories'] + $summary['brands'] + $summary['units'] + $summary['taxes'];

        return $summary;
    }
}
