<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\AuditLogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class OrderObserver
{
    public function created(Order $order): void
    {
        AuditLogService::log("Tạo mới đơn hàng: $order->order_number (ID: $order->id)", $order, 'order');
    }

    public function updated(Order $order): void
    {
        $newData = $order->getChanges();
        $oldData = Arr::only($order->getOriginal(), array_keys($newData));

        AuditLogService::log("Cập nhật đơn hàng: $order->order_number (ID: $order->id)", $order, 'order', Auth::user(), [
            'old' => $oldData,
            'attributes' => $newData,
        ]);
    }

    public function deleted(Order $order): void
    {
        AuditLogService::log("Xóa đơn hàng: $order->order_number (ID: $order->id)", $order, 'order');
    }
}
