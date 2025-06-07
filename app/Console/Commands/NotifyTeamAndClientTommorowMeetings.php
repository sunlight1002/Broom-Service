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
        // $count = 1;

        if ($schedules->isNotEmpty()) {
            foreach ($schedules as $schedule) {
                $propertyAddress = $schedule->propertyAddress;
                $client = $schedule->client;

                if (!$propertyAddress || !$client) {
                    continue;
                }

                $fullAddressParts = [];

                if (!empty($propertyAddress->geo_address)) {
                    $fullAddressParts[] = $propertyAddress->geo_address;
                }
                if (!empty($propertyAddress->apt_no)) {
                    $fullAddressParts[] = 'דירה ' . $propertyAddress->apt_no;
                }
                if (!empty($propertyAddress->floor)) {
                    $fullAddressParts[] = 'קומה ' . $propertyAddress->floor;
                }
                if (!empty($propertyAddress->city)) {
                    $fullAddressParts[] = $propertyAddress->city;
                }
                if (!empty($propertyAddress->zipcode)) {
                    $fullAddressParts[] = $propertyAddress->zipcode;
                }
                $geoAddress = urlencode($propertyAddress->geo_address);

                $fullAddress = implode(', ', array_reverse($fullAddressParts)); // for RTL
                $zoom = 17;

                // Determine the map link
                if (!empty($propertyAddress->latitude) && !empty($propertyAddress->longitude)) {
                    $mapLink = "https://www.google.com/maps/place/{$geoAddress}/@{$propertyAddress->latitude},{$propertyAddress->longitude},{$zoom}z";
                } else {
                    $mapLink = "https://www.google.com/maps/search/?api=1&query=" . $geoAddress;
                }

                $shortMapLink = generateShortUrl(url($mapLink), 'admin');
                $leadDetailLink = generateShortUrl(url("admin/leads/view/" . $client->id), 'admin');

                $TeamMessage = "*פגישה עם {$client->firstname} {$client->lastname}*" . "  
- *שעה*: " . Carbon::parse($schedule->start_date ?? "00-00-0000")->format('M d Y') . " " . ($schedule->start_time ?? '') . "  
- *מיקום*: {$schedule->meet_link}
- *נושא הפגישה*: {$schedule->purpose}
- *פרטי קשר של הלקוח*: {$client->phone} / {$client->email}
- *הלקוח לְקַשֵׁר*: {$leadDetailLink}*
- *כתובת*: {$fullAddress}
- *קישור למפה*: {$shortMapLink}\n";

                $this->notifyClient($schedule);
                $this->notifyTeam($TeamMessage);
            }

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
