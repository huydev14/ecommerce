<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
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
