<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $crudPermissionModules = [
            'users',
            'roles',
            'banners',
            'brands',
            'categories',
            'products',
            'product-variants',
            'customer-addresses',
            'units',
            'taxes',
            'warehouses',
            'stocks',
            'stock-movements',
        ];

        $permissions = [
            'log.view',
            'log.detail',
            'log.remove',
            'orders.view',
            'orders.edit',
            'product-imports.view',
            'product-imports.create',
            'product-imports.remove',
            'settings.view',
            'settings.edit',
        ];

        foreach ($crudPermissionModules as $module) {
            foreach (['view', 'create', 'edit', 'remove'] as $action) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Create role
        $superAdmin =
            Role::firstOrCreate(
                [
                    'name' => 'Super Admin',
                    'guard_name' => 'web'
                ],
                [
                    'description' => 'Toàn quyền quản trị hệ thống. Kiểm soát cấu hình lõi, phân quyền và dữ liệu tổ chức.',
                ],
            );
        $allPermissions = Permission::all();
        $viewOnlyPermissions = Permission::query()
            ->where('name', 'like', '%.view')
            ->orWhereIn('name', ['log.detail'])
            ->get();

        $superAdmin->syncPermissions($allPermissions);

        $rolesData = [
            'Admin' => 'Quản lý tổng thể hoạt động kinh doanh. Có quyền điều hành và xem báo cáo toàn cục.',
            'Manager' => 'Quản lý nhân sự cấp dưới, phân công và xem thống kê hiệu suất.',
            'Employee' => 'Nhân viên được truy cập các tính năng nghiệp vụ cơ bản và xử lý công việc cá nhân.',
            'HR Demo' => 'Tài khoản demo dành cho HR. Chỉ có quyền xem dữ liệu, không được tạo, sửa hoặc xóa.',
        ];

        foreach ($rolesData as $role_name => $role_description) {
            Role::firstOrCreate(
                [
                    'name' => $role_name,
                    'guard_name' => 'web'
                ],
                [
                    'description' => $role_description,
                ]
            );
        }

        Role::findByName('Admin', 'web')->syncPermissions($allPermissions);
        Role::findByName('Manager', 'web')->syncPermissions($viewOnlyPermissions);
        Role::findByName('Employee', 'web')->syncPermissions($viewOnlyPermissions);
        Role::findByName('HR Demo', 'web')->syncPermissions($viewOnlyPermissions);

        // Assign role to users ID
        $assignRole = [
            1 => 'Super Admin',
            2 => 'Admin',
            3 => 'Manager',
            4 => 'Employee',
        ];

        foreach($assignRole as $userId => $roleName) {
            $user = User::find($userId);
            $user?->assignRole($roleName);
        }

        User::where('email', 'hr.demo@gmail.com')->first()?->syncRoles(['HR Demo']);
    }
}
