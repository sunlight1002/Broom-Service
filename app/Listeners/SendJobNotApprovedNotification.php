<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use App\Events\WorkerNotApprovedJob;
use App\Models\Admin;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\JobNotificationToAdmin;
use App\Events\JobNotificationToWorker;
use App\Models\Notification;

class SendJobNotApprovedNotification implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\WorkerNotApprovedJob  $event
     * @return void
     */
    public function handle(WorkerNotApprovedJob $event)
    {
        App::setLocale('en');
        //send notification to admin
        $adminEmailData = [
            'emailData'   => [
                'job' => $event->job->toArray(),
            ],
            'emailSubject'  => 'Job Not Approved | Broom Service',
            'emailTitle'  => 'Worker Not Approved Job',
            'emailContent'  => 'Worker has not approved the job yet.'
        ];
        event(new JobNotificationToAdmin($adminEmailData));

        //send notification to worker
        $job = $event->job->toArray();
        $worker = $job['worker'];
        App::setLocale($worker['lng']);

        $emailData = [
            'emailSubject'  => __('mail.job_common.not_approve_subject'),
            'emailTitle'  => __('mail.job_common.not_approve_title'),
            'emailContent'  => __('mail.job_common.not_approve_content')
        ];
        event(new JobNotificationToWorker($worker, $job, $emailData));

        Notification::create([
            'user_id' => $worker['id'],
            'user_type' => get_class($event->job->worker),
            'type' => NotificationTypeEnum::WORKER_NOT_APPROVED_JOB,
            'status' => 'not-approved',
            'job_id' => $job['id']
        ]);

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::WORKER_NOT_APPROVED_JOB,
            "notificationData" => array(
                'job' => $event->job->toArray(),
                'content' => 'Worker has not approved the job yet.'
            )
        ]));
    }
}
