<?php

namespace App\Observers;

use App\Models\Cart;
use App\Services\AuditLogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class CartObserver
{
    public function created(Cart $cart): void
    {
        AuditLogService::log("Tạo mới giỏ hàng (ID: $cart->id)", $cart, 'cart');
    }

    public function updated(Cart $cart): void
    {
        $newData = $cart->getChanges();
        $oldData = Arr::only($cart->getOriginal(), array_keys($newData));

        AuditLogService::log("Cập nhật giỏ hàng (ID: $cart->id)", $cart, 'cart', Auth::user(), [
            'old' => $oldData,
            'attributes' => $newData,
        ]);
    }

    public function deleted(Cart $cart): void
    {
        AuditLogService::log("Xóa giỏ hàng (ID: $cart->id)", $cart, 'cart');
    }
}
