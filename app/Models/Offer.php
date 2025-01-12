<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = [
        'client_id',
        'services',
        'subtotal',
        'total',
        'status',
        'is_fixed_for_services',
        'comment'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_fixed_for_services' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function service()
    {
        return $this->belongsTo(Services::class, 'job_id');
    }

    public function contract()
    {
        return $this->hasOne(Contract::class, 'offer_id');
    }
}
