<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\ClientOrderCancelled;
use App\Events\WhatsappNotificationEvent;
use App\Models\Notification;

class NotifyForOrderCancelled implements ShouldQueue
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
     * @param  \App\Events\ClientOrderCancelled  $event
     * @return void
     */
    public function handle(ClientOrderCancelled $event)
    {
        $client = $event->client;
        $order = $event->order;

        Notification::create([
            'user_id' => $client->id,
            'user_type' => get_class($client),
            'type' => NotificationTypeEnum::ORDER_CANCELLED,
            'status' => 'cancelled',
            'data' => [
                'order_id' => $order->order_id
            ]
        ]);

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::ORDER_CANCELLED,
            "notificationData" => [
                'client' => $client,
                'order' => $order,
            ]
        ]));
    }
}
