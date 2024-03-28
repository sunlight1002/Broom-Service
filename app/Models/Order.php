<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'order';

    protected $fillable = [
        'order_id',
        'job_id',
        'contract_id',
        'client_id',
        'doc_url',
        'response',
        'items',
        'status',
        'invoice_status'
    ];

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
