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
use App\Jobs\SaveGoogleCalendarEventJob;
use App\Jobs\GetUserCalendarTimezoneJob;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\GoogleCalendarController;

trait ScheduleMeeting
{
    use GoogleAPI;

    private function saveGoogleCalendarEvent($schedule)
    {
        try {
            SaveGoogleCalendarEventJob::dispatch($schedule);
        } catch (\Throwable $th) {
            Log::error($th);
        }
    }

    // private function userCalendarTimezone($access_token)
    // {
    //     $response = Http::withHeaders([
    //         'Authorization' => 'Bearer ' . $access_token,
    //     ])->get('https://www.googleapis.com/calendar/v3/users/me/settings/timezone');

    //     $data = $response->json();
    //     $http_code = $response->status();

    //     if ($http_code != 200) {
    //         $this->notifyError($http_code);

    //         throw new Exception('Error : Failed to get timezone');
    //     }

    //     return $data['value'];
    // }

    private function deleteGoogleCalendarEvent($schedule)
    {
        $googleCalendarID = Setting::query()
                ->where('key', SettingKeyEnum::GOOGLE_CALENDAR_ID)
                ->value('value');

        if (!$googleCalendarID) {
            Log::error('No Google Calendar ID found.');
        }
        try {
            $googleAccessToken = Setting::query()
                ->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)
                ->value('value');

            $url = 'https://www.googleapis.com/calendar/v3/calendars/' . $googleCalendarID . '/events/' . $schedule->google_calendar_event_id;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $googleAccessToken,
                'Content-Type' => 'application/json',
            ])->delete($url);
        } catch (\Throwable $th) {
            Log::error($th);
        }

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
