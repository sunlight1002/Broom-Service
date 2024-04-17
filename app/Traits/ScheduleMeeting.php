<?php

namespace App\Traits;

use App\Enums\SettingKeyEnum;
use App\Enums\MeetingStatusWithColorIDEnum;
use App\Events\WhatsappNotificationEvent;
use App\Models\Setting;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

trait ScheduleMeeting
{
    use GoogleAPI;

    private function sendMeetingMail($schedule)
    {
        $scheduleArr = $schedule->toArray();
        App::setLocale($scheduleArr['client']['lng']);
        if (isset($scheduleArr['client']) && !empty($scheduleArr['client']['phone'])) {
            event(new WhatsappNotificationEvent([
                "type" => 'client_meeting_schedule',
                "notificationData" => $scheduleArr
            ]));
        }

        Mail::send('/Mails/MeetingMail', $scheduleArr, function ($messages) use ($scheduleArr) {
            $messages->to($scheduleArr['client']['email']);

            if ($scheduleArr['client']['lng'] == 'en') {
                $subject = __('mail.meeting.subject') . " " . __('mail.meeting.from') . " " . __('mail.meeting.company') . " #" . $scheduleArr['id'];
            } else {
                $subject = $scheduleArr['id'] . "# " . __('mail.meeting.subject') . " " . __('mail.meeting.from') . " " . __('mail.meeting.company');
            }

            $messages->subject($subject);
        });

        $schedule->update(['meeting_mail_sent_at' => now()]);
    }

    private function saveGoogleCalendarEvent($schedule)
    {
        if (!$schedule->start_date) {
            return NULL;
        }

        $googleAccessToken = Setting::query()
            ->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)
            ->value('value');

        $userTimezone = $this->userCalendarTimezone($googleAccessToken);

        $eventTitle = "Meeting with " . $schedule->client->firstname . " " . $schedule->client->lastname;
        $clientPhone = (!empty($schedule->client->phone)) ? $schedule->client->phone : 'phone N/A';

        $eventDate = Carbon::parse($schedule->start_date)->toDateString();
        if (!empty($schedule->start_time) && !empty($schedule->end_time)) {
            $description = "Between " . $schedule->start_time . " - " . $schedule->end_time . " <br>" . $schedule->client->email . " <br> " . $clientPhone;

            $fullDayEvent = false;

            $eventTime = [
                'event_date' => $eventDate,
                'event_start_at' => Carbon::createFromFormat('Y-m-d H:i A', $eventDate . ' ' . $schedule->start_time)->toRfc3339String(),
                'event_end_at' => Carbon::createFromFormat('Y-m-d H:i A', $eventDate . ' ' . $schedule->end_time)->toRfc3339String(),
            ];
        } else {
            $description = $schedule->client->email . " <br> " . $clientPhone;

            $fullDayEvent = true;

            $eventTime = [
                'event_date' => $eventDate,
            ];
        }

        $postData = [
            'summary' => $eventTitle,
            'description' => $description,
            'start' => [
                'timeZone' => $userTimezone,
            ],
            'end' => [
                'timeZone' => $userTimezone,
            ],
            'colorId' => MeetingStatusWithColorIDEnum::status[$schedule->booking_status],
            // 'attendees' => array(
            //     array('email' => 'demo01@example.com'),
            //     array('email' => 'demo02@example.com'),
            // ),
            'location' => $schedule->propertyAddress->geo_address,
            'reminders' => [
                'useDefault' => FALSE,
                'overrides' => [
                    ['method' => 'email', 'minutes' => 24 * 60],
                    ['method' => 'popup', 'minutes' => 10],
                ],
            ],
        ];

        if ($fullDayEvent) {
            $postData['start']['date'] = $eventTime['event_date'];
            $postData['end']['date'] = $eventTime['event_date'];
        } else {
            $postData['start']['dateTime'] = $eventTime['event_start_at'];
            $postData['end']['dateTime'] = $eventTime['event_end_at'];
        }

        $googleCalendarID = config('services.google.calendar_id');

        if ($schedule->is_calendar_event_created) {
            $url = 'https://www.googleapis.com/calendar/v3/calendars/' . $googleCalendarID . '/events/' . $schedule->google_calendar_event_id;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $googleAccessToken,
                'Content-Type' => 'application/json',
            ])->put(
                $url,
                $postData
            );
        } else {
            $url = 'https://www.googleapis.com/calendar/v3/calendars/' . $googleCalendarID . '/events';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $googleAccessToken,
                'Content-Type' => 'application/json',
            ])->post(
                $url,
                $postData
            );
        }

        $data = $response->json();
        $http_code = $response->status();

        if ($http_code != 200) {
            $this->notifyError($http_code);

            throw new Exception('Error : Failed to save calendar event');
        }

        if (!$schedule->is_calendar_event_created) {
            $schedule->update([
                'is_calendar_event_created' => true,
                'google_calendar_event_id' => $data['id']
            ]);
        }
    }

    private function userCalendarTimezone($access_token)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
        ])->get('https://www.googleapis.com/calendar/v3/users/me/settings/timezone');

        $data = $response->json();
        $http_code = $response->status();

        if ($http_code != 200) {
            $this->notifyError($http_code);

            throw new Exception('Error : Failed to get timezone');
        }

        return $data['value'];
    }

    private function deleteGoogleCalendarEvent($eventId)
    {
        $googleCalendarID = config('services.google.calendar_id');

        $googleAccessToken = Setting::query()
            ->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)
            ->value('value');

        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . $googleCalendarID . '/events/' . $eventId;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $googleAccessToken,
            'Content-Type' => 'application/json',
        ])->delete($url);

        // $data = $response->json();
        // $http_code = $response->status();

        // if ($http_code != 200) {
        //     $this->notifyError($http_code);

        //     throw new Exception('Error : Failed to delete calendar event');
        // }
    }
}
