<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
 
class Order extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

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
        'amount',
        'discount_amount',
        'total_amount',
        'amount_with_tax',
        'paid_amount',
        'unpaid_amount',
        'is_force_closed',
        'force_closed_at',
        'is_webhook_created',
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
        'unpaid_amount' => 'double',
        'is_force_closed' => 'boolean',
        'force_closed_at' => 'datetime',
        'is_webhook_created' => 'boolean',
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
