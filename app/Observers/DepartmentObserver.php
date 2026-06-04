<?php

namespace App\Observers;

use App\Models\Department;
use App\Services\AuditLogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class DepartmentObserver
{
    public function created(Department $department): void
    {
        AuditLogService::log("Tạo mới phòng ban: $department->name (ID: $department->id)", $department, 'department');
    }

    public function updated(Department $department): void
    {
        $newData = $department->getChanges();
        $oldData = Arr::only($department->getOriginal(), array_keys($newData));

        AuditLogService::log("Cập nhật phòng ban: $department->name (ID: $department->id)", $department, 'department', Auth::user(), [
            'old' => $oldData,
            'attributes' => $newData,
        ]);
    }

    public function deleted(Department $department): void
    {
        AuditLogService::log("Xóa phòng ban: $department->name (ID: $department->id)", $department, 'department');
    }
}
