<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\ClientLeadStatusChanged;
use App\Events\WhatsappNotificationEvent;
use App\Models\Notification;

class NotifyForLeadStatusChanged implements ShouldQueue
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
     * @param  \App\Events\ClientLeadStatusChanged  $event
     * @return void
     */
    public function handle(ClientLeadStatusChanged $event)
    {
        // Notification::create([
        //     'user_id' => $event->client->id,
        //     'user_type' => get_class($event->client),
        //     'type' => NotificationTypeEnum::CLIENT_LEAD_STATUS_CHANGED,
        //     'status' => 'changed',
        //     'data' => [
        //         'new_status' => $event->newStatus
        //     ]
        // ]);

        // event(new WhatsappNotificationEvent([
        //     "type" => WhatsappMessageTemplateEnum::CLIENT_LEAD_STATUS_CHANGED,
        //     "notificationData" => [
        //         'client' => $event->client,
        //         'new_status' => $event->newStatus
        //     ]
        // ]));
    }
}
