<?php

namespace App\Observers;

use App\Models\Setting;
use App\Services\AuditLogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class SettingObserver
{
    public function created(Setting $setting): void
    {
        AuditLogService::log("Tạo mới cấu hình: $setting->key (ID: $setting->id)", $setting, 'setting');
    }

    public function updated(Setting $setting): void
    {
        $newData = $setting->getChanges();
        $oldData = Arr::only($setting->getOriginal(), array_keys($newData));

        AuditLogService::log("Cập nhật cấu hình: $setting->key (ID: $setting->id)", $setting, 'setting', Auth::user(), [
            'old' => $oldData,
            'attributes' => $newData,
        ]);
    }

    public function deleted(Setting $setting): void
    {
        AuditLogService::log("Xóa cấu hình: $setting->key (ID: $setting->id)", $setting, 'setting');
    }
}
