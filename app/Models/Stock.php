<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stock extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_variant_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
        'low_stock_threshold',
    ];

    protected $casts = [
        'product_variant_id' => 'integer',
        'warehouse_id' => 'integer',
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
    ];

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->quantity <= $this->low_stock_threshold;
    }

    public function recordMovement(string $type, int $quantityChanged, ?string $note = null): StockMovement
    {
        $quantityAfter = $this->quantity + $quantityChanged;

        if ($quantityAfter < 0) {
            throw new \InvalidArgumentException(__('stock.quantity_after_negative'));
        }

        $this->forceFill(['quantity' => $quantityAfter])->save();

        return $this->movements()->create([
            'type' => $type,
            'quantity_changed' => $quantityChanged,
            'quantity_after' => $quantityAfter,
            'note' => $note,
        ]);
    }
}
