<?php

namespace App\Jobs;

use App\Models\Schedule;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Enums\MeetingStatusWithColorIDEnum;
use App\Models\Setting;
use App\Traits\GoogleAPI;
use App\Enums\SettingKeyEnum;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Http\Controllers\GoogleCalendarController;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Models\Admin;
use App\Models\ManageTime;
use App\Models\TeamMemberAvailability;
use App\Models\TeamMemberDefaultAvailability;
use App\Jobs\GetUserCalendarTimezoneJob;
use Carbon\Carbon;

class SaveGoogleCalendarEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, GoogleAPI;

    protected $schedule;

    /**
     * Create a new job instance.
     *
     * @param Schedule $schedule
     * @return void
     */
    public function __construct(Schedule $schedule)
    {
        $this->schedule = $schedule;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('SaveGoogleCalendarEventJob started', ['schedule_id' => $this->schedule->id]);

        $schedule = $this->schedule;

        if (!$schedule->start_date) {
            return;
        }

        $googleAccessToken = Setting::query()
        ->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)
        ->value('value');

        $userTimezone = $this->userCalendarTimezone($googleAccessToken);

        if (!$userTimezone) {
            $refreshToken = Setting::query()
                ->where('key', SettingKeyEnum::GOOGLE_REFRESH_TOKEN)
                ->value('value');

            if (!$refreshToken) {
                throw new Exception('Error: Refresh token not found.');
            }

            $googleClient = $this->getClient();
            $googleClient->refreshToken($refreshToken);
            $newAccessToken = $googleClient->getAccessToken();
            $newRefreshToken = $googleClient->getRefreshToken();

            if (!$newAccessToken) {
                throw new Exception('Error: Failed to refresh access token.');
            }

            Setting::updateOrCreate(
                ['key' => SettingKeyEnum::GOOGLE_ACCESS_TOKEN],
                ['value' => $newAccessToken['access_token']]
            );

            if ($newRefreshToken) {
                Setting::updateOrCreate(
                    ['key' => SettingKeyEnum::GOOGLE_REFRESH_TOKEN],
                    ['value' => $newRefreshToken]
                );
            }

            $googleAccessToken = $newAccessToken['access_token'];
            $userTimezone = $this->userCalendarTimezone($googleAccessToken);
        }

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

         $googleCalendarID = Setting::query()
                ->where('key', SettingKeyEnum::GOOGLE_CALENDAR_ID)
                ->value('value');

        if (!$googleCalendarID) {
            Log::error('No Google Calendar ID found.');
            throw new Exception('No Google Calendar ID found.');
        }
        if ($schedule->is_calendar_event_created) {
            Log::info("Updating event in Google Calendar");

            $url = 'https://www.googleapis.com/calendar/v3/calendars/' . $googleCalendarID . '/events/' . $schedule->google_calendar_event_id;
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $googleAccessToken,
                'Content-Type' => 'application/json',
            ])->put($url, $postData);
        } else {

            Log::info("Creating new event in Google Calendar");

            $url = 'https://www.googleapis.com/calendar/v3/calendars/' . $googleCalendarID . '/events';
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $googleAccessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $postData);
        }

        $data = $response->json();
        $http_code = $response->status();

        if ($http_code != 200) {
            Log::error('Failed to save calendar event', ['http_code' => $http_code, 'response' => $data]);
            throw new Exception('Error: Failed to save calendar event');
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
            Log::error('Failed to get timezone', ['http_code' => $http_code, 'response' => $data]);
            return;
        }

        return $data['value'];
    }
}
