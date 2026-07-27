<?php

namespace App\Observers;

use App\Models\ProductVariant;
use App\Services\AuditLogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class ProductVariantObserver
{
    /**
     * Handle the ProductVariant "created" event.
     */
    public function created(ProductVariant $variant): void
    {
        AuditLogService::log(
            "Tạo mới biến thể: $variant->sku (ID: $variant->id)",
            $variant,
            'product_variant'
        );
    }

    /**
     * Handle the ProductVariant "updated" event.
     */
    public function updated(ProductVariant $variant): void
    {
        $newData = $variant->getChanges();
        $oldData = Arr::only($variant->getOriginal(), array_keys($newData));

        $properties = [
            'old' => $oldData,
            'attributes' => $newData,
        ];

        AuditLogService::log(
            "Cập nhật biến thể: $variant->sku (ID: $variant->id)",
            $variant,
            'product_variant',
            Auth::user(),
            $properties
        );
    }

    /**
     * Handle the ProductVariant "deleted" event.
     */
    public function deleted(ProductVariant $variant): void
    {
        AuditLogService::log(
            "Xóa biến thể: $variant->sku (ID: $variant->id)",
            $variant,
            'product_variant'
        );
    }

}
