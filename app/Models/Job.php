<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'worker_id',
        'offer_id',
        'contract_id',
        'schedule_id',
        'schedule',
        'address_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'shifts',
        'comment',
        'total_amount',
        'order_id',
        'is_order_generated',
        'isOrdered',
        'invoice_id',
        'is_invoice_generated',
        'invoice_no',
        'invoice_url',
        'status',
        'next_start_date',
        'is_next_job_created',
        'keep_prev_worker',
        'is_one_time_job',
        'original_worker_id',
        'original_shifts',
        'previous_worker_id',
        'previous_worker_after',
        'previous_shifts',
        'previous_shifts_after',
        'cancellation_fee_percentage',
        'cancellation_fee_amount',
        'cancelled_by_role',
        'cancelled_by',
        'cancelled_at',
        'cancelled_for',
        'cancel_until_date',
        'job_opening_timestamp'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'total_amount' => 'double',
        'is_order_generated' => 'boolean',
        'is_invoice_generated' => 'boolean',
        'next_start_date' => 'datetime',
        'is_next_job_created' => 'boolean',
        'is_one_time_job' => 'boolean',
        'previous_worker_after' => 'date:Y-m-d',
        'previous_shifts_after' => 'date:Y-m-d',
        'cancellation_fee_percentage' => 'double',
        'cancellation_fee_amount' => 'double',
        'cancelled_at' => 'datetime',
        'cancel_until_date' => 'date:Y-m-d',
        'job_opening_timestamp' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();
        static::deleting(function ($job) {
            JobService::where('job_id', $job->id)->delete();
            JobHours::where('job_id', $job->id)->delete();
            JobComments::where('job_id', $job->id)->delete();
        });
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function service()
    {
        return $this->belongsTo(Services::class, 'job_id');
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class, 'offer_id');
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function jobservice()
    {
        return $this->hasOne(JobService::class, 'job_id');
    }

    public function hours()
    {
        return $this->hasMany(JobHours::class, 'job_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoices::class, 'invoice_id');
    }

    public function propertyAddress()
    {
        return $this->belongsTo(ClientPropertyAddress::class, 'address_id');
    }

    public function workerShifts()
    {
        return $this->hasMany(JobWorkerShift::class, 'job_id');
    }

    public function changeWorkerRequests()
    {
        return $this->hasMany(ChangeJobWorkerRequest::class, 'job_id');
    }
}
