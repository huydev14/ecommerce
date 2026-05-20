<?php

namespace App\Observers;

use App\Models\Tax;
use App\Services\AuditLogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class TaxObserver
{
    public function created(Tax $tax): void
    {
        AuditLogService::log(
            "Tạo mới thuế: $tax->name (ID: $tax->id)",
            $tax,
            'tax'
        );
    }

    public function updated(Tax $tax): void
    {
        $newData = $tax->getChanges();
        $oldData = Arr::only($tax->getOriginal(), array_keys($newData));

        AuditLogService::log(
            "Cập nhật thuế: $tax->name (ID: $tax->id)",
            $tax,
            'tax',
            Auth::user(),
            [
                'old' => $oldData,
                'attributes' => $newData,
            ]
        );
    }

    public function deleted(Tax $tax): void
    {
        AuditLogService::log(
            "Xóa thuế: $tax->name (ID: $tax->id)",
            $tax,
            'tax'
        );
    }

    public function restored(Tax $tax): void
    {
        AuditLogService::log(
            "Khôi phục thuế: $tax->name (ID: $tax->id)",
            $tax,
            'tax',
            Auth::user(),
        );
    }

    public function forceDeleted(Tax $tax): void
    {
        AuditLogService::log(
            "Xóa vĩnh viễn thuế: $tax->name (ID: $tax->id)",
            $tax,
            'tax',
            Auth::user(),
        );
    }
}
