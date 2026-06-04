<?php

namespace App\Observers;

use App\Models\Warehouse;
use App\Services\AuditLogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class WarehouseObserver
{
    public function created(Warehouse $warehouse): void
    {
        AuditLogService::log("Tạo mới kho: $warehouse->name (ID: $warehouse->id)", $warehouse, 'warehouse');
    }

    public function updated(Warehouse $warehouse): void
    {
        $newData = $warehouse->getChanges();
        $oldData = Arr::only($warehouse->getOriginal(), array_keys($newData));

        AuditLogService::log("Cập nhật kho: $warehouse->name (ID: $warehouse->id)", $warehouse, 'warehouse', Auth::user(), [
            'old' => $oldData,
            'attributes' => $newData,
        ]);
    }

    public function deleted(Warehouse $warehouse): void
    {
        AuditLogService::log("Xóa kho: $warehouse->name (ID: $warehouse->id)", $warehouse, 'warehouse');
    }

    public function restored(Warehouse $warehouse): void
    {
        AuditLogService::log("Khôi phục kho: $warehouse->name (ID: $warehouse->id)", $warehouse, 'warehouse', Auth::user());
    }

    public function forceDeleted(Warehouse $warehouse): void
    {
        AuditLogService::log("Xóa vĩnh viễn kho: $warehouse->name (ID: $warehouse->id)", $warehouse, 'warehouse', Auth::user());
    }
}
