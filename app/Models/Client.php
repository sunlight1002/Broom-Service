<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\HasApiTokens;

class Client extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = "clients";

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
        'lng',
        'passcode',
        'color',
        'geo_address',
        'latitude',
        'longitude',
        'phone',
        'email',
        'status',
        'password',
        'extra',
        'verify_last_address_with_wa_bot',
        'payment_method',
        'icount_client_id',
        'avatar',
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
        'verify_last_address_with_wa_bot' => 'array',
    ];

    public static function boot()
    {
        parent::boot();
        static::deleting(function ($model) {
            Schedule::where('client_id', $model->id)->delete();
            Offer::where('client_id', $model->id)->delete();
            Contract::where('client_id', $model->id)->delete();
            Notification::where('user_id', $model->id)->delete();
            Job::where('client_id', $model->id)->delete();
            Order::where('client_id', $model->id)->delete();

            $comments = $model->comments()->get();
            foreach ($comments as $key => $comment) {
                $comment->delete();
            }
        });
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    public function meetings()
    {
        return $this->hasMany(Schedule::class, 'client_id', 'id');
    }

    public function offers()
    {
        return $this->hasMany(Offer::class, 'client_id', 'id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'client_id', 'id');
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    public function contract()
    {
        return $this->hasMany(Contract::class, 'client_id');
    }

    public function lead_status()
    {
        return $this->hasOne(LeadStatus::class, 'client_id', 'id');
    }

    public function property_addresses()
    {
        return $this->hasMany(ClientPropertyAddress::class)->orderBy('id', 'desc');
    }

    public function jobComments()
    {
        return $this->morphMany(JobComments::class, 'commenter');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'relation');
    }

    public function ScopeReply($query)
    {
        return WhatsappLastReply::where('message', '=', '0')
            ->join('clients', 'whatsapp_last_replies.phone', 'like', DB::raw("CONCAT('%', clients.phone, '%')"))
            ->orWhere('message', '=', '2_no')
            ->orWhere('message', '=', '2')
            ->where('clients.phone', '!=', '')
            ->where('clients.phone', '!=', 0)
            ->where('clients.phone', '!=', NULL);
    }
}
