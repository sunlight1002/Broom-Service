<?php

namespace App\Listeners;

use App\Events\JobWorkerChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Helpers\Helper;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

class SendWorkerChangedNotification
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
     * @param  \App\Events\JobWorkerChanged  $event
     * @return void
     */
    public function handle(JobWorkerChanged $event)
    {
        if (!is_null($event->job['worker']['email']) && $event->job['worker']['email'] != 'Null') {
            App::setLocale($event->job->worker->lng);

            $emailData = array(
                'email' => $event->job['worker']['email'],
                'job' => $event->job->toArray(),
                'start_time' => $event->shiftsInHour[0]['start'],
                'content' => __('mail.worker_new_job.new_job_assigned') . " " . __('mail.worker_new_job.please_check'),
            );
            Helper::sendJobWANotification($emailData);
            Mail::send('/Mails/NewJobMail', $emailData, function ($messages) use ($emailData) {
                $messages->to($emailData['email']);
                $sub = __('mail.worker_new_job.subject') . "  " . __('mail.worker_new_job.company');
                $messages->subject($sub);
            });
        }

        if (
            isset($event->oldWorker['email']) &&
            $event->oldWorker['email']
        ) {
            App::setLocale($event->oldWorker['lng']);

            $emailData = array(
                'email' => $event->oldWorker['email'],
                'job' => $event->job->toArray(),
                'old_worker' => $event->oldWorker,
                'old_job' => $event->old_job_data
            );
            if (isset($emailData['old_worker']) && !empty($emailData['old_worker']['phone'])) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::WORKER_UNASSIGNED,
                    "notificationData" => $emailData
                ]));
            }
            Mail::send('/Mails/WorkerUnassignedMail', $emailData, function ($messages) use ($emailData) {
                $messages->to($emailData['email']);
                $sub = __('mail.worker_unassigned.subject') . "  " . __('mail.worker_unassigned.company');
                $messages->subject($sub);
            });
        }
    }
}
