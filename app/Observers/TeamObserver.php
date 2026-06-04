<?php

namespace App\Observers;

use App\Models\Team;
use App\Services\AuditLogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class TeamObserver
{
    public function created(Team $team): void
    {
        AuditLogService::log("Tạo mới nhóm: $team->name (ID: $team->id)", $team, 'team');
    }

    public function updated(Team $team): void
    {
        $newData = $team->getChanges();
        $oldData = Arr::only($team->getOriginal(), array_keys($newData));

        AuditLogService::log("Cập nhật nhóm: $team->name (ID: $team->id)", $team, 'team', Auth::user(), [
            'old' => $oldData,
            'attributes' => $newData,
        ]);
    }

    public function deleted(Team $team): void
    {
        AuditLogService::log("Xóa nhóm: $team->name (ID: $team->id)", $team, 'team');
    }
}
