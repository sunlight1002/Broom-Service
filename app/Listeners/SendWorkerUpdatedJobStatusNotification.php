<?php

namespace App\Listeners;

use App\Enums\JobStatusEnum;
use App\Events\WorkerUpdatedJobStatus;
use App\Models\Admin;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class SendWorkerUpdatedJobStatusNotification
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
            'comment'    => $event->comment->comment,
            'job'        => $event->job->toArray(),
        );

        Notification::create([
            'user_id' => $event->job->client->id,
            'type' => 'worker-reschedule',
            'job_id' => $event->job->id,
            'status' => 'reschedule'
        ]);

        Mail::send('/WorkerPanelMail/JobStatusNotification', $data, function ($messages) use ($data) {
            $messages->to($data['email']);
            $sub = __('mail.job_status.subject');
            $messages->subject($sub);
        });

        if ($event->job->status == JobStatusEnum::COMPLETED) {
            App::setLocale($event->job->client->lng);
            $emailData = array(
                'email'      => $event->job->client->email,
                'job'        => $event->job->toArray(),
            );

            Mail::send('/Mails/ClientJobUpdated', $emailData, function ($messages) use ($emailData) {
                $messages->to($emailData['email']);
                $sub = __('mail.client_job_status.job_completed_subject');
                $messages->subject($sub);
            });
        }
    }
}
