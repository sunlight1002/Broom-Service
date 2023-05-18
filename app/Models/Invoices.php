<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoices extends Model
{
    use HasFactory;
    protected $fillable = [
        'invoice_id',
        'job_id',
        'amount',
        'paid_amount',
        'doc_url',
        'due_date',
        'customer',
        'txn_id',
        'type',
        'session_id',
        'callback',
        'pay_method',
        'invoice_icount_status',
        'status'
    ];

    public function client(){
        return $this->belongsTo(Client::class,'customer');
    }
    public function job(){
        return $this->belongsTo(Job::class,'job_id');
    }
   
}
