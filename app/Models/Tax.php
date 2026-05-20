<?php

namespace App\Models;

use App\Observers\TaxObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(TaxObserver::class)]
class Tax extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'rate',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
    ];
}
