<?php

namespace App\Observers;

use App\Models\OAuthAccount;
use App\Services\AuditLogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class OAuthAccountObserver
{
    public function created(OAuthAccount $account): void
    {
        AuditLogService::log("Tạo mới tài khoản OAuth: $account->provider (ID: $account->id)", $account, 'oauth_account');
    }

    public function updated(OAuthAccount $account): void
    {
        $newData = Arr::except($account->getChanges(), ['provider_user_id']);
        $oldData = Arr::except(Arr::only($account->getOriginal(), array_keys($account->getChanges())), ['provider_user_id']);

        AuditLogService::log("Cập nhật tài khoản OAuth: $account->provider (ID: $account->id)", $account, 'oauth_account', Auth::user(), [
            'old' => $oldData,
            'attributes' => $newData,
        ]);
    }

    public function deleted(OAuthAccount $account): void
    {
        AuditLogService::log("Xóa tài khoản OAuth: $account->provider (ID: $account->id)", $account, 'oauth_account');
    }
}
