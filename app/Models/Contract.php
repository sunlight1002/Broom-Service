<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = [
        'offer_id',
        'client_id',
        'additional_address',
        'name_on_card',
        'cvv',
        'unique_hash',
        'signature',
        'card_sign',
        'status',
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
}
