<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
   public function index(){
        $categories = Category::query()
        ->whereNull('parent_id')
        ->where('is_active', true)
        ->with('children')
        ->orderBy('sort_order')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
   }
}
