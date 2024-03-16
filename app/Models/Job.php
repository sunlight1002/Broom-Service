<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'offer_id',
        'contract_id',
        'worker_id',
        'start_date',
        'end_date',
        'schedule_id',
        'schedule',
        'comment',
        'instruction',
        'address',
        'start_time',
        'shifts',
        'end_time',
        'rate',
        'invoice_no',
        'invoice_url',
        'status',
        'address_id',
        'next_start_date',
        'is_next_job_created',
        'keep_prev_worker',
        'cancellation_fee_percentage',
        'cancellation_fee_amount',
        'cancelled_by_role',
        'cancelled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'next_start_date' => 'datetime',
        'is_next_job_created' => 'boolean',
        'cancellation_fee_percentage' => 'double',
        'cancellation_fee_amount' => 'double',
        'cancelled_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();
        static::deleting(function ($job) {
            Invoices::where('job_id', $job->id)->delete();
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
        return $this->hasMany(Order::class, 'job_id');
    }

    public function invoice()
    {
        return $this->hasMany(Invoices::class, 'job_id');
    }

    public function propertyAddress()
    {
        return $this->belongsTo(ClientPropertyAddress::class, 'address_id');
    }

    public function workerShifts()
    {
        return $this->hasMany(JobWorkerShift::class, 'job_id');
    }
}
