<?php

namespace App\Listeners;

use App\Events\MeetingReminderEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

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
            App::setLocale('lng');
            Mail::send('/Mails/team/MeetingReminder', [
                'schedule'  => $schedule, 
                'client'    => $client, 
                'team'      => $team,
            ], function ($messages) use ($team) {
                $messages->to($team['email']);
                $messages->subject('Meeting Remider');
            });
        }
        
        //send reminder to client
        if(isset($client['email']) && !empty($client['email'])){
            App::setLocale($client['lng']);
            Mail::send('/Mails/client/MeetingReminder', [
                'schedule'  => $schedule, 
                'client'    => $client, 
                'team'      => $team,
            ], function ($messages) use ($client) {
                $messages->to($client['email']);
                $messages->subject('Meeting Remider');
            });
        }
    }
}
