<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Schedule;
use Carbon\Carbon;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

class NotifyTeamAndClientTommorowMeetings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifyTeamAndClientTommorowMeeting';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify team and client about meetings scheduled for tomorrow that are on-site at 7:00 PM in the evening';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tomorrow = Carbon::tomorrow()->toDateString();

        // Retrieve schedules that are scheduled for tomorrow and meet_via is "on-site"
        $schedules = Schedule::with(['client', 'propertyAddress'])
                    ->where('start_date', $tomorrow)
                    ->where('meet_via', 'on-site')
                    ->get();

        $TeamMessage = "";
        $count = 1;

        if ($schedules->isNotEmpty()) {
            foreach ($schedules as $schedule) {
                $geoAddress = urlencode($schedule->propertyAddress->geo_address); // Encode the address
                $TeamMessage .= "$count. *פגישה עם " . $schedule->client->firstname ." " .$schedule->client->lastname ."*"."  
 - *שעה*: " . Carbon::parse($schedule->start_date ?? "00-00-0000")->format('M d Y') . " " .  ($schedule->start_time ?? '') . "  
 - *מיקום*: " . $schedule->meet_link ."
 - *נושא הפגישה*: " . $schedule->purpose ."
 - *פרטי קשר של הלקוח*: " . $schedule->client->phone ." / " . $schedule->client->email . "
 - *כתובת*: " . url("https://maps.google.com?q=" . $geoAddress) . "\n";

                $count += 1;
                
                $geoAddress = url("https://maps.google.com?q=". $schedule->client->geo_address);
            $this->notifyClient($schedule);
            }
            
            $this->notifyTeam($TeamMessage);
            $this->info("Notifications sent for tomorrow's on-site meetings.");
        } else {
            $this->info("No on-site meetings scheduled for tomorrow.");
        }

        return 0;
    }

    /**
     * Notify the team and client about the meeting.
     *
     * @param Schedule $schedule
     * @return void
     */
    private function notifyTeam($TeamMessage)
    {
        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::NOTIFY_TEAM_FOR_TOMMOROW_MEETINGS,
            "notificationData" => [
                "all_meetings" => $TeamMessage
            ]
        ]));
        // $this->info("Notifying for meeting with ID: {$schedule->id}");
    }

    private function notifyClient($schedule)
    {
        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::NOTIFY_CLIENT_FOR_TOMMOROW_MEETINGS,
            "notificationData" => $schedule
        ]));
        // $this->info("Notifying for meeting with ID: {$schedule->id}");
    }
}
