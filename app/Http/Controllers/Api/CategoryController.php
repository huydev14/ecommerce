<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function tree()
    {
        $categories = Cache::rememberForever('api_category_tree', function () {
            return Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->with([
                    'children' => function ($query) {
                        $query->where('is_active', true)->orderBy('sort_order');
                    },
                    'children.children' => function ($query) {
                        $query->where('is_active', true)->orderBy('sort_order');
                    }
                ])
                ->orderBy('sort_order')
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
        ]);
    }
}
