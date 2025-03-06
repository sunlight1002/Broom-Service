<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

class JobHours extends Model
{
    protected $table = 'job_hours';

    protected $fillable = ['job_id', 'worker_id', 'start_time', 'end_time', 'time_diff'];

    public static function boot()
    {
        parent::boot();

        //send notification to worker about next step
        static::saving(function ($model) {
            $model->load(['job', 'job.client', 'job.worker', 'job.jobservice', 'job.propertyAddress', 'job.comments']);
            $job = $model->job->toArray();
            $worker = $job['worker'];
            $client = $job['client'];
            $job['start_time'] = $model->start_time;
            if ($worker['email']) {
                if ($model->isDirty('start_time')) {
                    event(new WhatsappNotificationEvent([
                        "type" => WhatsappMessageTemplateEnum::WORKER_START_THE_JOB,
                        "notificationData" => [
                            'job' => $job,
                            'worker' => $worker,
                            'client' => $client,
                        ]
                    ]));
                } elseif ($model->isDirty('end_time')) {
                    event(new WhatsappNotificationEvent([
                        "type" => WhatsappMessageTemplateEnum::SEND_WORKER_TO_STOP_TIMER,
                        "notificationData" => [
                            'job' => $job,
                            'worker' => $worker,
                            'client' => $client,
                        ]
                    ]));

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
