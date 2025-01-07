<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\JobReviewRequest;
use App\Mail\Client\JobReviewRequestMail;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

class SendJobReviewRequestNotification implements ShouldQueue
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
     * @param  \App\Events\JobReviewRequest  $event
     * @return void
     */
    public function handle(JobReviewRequest $event)
    {
        if (!empty($event->job->client->email)) {
            App::setLocale($event->job->client->lng);

            $job = $event->job;
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

            // Mail::to($event->job->client->email)->send(new JobReviewRequestMail($event->job));
            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::CLIENT_JOB_UPDATED,
                "notificationData" => [
                    'job' => $event->job->toArray(),
                    'offer' => $offerArr,
                    'property' => $property
                ]
            ]));
        }

        $event->job->update([
            'review_request_sent' => true
        ]);
    }
}
