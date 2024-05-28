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
                        // 'emailSubject'  => __('mail.job_status.subject'),
                        'emailSubject'  => 'Job time started | Next step | Broom Service',
                        'emailTitle'  => 'Job time started',
                        'emailContent'  => "Job time has been started by you. Click <a href='" . url("worker/view-job/{$job['id']}") . "'> <b>End time</b> </a> for stop your job work time."
                    ];
                } elseif ($model->isDirty('end_time')) {
                    $isSend = true;
                    $emailData = [
                        'emailSubject'  => 'Job time ended | Next step | Broom Service',
                        'emailTitle'  => 'Job time ended',
                        'emailContent'  => "The job time has been stopped by you. Click <a href='" . url("worker/view-job/{$job['id']}") . "'> <b>Mark as complete</b> </a> if you want to complete your job else click on <b>Resume timer</b> to continue job."
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
