<?php

namespace App\Observers;

use App\Models\Stock;
use App\Services\AuditLogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class StockObserver
{
    public function created(Stock $stock): void
    {
        AuditLogService::log("Tạo mới tồn kho (ID: $stock->id)", $stock, 'stock');
    }

    public function updated(Stock $stock): void
    {
        $newData = $stock->getChanges();
        $oldData = Arr::only($stock->getOriginal(), array_keys($newData));

        AuditLogService::log("Cập nhật tồn kho (ID: $stock->id)", $stock, 'stock', Auth::user(), [
            'old' => $oldData,
            'attributes' => $newData,
        ]);
    }

    public function deleted(Stock $stock): void
    {
        AuditLogService::log("Xóa tồn kho (ID: $stock->id)", $stock, 'stock');
    }

    public function restored(Stock $stock): void
    {
        AuditLogService::log("Khôi phục tồn kho (ID: $stock->id)", $stock, 'stock', Auth::user());
    }

    public function forceDeleted(Stock $stock): void
    {
        AuditLogService::log("Xóa vĩnh viễn tồn kho (ID: $stock->id)", $stock, 'stock', Auth::user());
    }
}
