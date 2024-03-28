<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = [
        'offer_id',
        'client_id',
        'additional_address',
        'signature',
        'status',
        'unique_hash',
        'card_id',
        'job_status',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class, 'offer_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'id', 'contract_id');
    }

    public function card()
    {
        return $this->belongsTo(ClientCard::class, 'card_id');
    }
}
