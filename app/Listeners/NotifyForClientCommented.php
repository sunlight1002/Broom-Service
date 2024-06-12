<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\ClientCommented;
use App\Events\WhatsappNotificationEvent;
use App\Models\Client;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;

class NotifyForClientCommented implements ShouldQueue
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
     * @param  \App\Events\ClientCommented  $event
     * @return void
     */
    public function handle(ClientCommented $event)
    {
        Notification::create([
            'user_id' => $event->client['id'],
            'user_type' => Client::class,
            'type' => NotificationTypeEnum::CLIENT_COMMENTED,
            'job_id' => $event->job['id'],
            'status' => 'commented'
        ]);

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::CLIENT_COMMENTED,
            "notificationData" => [
                'client' => $event->client,
                'job' => $event->job
            ]
        ]));
    }
}
