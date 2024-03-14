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
        'status'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function service()
    {
        return $this->belongsTo(Services::class, 'job_id');
    }
}
