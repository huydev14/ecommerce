<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'warehouse_id',
        'product_name',
        'product_sku',
        'price',
        'quantity',
        'total_price',
    ];

    public function order(): belongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product(): belongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}
