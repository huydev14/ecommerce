<?php

namespace App\Observers;

use App\Services\AuditLogService;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class RoleObserver
{
    public function created(Role $role): void
    {
        AuditLogService::log("Tạo mới vai trò: {$role->name}", $role, 'role', Auth::user());
    }

    public function deleted(Role $role): void
    {
        AuditLogService::log("Đã xóa vai trò: {$role->name}", $role, 'role', Auth::user());
    }
}
