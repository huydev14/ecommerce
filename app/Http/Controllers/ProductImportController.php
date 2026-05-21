<?php

namespace App\Http\Controllers;

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

        return view('product-imports.preview', compact('batch', 'rows', 'validRows', 'errorRows'));
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
}
