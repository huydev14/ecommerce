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

        // Create permissions
        $permissions = [
            'users.view',
            'users.create',
            'users.edit',
            'users.remove',
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.remove',
            'log.view',
            'log.detail'
        ];

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
        $superAdmin->syncPermissions(Permission::all());

        $rolesData = [
            'Admin' => 'Quản lý tổng thể hoạt động kinh doanh. Có quyền điều hành và xem báo cáo toàn cục.',
            'Manager' => 'Quản lý nhân sự cấp dưới, phân công và xem thống kê hiệu suất.',
            'Employee' => 'Nhân viên được truy cập các tính năng nghiệp vụ cơ bản và xử lý công việc cá nhân.',
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

        // Assign role to users ID
        $assignRole = [
            1 => 'Super Admin',
            2 => 'Admin',
            3 => 'Manager',
            4 => 'Employee',
        ];

        foreach($assignRole as $userId => $roleName) {
            $user = User::find($userId);
            $user->assignRole($roleName);
        }
    }
}
