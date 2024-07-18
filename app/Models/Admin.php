<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

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
        'otp',
        'otp_expiry',
        'two_factor_enabled',
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

    
   

    // public function generateCode(){
    //     $this->timestamps = false;
    //     $this->otp = rand(1000,9999);
    //     $this->otp_expiry = now()->addMinute(5);
    //     $this->save();
    // }
}
