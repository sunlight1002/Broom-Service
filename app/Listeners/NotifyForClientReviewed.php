<?php

namespace App\Listeners;

use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\ClientReviewed;
use App\Events\WhatsappNotificationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;

class NotifyForClientReviewed implements ShouldQueue
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
     * @param  \App\Events\ClientReviewed  $event
     * @return void
     */
    public function handle(ClientReviewed $event)
    {
        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::CLIENT_REVIEWED,
            "notificationData" => [
                'client' => $event->client->toArray(),
                'job' => $event->job->toArray()
            ]
        ]));
    }
}
