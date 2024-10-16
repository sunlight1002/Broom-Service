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
        Notification::create([
            'user_id' => $event->client->id,
            'user_type' => get_class($event->client),
            'type' => NotificationTypeEnum::CLIENT_LEAD_STATUS_CHANGED,
            'status' => 'changed',
            'data' => [
                'new_status' => $event->newStatus
            ]
        ]);

        if($event->newStatus === 'freeze client'){
            event(new WhatsappNotificationEvent([
               "type" => WhatsappMessageTemplateEnum::CLIENT_IN_FREEZE_STATUS,
               "notificationData" => [
                   'client' => $event->client->toArray(),
               ]
           ]));
       }

        if ($event->newStatus === 'uninterested') {

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::FOLLOW_UP_ON_OUR_CONVERSATION,
                "notificationData" => [
                    'client' => $event->client->toArray(),
                ]
            ]));

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::UNINTERESTED,
                "notificationData" => [
                    'client' => $event->client->toArray(),
                ]
            ]));

        }

        if ($event->newStatus === 'unanswered') {

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
                "notificationData" => [
                    'client' => $event->client->toArray(),
                ]
            ]));

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::UNANSWERED,
                "notificationData" => [
                    'client' => $event->client->toArray(),
                ]
            ]));
        }

        if ($event->newStatus === 'irrelevant') {

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
                "notificationData" => [
                    'client' => $event->client->toArray(),
                ]
            ]));

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::IRRELEVANT,
                "notificationData" => [
                    'client' => $event->client->toArray(),
                ]
                ]));
        };

        if ($event->newStatus === 'pending') {

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::PENDING,
                "notificationData" => [
                    'client' => $event->client->toArray(),
                ]
            ]));
        };

        if ($event->newStatus === 'potential') {

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::POTENTIAL,
                "notificationData" => [
                    'client' => $event->client->toArray(),
                ]
            ]));
        };

        if ($event->newStatus === 'potential client') {

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::POTENTIAL_CLIENT,
                "notificationData" => [
                    'client' => $event->client->toArray(),
                ]
            ]));
        };

        if ($event->newStatus === 'pending client') {

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::PENDING_CLIENT,
                "notificationData" => [
                    'client' => $event->client->toArray(),
                ]
            ]));
        };

        if ($event->newStatus === 'waiting') {

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::WAITING,
                "notificationData" => [
                    'client' => $event->client->toArray(),
                ]
            ]));
        };

        if ($event->newStatus === 'active client') {

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::ACTIVE_CLIENT,
                "notificationData" => [
                    'client' => $event->client->toArray(),
                ]
            ]));
        };

        if ($event->newStatus === 'unhappy') {

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::UNHAPPY,
                "notificationData" => [
                    'client' => $event->client->toArray(),
                ]
            ]));
        };

        if ($event->newStatus === 'price issue') {

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::PRICE_ISSUE,
                "notificationData" => [
                    'client' => $event->client->toArray(),
                ]
            ]));
        };

        if ($event->newStatus === 'moved') {

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::MOVED,
                "notificationData" => [
                    'client' => $event->client->toArray(),
                ]
            ]));
        };

        if ($event->newStatus === 'one-time') {

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::ONETIME,
                "notificationData" => [
                    'client' => $event->client->toArray(),
                ]
            ]));
        };
    }
}
