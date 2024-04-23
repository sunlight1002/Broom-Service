<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'phone',
        'address',
        'renewal_visa',
        'gender',
        'payment_per_hour',
        'worker_id',
        'lng',
        'skill',
        'company_type',
        'status',
        'passcode',
        'password',
        'is_afraid_by_cat',
        'is_afraid_by_dog',
        'country',
        'form_101',
        'form_insurance',
        'worker_contract',
        'geo_address',
        'latitude',
        'longitude',
        'freeze_shift_start_time',
        'freeze_shift_end_time',
        'visa', 
        'passport'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_afraid_by_cat' => 'boolean',
        'is_afraid_by_dog' => 'boolean',
    ];

    public function setSkillAttribute($value)
    {
        $this->attributes['skill'] = json_encode($value);
    }

    public function getSkillAttribute($value)
    {
        return $this->attributes['skill'] = $value;
    }

    public function jobs()
    {
        return $this->hasMany(Job::class, 'worker_id');
    }

    public function availabilities()
    {
        return $this->hasMany(WorkerAvailability::class);
    }

    public function notAvailableDates()
    {
        return $this->hasMany(WorkerNotAvailableDate::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'userable')->orderBy('created_at','DESC');
    }

    public function forms()
    {
        return $this->morphMany(Form::class, 'user');
    }

    public function defaultAvailabilities()
    {
        return $this->hasMany(WorkerDefaultAvailability::class);
    }
}
