<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        $products = Product::with(['cheapestVariant', 'brand'])
            ->where('status', 'published')
            ->when(!empty($categoryIds), fn($q) => $q->whereIn('category_id', $categoryIds))

            ->when($request->input('brand', $request->input('brands')), function ($q, $brands) {
                $brandSlugs = is_array($brands) ? $brands : explode(',', str_replace(' ', '', $brands));
                $q->whereHas('brand', fn($q) => $q->whereIn('slug', $brandSlugs));
            })
            ->latest()
            ->paginate(24);

        $availableBrands = Brand::where('is_active', true)
            ->when(!empty($categoryIds), function ($q) use ($categoryIds) {
                $q->whereHas('products', fn($p) => $p->whereIn('category_id', $categoryIds));
            })
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
    public function newArrivals(Request $request)
    {
        $products = Cache::remember('api_new_arrivals', now()->addMinutes(30), function () {
            return Product::query()
                ->where('status', 'published')
                ->with(['cheapestVariant'])
                ->orderBy('created_at', 'desc')
                ->take(12)
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
        ]);
    }
}
