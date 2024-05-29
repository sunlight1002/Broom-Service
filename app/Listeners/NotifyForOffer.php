<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Models\Admin;
use App\Models\Notification;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\WhatsappNotificationEvent;
use App\Events\OfferSaved;

class NotifyForOffer implements ShouldQueue
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
     * @param  \App\Events\OfferSaved  $event
     * @return void
     */
    public function handle(OfferSaved $event)
    {
        $offer = $event->offer;

        $services = ($offer['services'] != '') ? json_decode($offer['services']) : [];
        if (isset($services)) {
            $s_names  = '';
            foreach ($services as $k => $service) {

                if ($k != count($services) - 1 && $service->service != 10) {
                    $s_names .= $service->name . ", ";
                } else if ($service->service == 10) {
                    if ($k != count($services) - 1) {
                        $s_names .= $service->other_title . ", ";
                    } else {
                        $s_names .= $service->other_title;
                    }
                } else {
                    $s_names .= $service->name;
                }
            }
        }

        $offer['service_names'] = $s_names;

        App::setLocale($offer['client']['lng']);
        if (isset($offer['client']) && !empty($offer['client']['phone'])) {
            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::OFFER_PRICE,
                "notificationData" => $offer
            ]));
        }

        Mail::send('/Mails/OfferMail', $offer, function ($messages) use ($offer) {
            $messages->to($offer['client']['email']);
            ($offer['client']['lng'] == 'en') ?
                $sub = __('mail.offer.subject') . " " . __('mail.offer.from') . " " . __('mail.offer.company') . " #" . ($offer['id'])
                : $sub = $offer['id'] . "# " . __('mail.offer.subject') . " " . __('mail.offer.from') . " " . __('mail.offer.company');

            $messages->subject($sub);
        });
    }
}
