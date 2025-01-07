<?php

namespace App\Listeners;

use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\JobNotificationToClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\WhatsappNotificationEvent;
use App\Models\ClientPropertyAddress;

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
        $offerArr = $job['offer'] ?? null;
        
        if (isset($offerArr)) {
            $services = json_decode($offerArr['services']);
        
            if (isset($services)) {
                $s_names = '';
                $s_templates_names = '';
                foreach ($services as $k => $service) {
                    if ($k != count($services) - 1 && $service->template != "others") {
                        $s_names .= $service->name . ", ";
                        $s_templates_names .= $service->template . ", ";
                    } else if ($service->template == "others") {
                        if ($k != count($services) - 1) {
                            $s_names .= $service->other_title . ", ";
                            $s_templates_names .= $service->template . ", ";
                        } else {
                            $s_names .= $service->other_title;
                            $s_templates_names .= $service->template;
                        }
                    } else {
                        $s_names .= $service->name;
                        $s_templates_names .= $service->template;
                    }
                }
            }
            $offerArr['services'] = $services;
            $offerArr['service_names'] = $s_names;
            $offerArr['service_template_names'] = $s_templates_names;

            $property = null;

            $addressId = $services[0]->address;
            if (isset($addressId)) {
                $address = ClientPropertyAddress::find($addressId);
                if (isset($address)) {
                    $property = $address;
                }
            }
        }

        App::setLocale($client['lng']);

        $notificationType = $client["notification_type"] ?? '';

        if (isset($client["phone"]) && !empty($client["phone"])) {
            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::CREATE_JOB,
                "notificationData" => [
                    'job' => $job,
                    'offer' => $offerArr ?? null,
                    'property' => $property ?? null,
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
