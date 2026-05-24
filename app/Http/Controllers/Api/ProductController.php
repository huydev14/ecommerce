<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->where('status', 'published');

        if ($request->has('category')) {
            $categorySlug = $request->category;

            $category = Category::with('children')->where('slug', $categorySlug)->first();
            if ($category) {
                $categoryIds = $category->getAllChildIds();
                $query->whereIn('category_id', $categoryIds);
            }
        }

        $products = $query->with(['cheapestVariant'])
            ->orderBy('created_at', 'desc')
            ->paginate(24);
        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
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
