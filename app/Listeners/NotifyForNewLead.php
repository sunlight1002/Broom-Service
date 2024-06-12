<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\NewLeadArrived;
use App\Events\WhatsappNotificationEvent;
use App\Models\Notification;

class NotifyForNewLead implements ShouldQueue
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
     * @param  \App\Events\NewLeadArrived  $event
     * @return void
     */
    public function handle(NewLeadArrived $event)
    {
        Notification::create([
            'user_id' => $event->client->id,
            'user_type' => get_class($event->client),
            'type' => NotificationTypeEnum::NEW_LEAD_ARRIVED,
            'status' => 'created'
        ]);

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::NEW_LEAD_ARRIVED,
            "notificationData" => [
                'client' => $event->client
            ]
        ]));
    }
}
