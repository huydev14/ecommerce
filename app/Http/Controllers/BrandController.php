<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;

class BrandController extends Controller
{
    public function index()
    {
        return view('brands.index');
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $brands = Brand::query();

            return DataTables::of($brands)
                ->editColumn('logo', function ($brand) {
                    if ($brand->logo) {
                        $url = asset('storage/' . $brand->logo);
                        return '<img src="' . $url . '" alt="' . $brand->name . '" class="tw-h-8 tw-w-auto tw-object-contain tw-rounded">';
                    }
                    return '<span class="tw-text-gray-400 tw-italic tw-text-sm">' . __('brand.no_logo') . '</span>';
                })
                ->editColumn('is_active', function ($brand) {
                    if ($brand->is_active) {
                        return '<span class="tw-px-2 tw-py-1 tw-bg-green-100 tw-text-green-700 tw-text-xs tw-font-medium tw-rounded-full">' . __('brand.active') . '</span>';
                    }
                    return '<span class="tw-px-2 tw-py-1 tw-bg-gray-100 tw-text-gray-600 tw-text-xs tw-font-medium tw-rounded-full">' . __('brand.hidden') . '</span>';
                })

                ->editColumn('created_at', function ($brand) {
                    return $brand->created_at ? $brand->created_at->format('d/m/Y') : '';
                })
                ->editColumn('updated_at', function ($brand) {
                    return $brand->updated_at ? $brand->updated_at->format('d/m/Y') : '';
                })
                ->editColumn('action', function ($brand) {
                    return view('brands._brands-action', compact('brand'))->render();
                })
                ->rawColumns(['is_active', 'logo', 'action'])
                ->make(true);
        }
    }

    public function getFilterData(Request $request)
    {
        $isActive = collect([
            ['id' => 1, 'text' => __('brand.active')],
            ['id' => 0, 'text' => __('brand.inactive')],
        ]);
        $brandNames = Brand::select('id', 'name as text')->orderBy('name')->get();

        return response()->json([
            'isActive' => $isActive,
            'brandName' => $brandNames,
        ]);
    }

    public function create()
    {
        return view('brands.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:255|unique:brands,name',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'name.required' => __('brand.name_required'),
            'name.unique' => __('brand.name_unique'),
            'logo.max' => __('brand.logo_max'),
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('brands', 'public');
        }
        try {
            Brand::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'website' => $request->website,
                'logo' => $logoPath,
                'is_active' => $request->has('is_active'),
            ]);

            if ($request->ajax()) {
                session()->flash('success', __('brand.create_success'));

                return response()->json([
                    'success' => true,
                    'msg' => __('brand.create_success'),
                ], 200);
            }
            return redirect()->route('brands.index')->with('success', __('brand.create_success'));
        } catch (Exception $e) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }

            Log::error('Create brand failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'msg' => __('brand.system_error')
            ], 500);
        }
    }

    public function edit(Brand $brand)
    {
        return view('brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:255|unique:brands,name,' . $brand->id,
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'name.required' => __('brand.name_required'),
            'name.unique' => __('brand.name_unique'),
            'logo.max' => __('brand.logo_max'),
        ]);

        $oldLogoPath = $brand->logo;
        $newLogoPath = $oldLogoPath;

        if ($request->hasFile('logo')) {
            $newLogoPath = $request->file('logo')->store('brands', 'public');
        }

        try {
            $brand->update([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'website' => $request->website,
                'logo' => $newLogoPath,
                'is_active' => $request->has('is_active'),
            ]);

            if ($request->hasFile('logo') && $oldLogoPath) {
                Storage::disk('public')->delete($oldLogoPath);
            }

            return response()->json([
                'success' => true,
                'msg' => __('brand.update_success'),
            ], 200);
        } catch (Exception $e) {
            if ($request->hasFile('logo') && $newLogoPath && $newLogoPath !== $oldLogoPath) {
                Storage::disk('public')->delete($newLogoPath);
            }

            Log::error('Update brand failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('brand.messages.system_error'),
            ], 500);
        }
    }

    public function destroy(Brand $brand)
    {
        try {
            $brand->delete();

            return response()->json([
                'success' => true,
                'status' => 200,
                'msg' => __('brand.delete_success'),
            ]);
        } catch (Exception $e) {
            Log::error('Delete brand failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('brand.messages.system_error'),
            ], 500);
        }
    }

    public function restore($id)
    {
        try {
            $brand = Brand::withTrashed()->findOrFail($id);
            $brand->restore();

            return response()->json([
                'success' => true,
                'msg' => __('brand.restore_success'),
            ]);
        } catch (Exception $e) {
            Log::error('Restore brand failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('brand.restore_error'),
            ], 500);
        }
    }
}
