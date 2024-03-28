<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'status',
        'seen',
        'meet_id',
        'offer_id',
        'contract_id',
        'job_id',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'user_id');
    }
}
