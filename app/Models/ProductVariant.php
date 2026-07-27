<?php

namespace App\Models;

use App\Observers\ProductVariantObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(ProductVariantObserver::class)]
class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'price',
        'compare_at_price',
        'cost_price',
        'unit_id',
        'tax_id',
        'net_weight',
        'gross_weight',
        'attributes',
        'is_active',
    ];

    protected $casts = [
        'attributes' => 'json',
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'net_weight' => 'decimal:3',
        'gross_weight' => 'decimal:3',
        'unit_id' => 'integer',
        'tax_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'product_variant_id');
    }
    
    public function getAvailableStockAttribute()
    {
        return $this->stocks->sum(function ($stock) {
            return $stock->quantity - $stock->reserved_quantity;
        });
    }
}
