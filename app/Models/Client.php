<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Client extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table="clients";
    
    protected $fillable = [
        'firstname',
        'lastname',
        'invoicename',
        'city',
        'street_n_no',
        'floor',
        'apt_no',
        'entrence_code',
        'zipcode',
        'dob',
        'passcode',
        'geo_address',
        'latitude',
        'longitude',
        'color',
        'address',
        'phone',
        'email',
        'status',
        'password',

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
    
    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

}
