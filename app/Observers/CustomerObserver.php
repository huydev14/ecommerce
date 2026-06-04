<?php

namespace App\Observers;

use App\Models\Customer;
use App\Services\AuditLogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class CustomerObserver
{
    public function created(Customer $customer): void
    {
        AuditLogService::log("Tạo mới khách hàng: $customer->name (ID: $customer->id)", $customer, 'customer');
    }

    public function updated(Customer $customer): void
    {
        $newData = Arr::except($customer->getChanges(), ['password', 'remember_token', 'provider_user_id']);
        $oldData = Arr::except(Arr::only($customer->getOriginal(), array_keys($customer->getChanges())), ['password', 'remember_token', 'provider_user_id']);

        AuditLogService::log("Cập nhật khách hàng: $customer->name (ID: $customer->id)", $customer, 'customer', Auth::user(), [
            'old' => $oldData,
            'attributes' => $newData,
        ]);
    }

    public function deleted(Customer $customer): void
    {
        AuditLogService::log("Xóa khách hàng: $customer->name (ID: $customer->id)", $customer, 'customer');
    }

    public function restored(Customer $customer): void
    {
        AuditLogService::log("Khôi phục khách hàng: $customer->name (ID: $customer->id)", $customer, 'customer', Auth::user());
    }

    public function forceDeleted(Customer $customer): void
    {
        AuditLogService::log("Xóa vĩnh viễn khách hàng: $customer->name (ID: $customer->id)", $customer, 'customer', Auth::user());
    }
}
