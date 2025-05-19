<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\OfferAccepted;
use App\Events\WhatsappNotificationEvent;
use App\Models\ClientPropertyAddress;

class NotifyForContract implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\OfferAccepted  $event
     * @return void
     */
    public function handle(OfferAccepted $event)
    {
        $ofr = $event->offer;

        $services = json_decode($ofr['services']);
                
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
        $ofr['services'] = $services;
        $ofr['service_names'] = $s_names;
        $ofr['service_template_names'] = $s_templates_names;

        $property = null;

        $addressId = $services[0]->address;
        if (isset($addressId)) {
            $address = ClientPropertyAddress::find($addressId);
            if (isset($address)) {
                $property = $address;
            }
        }

        $notificationType = $ofr['client']['notification_type'];

        App::setLocale($ofr['client']['lng']);
        if ($notificationType === 'both') {
            if (isset($ofr['client']) && !empty($ofr['client']['phone'])) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::CONTRACT,
                    "notificationData" => [
                        'offer' => $ofr,
                        'client' => $ofr['client'],
                        'property' => $property,
                        'contract' => [
                            'contract_id' => $ofr['contract_id'],
                        ]
                    ]
                ]));
            }

            Mail::send('/Mails/ContractMail', $ofr, function ($messages) use ($ofr) {
                $messages->to($ofr['client']['email']);
                $messages->bcc("office@broomservice.co.il");
                $messages->subject(__('mail.contract.subject', [
                    'id' => $ofr['id']
                ]));
            });
        }elseif ($notificationType === 'email') {
            Mail::send('/Mails/ContractMail', $ofr, function ($messages) use ($ofr) {
                $messages->to($ofr['client']['email']);
                $messages->bcc("office@broomservice.co.il");
                $messages->subject(__('mail.contract.subject', [
                    'id' => $ofr['id']
                ]));
            });
        }else{
            if (isset($ofr['client']) && !empty($ofr['client']['phone'])) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::CONTRACT,
                    "notificationData" => [
                        'offer' => $ofr,
                        'client' => $ofr['client'],
                        'property' => $property,
                        'contract' => [
                            'contract_id' => $ofr['contract_id']
                        ]
                    ]
                ]));
            }
        }

    }
}
