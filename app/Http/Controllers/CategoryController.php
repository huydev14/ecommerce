<?php

namespace App\Http\Controllers;

use App\Models\Category;
use DragonCode\Support\Facades\Helpers\Str;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;

class CategoryController extends Controller
{
    public function index()
    {
        return view('categories.index');
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $categories = Category::with('parent')->select('categories.*');

            if ($request->filled('category_id')) {
                $categories->where('categories.id', $request->category_id);
            }

            if ($request->filled('is_active')) {
                $categories->where('categories.is_active', $request->is_active);
            }

            return DataTables::of($categories)
                ->addColumn('parent_name', function ($category) {
                    return $category->parent?->name ?? '<span class="tw-text-gray-400 tw-italic tw-text-sm">---</span>';
                })
                ->editColumn('is_active', function ($category) {
                    if ($category->is_active) {
                        return '<span class="tw-px-2 tw-py-1 tw-bg-green-100 tw-text-green-700 tw-text-xs tw-font-medium tw-rounded-full">' . __('category.active') . '</span>';
                    }
                    return '<span class="tw-px-2 tw-py-1 tw-bg-gray-100 tw-text-gray-600 tw-text-xs tw-font-medium tw-rounded-full">' . __('category.hidden') . '</span>';
                })
                ->editColumn('created_at', function ($category) {
                    return $category->created_at ? $category->created_at->format('d/m/Y') : '';
                })
                ->editColumn('updated_at', function ($category) {
                    return $category->updated_at ? $category->updated_at->format('d/m/Y') : '';
                })
                ->editColumn('action', function ($category) {
                    return view('categories._categories-action', compact('category'))->render();
                })
                ->rawColumns(['is_active', 'parent_name', 'action'])
                ->make(true);
        }
    }

    public function getFilterData(Request $request)
    {
        $isActive = collect([
            ['id' => 1, 'text' => __('category.active')],
            ['id' => 0, 'text' => __('category.inactive')],
        ]);

        $categoryNames = Category::select('id', 'name as text')->orderBy('name')->get();

        return response()->json([
            'isActive' => $isActive,
            'categoryName' => $categoryNames,
        ]);
    }

    public function create()
    {
        $parents = Category::orderBy('name')->get();

        return view('categories.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:255|unique:categories,name',
            'description' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:categories,id',
            'order' => 'nullable|integer|min:0',
        ], [
            'name.required' => __('category.name_required'),
            'name.unique' => __('category.name_unique'),
            'description.required' => __('category.description_required'),
        ]);

        try {
            Category::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'description' => $request->description,
                'parent_id' => $request->parent_id,
                'order' => $request->filled('order') ? $request->order : 0,
                'is_active' => $request->has('is_active'),
            ]);

            if ($request->ajax()) {
                session()->flash('success', __('category.create_success'));

                return response()->json([
                    'success' => true,
                    'msg' => __('category.create_success'),
                ], 200);
            }

            return redirect()->route('categories.index')->with('success', __('category.create_success'));
        } catch (Exception $e) {
            Log::error('Create category failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'msg' => __('category.messages.system_error'),
            ], 500);
        }
    }

    public function edit(Category $category)
    {
        $parents = Category::where('id', '!=', $category->id)->orderBy('name')->get();

        return view('categories.edit', compact('category', 'parents'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:255|unique:categories,name,' . $category->id,
            'description' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:categories,id|not_in:' . $category->id,
            'order' => 'nullable|integer|min:0',
        ], [
            'name.required' => __('category.name_required'),
            'name.unique' => __('category.name_unique'),
            'description.required' => __('category.description_required'),
            'parent_id.not_in' => __('category.parent_not_self'),
        ]);

        try {
            $category->update([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'description' => $request->description,
                'parent_id' => $request->parent_id,
                'order' => $request->filled('order') ? $request->order : 0,
                'is_active' => $request->has('is_active'),
            ]);

            return response()->json([
                'success' => true,
                'msg' => __('category.update_success'),
            ], 200);
        } catch (Exception $e) {
            Log::error('Update category failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('category.system_error'),
            ], 500);
        }
    }

    public function destroy(Category $category)
    {
        try {
            $category->delete();

            return response()->json([
                'success' => true,
                'status' => 200,
                'msg' => __('category.delete_success'),
            ]);
        } catch (Exception $e) {
            Log::error('Delete category failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('category.system_error'),
            ], 500);
        }
    }

    public function restore($id)
    {
        try {
            $category = Category::withTrashed()->findOrFail($id);
            $category->restore();

            return response()->json([
                'success' => true,
                'msg' => __('category.restore_success'),
            ]);
        } catch (Exception $e) {
            Log::error('Restore category failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('category.restore_error'),
            ], 500);
        }
    }
}
