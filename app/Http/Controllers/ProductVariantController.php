<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;

class ProductVariantController extends Controller
{
    public function index()
    {
        return view('product-variants.index');
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $variants = ProductVariant::with('product')->select('product_variants.*');
            $this->applyFilters($variants, $request);

            return DataTables::of($variants)
                ->addColumn('product_name', function ($variant) {
                    return $variant->product?->name ?? '<span class="tw-text-gray-400 tw-italic tw-text-sm">---</span>';
                })
                ->addColumn('variant_name', function ($variant) {
                    return $variant->name ?? '<span class="tw-text-gray-400 tw-italic tw-text-sm">---</span>';
                })
                ->editColumn('price', function ($variant) {
                    return $this->formatVnd($variant->price);
                })
                ->editColumn('compare_at_price', function ($variant) {
                    return $this->formatNullableVnd($variant->compare_at_price);
                })
                ->editColumn('cost_price', function ($variant) {
                    return $this->formatNullableVnd($variant->cost_price);
                })
                ->editColumn('is_active', function ($variant) {
                    return $this->renderStatusBadge($variant->is_active);
                })
                ->editColumn('updated_at', function ($variant) {
                    return $variant->updated_at ? $variant->updated_at->format('d/m/Y') : '';
                })
                ->editColumn('action', function ($variant) {
                    return view('product-variants._product-variants-action', compact('variant'))->render();
                })
                ->rawColumns(['product_name', 'variant_name', 'compare_at_price', 'cost_price', 'is_active', 'action'])
                ->make(true);
        }
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('product_id')) {
            $query->where('product_variants.product_id', $request->product_id);
        }

        if ($request->filled('is_active')) {
            $query->where('product_variants.is_active', $request->is_active);
        }
    }

    private function formatVnd($value): string
    {
        return number_format((float) $value, 0, ',', '.') . ' VND';
    }

    private function formatNullableVnd($value): string
    {
        if ($value === null) {
            return '<span class="tw-text-gray-400 tw-italic tw-text-sm">---</span>';
        }

        return $this->formatVnd($value);
    }

    private function renderStatusBadge(bool $isActive): string
    {
        if ($isActive) {
            return '<span class="tw-inline-flex tw-items-center tw-gap-1.5 tw-rounded tw-bg-emerald-50 tw-px-2 tw-text-xs tw-font-medium tw-text-emerald-700 tw-capitalize"><i class="fas fa-circle-check"></i>' . strtolower(__('product_variant.active')) . '</span>';
        }

        return '<span class="tw-inline-flex tw-items-center tw-gap-1.5 tw-rounded tw-bg-gray-100 tw-px-2 tw-py-1 tw-text-xs tw-font-semibold tw-text-gray-600"><i class="fas fa-circle-xmark"></i>' . strtolower(__('product_variant.hidden')) . '</span>';
    }

    public function getFilterData(Request $request)
    {
        $status = collect([
            ['id' => 1, 'text' => __('product_variant.active')],
            ['id' => 0, 'text' => __('product_variant.inactive')],
        ]);

        $products = Product::select('id', 'name as text')->orderBy('name')->get();
        $variants = ProductVariant::select('id', 'sku as text')->orderBy('sku')->get();

        return response()->json([
            'status' => $status,
            'products' => $products,
            'variants' => $variants,
        ]);
    }

    public function create()
    {
        if (!auth()->user()?->can('product-variants.create')) {
            abort(403, 'Bạn không có quyền tạo biến thể sản phẩm.');
        }

        $products = Product::orderBy('name')->get();

        return view('product-variants.create', compact('products'));
    }

    public function store(Request $request)
    {
        if (!$request->user()?->can('product-variants.store')) {
            abort(403, 'Bạn không có quyền tạo biến thể sản phẩm.');
        }

        $attributesJson = $request->input('attributes');

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'sku' => 'required|string|max:100|unique:product_variants,sku',
            'name' => 'nullable|string|max:150',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'attributes' => 'nullable|json',
        ], [
            'product_id.required' => __('product_variant.product_required'),
            'sku.required' => __('product_variant.sku_required'),
            'sku.unique' => __('product_variant.sku_unique'),
            'price.required' => __('product_variant.price_required'),
            'attributes.json' => __('product_variant.attributes_json'),
        ]);

        try {
            ProductVariant::create([
                'product_id' => $request->product_id,
                'sku' => $request->sku,
                'name' => $request->name,
                'price' => $request->price,
                'compare_at_price' => $request->compare_at_price,
                'cost_price' => $request->cost_price,
                'attributes' => $attributesJson ? json_decode($attributesJson, true) : null,
                'is_active' => $request->has('is_active'),
            ]);

            if ($request->ajax()) {
                session()->flash('success', __('product_variant.create_success'));

                return response()->json([
                    'success' => true,
                    'message' => __('product_variant.create_success'),
                ], 200);
            }

            return redirect()->route('product-variants.index')->with('success', __('product_variant.create_success'));
        } catch (Exception $e) {
            Log::error('Create product variant failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => __('product_variant.system_error'),
            ], 500);
        }
    }

    public function edit(ProductVariant $product_variant)
    {
        if (!auth()->user()?->can('product-variants.edit')) {
            abort(403, 'Bạn không có quyền mở form sửa biến thể sản phẩm.');
        }

        $products = Product::orderBy('name')->get();

        return view('product-variants.edit', [
            'variant' => $product_variant,
            'products' => $products,
        ]);
    }

    public function update(Request $request, ProductVariant $product_variant)
    {
        if (!$request->user()?->can('product-variants.update')) {
            abort(403, 'Bạn không có quyền cập nhật biến thể sản phẩm.');
        }

        $attributesJson = $request->input('attributes');

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'sku' => 'required|string|max:100|unique:product_variants,sku,' . $product_variant->id,
            'name' => 'nullable|string|max:150',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'attributes' => 'nullable|json',
        ], [
            'product_id.required' => __('product_variant.product_required'),
            'sku.required' => __('product_variant.sku_required'),
            'sku.unique' => __('product_variant.sku_unique'),
            'price.required' => __('product_variant.price_required'),
            'attributes.json' => __('product_variant.attributes_json'),
        ]);

        try {
            $product_variant->update([
                'product_id' => $request->product_id,
                'sku' => $request->sku,
                'name' => $request->name,
                'price' => $request->price,
                'compare_at_price' => $request->compare_at_price,
                'cost_price' => $request->cost_price,
                'attributes' => $attributesJson ? json_decode($attributesJson, true) : null,
                'is_active' => $request->has('is_active'),
            ]);

            return response()->json([
                'success' => true,
                'message' => __('product_variant.update_success'),
            ], 200);
        } catch (Exception $e) {
            Log::error('Update product variant failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('product_variant.system_error'),
            ], 500);
        }
    }

    public function destroy(ProductVariant $product_variant)
    {
        if (!auth()->user()?->can('product-variants.remove')) {
            abort(403, 'Bạn không có quyền xóa biến thể sản phẩm.');
        }

        try {
            $product_variant->delete();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => __('product_variant.delete_success'),
            ]);
        } catch (Exception $e) {
            Log::error('Delete product variant failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('product_variant.system_error'),
            ], 500);
        }
    }

    public function restore($id)
    {
        if (!auth()->user()?->can('product-variants.remove')) {
            abort(403, 'Bạn không có quyền khôi phục biến thể sản phẩm.');
        }

        try {
            $variant = ProductVariant::withTrashed()->findOrFail($id);
            $variant->restore();

            return response()->json([
                'success' => true,
                'message' => __('product_variant.restore_success'),
            ]);
        } catch (Exception $e) {
            Log::error('Restore product variant failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('product_variant.restore_error'),
            ], 500);
        }
    }
}
