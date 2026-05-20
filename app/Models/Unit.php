<?php

namespace App\Models;

use App\Observers\UnitObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(UnitObserver::class)]
class Unit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'short_name',
    ];
}
