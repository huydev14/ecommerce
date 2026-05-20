<?php

namespace App\Http\Controllers;

use App\Models\Tax;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;

class TaxController extends Controller
{
    public function index()
    {
        return view('taxes.index');
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $taxes = Tax::query();

            if ($request->filled('tax_id')) {
                $taxes->where('taxes.id', $request->tax_id);
            }

            return DataTables::of($taxes)
                ->editColumn('rate', function ($tax) {
                    return number_format((float) $tax->rate, 2, ',', '.') . '%';
                })
                ->editColumn('created_at', function ($tax) {
                    return $tax->created_at ? $tax->created_at->format('d/m/Y') : '';
                })
                ->editColumn('updated_at', function ($tax) {
                    return $tax->updated_at ? $tax->updated_at->format('d/m/Y') : '';
                })
                ->editColumn('action', function ($tax) {
                    return view('taxes._taxes-action', compact('tax'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function getFilterData(Request $request)
    {
        $taxes = Tax::select('id', 'name as text')->orderBy('name')->get();

        return response()->json([
            'taxes' => $taxes,
        ]);
    }

    public function create()
    {
        return view('taxes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:255|unique:taxes,name',
            'rate' => 'required|numeric|min:0|max:100',
        ], [
            'name.required' => __('tax.name_required'),
            'name.unique' => __('tax.name_unique'),
            'rate.required' => __('tax.rate_required'),
            'rate.numeric' => __('tax.rate_numeric'),
            'rate.max' => __('tax.rate_max'),
        ]);

        try {
            Tax::create([
                'name' => $request->name,
                'rate' => $request->rate,
            ]);

            if ($request->ajax()) {
                session()->flash('success', __('tax.create_success'));

                return response()->json([
                    'success' => true,
                    'msg' => __('tax.create_success'),
                ], 200);
            }

            return redirect()->route('taxes.index')->with('success', __('tax.create_success'));
        } catch (Exception $e) {
            Log::error('Create tax failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'msg' => __('tax.system_error'),
            ], 500);
        }
    }

    public function edit(Tax $tax)
    {
        return view('taxes.edit', compact('tax'));
    }

    public function update(Request $request, Tax $tax)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:255|unique:taxes,name,' . $tax->id,
            'rate' => 'required|numeric|min:0|max:100',
        ], [
            'name.required' => __('tax.name_required'),
            'name.unique' => __('tax.name_unique'),
            'rate.required' => __('tax.rate_required'),
            'rate.numeric' => __('tax.rate_numeric'),
            'rate.max' => __('tax.rate_max'),
        ]);

        try {
            $tax->update([
                'name' => $request->name,
                'rate' => $request->rate,
            ]);

            return response()->json([
                'success' => true,
                'msg' => __('tax.update_success'),
            ], 200);
        } catch (Exception $e) {
            Log::error('Update tax failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('tax.system_error'),
            ], 500);
        }
    }

    public function destroy(Tax $tax)
    {
        try {
            $tax->delete();

            return response()->json([
                'success' => true,
                'status' => 200,
                'msg' => __('tax.delete_success'),
            ]);
        } catch (Exception $e) {
            Log::error('Delete tax failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('tax.system_error'),
            ], 500);
        }
    }

    public function restore($id)
    {
        try {
            $tax = Tax::withTrashed()->findOrFail($id);
            $tax->restore();

            return response()->json([
                'success' => true,
                'msg' => __('tax.restore_success'),
            ]);
        } catch (Exception $e) {
            Log::error('Restore tax failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('tax.restore_error'),
            ], 500);
        }
    }
}
