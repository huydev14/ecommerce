<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;

class UnitController extends Controller
{
    public function index()
    {
        return view('units.index');
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $units = Unit::query();

            if ($request->filled('unit_id')) {
                $units->where('units.id', $request->unit_id);
            }

            return DataTables::of($units)
                ->editColumn('short_name', function ($unit) {
                    return $unit->short_name ?: '<span class="tw-text-gray-400 tw-italic tw-text-sm">---</span>';
                })
                ->editColumn('created_at', function ($unit) {
                    return $unit->created_at ? $unit->created_at->format('d/m/Y') : '';
                })
                ->editColumn('updated_at', function ($unit) {
                    return $unit->updated_at ? $unit->updated_at->format('d/m/Y') : '';
                })
                ->editColumn('action', function ($unit) {
                    return view('units._units-action', compact('unit'))->render();
                })
                ->rawColumns(['short_name', 'action'])
                ->make(true);
        }
    }

    public function getFilterData(Request $request)
    {
        $units = Unit::select('id', 'name as text')->orderBy('name')->get();

        return response()->json([
            'units' => $units,
        ]);
    }

    public function create()
    {
        return view('units.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:255|unique:units,name',
            'short_name' => 'nullable|string|max:50',
        ], [
            'name.required' => __('unit.name_required'),
            'name.unique' => __('unit.name_unique'),
            'short_name.max' => __('unit.short_name_max'),
        ]);

        try {
            Unit::create([
                'name' => $request->name,
                'short_name' => $request->short_name,
            ]);

            if ($request->ajax()) {
                session()->flash('success', __('unit.create_success'));

                return response()->json([
                    'success' => true,
                    'msg' => __('unit.create_success'),
                ], 200);
            }

            return redirect()->route('units.index')->with('success', __('unit.create_success'));
        } catch (Exception $e) {
            Log::error('Create unit failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'msg' => __('unit.system_error'),
            ], 500);
        }
    }

    public function edit(Unit $unit)
    {
        return view('units.edit', compact('unit'));
    }

    public function update(Request $request, Unit $unit)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:255|unique:units,name,' . $unit->id,
            'short_name' => 'nullable|string|max:50',
        ], [
            'name.required' => __('unit.name_required'),
            'name.unique' => __('unit.name_unique'),
            'short_name.max' => __('unit.short_name_max'),
        ]);

        try {
            $unit->update([
                'name' => $request->name,
                'short_name' => $request->short_name,
            ]);

            return response()->json([
                'success' => true,
                'msg' => __('unit.update_success'),
            ], 200);
        } catch (Exception $e) {
            Log::error('Update unit failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('unit.system_error'),
            ], 500);
        }
    }

    public function destroy(Unit $unit)
    {
        try {
            $unit->delete();

            return response()->json([
                'success' => true,
                'status' => 200,
                'msg' => __('unit.delete_success'),
            ]);
        } catch (Exception $e) {
            Log::error('Delete unit failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('unit.system_error'),
            ], 500);
        }
    }

    public function restore($id)
    {
        try {
            $unit = Unit::withTrashed()->findOrFail($id);
            $unit->restore();

            return response()->json([
                'success' => true,
                'msg' => __('unit.restore_success'),
            ]);
        } catch (Exception $e) {
            Log::error('Restore unit failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('unit.restore_error'),
            ], 500);
        }
    }
}
