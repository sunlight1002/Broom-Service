<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\ClientPaymentPartiallyPaid;
use App\Events\WhatsappNotificationEvent;
use App\Models\Admin;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class NotifyForClientPaymentPartiallyPaid implements ShouldQueue
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
     * @param  \App\Events\ClientPaymentPartiallyPaid  $event
     * @return void
     */
    public function handle(ClientPaymentPartiallyPaid $event)
    {
        Notification::create([
            'user_id' => $event->client->id,
            'user_type' => get_class($event->client),
            'type' => NotificationTypeEnum::PAYMENT_PARTIAL_PAID,
            'data' => [
                'amount' => $event->amount
            ],
            'status' => 'paid'
        ]);

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::PAYMENT_PARTIAL_PAID,
            "notificationData" => [
                'client' => $event->client->toArray(),
                'amount' => $event->amount
            ]
        ]));
    }
}
