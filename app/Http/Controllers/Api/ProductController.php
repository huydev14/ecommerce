<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $categoryIds = [];
        if ($request->category && $category = Category::where('slug', $request->category)->first()) {
            $categoryIds = $category->getAllChildIds();
        }

        $keyword = $request->input('keyword');

        $products = Product::query()
            ->select('products.*')
            ->withTotalSoldPastMonth()
            ->with(['cheapestVariant', 'brand'])
            ->where('status', 'published')

            // Filter by categories
            ->when(!empty($categoryIds), fn($q) => $q->whereIn('category_id', $categoryIds))

            // Filter by keyword
            ->when($keyword, function ($query, $keyword){
                $query->where(function ($q) use ($keyword){
                    $q->where('name' , 'LIKE', $keyword . '%');
                });
            })

            // Filter by brands
            ->when($request->input('brand', $request->input('brands')), function ($q, $brands) {
                $brandSlugs = is_array($brands) ? $brands : explode(',', str_replace(' ', '', $brands));
                $q->whereHas('brand', fn($q) => $q->whereIn('slug', $brandSlugs));
            })
            ->latest()
            ->paginate(24);

        $brandLimit = $request->integer('brandLimit', 10);

        $availableBrands = Brand::where('is_active', true)
            ->when(!empty($categoryIds), function ($q) use ($categoryIds) {
                $q->whereHas('products', fn($p) => $p->whereIn('category_id', $categoryIds));
            })
            ->limit($brandLimit)
            ->get(['name', 'slug']);

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
                'filters' => [
                    'brands' => $availableBrands,
                ],
            ]
        ]);
    }

    public function bestSellers(Request $request)
    {
        $limit = $request->get('limit', 10);

        $products = Cache::remember('products_best_sellers_' . $limit, now()->addHour(), function () use ($limit) {
            return Product::query()
                ->select('products.*')
                ->selectRaw('SUM(order_items.quantity) as total_sold')
                ->join('order_items', 'products.id', '=', 'order_items.product_id')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.status', 'completed')
                ->where('products.status', 'published')
                ->groupBy('products.id')
                ->orderByDesc('total_sold')
                ->limit($limit)
                ->with(['brand', 'category', 'cheapestVariant'])
                ->get();
        });

        return ProductResource::collection($products)
            ->additional([
                'success' => true,
                'message' => 'Get best sellers products successfully.'
            ]);
    }

    public function newArrivals(Request $request)
    {
        $products = Cache::remember('api_new_arrivals', now()->addMinutes(30), function () {
            return Product::query()
                ->select('products.*')
                ->withTotalSoldPastMonth()
                ->where('status', 'published')
                ->with(['cheapestVariant', 'brand'])
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
        ]);
    }

    public function featuredProducts()
    {
        $cacheKey = 'featured_products';

        $products = Cache::remember($cacheKey, 3600, function () {
            return Product::query()
                ->select('products.*')
                ->withTotalSoldPastMonth()
                ->where('status', 'published')
                ->where('is_featured', true)
                ->with([
                    'brand:id,name,slug',
                    'category:id,name,slug',
                    'cheapestVariant'
                ])
                ->latest()
                ->take(8)
                ->get()
                ->map(function ($product) {
                    $variant = $product->cheapestVariant;
                    $compareAtPrice = $variant?->compare_at_price;
                    $price = $variant?->price ?? 0;
                    $displayCompareAtPrice = null;

                    if ($compareAtPrice !== null) {
                        $displayCompareAtPrice = (float) $compareAtPrice;
                    } elseif ($price > 0) {
                        $displayCompareAtPrice = round($price / 0.9, 2);
                    }

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'thumbnail' => $product->thumbnail,
                        'brand' => $product->brand,
                        'category' => $product->category,
                        'price' => $price,
                        'compare_at_price' => $displayCompareAtPrice,
                        'product_variant_id' => $variant?->id,
                        'total_sold' => (int) ($product->total_sold ?? 0),
                    ];
                });
        });

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách sản phẩm nổi bật thành công.',
            'data' => $products
        ], 200);
    }

    public function show(string $slug)
    {
        $product = Product::query()
            ->select('products.*')
            ->withTotalSoldPastMonth()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->with(['brand', 'category', 'variants'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new ProductDetailResource($product),
        ]);
    }
}
