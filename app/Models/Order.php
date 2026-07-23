<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    const STATUS_PENDING_PAYMENT = 1;
    const STATUS_NEW = 2;
    const STATUS_PROCESSING = 3;
    const STATUS_SHIPPING = 4;
    const STATUS_DELIVERED = 5;
    const STATUS_CANCELLED = 6;

    protected $fillable = [
        'order_number',
        'customer_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'shipping_address',
        'notes',
        'subtotal',
        'shipping_fee',
        'total_amount',
        'status',
        'payment_method',
        'payment_status',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status' => 'integer',
    ];

    public static function statusMeta(): array
    {
        return [
            self::STATUS_PENDING_PAYMENT => ['bg' => 'tw-bg-gray-100', 'text' => 'tw-text-gray-600', 'dot' => 'tw-bg-gray-400', 'label' => 'order.statuses.pending_payment'],
            self::STATUS_NEW => ['bg' => 'tw-bg-gray-100', 'text' => 'tw-text-gray-600', 'dot' => 'tw-bg-gray-400', 'label' => 'order.statuses.new'],
            self::STATUS_PROCESSING => ['bg' => 'tw-bg-amber-200', 'text' => 'tw-text-gray-900', 'dot' => 'tw-bg-amber-500', 'label' => 'order.statuses.processing'],
            self::STATUS_SHIPPING => ['bg' => 'tw-bg-indigo-50', 'text' => 'tw-text-indigo-700', 'dot' => 'tw-bg-indigo-500', 'label' => 'order.statuses.shipping'],
            self::STATUS_DELIVERED => ['bg' => 'tw-bg-emerald-50', 'text' => 'tw-text-emerald-700', 'dot' => 'tw-bg-emerald-500', 'label' => 'order.statuses.delivered'],
            self::STATUS_CANCELLED => ['bg' => 'tw-bg-red-50', 'text' => 'tw-text-red-700', 'dot' => 'tw-bg-red-500', 'label' => 'order.statuses.cancelled'],
        ];
    }

    public static function statusOptions(): array
    {
        return collect(self::statusMeta())
            ->map(fn ($meta, $id) => ['id' => $id, 'text' => __($meta['label'])])
            ->values()
            ->all();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}
