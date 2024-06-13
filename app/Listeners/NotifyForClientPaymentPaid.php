<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\ClientPaymentPaid;
use App\Events\WhatsappNotificationEvent;
use App\Models\Admin;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class NotifyForClientPaymentPaid implements ShouldQueue
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
     * @param  \App\Events\ClientPaymentPaid  $event
     * @return void
     */
    public function handle(ClientPaymentPaid $event)
    {
        Notification::create([
            'user_id' => $event->client->id,
            'user_type' => get_class($event->client),
            'type' => NotificationTypeEnum::PAYMENT_PAID,
            'data' => [
                'amount' => $event->amount
            ],
            'status' => 'paid'
        ]);

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::PAYMENT_PAID,
            "notificationData" => [
                'client' => $event->client->toArray(),
                'amount' => $event->amount
            ]
        ]));
    }
}
