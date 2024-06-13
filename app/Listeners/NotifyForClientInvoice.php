<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\ClientInvoiceCreated;
use App\Events\WhatsappNotificationEvent;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;

class NotifyForClientInvoice implements ShouldQueue
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
     * @param  \App\Events\ClientInvoiceCreated  $event
     * @return void
     */
    public function handle(ClientInvoiceCreated $event)
    {
        Notification::create([
            'user_id' => $event->client->id,
            'user_type' => get_class($event->client),
            'type' => NotificationTypeEnum::CLIENT_INVOICE_CREATED_AND_SENT_TO_PAY,
            'data' => [
                'invoice_id' => $event->invoice->invoice_id
            ],
            'status' => 'created'
        ]);

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::CLIENT_INVOICE_CREATED_AND_SENT_TO_PAY,
            "notificationData" => [
                'client' => $event->client->toArray(),
                'invoice' => $event->invoice
            ]
        ]));
    }
}
