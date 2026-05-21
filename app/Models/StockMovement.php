<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockMovement extends Model
{
    use SoftDeletes;

    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'stock_id',
        'type',
        'quantity_changed',
        'quantity_after',
        'reference_type',
        'reference_id',
        'note',
    ];

    protected $casts = [
        'stock_id' => 'integer',
        'quantity_changed' => 'integer',
        'quantity_after' => 'integer',
        'reference_id' => 'integer',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
