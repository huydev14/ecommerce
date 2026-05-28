<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;
use Exception;
use Illuminate\Support\Facades\Blade;

class BannerController extends Controller
{
    private const SYSTEM_ERROR_MESSAGE = 'Đã có lỗi hệ thống xảy ra!';
    private const CLOUD_FOLDER = 'banners';

    public function index()
    {
        return view('banners.index');
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $banners = Banner::query()->orderBy('sort_order')->orderByDesc('id');

            return DataTables::of($banners)
                ->editColumn('image_url', function ($banner) {
                    if (!$banner->image_public_id) {
                        return '<span class="tw-text-gray-400 tw-italic tw-text-sm">Không có ảnh</span>';
                    }
                    return Blade::render(
                        '<x-cloudinary::image :public-id="$publicId" class="tw-h-16 tw-w-28 tw-rounded-md tw-object-cover tw-border tw-border-gray-200" />',
                        ['publicId' => $banner->image_public_id]
                    );
                })
                ->editColumn('is_active', function ($banner) {
                    return $banner->is_active
                        ? '<span class="tw-px-2 tw-py-1 tw-bg-green-100 tw-text-green-700 tw-text-xs tw-font-medium tw-rounded-full">Active</span>'
                        : '<span class="tw-px-2 tw-py-1 tw-bg-gray-100 tw-text-gray-600 tw-text-xs tw-font-medium tw-rounded-full">Hidden</span>';
                })
                ->editColumn('created_at', fn($banner) => $banner->created_at?->format('d/m/Y'))
                ->editColumn('updated_at', fn($banner) => $banner->updated_at?->format('d/m/Y'))
                ->editColumn('action', fn($banner) => view('banners._banners-action', compact('banner'))->render())
                ->rawColumns(['image_url', 'is_active', 'action'])
                ->make(true);
        }
    }

    public function create()
    {
        return view('banners.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'image_url' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'link' => 'nullable|url|max:255',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $imageUrl = null;
        $imagePath = null;

        try {
            if ($request->hasFile('image_url')) {
                $imagePath = $request->file('image_url')->store(self::CLOUD_FOLDER, 'cloudinary');
                $imageUrl = Storage::disk('cloudinary')->url($imagePath);
            }

            Banner::create([
                'title' => $validated['title'] ?? null,
                'image_url' => $imageUrl,
                'image_public_id' => $imagePath,
                'link' => $validated['link'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_active' => $request->boolean('is_active'),
            ]);

            Cache::forget('homepage_data');

            return response()->json(['success' => true, 'msg' => 'Đã tạo banner thành công!'], 200);

        } catch (Exception $e) {
            if ($imagePath) {
                Storage::disk('cloudinary')->delete($imagePath);
            }

            Log::error('Create banner failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'msg' => self::SYSTEM_ERROR_MESSAGE], 500);
        }
    }

    public function edit(Banner $banner)
    {
        return view('banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'link' => 'nullable|url|max:255',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $imageUrl = $banner->image_url;
        $imagePath = $banner->image_public_id;
        $oldImagePath = null;

        try {
            if ($request->hasFile('image_url')) {
                $newImagePath = $request->file('image_url')->store(self::CLOUD_FOLDER, 'cloudinary');
                $imageUrl = Storage::disk('cloudinary')->url($newImagePath);

                $oldImagePath = $banner->image_public_id;
                $imagePath = $newImagePath;
            }

            $banner->update([
                'title' => $validated['title'] ?? null,
                'image_url' => $imageUrl,
                'image_public_id' => $imagePath,
                'link' => $validated['link'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_active' => $request->boolean('is_active'),
            ]);

            if ($oldImagePath) {
                Storage::disk('cloudinary')->delete($oldImagePath);
            }

            Cache::forget('homepage_data');

            return response()->json(['success' => true, 'msg' => 'Đã cập nhật banner thành công!'], 200);

        } catch (Exception $e) {
            if ($request->hasFile('image_url') && $imagePath && $imagePath !== $banner->getOriginal('image_public_id')) {
                Storage::disk('cloudinary')->delete($imagePath);
            }

            Log::error('Update banner failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'msg' => self::SYSTEM_ERROR_MESSAGE], 500);
        }
    }

    public function destroy(Banner $banner)
    {
        try {
            if ($banner->image_public_id) {
                Storage::disk('cloudinary')->delete($banner->image_public_id);
            }

            $banner->delete();
            Cache::forget('homepage_data');

            return response()->json(['success' => true, 'status' => 200, 'msg' => 'Đã xóa banner.']);

        } catch (Exception $e) {
            Log::error('Delete banner failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'msg' => self::SYSTEM_ERROR_MESSAGE], 500);
        }
    }
}