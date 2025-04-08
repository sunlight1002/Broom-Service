<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\WhatsappNotificationEvent;
use App\Events\OfferSaved;
use App\Models\ClientPropertyAddress;

class NotifyForOffer implements ShouldQueue
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
     * @param  \App\Events\OfferSaved  $event
     * @return void
     */
    public function handle(OfferSaved $event)
    {
        $offer = $event->offer;

        $services = ($offer['services'] != '') ? json_decode($offer['services']) : [];
        if (isset($services)) {
            $s_names  = '';
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

        $offer['service_names'] = $s_names;
        $offer['service_template_names'] = $s_templates_names;

        $addressId = $services[0]->address;
        if (isset($addressId)) {
            $address = ClientPropertyAddress::find($addressId);
            if (isset($address)) {
                $property = $address;
            }
        }

        $notificationType = $offer['client']['notification_type'];

        App::setLocale($offer['client']['lng']);

        if ($notificationType === 'both') {

            if (isset($offer['client']) && !empty($offer['client']['phone'])) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::OFFER_PRICE,
                    "notificationData" => [
                        'offer' => $offer,
                        'client' => $offer['client'],
                        'property' => $property ?? []
                    ]
                ]));
            }

            Mail::send('/Mails/OfferMail', $offer, function ($messages) use ($offer) {
                $messages->to($offer['client']['email']);

                $messages->subject(__('mail.offer.subject', [
                    'id' => $offer['id']
                ]));
            });
        }elseif ($notificationType === 'email') {
            Mail::send('/Mails/OfferMail', $offer, function ($messages) use ($offer) {
                $messages->to($offer['client']['email']);

                $messages->subject(__('mail.offer.subject', [
                    'id' => $offer['id']
                ]));
            });
        }else{
            if (isset($offer['client']) && !empty($offer['client']['phone'])) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::OFFER_PRICE,
                    "notificationData" => [
                        'offer' => $offer,
                        'client' => $offer['client'],
                        'property' => $property ?? []
                    ]
                ]));
            }
        }

    }
}
