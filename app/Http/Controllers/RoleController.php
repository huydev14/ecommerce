<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::withCount('users')->get();
        return view('roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('roles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:255',
            'permissions' => 'exists:permissions,name',
            'permissions.*' => 'exists:permissions,name',
        ], [
            'name.required' => 'Vui lòng nhập tên vai trò.',
            'name.unique' => 'Tên vai trò này đã tồn tại trong hệ thống.',
        ]);

        DB::beginTransaction();
        try {
            $role = Role::create([
                'name' => $request->name,
                'description' => $request->description,
                'guard_name' => 'web'
            ]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            DB::commit();

            session()->flash('success', __('Create role successfully'));

            return response()->json([
                'success' => true,
                'msg' => 'Tạo vai trò mới thành công',
                'redirect' => route('roles.index'),
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Create role failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'status' => 'error',
                'msg' => 'Lỗi hệ thống'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $role = Role::findOrFail($id);
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('roles.edit', compact('role', 'rolePermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'permissions' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $oldName = $role->name;
            $oldPermissions = $role->permissions->pluck('name')->toArray();

            $role->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            $newPermissions = $request->permissions ?? [];
            $role->syncPermissions($newPermissions);

            DB::commit();

            $changes = [];
            if ($oldName !== $role->name) {
                $changes['name'] = [
                    'old' => $oldName,
                    'new' => $role->name,
                ];
            }

            $addedPermissions = array_values(array_diff($newPermissions, $oldPermissions));
            if (!empty($addedPermissions)) {
                $changes['permissions_added'] = $addedPermissions;
            }

            $removedPermissions = array_values(array_diff($oldPermissions, $newPermissions));
            if (!empty($removedPermissions)) {
                $changes['permissions_removed'] = $removedPermissions;
            }

            AuditLogService::log("Cập nhật vai trò: {$role->name}", $role, 'role', Auth::user(), $changes);

            session()->flash('success', __('Update role successfully'));

            return response()->json([
                'success' => true,
                'msg' => 'Tạo vai trò mới thành công',
                'redirect' => route('roles.index'),
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update role failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'status' => 'error',
                'msg' => 'Lỗi hệ thống'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);

        DB::beginTransaction();

        try {
            $role->delete();
            DB::commit();

            session()->flash('success', __('Delete role successfully'));

            return response()->json([
                'status' => 'success',
                'msg' => 'Đã xóa vai trò thành công!'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete role failed:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'msg' => 'Không thể xóa vai trò này!'
            ], 500);
        }
    }
}
