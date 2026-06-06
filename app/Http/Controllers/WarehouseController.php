<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;

class WarehouseController extends Controller
{
    public function index()
    {
        return view('warehouses.index');
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $warehouses = Warehouse::query();

            if ($request->filled('warehouse_id')) {
                $warehouses->where('warehouses.id', $request->warehouse_id);
            }

            if ($request->filled('is_active')) {
                $warehouses->where('warehouses.is_active', $request->is_active);
            }

            return DataTables::of($warehouses)
                ->editColumn('address', function ($warehouse) {
                    return $warehouse->address ?: '<span class="tw-text-gray-400 tw-italic tw-text-sm">---</span>';
                })
                ->editColumn('is_active', function ($warehouse) {
                    return $this->renderStatusBadge($warehouse->is_active);
                })
                ->editColumn('updated_at', function ($warehouse) {
                    return $warehouse->updated_at ? $warehouse->updated_at->format('d/m/Y') : '';
                })
                ->editColumn('action', function ($warehouse) {
                    return view('warehouses._warehouses-action', compact('warehouse'))->render();
                })
                ->rawColumns(['address', 'is_active', 'action'])
                ->make(true);
        }
    }

    private function renderStatusBadge(bool $isActive): string
    {
        if ($isActive) {
            return '<span class="tw-px-1 tw-py-0.5 tw-bg-green-100 tw-text-green-700 tw-text-xs tw-font-medium tw-rounded-sm">' . __('warehouse.active') . '</span>';
        }

        return '<span class="tw-px-2 tw-py-1 tw-bg-gray-100 tw-text-gray-600 tw-text-xs tw-font-medium tw-rounded-full">' . __('warehouse.inactive') . '</span>';
    }

    public function getFilterData(Request $request)
    {
        $warehouses = Warehouse::select('id', 'name as text')->orderBy('name')->get();
        $status = collect([
            ['id' => 1, 'text' => __('warehouse.active')],
            ['id' => 0, 'text' => __('warehouse.inactive')],
        ]);

        return response()->json([
            'warehouses' => $warehouses,
            'status' => $status,
        ]);
    }

    public function create()
    {
        if (!auth()->user()?->can('warehouses.create')) {
            abort(403, 'Bạn không có quyền tạo kho.');
        }

        return view('warehouses.create');
    }

    public function store(Request $request)
    {
        if (!$request->user()?->can('warehouses.create')) {
            abort(403, 'Bạn không có quyền tạo kho.');
        }

        $request->validate([
            'name' => 'required|string|min:2|max:255|unique:warehouses,name',
            'code' => 'required|string|min:2|max:50|unique:warehouses,code',
            'address' => 'nullable|string|max:500',
        ], [
            'name.required' => __('warehouse.name_required'),
            'name.unique' => __('warehouse.name_unique'),
            'code.required' => __('warehouse.code_required'),
            'code.unique' => __('warehouse.code_unique'),
        ]);

        try {
            Warehouse::create([
                'name' => $request->name,
                'code' => $request->code,
                'address' => $request->address,
                'is_active' => $request->has('is_active'),
            ]);

            if ($request->ajax()) {
                session()->flash('success', __('warehouse.create_success'));

                return response()->json([
                    'success' => true,
                    'message' => __('warehouse.create_success'),
                ], 200);
            }

            return redirect()->route('warehouses.index')->with('success', __('warehouse.create_success'));
        } catch (Exception $e) {
            Log::error('Create warehouse failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => __('warehouse.system_error'),
            ], 500);
        }
    }

    public function edit(Warehouse $warehouse)
    {
        if (!auth()->user()?->can('warehouses.edit')) {
            abort(403, 'Bạn không có quyền mở form sửa kho.');
        }

        return view('warehouses.edit', compact('warehouse'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        if (!$request->user()?->can('warehouses.update')) {
            abort(403, 'Bạn không có quyền cập nhật kho.');
        }

        $request->validate([
            'name' => 'required|string|min:2|max:255|unique:warehouses,name,' . $warehouse->id,
            'code' => 'required|string|min:2|max:50|unique:warehouses,code,' . $warehouse->id,
            'address' => 'nullable|string|max:500',
        ], [
            'name.required' => __('warehouse.name_required'),
            'name.unique' => __('warehouse.name_unique'),
            'code.required' => __('warehouse.code_required'),
            'code.unique' => __('warehouse.code_unique'),
        ]);

        try {
            $warehouse->update([
                'name' => $request->name,
                'code' => $request->code,
                'address' => $request->address,
                'is_active' => $request->has('is_active'),
            ]);

            return response()->json([
                'success' => true,
                'message' => __('warehouse.update_success'),
            ], 200);
        } catch (Exception $e) {
            Log::error('Update warehouse failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('warehouse.system_error'),
            ], 500);
        }
    }

    public function destroy(Warehouse $warehouse)
    {
        if (!auth()->user()?->can('warehouses.remove')) {
            abort(403, 'Bạn không có quyền xóa kho.');
        }

        try {
            $warehouse->delete();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => __('warehouse.delete_success'),
            ]);
        } catch (Exception $e) {
            Log::error('Delete warehouse failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('warehouse.system_error'),
            ], 500);
        }
    }

    public function restore($id)
    {
        if (!auth()->user()?->can('warehouses.remove')) {
            abort(403, 'Bạn không có quyền khôi phục kho.');
        }

        try {
            $warehouse = Warehouse::withTrashed()->findOrFail($id);
            $warehouse->restore();

            return response()->json([
                'success' => true,
                'message' => __('warehouse.restore_success'),
            ]);
        } catch (Exception $e) {
            Log::error('Restore warehouse failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('warehouse.restore_error'),
            ], 500);
        }
    }
}
