<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable;

class Admin extends Authenticatable implements Auditable
{
    use HasApiTokens, HasFactory, Notifiable;
    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'heb_name',
        'email',
        'phone',
        'address',
        'avatar',
        'color',
        'status',
        'role',
        'password',
        'lng',
        'otp',
        'otp_expiry',
        'two_factor_enabled',
        'payment_type',
        'full_name',
        'bank_name',
        'bank_number',
        'branch_number',
        'account_number'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function availabilities()
    {
        return $this->hasOne(TeamMemberAvailability::class, 'team_member_id');
    }

    public function defaultAvailabilities()
    {
        return $this->hasMany(TeamMemberDefaultAvailability::class, 'team_member_id');
    }

    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = preg_replace('/\D/', '', $value);
    }

    public function tasks()
    {
        return $this->morphToMany(TaskManagement::class, 'assignable','task_workers','assignable_id', 'task_management_id');
    }

    public function tokens()
    {
        return $this->morphMany(DeviceToken::class, 'tokenable');
    }
}
