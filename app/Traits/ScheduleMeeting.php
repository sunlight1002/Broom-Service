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
use App\Enums\WhatsappMessageTemplateEnum;
use App\Models\Admin;
use App\Models\ManageTime;
use App\Models\Schedule;
use App\Models\TeamMemberAvailability;
use App\Models\TeamMemberDefaultAvailability;

trait ScheduleMeeting
{
    use GoogleAPI;

    private function sendMeetingMail($schedule)
    {
        $scheduleArr = $schedule->toArray();
        App::setLocale($scheduleArr['client']['lng']);

        $notificationType = $schedule->client->notification_type;

        if ($notificationType === 'both') {
            if (isset($scheduleArr['client']) && !empty($scheduleArr['client']['phone'])) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::CLIENT_MEETING_SCHEDULE,
                    "notificationData" => $scheduleArr
                ]));
            }

            Mail::send('/Mails/MeetingMail', $scheduleArr, function ($messages) use ($scheduleArr) {
                $messages->to($scheduleArr['client']['email']);

                $messages->subject(__('mail.meeting.subject', [
                    'id' => $scheduleArr['id']
                ]));
            });

        } elseif ($notificationType === 'email') {
            Mail::send('/Mails/MeetingMail', $scheduleArr, function ($messages) use ($scheduleArr) {
                $messages->to($scheduleArr['client']['email']);

                $messages->subject(__('mail.meeting.subject', [
                    'id' => $scheduleArr['id']
                ]));
            });

        } elseif ($notificationType === 'whatsapp') {
            if (isset($scheduleArr['client']) && !empty($scheduleArr['client']['phone'])) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::CLIENT_MEETING_SCHEDULE,
                    "notificationData" => $scheduleArr
                ]));
            }
        }

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

    private function deleteGoogleCalendarEvent($schedule)
    {
        $googleCalendarID = config('services.google.calendar_id');

        $googleAccessToken = Setting::query()
            ->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)
            ->value('value');

        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . $googleCalendarID . '/events/' . $schedule->google_calendar_event_id;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $googleAccessToken,
            'Content-Type' => 'application/json',
        ])->delete($url);

        $schedule->update([
            'is_calendar_event_created' => false,
            'google_calendar_event_id' => NULL
        ]);

        // $data = $response->json();
        // $http_code = $response->status();

        // if ($http_code != 200) {
        //     $this->notifyError($http_code);

        //     throw new Exception('Error : Failed to delete calendar event');
        // }
    }

    private function nextAvailableMeetingSlot($inDays = 4)
    {
        $nextAvailableSlot = NULL;

        $manageTime = ManageTime::first();

        $workingWeekDays = array_map('intval', json_decode($manageTime->days, true));

        $teamMembers = Admin::query()
            ->where('name', '!=', 'superadmin')
            ->where('status', 1)
            ->get(['id']);

        $today = today();
        foreach ($teamMembers as $key => $teamMember) {
            if (!is_null($nextAvailableSlot)) {
                break;
            }

            $currentDate = $today->clone();

            $availabilities = TeamMemberAvailability::query()
                ->where('team_member_id', $teamMember->id)
                ->whereDate('date', '>=', date('Y-m-d'))
                ->orderBy('date', 'asc')
                ->orderBy('start_time', 'asc')
                ->get(['team_member_id', 'date', 'start_time', 'end_time']);

            $defaultAvailabilities = TeamMemberDefaultAvailability::query()
                ->where('team_member_id', $teamMember->id)
                ->whereIn('weekday', $workingWeekDays)
                ->get(['team_member_id', 'weekday', 'start_time', 'end_time', 'until_date']);

            $schedules = Schedule::query()
                ->where('team_id', $teamMember->id)
                ->where('booking_status', '!=', 'declined')
                ->whereNotNull('start_date')
                ->whereNotNull('start_time')
                ->whereDate('start_date', '>=', date('Y-m-d'))
                ->orderBy('start_date', 'asc')
                ->select(['team_id', 'start_date', 'start_time_standard_format'])
                ->get();

            for ($i = 0; $i < $inDays; $i++) {
                $currentDateStr = $currentDate->toDateString();

                $currentDayAvails = $availabilities->where('date', $currentDateStr)->values();

                if ($currentDayAvails->count() == 0) {
                    $currentDayAvails = $defaultAvailabilities
                        ->where('weekday', $currentDate->weekday())
                        ->where('until_date', '>=', $currentDateStr)
                        ->values();
                }

                $teamStartTimeArr = $currentDayAvails->pluck('start_time')->toArray();
                $scheduleStartTimeArr = $schedules->where('start_date', $currentDateStr)->pluck('start_time_standard_format')->toArray();

                $timeDiffArr = array_values(array_diff($teamStartTimeArr, $scheduleStartTimeArr));

                if (count($timeDiffArr) > 0) {
                    $nextAvailableSlot = [
                        'team_member_id' => $teamMember->id,
                        'date' => $currentDateStr,
                        'start_time' => $timeDiffArr[0]
                    ];

                    break;
                }

                $currentDate->addDay(1);
            }
        }

        return $nextAvailableSlot;
    }
}
