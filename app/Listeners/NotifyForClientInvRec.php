<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\ClientInvRecCreated;
use App\Events\WhatsappNotificationEvent;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;

class NotifyForClientInvRec implements ShouldQueue
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
     * @param  \App\Events\ClientInvRecCreated  $event
     * @return void
     */
    public function handle(ClientInvRecCreated $event)
    {
        \Log::info(['event ' => $event]);
        Notification::create([
            'user_id' => $event->client->id,
            'user_type' => get_class($event->client),
            'type' => NotificationTypeEnum::CLIENT_INVOICE_PAID_CREATED_RECEIPT,
            'data' => [
                'invoice_id' => $event->invoice_id
            ],
            'status' => 'created'
        ]);

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::CLIENT_INVOICE_PAID_CREATED_RECEIPT,
            "notificationData" => [
                'client' => $event->client->toArray(),
                'invoice' => $event->invoice_id
            ]
        ]));
    }
}
