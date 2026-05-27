<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;

class ProductController extends Controller
{
    public function index()
    {
        return view('products.index');
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $products = Product::with(['category', 'brand'])->select('products.*');

            if ($request->filled('product_id')) {
                $products->where('products.id', $request->product_id);
            }

            if ($request->filled('category_id')) {
                $products->where('products.category_id', $request->category_id);
            }

            if ($request->filled('brand_id')) {
                $products->where('products.brand_id', $request->brand_id);
            }

            if ($request->filled('status')) {
                $products->where('products.status', $request->status);
            }

            if ($request->filled('is_featured')) {
                $products->where('products.is_featured', $request->boolean('is_featured'));
            }

            return DataTables::of($products)
                ->addColumn('category_name', function ($product) {
                    return $product->category?->name ?? '<span class="tw-text-gray-400 tw-italic tw-text-sm">---</span>';
                })
                ->addColumn('brand_name', function ($product) {
                    return $product->brand?->name ?? '<span class="tw-text-gray-400 tw-italic tw-text-sm">---</span>';
                })
                ->editColumn('status', function ($product) {
                    return match ($product->status) {
                        'published' => '<span class="tw-px-2 tw-py-1 tw-bg-green-100 tw-text-green-700 tw-text-xs tw-font-medium tw-rounded-full">' . __('product.published') . '</span>',
                        'archived' => '<span class="tw-px-2 tw-py-1 tw-bg-gray-100 tw-text-gray-600 tw-text-xs tw-font-medium tw-rounded-full">' . __('product.archived') . '</span>',
                        default => '<span class="tw-px-2 tw-py-1 tw-bg-yellow-100 tw-text-yellow-700 tw-text-xs tw-font-medium tw-rounded-full">' . __('product.draft') . '</span>',
                    };
                })
                ->editColumn('is_featured', function ($product) {
                    return $product->is_featured
                        ? '<span class="tw-px-2 tw-py-1 tw-bg-blue-100 tw-text-blue-700 tw-text-xs tw-font-medium tw-rounded-full">' . __('product.featured') . '</span>'
                        : '<span class="tw-px-2 tw-py-1 tw-bg-gray-100 tw-text-gray-600 tw-text-xs tw-font-medium tw-rounded-full">' . __('product.not_featured') . '</span>';
                })
                ->editColumn('created_at', function ($product) {
                    return $product->created_at ? $product->created_at->format('d/m/Y') : '';
                })
                ->editColumn('updated_at', function ($product) {
                    return $product->updated_at ? $product->updated_at->format('d/m/Y') : '';
                })
                ->editColumn('action', function ($product) {
                    return view('products._products-action', compact('product'))->render();
                })
                ->rawColumns(['category_name', 'brand_name', 'status', 'is_featured', 'action'])
                ->make(true);
        }
    }

    public function getFilterData(Request $request)
    {
        $status = collect([
            ['id' => 'draft', 'text' => __('product.draft')],
            ['id' => 'published', 'text' => __('product.published')],
            ['id' => 'archived', 'text' => __('product.archived')],
        ]);

        $featuredStatuses = collect([
            ['id' => 1, 'text' => __('product.featured')],
            ['id' => 0, 'text' => __('product.not_featured')],
        ]);

        $products = Product::select('id', 'name as text')->orderBy('name')->get();
        $categories = Category::select('id', 'name as text')->orderBy('name')->get();
        $brands = Brand::select('id', 'name as text')->orderBy('name')->get();

        return response()->json([
            'status' => $status,
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'featured_statuses' => $featuredStatuses,
        ]);
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();

        return view('products.create', compact('categories', 'brands'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:255|unique:products,name',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'status' => 'required|in:draft,published,archived',
            'is_featured' => 'nullable|boolean',
        ], [
            'name.required' => __('product.name_required'),
            'name.unique' => __('product.name_unique'),
            'category_id.required' => __('product.category_required'),
            'status.required' => __('product.status_required'),
        ]);

        $path = null;
        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('products/thumbnails', 'public');
        }

        $data['slug'] = Str::slug($request->name);
        $data['thumbnail'] = $path;
        $data['is_featured'] = $request->boolean('is_featured');

        try {
            Product::create($data);
            $this->clearProductApiCaches();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'msg' => __('product.create_success'),
                ], 200);
            }

            return redirect()->route('products.index')->with('success', __('product.create_success'));

        } catch (\Exception $e) {
            Log::error('Create product failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'msg' => __('product.system_error'),
                ], 500);
            }

            return back()->withInput()->with('error', __('product.system_error'));
        }
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();

        return view('products.edit', compact('product', 'categories', 'brands'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:255|unique:products,name,' . $product->id,
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'status' => 'required|in:draft,published,archived',
            'is_featured' => 'nullable|boolean',
        ], [
            'name.required' => __('product.name_required'),
            'name.unique' => __('product.name_unique'),
            'category_id.required' => __('product.category_required'),
            'status.required' => __('product.status_required'),
        ]);

        $path = $product->thumbnail;

        if ($request->hasFile('thumbnail')) {
            if ($product->thumbnail && Storage::disk('public')->exists($product->thumbnail)) {
                Storage::disk('public')->delete($product->thumbnail);
            }
            $path = $request->file('thumbnail')->store('products/thumbnails', 'public');
        }

        $data['slug'] = Str::slug($request->name);
        $data['thumbnail'] = $path;
        $data['is_featured'] = $request->boolean('is_featured');

        try {
            $product->update($data);
            $this->clearProductApiCaches();

            return response()->json([
                'success' => true,
                'msg' => __('product.update_success'),
            ], 200);
        } catch (Exception $e) {
            Log::error('Update product failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('product.system_error'),
            ], 500);
        }
    }

    public function destroy(Product $product)
    {
        try {
            $product->delete();
            $this->clearProductApiCaches();

            return response()->json([
                'success' => true,
                'status' => 200,
                'msg' => __('product.delete_success'),
            ]);
        } catch (Exception $e) {
            Log::error('Delete product failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('product.system_error'),
            ], 500);
        }
    }

    public function restore($id)
    {
        try {
            $product = Product::withTrashed()->findOrFail($id);
            $product->restore();
            $this->clearProductApiCaches();

            return response()->json([
                'success' => true,
                'msg' => __('product.restore_success'),
            ]);
        } catch (Exception $e) {
            Log::error('Restore product failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('product.restore_error'),
            ], 500);
        }
    }

    private function clearProductApiCaches(): void
    {
        Cache::forget('api_new_arrivals');
        Cache::forget('featured_products');
    }
}
