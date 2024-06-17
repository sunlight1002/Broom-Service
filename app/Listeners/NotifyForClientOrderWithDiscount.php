<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\ClientOrderWithDiscount;
use App\Events\WhatsappNotificationEvent;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;

class NotifyForClientOrderWithDiscount implements ShouldQueue
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
     * @param  \App\Events\ClientOrderWithDiscount  $event
     * @return void
     */
    public function handle(ClientOrderWithDiscount $event)
    {
        Notification::create([
            'user_id' => $event->client->id,
            'user_type' => get_class($event->client),
            'type' => NotificationTypeEnum::ORDER_CREATED_WITH_DISCOUNT,
            'data' => [
                'order_id' => $event->order->order_id,
                'discount' => $event->order->discount_amount,
                'total_amount' => $event->order->total_amount,
            ],
            'status' => 'created'
        ]);

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::ORDER_CREATED_WITH_DISCOUNT,
            "notificationData" => [
                'client' => $event->client->toArray(),
                'order_id' => $event->order->order_id,
                'discount' => $event->order->discount_amount,
                'total_amount' => $event->order->total_amount,
            ]
        ]));
    }
}
