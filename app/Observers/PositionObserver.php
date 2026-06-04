<?php

namespace App\Observers;

use App\Models\Position;
use App\Services\AuditLogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class PositionObserver
{
    public function created(Position $position): void
    {
        AuditLogService::log("Tạo mới chức vụ: $position->name (ID: $position->id)", $position, 'position');
    }

    public function updated(Position $position): void
    {
        $newData = $position->getChanges();
        $oldData = Arr::only($position->getOriginal(), array_keys($newData));

        AuditLogService::log("Cập nhật chức vụ: $position->name (ID: $position->id)", $position, 'position', Auth::user(), [
            'old' => $oldData,
            'attributes' => $newData,
        ]);
    }

    public function deleted(Position $position): void
    {
        AuditLogService::log("Xóa chức vụ: $position->name (ID: $position->id)", $position, 'position');
    }
}
