<?php

namespace App\Observers;

use App\Models\Banner;
use App\Services\AuditLogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class BannerObserver
{
    public function created(Banner $banner): void
    {
        AuditLogService::log("Tạo mới banner: $banner->title (ID: $banner->id)", $banner, 'banner');
    }

    public function updated(Banner $banner): void
    {
        $newData = $banner->getChanges();
        $oldData = Arr::only($banner->getOriginal(), array_keys($newData));

        AuditLogService::log("Cập nhật banner: $banner->title (ID: $banner->id)", $banner, 'banner', Auth::user(), [
            'old' => $oldData,
            'attributes' => $newData,
        ]);
    }

    public function deleted(Banner $banner): void
    {
        AuditLogService::log("Xóa banner: $banner->title (ID: $banner->id)", $banner, 'banner');
    }
}
