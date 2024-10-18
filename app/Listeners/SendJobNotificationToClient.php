<?php

namespace App\Listeners;

use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\JobNotificationToClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\WhatsappNotificationEvent;

class SendJobNotificationToClient implements ShouldQueue
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
     * @param  \App\Events\JobNotificationToClient  $event
     * @return void
     */
    public function handle(JobNotificationToClient $event)
    {
        $client = $event->client;
        $worker = $event->worker;
        $job = $event->job;
        $emailData = $event->emailData;

        App::setLocale($client['lng']);

        $notificationType = $client["notification_type"] ?? '';

        if (isset($client["phone"]) && !empty($client["phone"])) {
            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::CREATE_JOB,
                "notificationData" => [
                    'job' => $job,
                    'client' => $client,
                ]
            ]));
        };

        // if ($notificationType === 'both') {
        //     if (isset($client["phone"]) && !empty($client["phone"])) {
        //         event(new WhatsappNotificationEvent([
        //             "type" => WhatsappMessageTemplateEnum::CREATE_JOB,
        //             "notificationData" => [
        //                 'job' => $job,
        //                 'client' => $client,
        //             ]
        //         ]));
        //     };

        //     // Mail::send('/Mails/client/JobNotification', [
        //     //     'job' => $job,
        //     //     'worker' => $worker,
        //     //     'emailData' => $emailData,
        //     //     'client' => $client,
        //     // ], function ($messages) use ($client, $emailData) {
        //     //     $messages->to($client['email']);
        //     //     $messages->subject($emailData['emailSubject']);
        //     // });
        // } elseif ($notificationType === 'email') {
        //     // Mail::send('/Mails/client/JobNotification', [
        //     //     'job' => $job,
        //     //     'worker' => $worker,
        //     //     'emailData' => $emailData,
        //     //     'client' => $client,
        //     // ], function ($messages) use ($client, $emailData) {
        //     //     $messages->to($client['email']);
        //     //     $messages->subject($emailData['emailSubject']);
        //     // });
        // } else {
        //     if (isset($client["phone"]) && !empty($client["phone"])) {
        //         event(new WhatsappNotificationEvent([
        //             "type" => WhatsappMessageTemplateEnum::CREATE_JOB,
        //             "notificationData" => [
        //                 'job' => $job,
        //                 'client' => $client,
        //             ]
        //         ]));
        //     };
        // }
    }
}
