<?php

namespace App\Models;

use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

#[ObservedBy(UserObserver::class)]
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'department_id',
        'position_id',
        'team_id',
        'employment_type',
        'status',
        'start_date',
        'end_date',
        'gender',
        'birthday',
        'phone',
        'address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function oauthAccounts()
    {
        return $this->hasMany(OAuthAccount::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    // --- JWT Authentication ---------------
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'email' => $this->email,
        ];
    }

    // --- Spatie activity log ---------------
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('user_profile')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
