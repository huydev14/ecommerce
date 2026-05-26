<?php

namespace App\Observers;

use App\Models\Brand;
use App\Services\AuditLogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class BrandObserver
{
    /**
     * Handle the Brand "created" event.
     */
    public function created(Brand $brand): void
    {
        AuditLogService::log(
            "Tạo mới thương hiệu: $brand->name (ID: $brand->id)",
            $brand,
            'brand'
        );
    }

    /**
     * Handle the Brand "updated" event.
     */
    public function updated(Brand $brand): void
    {
        $newData = $brand->getChanges();
        $oldData = Arr::only($brand->getOriginal(), array_keys($newData));

        $properties = [
            'old' => $oldData,
            'attributes' => $newData,
        ];

        AuditLogService::log(
            "Cập nhật thương hiệu: $brand->name (ID: $brand->id)",
            $brand,
            'brand',
            Auth::user(),
            $properties
        );
    }

    /**
     * Handle the Brand "deleted" event.
     */
    public function deleted(Brand $brand): void
    {
        AuditLogService::log(
            "Xóa thương hiệu: $brand->name (ID: $brand->id)",
            $brand,
            'brand'
        );
    }

}
