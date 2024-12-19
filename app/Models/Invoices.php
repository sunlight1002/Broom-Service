<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoices extends Model
{
    protected $fillable = [
        'invoice_id',
        'order_id',
        'amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'amount_with_tax',
        'doc_url',
        'cancel_doc_url',
        'refund_doc_url',
        'type',
        'client_id',
        'due_date',
        'pay_method',
        'txn_id',
        'session_id',
        'callback',
        'receipt_id',
        'status',
        'invoice_icount_status',
        'is_webhook_created'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'double',
        'discount_amount' => 'double',
        'total_amount' => 'double',
        'amount_with_tax' => 'double',
        'paid_amount' => 'double',
        'is_webhook_created' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function jobs()
    {
        return $this->hasMany(Job::class, 'order_id');
    }

    public function receipt()
    {
        return $this->belongsTo(Receipts::class, 'receipt_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
