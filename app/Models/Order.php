<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'order';

    protected $fillable = [
        'order_id',
        'client_id',
        'doc_url',
        'response',
        'items',
        'status',
        'invoice_status',
        'paid_status',
    ];

    public function jobs()
    {
        return $this->hasMany(Job::class, 'order_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function jobCancellationFees()
    {
        return $this->hasMany(JobCancellationFee::class, 'order_id');
    }
}
