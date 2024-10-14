<?php

namespace App\Listeners;

use App\Events\JobNotificationToWorker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

class SendJobNotificationToWorker implements ShouldQueue
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
     * @param  \App\Events\JobNotificationToWorker  $event
     * @return void
     */
    public function handle(JobNotificationToWorker $event)
    {
        $worker = $event->worker;
        $job = $event->job;
        $emailData = $event->emailData;
        \Log::info($job);

        App::setLocale($worker['lng']);

        // Mail::send('/Mails/worker/JobNotification', [
        //     'job' => $job,
        //     'worker' => $worker,
        //     'emailData' => $emailData
        // ], function ($messages) use ($worker, $emailData) {
        //     $messages->to($worker['email']);
        //     $messages->subject($emailData['emailSubject']);
        // });

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::JOB_APPROVED_NOTIFICATION_TO_WORKER,
            "notificationData" => [
                'job' => $job,
                'emailData' => $emailData,
                'worker' => $worker
            ]
        ]));
        
    }
}
