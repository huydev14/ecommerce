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
use Illuminate\Support\Facades\Redis;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $categoryIds = [];
        if ($request->category && $category = Category::where('slug', $request->category)->first()) {
            $categoryIds = $category->getAllChildIds();
        }

        $keyword = $request->input('keyword');

        // Increase keyword score
        if ($keyword) {
            $normalized_keyword = mb_strtolower(trim($keyword));

            if (mb_strlen($normalized_keyword) >= 3) {
                Redis::zIncrBy('trending_keywords', 1, $normalized_keyword);
            }
        }

        // Get brands checkboxes
        $brandLimit = $request->integer('brandLimit', 10);

        $brandIdsInResult = $this->buildBaseQuery($categoryIds, $keyword)
            ->distinct()
            ->pluck('brand_id')
            ->filter();

        $availableBrands = Brand::whereIn('id', $brandIdsInResult)
            ->where('is_active', true)
            ->limit($brandLimit)
            ->get(['name', 'slug']);

        // Get products with filters
        $products = $this->buildBaseQuery($categoryIds, $keyword)
            ->withTotalSoldPastMonth()
            ->with(['cheapestVariant', 'brand'])
            ->when($request->input('brand', $request->input('brands')), function ($q, $brands) {
                $brandSlugs = is_array($brands) ? $brands : explode(',', str_replace(' ', '', $brands));
                $q->whereHas('brand', fn($q) => $q->whereIn('slug', $brandSlugs));
            })
            ->when($request->filled('min_price'), function ($q) use ($request) {
                $q->whereHas('variants', fn($q) => $q->where('price', '>=', $request->min_price));
            })
            ->when($request->filled('max_price'), function ($q) use ($request) {
                $q->whereHas('variants', fn($q) => $q->where('price', '<=', $request->max_price));
            })
            ->when($request->boolean('is_featured'), function ($q) {
                $q->where('is_featured', true);
            })
            ->when($request->boolean('is_new'), function ($q) {
                $q->orderByDesc('products.created_at');
            })
            ->when($request->boolean('is_best_seller'), function ($q) {
                $q->where(function($query) {
                    $query->selectRaw('COALESCE(SUM(quantity), 0)')
                          ->from('order_items')
                          ->join('orders', 'orders.id', '=', 'order_items.order_id')
                          ->whereColumn('order_items.product_id', 'products.id')
                          ->where('orders.status', 'completed');
                }, '>', 0)->orderByDesc('total_sold');
            })
            ->latest()
            ->paginate(20);

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

    private function buildBaseQuery($categoryIds, $keyword)
    {
        return Product::query()
            ->where('status', 'published')
            ->when(!empty($categoryIds), fn($q) => $q->whereIn('category_id', $categoryIds))
            ->when($keyword, function ($query, $keyword) {
                $query->where('name', 'LIKE', $keyword . '%');
            });
    }

    public function getTrendingKeywords()
    {
        $trending_keywords = Redis::zRevRange('trending_keywords', 0, 8);

        return response()->json([
            'success' => true,
            'data' => $trending_keywords
        ]);
    }

    public function bestSellers(Request $request)
    {
        $limit = $request->get('limit', 10);

        $products = Cache::remember('products_best_sellers_' . $limit, now()->addHour(), function () use ($limit) {
            return Product::query()
                ->withTotalSoldPastMonth()
                ->where('products.status', 'published')
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
            ->with(['brand', 'category', 'variants.unit'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new ProductDetailResource($product),
        ]);
    }
}
