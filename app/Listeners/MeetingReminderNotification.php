<?php

namespace App\Listeners;

use App\Events\MeetingReminderEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

class MeetingReminderNotification implements ShouldQueue
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
     * @param  \App\Events\MeetingReminderEvent  $event
     * @return void
     */
    public function handle(MeetingReminderEvent $event)
    {
        $schedule = $event->schedule;
        $client = $schedule->client;
        $team = $schedule->team;
        //send reminder to team
        if(isset($team['email']) && !empty($team['email'])){
            $scheduleData = $schedule;
            $scheduleData['phone'] = $team['phone'];
            $scheduleData['lng'] = 'en';
            $scheduleData['firstname'] = $team['name'];
            $scheduleData['lastname'] = " ";
            if (isset($scheduleData['phone']) && !empty($scheduleData['phone'])) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::CLIENT_MEETING_REMINDER,
                    "notificationData" => $scheduleData
                ]));
            }
        }
        
        //send reminder to client
        if(isset($client['email']) && !empty($client['email'])){
            $scheduleData = $schedule;
            $scheduleData['phone'] = $client['phone'];
            $scheduleData['lng'] = $client['lng'];
            $scheduleData['firstname'] = $client['firstname'];
            $scheduleData['lastname'] = $client['lastname'];
            if (isset($scheduleData['phone']) && !empty($scheduleData['phone'])) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::CLIENT_MEETING_REMINDER,
                    "notificationData" => $scheduleData
                ]));
            }
        }
    }
}
