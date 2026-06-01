<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    protected $fillable = [
        'customer_id',
        'receiver_name',
        'receiver_phone',
        'province_id',
        'district_id',
        'ward_code',
        'province_name',
        'district_name',
        'ward_name',
        'specific_address',
        'is_default',
        'label',
        'delivery_note',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'province_id' => 'integer',
        'district_id' => 'integer',
        'is_default' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->specific_address,
            $this->ward_name,
            $this->district_name,
            $this->province_name,
        ])->filter()->implode(', ');
    }
}
