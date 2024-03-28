<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoices extends Model
{
    protected $fillable = [
        'invoice_id',
        'job_id',
        'amount',
        'paid_amount',
        'doc_url',
        'type',
        'customer',
        'due_date',
        'pay_method',
        'txn_id',
        'session_id',
        'callback',
        'receipt_id',
        'status',
        'invoice_icount_status',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'customer');
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function receipt()
    {
        return $this->belongsTo(Receipts::class, 'receipt_id');
    }
}
