<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\ClientOfferAccepted;
use App\Events\WhatsappNotificationEvent;

class ClientNotifyForContract implements ShouldQueue
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
     * @param  \App\Events\OfferAccepted  $event
     * @return void
     */
    public function handle(ClientOfferAccepted $event)
    {
        $ofr = $event->offer;

        $notificationType = $ofr['client']['notification_type'];
        \Log::info($ofr);
        App::setLocale($ofr['client']['lng']);

        Notification::create([
            'user_id' => $ofr['client']['id'],
            'user_type' => Client::class,
            'type' => NotificationTypeEnum::LEAD_ACCEPTED_PRICE_OFFER,
            'offer_id' => $offer->id,
            'status' => 'accepted'
        ]);
    
        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::LEAD_ACCEPTED_PRICE_OFFER,
            "notificationData" => [
                'client' => $ofr->toArray(),
            ]
        ]));
        
       
    }
}
