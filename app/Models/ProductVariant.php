<?php

namespace App\Models;

use App\Observers\ProductVariantObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(ProductVariantObserver::class)]
class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'compare_at_price',
        'cost_price',
        'unit_id',
        'tax_id',
        'attributes',
        'is_active',
    ];

    protected $casts = [
        'attributes' => 'json',
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
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

    public function inventory()
    {
        return $this->hasOne(Inventory::class, 'variant_id');
    }
}
