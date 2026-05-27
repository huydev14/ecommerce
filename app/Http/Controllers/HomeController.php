<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class HomeController extends Controller
{
    private const SYSTEM_ERROR_MESSAGE = 'Đã có lỗi hệ thống xảy ra!';

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
                    $imageUrl = $this->resolveBannerImageUrl($banner->image_url);

                    if (!$imageUrl) {
                        return '<span class="tw-text-gray-400 tw-italic tw-text-sm">Không có ảnh</span>';
                    }

                    return '<img src="' . e($imageUrl) . '" alt="' . e($banner->title ?? 'Banner') . '" class="tw-h-16 tw-w-28 tw-rounded-md tw-object-cover tw-border tw-border-gray-200">';
                })
                ->editColumn('is_active', function ($banner) {
                    if ($banner->is_active) {
                        return '<span class="tw-px-2 tw-py-1 tw-bg-green-100 tw-text-green-700 tw-text-xs tw-font-medium tw-rounded-full">Active</span>';
                    }

                    return '<span class="tw-px-2 tw-py-1 tw-bg-gray-100 tw-text-gray-600 tw-text-xs tw-font-medium tw-rounded-full">Hidden</span>';
                })
                ->editColumn('created_at', function ($banner) {
                    return $banner->created_at ? $banner->created_at->format('d/m/Y') : '';
                })
                ->editColumn('updated_at', function ($banner) {
                    return $banner->updated_at ? $banner->updated_at->format('d/m/Y') : '';
                })
                ->editColumn('action', function ($banner) {
                    return view('banners._banners-action', compact('banner'))->render();
                })
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

        $imagePath = $request->file('image_url')->store('banners', 'public');
        $response = null;

        try {
            Banner::create([
                'title' => $validated['title'] ?? null,
                'image_url' => $imagePath,
                'link' => $validated['link'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_active' => $request->boolean('is_active'),
            ]);

            Cache::forget('homepage_data');
            $response = $this->bannerResponse($request, true, 'Đã tạo banner thành công!', 200, 'banners.index');
        } catch (Exception $e) {
            $this->deleteStoredImage($imagePath);

            Log::error('Create banner failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $response = $this->bannerResponse($request, false, self::SYSTEM_ERROR_MESSAGE, 500);
        }

        return $response;
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

        $imagePath = $banner->image_url;

        if ($request->hasFile('image_url')) {
            $imagePath = $request->file('image_url')->store('banners', 'public');
        }

        try {
            $banner->update([
                'title' => $validated['title'] ?? null,
                'image_url' => $imagePath,
                'link' => $validated['link'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_active' => $request->boolean('is_active'),
            ]);

            if ($request->hasFile('image_url') && $banner->getOriginal('image_url')) {
                $this->deleteStoredImage($banner->getOriginal('image_url'));
            }

            Cache::forget('homepage_data');

            return response()->json([
                'success' => true,
                'msg' => 'Đã cập nhật banner thành công!',
            ], 200);
        } catch (Exception $e) {
            if ($request->hasFile('image_url') && $imagePath !== $banner->getOriginal('image_url')) {
                $this->deleteStoredImage($imagePath);
            }

            Log::error('Update banner failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => self::SYSTEM_ERROR_MESSAGE,
            ], 500);
        }
    }

    public function destroy(Banner $banner)
    {
        try {
            $this->deleteStoredImage($banner->image_url);
            $banner->delete();

            Cache::forget('homepage_data');

            return response()->json([
                'success' => true,
                'status' => 200,
                'msg' => 'Đã xóa banner.',
            ]);
        } catch (Exception $e) {
            Log::error('Delete banner failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => self::SYSTEM_ERROR_MESSAGE,
            ], 500);
        }
    }

    private function bannerResponse(Request $request, bool $success, string $message, int $status = 200, ?string $redirectRoute = null)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => $success,
                'msg' => $message,
            ], $status);
        }

        if ($success && $redirectRoute) {
            return redirect()->route($redirectRoute)->with('success', $message);
        }

        return back()->withInput()->with('error', $message);
    }

    private function deleteStoredImage(?string $imagePath): void
    {
        if (!$imagePath) {
            return;
        }

        if (Str::startsWith($imagePath, ['http://', 'https://', '/'])) {
            return;
        }

        Storage::disk('public')->delete($imagePath);
    }

    private function resolveBannerImageUrl(?string $imagePath): ?string
    {
        if (!$imagePath) {
            return null;
        }

        if (Str::startsWith($imagePath, ['http://', 'https://', '/'])) {
            return $imagePath;
        }

        return asset('storage/' . $imagePath);
    }
}
