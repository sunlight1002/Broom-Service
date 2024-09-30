<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Events\JobNotificationToWorker;

class JobHours extends Model
{
    protected $table = 'job_hours';

    protected $fillable = ['job_id', 'worker_id', 'start_time', 'end_time', 'time_diff'];

    public static function boot()
    {
        parent::boot();

        //send notification to worker about next step
        static::saving(function ($model) {
            $isSend = false;
            $model->load(['job', 'job.client', 'job.worker', 'job.jobservice', 'job.propertyAddress']);
            $job = $model->job->toArray();
            $worker = $job['worker'];
            $job['start_time'] = $model->start_time;
            if (auth()->user()->email == $worker['email']) {
                if ($model->isDirty('start_time')) {
                    $isSend = true;
                    $emailData = [
                        'emailSubject'  => __('mail.job_nxt_step.start_time_nxt_step_email_subject'),
                        'emailTitle'  => __('mail.job_nxt_step.start_time_nxt_step_email_title'),
                        'emailContent'  => __('mail.job_nxt_step.start_time_nxt_step_email_content', ['label' => " <b>".__('mail.job_common.end_time')."</b>"]),
                        'emailContentWa'  => __('mail.job_nxt_step.start_time_nxt_step_email_content', ['label' => " *".__('mail.job_common.end_time')."*"]),

                    ];
                    
                } elseif ($model->isDirty('end_time')) {
                    $isSend = true;
                    $emailData = [
                        'emailSubject'  => __('mail.job_nxt_step.end_time_nxt_step_email_subject'),
                        'emailTitle'  => __('mail.job_nxt_step.end_time_nxt_step_email_title'),
                        'emailContent'  => __('mail.job_nxt_step.end_time_nxt_step_email_content', ['l1' => " <b>".__('mail.job_common.mark_as_complete')."</b>", 'l2' => " <b>".__('mail.job_common.resume_timer')."</b>"]),
                        'emailContentWa'  => __('mail.job_nxt_step.end_time_nxt_step_email_content', ['l1' => " *".__('mail.job_common.mark_as_complete')."*", 'l2' => " *".__('mail.job_common.resume_timer')."*"]),

                    ];
                }

                if ($isSend) {
                    event(new JobNotificationToWorker($worker, $job, $emailData));
                }
            }
        });
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }
    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }
}
