<?php

namespace App\Http\Controllers;

use App\Actions\ResolveProductImportMasterDataAction;
use App\Imports\TempProductsImport;
use App\Jobs\ProcessImportBatchJob;
use App\Models\ImportBatch;
use App\Models\ImportProductRow;
use App\Models\Warehouse;
use Illuminate\Http\Request;
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

        Excel::import(new TempProductsImport($batch->id), $request->file('excel_file'));

        $totalRows = ImportProductRow::where('import_batch_id', $batch->id)->count();
        $batch->update([
            'status' => 'ready',
            'total_rows' => $totalRows,
        ]);

        return redirect()->route('product-imports.preview', $batch->id);
    }

    public function showPreview($batchId)
    {
        $batch = ImportBatch::findOrFail($batchId);
        $rows = ImportProductRow::where('import_batch_id', $batchId)->paginate(20);
        $validRows = ImportProductRow::where('import_batch_id', $batchId)->where('status', 'valid')->count();
        $errorRows = ImportProductRow::where('import_batch_id', $batchId)->where('status', 'error')->count();
        $missingMasterData = $this->missingMasterDataSummary((int) $batchId);

        return view('product-imports.preview', compact('batch', 'rows', 'validRows', 'errorRows', 'missingMasterData'));
    }

    public function confirmImport($batchId)
    {
        $batch = ImportBatch::findOrFail($batchId);

        if ($batch->status !== 'ready') {
            return redirect()->back()->with('error', 'Trạng thái file không hợp lệ để import.');
        }

        ProcessImportBatchJob::dispatch($batch->id);

        return redirect()->route('products.index')
            ->with('success', 'Hệ thống đang đưa sản phẩm vào kho.');
    }

    public function resolveMissingMasterData($batchId, ResolveProductImportMasterDataAction $action)
    {
        $batch = ImportBatch::findOrFail($batchId);

        if (!in_array($batch->status, ['ready', 'completed_with_errors'], true)) {
            return redirect()->back()->with('error', __('product_import.resolve_invalid_status'));
        }

        $result = $action->execute((int) $batch->id);

        return redirect()
            ->route('product-imports.preview', $batch->id)
            ->with('success', __('product_import.resolve_success', [
                'rows' => $result['resolved_rows'],
                'categories' => $result['categories'],
                'units' => $result['units'],
                'taxes' => $result['taxes'],
            ]));
    }

    private function missingMasterDataSummary(int $batchId): array
    {
        $summary = [
            'categories' => 0,
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

                if (in_array('missing_unit', $codes, true)) {
                    $summary['units']++;
                }

                if (in_array('missing_tax', $codes, true)) {
                    $summary['taxes']++;
                }
            });

        $summary['total'] = $summary['categories'] + $summary['units'] + $summary['taxes'];

        return $summary;
    }
}
