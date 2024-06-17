<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\ClientOrderWithExtra;
use App\Events\WhatsappNotificationEvent;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;

class NotifyForClientOrderWithExtra implements ShouldQueue
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
     * @param  \App\Events\ClientOrderWithExtra  $event
     * @return void
     */
    public function handle(ClientOrderWithExtra $event)
    {
        Notification::create([
            'user_id' => $event->client->id,
            'user_type' => get_class($event->client),
            'type' => NotificationTypeEnum::ORDER_CREATED_WITH_EXTRA,
            'data' => [
                'extra' => $event->extra,
                'order_id' => $event->order->order_id,
                'total_amount' => $event->order->total_amount,
            ],
            'status' => 'created'
        ]);

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::ORDER_CREATED_WITH_EXTRA,
            "notificationData" => [
                'client' => $event->client->toArray(),
                'extra' => $event->extra,
                'order_id' => $event->order->order_id,
                'total_amount' => $event->order->total_amount,
            ]
        ]));
    }
}
