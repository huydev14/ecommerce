<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\SoftDeletes;


class Customer extends Authenticatable implements JWTSubject
{
    use Notifiable, SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'provider_user_id',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    public function oauthAccounts()
    {
        return $this->hasMany(OAuthAccount::class);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'name' => $this->name,
            'tier' => $this->membership_tier,
        ];
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }
}
