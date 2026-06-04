<?php

namespace App\Models;

use App\Observers\ProductObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(ProductObserver::class)]
class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'thumbnail',
        'thumbnail_public_id',
        'category_id',
        'brand_id',
        'status',
        'is_featured',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
    ];

    protected $appends = ['optimized_thumbnail_url'];

    protected $hidden = ['thumbnail'];

    protected function optimizedThumbnailUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->thumbnail) {
                    return str_replace('/image/upload/', '/image/upload/q_auto,f_auto/', $this->thumbnail);
                }

                return null;
            }
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function cheapestVariant()
    {
        return $this->hasOne(ProductVariant::class)->ofMany('price', 'min');
    }

    public function scopeWithTotalSoldPastMonth($query){
        return $query->addSelect([
            'total_sold' => OrderItem::selectRaw('COALESCE(SUM(quantity), 0)')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereColumn('order_items.product_id', 'products.id')
            ->where('orders.status', 'completed')
        ]);
    }
}
