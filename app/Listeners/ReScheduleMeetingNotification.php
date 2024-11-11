<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\ReScheduleMeetingJob;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class ReScheduleMeetingNotification implements ShouldQueue
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
     * @param  object  $event
     * @return void
     */
    public function handle(ReScheduleMeetingJob $event)
    {
        $schedules = $event->schedules;
        $scheduleArr = $schedules->toArray();
        App::setLocale($scheduleArr['client']['lng']);

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::ADMIN_RESCHEDULE_MEETING,
            "notificationData" => $scheduleArr
        ]));
        if (!empty($schedules->start_time) && !empty($schedules->end_time)) {
            Notification::create([
                'user_id' => $schedules->client_id,
                'user_type' => Client::class,
                'type' => NotificationTypeEnum::RESCHEDULE_MEETING,
                'meet_id' => $schedules->id,
                'status' => $schedules->booking_status
            ]);
        }
    }
}
