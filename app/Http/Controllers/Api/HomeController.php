<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        $data = Cache::remember('homepage_data', 3600, function () {
            return [
                'banners' => Banner::where('is_active', true)
                    ->orderBy('sort_order', 'asc')
                    ->get(['id', 'image_url', 'link']),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
