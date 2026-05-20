<?php

namespace App\Observers;

use App\Models\Unit;
use App\Services\AuditLogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class UnitObserver
{
    public function created(Unit $unit): void
    {
        AuditLogService::log(
            "Tạo mới đơn vị: $unit->name (ID: $unit->id)",
            $unit,
            'unit'
        );
    }

    public function updated(Unit $unit): void
    {
        $newData = $unit->getChanges();
        $oldData = Arr::only($unit->getOriginal(), array_keys($newData));

        AuditLogService::log(
            "Cập nhật đơn vị: $unit->name (ID: $unit->id)",
            $unit,
            'unit',
            Auth::user(),
            [
                'old' => $oldData,
                'attributes' => $newData,
            ]
        );
    }

    public function deleted(Unit $unit): void
    {
        AuditLogService::log(
            "Xóa đơn vị: $unit->name (ID: $unit->id)",
            $unit,
            'unit'
        );
    }

    public function restored(Unit $unit): void
    {
        AuditLogService::log(
            "Khôi phục đơn vị: $unit->name (ID: $unit->id)",
            $unit,
            'unit',
            Auth::user(),
        );
    }

    public function forceDeleted(Unit $unit): void
    {
        AuditLogService::log(
            "Xóa vĩnh viễn đơn vị: $unit->name (ID: $unit->id)",
            $unit,
            'unit',
            Auth::user(),
        );
    }
}
