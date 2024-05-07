<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobCancellationFee extends Model
{
    protected $table = 'job_cancellation_fees';

    protected $fillable = [
        'job_id',
        'is_paid',
        'cancellation_fee_percentage',
        'cancellation_fee_amount',
        'cancelled_user_role',
        'cancelled_by',
        'duration',
        'until_date',
        'action',
        'order_id',
        'is_order_generated',
        'invoice_id',
        'is_invoice_generated',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_paid' => 'boolean',
        'cancellation_fee_percentage' => 'double',
        'cancellation_fee_amount' => 'double',
        'until_date' => 'date:Y-m-d',
        'is_order_generated' => 'boolean',
        'is_invoice_generated' => 'boolean',
    ];
}
