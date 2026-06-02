<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public static function log($description, $model = null, $logName = 'audit', ?Model $causer = null, $properties = []) {

        $causer = $causer ?? Auth::user();

        $extra_properties = $properties ?? [];
        $default_properties = [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url'        => request()->fullUrl(),
            'method'     => request()->method(),
            'is_ajax'    => request()->ajax(),
            'session_id' => session()->getId(),
        ];

        $final_properties = array_merge($default_properties, $extra_properties);

        $activity = activity($logName);

        if ($causer) {
            $activity->causedBy($causer);
        }

        if ($model) {
            $activity->performedOn($model);
        }

        $activity->withProperties($final_properties)
            ->log($description);
    }
}
