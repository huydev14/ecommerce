<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];
    protected $casts  = [
        'value' => 'array',
        'is_active' => 'boolean',
        'client_secret' => 'encrypted'
    ];
}
