<?php

namespace App\Listeners;

use App\Enums\JobStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Events\WorkerUpdatedJobStatus;
use App\Models\Admin;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\WhatsappNotificationEvent;
use App\Events\JobNotificationToAdmin;

class SendWorkerUpdatedJobStatusNotification implements ShouldQueue
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
     * @param  \App\Events\WorkerUpdatedJobStatus  $event
     * @return void
     */
    public function handle(WorkerUpdatedJobStatus $event)
    {
        $admin = Admin::first();
        App::setLocale('en');
        $data = array(
            'email'      => $admin->email,
            'admin'      => $admin->toArray(),
            'comment'    => $event->comment && $event->comment->comment ? $event->comment->comment : '-',
            'job'        => $event->job->toArray(),
        );
        Notification::create([
            'user_id' => $event->job->client->id,
            'user_type' => get_class($event->job->client),
            'type' => NotificationTypeEnum::WORKER_RESCHEDULE,
            'job_id' => $event->job->id,
            'status' => 'reschedule'
        ]);

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::WORKER_JOB_STATUS_NOTIFICATION,
            "notificationData" => array(
                'comment'    => $event->comment && $event->comment->comment ? $event->comment->comment : '-',
                'job'        => $event->job->toArray(),
            )
        ]));

        //send notification to admin
        $emailContent = __('mail.job_status.content') . '' . ucfirst($data['job']['status']) . '.';
        if ($data['job']['status'] != 'completed') {
            $emailContent .= __('mail.job_status.reason') . ' ' . $event->comment->comment . '.';
        }

        if ($event->job->status == JobStatusEnum::COMPLETED) {
            App::setLocale($event->job->client->lng);
            $emailData = array(
                'email'      => $event->job->client->email,
                'job'        => $event->job->toArray(),
            );

            if ($event->job->client && !empty($event->job->client['phone'])) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::CLIENT_JOB_UPDATED,
                    "notificationData" => $emailData
                ]));
            }
        }
    }
}
