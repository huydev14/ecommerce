<?php

namespace App\Http\Controllers;

use App\Imports\TempProductsImport;
use App\Jobs\ProcessImportBatchJob;
use App\Models\ImportBatch;
use App\Models\ImportProductRow;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel;

class ProductImportController extends Controller
{
    public function uploadAndPreview(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|mimes:xlsx, xls,csv|max:10240'
        ]);

        $batch = ImportBatch::create([
            'user_id' => auth()->id(),
            'status' => 'processing',
        ]);

        Excel::import(new TempProductsImport($batch->id), $request->file('excel_fiel'));

        $totalRows = ImportProductRow::where('import_batch_id', $batch->id)->count();
        $batch->update([
            'status' => 'ready',
            'total_rows' => $totalRows,
        ]);

        return redirect()->route('import.preview', $batch->id);
    }

    public function showPreview($batchId)
    {
        $batch = ImportBatch::findOrFail($batchId);
        $rows = ImportProductRow::where('import_batch_id', $batchId)->paginate(20);

        return view('admin.products.preview', compact('batch', 'rows'));
    }

    public function confirmImport($batchId) {
        $batch = ImportBatch::findOrFail($batchId);

        if($batch->status !== 'ready') {
            return redirect()->back()->with('error', 'Trạng thái file không hợp lệ để import.');
        }

        ProcessImportBatchJob::dispatch($batch->id);

        return redirect()->route('products.index')
            ->with('success', 'Hệ thống tiến hành đưa sản phẩm vào kho.');
    }
}
