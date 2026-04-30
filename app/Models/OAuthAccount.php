<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OAuthAccount extends Model
{
    protected $table = 'oauth_accounts';

    protected $fillable = [
        'user_id',
        'customer_id',
        'provider',
        'provider_user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
