<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockMovement;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\DataTables;

class StockMovementController extends Controller
{
    public function index()
    {
        return view('stock-movements.index');
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $movements = StockMovement::with(['stock.productVariant.product', 'stock.warehouse'])->select('stock_movements.*');

            if ($request->filled('stock_id')) {
                $movements->where('stock_movements.stock_id', $request->stock_id);
            }

            if ($request->filled('type')) {
                $movements->where('stock_movements.type', $request->type);
            }

            return DataTables::of($movements)
                ->addColumn('stock_name', function ($movement) {
                    return $this->stockText($movement->stock);
                })
                ->editColumn('type', function ($movement) {
                    return __('stock_movement.types.' . $movement->type);
                })
                ->editColumn('quantity_changed', function ($movement) {
                    $class = $movement->quantity_changed < 0 ? 'tw-text-red-600' : 'tw-text-green-700';

                    return '<span class="tw-font-semibold ' . $class . '">' . ($movement->quantity_changed > 0 ? '+' : '') . $movement->quantity_changed . '</span>';
                })
                ->editColumn('note', function ($movement) {
                    return $movement->note ?: '<span class="tw-text-gray-400 tw-italic tw-text-sm">---</span>';
                })
                ->editColumn('created_at', function ($movement) {
                    return $movement->created_at ? $movement->created_at->format('d/m/Y H:i') : '';
                })
                ->editColumn('action', function ($movement) {
                    return view('stock-movements._stock-movements-action', compact('movement'))->render();
                })
                ->rawColumns(['stock_name', 'quantity_changed', 'note', 'action'])
                ->make(true);
        }
    }

    private function stockText(?Stock $stock): string
    {
        if (! $stock) {
            return '<span class="tw-text-gray-400 tw-italic tw-text-sm">---</span>';
        }

        $productName = $stock->productVariant?->product?->name;
        $sku = $stock->productVariant?->sku;
        $warehouse = $stock->warehouse?->name;

        return trim(($productName ? $productName . ' - ' : '') . ($sku ?: '---') . ($warehouse ? ' / ' . $warehouse : ''));
    }

    public function getFilterData(Request $request)
    {
        $stocks = Stock::with(['productVariant.product', 'warehouse'])->orderBy('id')->get()->map(function ($stock) {
            return [
                'id' => $stock->id,
                'text' => $this->stockText($stock),
            ];
        });

        $types = collect([
            ['id' => 'in', 'text' => __('stock_movement.types.in')],
            ['id' => 'out', 'text' => __('stock_movement.types.out')],
            ['id' => 'adjustment', 'text' => __('stock_movement.types.adjustment')],
        ]);

        return response()->json([
            'stocks' => $stocks,
            'types' => $types,
        ]);
    }

    public function create()
    {
        if (! auth()->user()?->can('stock-movements.create')) {
            abort(403, 'Bạn không có quyền tạo biến động kho.');
        }

        $stocks = Stock::with(['productVariant.product', 'warehouse'])->orderBy('id')->get();

        return view('stock-movements.create', compact('stocks'));
    }

    public function store(Request $request)
    {
        if (! $request->user()?->can('stock-movements.store')) {
            abort(403, 'Bạn không có quyền tạo biến động kho.');
        }

        $request->validate([
            'stock_id' => 'required|exists:stocks,id',
            'type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|integer|min:0',
            'note' => 'nullable|string|max:500',
        ], [
            'stock_id.required' => __('stock_movement.stock_required'),
            'type.required' => __('stock_movement.type_required'),
            'quantity.required' => __('stock_movement.quantity_required'),
        ]);

        try {
            if (in_array($request->type, ['in', 'out'], true) && (int) $request->quantity < 1) {
                throw ValidationException::withMessages([
                    'quantity' => __('stock_movement.quantity_positive_required'),
                ]);
            }

            DB::transaction(function () use ($request) {
                $stock = Stock::whereKey($request->stock_id)->lockForUpdate()->firstOrFail();
                $quantity = (int) $request->quantity;
                $quantityChanged = $request->type === 'in' ? $quantity : -$quantity;

                if ($request->type === 'adjustment') {
                    $quantityChanged = $quantity - $stock->quantity;
                }

                $stock->recordMovement($request->type, $quantityChanged, $request->note);
            });

            if ($request->ajax()) {
                session()->flash('success', __('stock_movement.create_success'));

                return response()->json([
                    'success' => true,
                    'message' => __('stock_movement.create_success'),
                ], 200);
            }

            return redirect()->route('stock-movements.index')->with('success', __('stock_movement.create_success'));
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Create stock movement failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => __('stock_movement.system_error'),
            ], 500);
        }
    }

    public function destroy(StockMovement $stock_movement)
    {
        if (! auth()->user()?->can('stock-movements.remove')) {
            abort(403, 'Bạn không có quyền xóa biến động kho.');
        }

        try {
            $stock_movement->delete();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => __('stock_movement.delete_success'),
            ]);
        } catch (Exception $e) {
            Log::error('Delete stock movement failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('stock_movement.system_error'),
            ], 500);
        }
    }

    public function restore($id)
    {
        if (! auth()->user()?->can('stock-movements.remove')) {
            abort(403, 'Bạn không có quyền khôi phục biến động kho.');
        }

        try {
            $movement = StockMovement::withTrashed()->findOrFail($id);
            $movement->restore();

            return response()->json([
                'success' => true,
                'message' => __('stock_movement.restore_success'),
            ]);
        } catch (Exception $e) {
            Log::error('Restore stock movement failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('stock_movement.restore_error'),
            ], 500);
        }
    }
}
