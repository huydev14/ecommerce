<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\Warehouse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class StockController extends Controller
{
    public function index()
    {
        return view('stocks.index');
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $stocks = Stock::with(['productVariant.product', 'warehouse'])->select('stocks.*');

            if ($request->filled('warehouse_id')) {
                $stocks->where('stocks.warehouse_id', $request->warehouse_id);
            }

            if ($request->filled('product_variant_id')) {
                $stocks->where('stocks.product_variant_id', $request->product_variant_id);
            }

            return DataTables::of($stocks)
                ->addColumn('variant_name', function ($stock) {
                    $productName = $stock->productVariant?->product?->name;
                    $sku = $stock->productVariant?->sku;

                    return trim(($productName ? $productName . ' - ' : '') . ($sku ?: '---'));
                })
                ->addColumn('warehouse_name', function ($stock) {
                    return $stock->warehouse?->name ?? '<span class="tw-text-gray-400 tw-italic tw-text-sm">---</span>';
                })
                ->addColumn('available_quantity', function ($stock) {
                    return $stock->available_quantity;
                })
                ->editColumn('quantity', function ($stock) {
                    if ($stock->is_low_stock) {
                        return '<span class="tw-font-semibold tw-text-red-600">' . $stock->quantity . '</span>';
                    }

                    return $stock->quantity;
                })
                ->editColumn('updated_at', function ($stock) {
                    return $stock->updated_at ? $stock->updated_at->format('d/m/Y') : '';
                })
                ->editColumn('action', function ($stock) {
                    return view('stocks._stocks-action', compact('stock'))->render();
                })
                ->rawColumns(['warehouse_name', 'quantity', 'action'])
                ->make(true);
        }
    }

    public function getFilterData(Request $request)
    {
        $warehouses = Warehouse::where('is_active', true)->select('id', 'name as text')->orderBy('name')->get();
        $variants = ProductVariant::with('product')->orderBy('sku')->get()->map(function ($variant) {
            $productName = $variant->product?->name;

            return [
                'id' => $variant->id,
                'text' => trim(($productName ? $productName . ' - ' : '') . $variant->sku),
            ];
        });

        return response()->json([
            'warehouses' => $warehouses,
            'variants' => $variants,
        ]);
    }

    public function create()
    {
        if (! auth()->user()?->can('stocks.create')) {
            abort(403, 'Bạn không có quyền tạo tồn kho.');
        }

        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $variants = ProductVariant::with('product')->orderBy('sku')->get();

        return view('stocks.create', compact('warehouses', 'variants'));
    }

    public function store(Request $request)
    {
        if (! $request->user()?->can('stocks.create')) {
            abort(403, 'Bạn không có quyền tạo tồn kho.');
        }

        $request->validate([
            'product_variant_id' => [
                'required',
                'exists:product_variants,id',
                Rule::unique('stocks')->where(fn ($query) => $query
                    ->where('warehouse_id', $request->warehouse_id)),
            ],
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer|min:0',
            'reserved_quantity' => 'required|integer|min:0|lte:quantity',
            'low_stock_threshold' => 'required|integer|min:0',
        ], [
            'product_variant_id.required' => __('stock.variant_required'),
            'product_variant_id.unique' => __('stock.unique_variant_warehouse'),
            'warehouse_id.required' => __('stock.warehouse_required'),
            'reserved_quantity.lte' => __('stock.reserved_lte_quantity'),
        ]);

        try {
            Stock::create($request->only([
                'product_variant_id',
                'warehouse_id',
                'quantity',
                'reserved_quantity',
                'low_stock_threshold',
            ]));

            if ($request->ajax()) {
                session()->flash('success', __('stock.create_success'));

                return response()->json([
                    'success' => true,
                    'message' => __('stock.create_success'),
                ], 200);
            }

            return redirect()->route('stocks.index')->with('success', __('stock.create_success'));
        } catch (Exception $e) {
            Log::error('Create stock failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => __('stock.system_error'),
            ], 500);
        }
    }

    public function edit(Stock $stock)
    {
        if (! auth()->user()?->can('stocks.edit')) {
            abort(403, 'Bạn không có quyền mở form sửa tồn kho.');
        }

        $warehouses = Warehouse::where('is_active', true)->orWhere('id', $stock->warehouse_id)->orderBy('name')->get();
        $variants = ProductVariant::with('product')->orderBy('sku')->get();

        return view('stocks.edit', compact('stock', 'warehouses', 'variants'));
    }

    public function update(Request $request, Stock $stock)
    {
        if (! $request->user()?->can('stocks.update')) {
            abort(403, 'Bạn không có quyền cập nhật tồn kho.');
        }

        $request->validate([
            'product_variant_id' => [
                'required',
                'exists:product_variants,id',
                Rule::unique('stocks')->ignore($stock->id)->where(fn ($query) => $query
                    ->where('warehouse_id', $request->warehouse_id)),
            ],
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer|min:0',
            'reserved_quantity' => 'required|integer|min:0|lte:quantity',
            'low_stock_threshold' => 'required|integer|min:0',
        ], [
            'product_variant_id.required' => __('stock.variant_required'),
            'product_variant_id.unique' => __('stock.unique_variant_warehouse'),
            'warehouse_id.required' => __('stock.warehouse_required'),
            'reserved_quantity.lte' => __('stock.reserved_lte_quantity'),
        ]);

        try {
            DB::transaction(function () use ($request, $stock) {
                $oldQuantity = $stock->quantity;

                $stock->update($request->only([
                    'product_variant_id',
                    'warehouse_id',
                    'quantity',
                    'reserved_quantity',
                    'low_stock_threshold',
                ]));

                $quantityChanged = (int) $request->quantity - $oldQuantity;

                if ($quantityChanged !== 0) {
                    $stock->movements()->create([
                        'type' => 'adjustment',
                        'quantity_changed' => $quantityChanged,
                        'quantity_after' => (int) $request->quantity,
                        'note' => __('stock.adjustment_note'),
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => __('stock.update_success'),
            ], 200);
        } catch (Exception $e) {
            Log::error('Update stock failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('stock.system_error'),
            ], 500);
        }
    }

    public function destroy(Stock $stock)
    {
        if (! auth()->user()?->can('stocks.remove')) {
            abort(403, 'Bạn không có quyền xóa tồn kho.');
        }

        try {
            $stock->delete();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => __('stock.delete_success'),
            ]);
        } catch (Exception $e) {
            Log::error('Delete stock failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('stock.system_error'),
            ], 500);
        }
    }

    public function restore($id)
    {
        if (! auth()->user()?->can('stocks.remove')) {
            abort(403, 'Bạn không có quyền khôi phục tồn kho.');
        }

        try {
            $stock = Stock::withTrashed()->findOrFail($id);
            $stock->restore();

            return response()->json([
                'success' => true,
                'message' => __('stock.restore_success'),
            ]);
        } catch (Exception $e) {
            Log::error('Restore stock failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('stock.restore_error'),
            ], 500);
        }
    }
}
