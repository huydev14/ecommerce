<?php

namespace App\Observers;

use App\Models\CustomerAddress;
use App\Services\AuditLogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class CustomerAddressObserver
{
    public function created(CustomerAddress $address): void
    {
        AuditLogService::log("Tạo mới địa chỉ khách hàng: $address->receiver_name (ID: $address->id)", $address, 'customer_address');
    }

    public function updated(CustomerAddress $address): void
    {
        $newData = $address->getChanges();
        $oldData = Arr::only($address->getOriginal(), array_keys($newData));

        AuditLogService::log("Cập nhật địa chỉ khách hàng: $address->receiver_name (ID: $address->id)", $address, 'customer_address', Auth::user(), [
            'old' => $oldData,
            'attributes' => $newData,
        ]);
    }

    public function deleted(CustomerAddress $address): void
    {
        AuditLogService::log("Xóa địa chỉ khách hàng: $address->receiver_name (ID: $address->id)", $address, 'customer_address');
    }
}
