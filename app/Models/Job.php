<?php

namespace App\Models;

use App\Enums\JobStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Events\JobNotificationToWorker;

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
        'extra_amount',
        'subtotal_amount',
        'discount_type',
        'discount_value',
        'discount_amount',
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
        'is_one_time_in_month_job',
        'is_job_done',
        'is_paid',
        'is_worker_reminded',
        'worker_approved_at',
        'actual_time_taken_minutes',
        'origin_job_id',
        'job_group_id',
        'original_worker_id',
        'original_shifts',
        'previous_worker_id',
        'previous_worker_after',
        'previous_shifts',
        'previous_shifts_after',
        'job_opening_timestamp',
        'rating',
        'review',
        'client_reviewed_at',
        'review_request_sent',
        'completed_at',
        'cancellation_fee_percentage',
        'cancellation_fee_amount',
        'cancelled_by_role',
        'cancelled_by',
        'cancelled_at',
        'cancelled_for',
        'cancel_until_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'extra_amount' => 'double',
        'subtotal_amount' => 'double',
        'discount_value' => 'double',
        'discount_amount' => 'double',
        'total_amount' => 'double',
        'is_order_generated' => 'boolean',
        'is_invoice_generated' => 'boolean',
        'next_start_date' => 'datetime',
        'is_next_job_created' => 'boolean',
        'is_one_time_in_month_job' => 'boolean',
        'is_job_done' => 'boolean',
        'is_paid' => 'boolean',
        'is_worker_reminded' => 'boolean',
        'previous_worker_after' => 'date:Y-m-d',
        'previous_shifts_after' => 'date:Y-m-d',
        'rating' => 'double',
        'client_reviewed_at' => 'datetime',
        'review_request_sent' => 'boolean',
        'completed_at' => 'datetime',
        'cancellation_fee_percentage' => 'double',
        'cancellation_fee_amount' => 'double',
        'cancelled_at' => 'datetime',
        'cancel_until_date' => 'date:Y-m-d',
        'job_opening_timestamp' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            if (
                $model->status == JobStatusEnum::COMPLETED &&
                $model->getOriginal('status') != $model->status
            ) {
                $model->is_job_done = true;
            }
        });

        //send notification to worker about next step
        static::updating(function ($model) {
            $isSend = false;
            $job = $model->load(['client', 'worker', 'jobservice', 'propertyAddress'])->toArray();
            $worker = $job['worker'];
            if ($worker && auth()->user()->email == $worker['email']) {
                if ($model->isDirty('worker_approved_at')) {
                    $isSend = true;
                    $emailData = [
                        'emailSubject'  => __('mail.job_nxt_step.approved_nxt_step_email_subject'),
                        'emailTitle'  => __('mail.job_nxt_step.approved_nxt_step_email_title'),
                        'emailContent'  => __('mail.job_nxt_step.approved_nxt_step_email_content', ['label' => " <b>".__('mail.job_nxt_step.leaving_for_work_link')."</b>"]),
                    ];
                } elseif ($model->isDirty('job_opening_timestamp')) {
                    $isSend = true;
                    $emailData = [
                        'emailSubject'  => __('mail.job_nxt_step.opened_nxt_step_email_subject'),
                        'emailTitle'  => __('mail.job_nxt_step.opened_nxt_step_email_title'),
                        'emailContent'  => __('mail.job_nxt_step.opened_nxt_step_email_content', ['l1' => " <b>".__('mail.job_common.start_time')."</b>", 'l2' => " <b>".__('mail.job_common.mark_as_complete')."</b>"]),
                    ];
                } elseif ($model->isDirty('is_job_done')) {
                    $isSend = true;
                    $emailData = [
                        'emailSubject'  => __('mail.job_nxt_step.completed_nxt_step_email_subject'),
                        'emailTitle'  => __('mail.job_nxt_step.completed_nxt_step_email_title'),
                        'emailContent'  => __('mail.job_nxt_step.completed_nxt_step_email_content', ['jobId' => " <b>".$job['id']."</b>"]),
                    ];
                }

                if ($isSend) {
                    event(new JobNotificationToWorker($worker, $job, $emailData));
                }
            }
        });

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

    public function comments()
    {
        return $this->hasMany(JobComments::class, 'job_id');
    }
}
