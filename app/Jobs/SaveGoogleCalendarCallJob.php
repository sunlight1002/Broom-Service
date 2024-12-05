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

class SaveGoogleCalendarCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, GoogleAPI;

    protected $schedule;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($schedule)
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
        $schedule = $this->schedule;

        \Log::info(['schedule' => $schedule]);

        // If no start date provided, don't proceed
        if (!$schedule['schedule']['start_date']) {
            return;
        }

        // Get the Google Access Token
        $googleAccessToken = Setting::query()
            ->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)
            ->value('value');

        // Get the user’s calendar timezone
        $userTimezone = $this->userCalendarTimezone($googleAccessToken);

        // If timezone is not available, refresh the token
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

            // Update the access and refresh tokens in settings
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

        // Prepare event details from the provided schedule data
        $eventTitle = "Meeting with " . $schedule['schedule']['client']['firstname'] . " " . $schedule['schedule']['client']['lastname'];
        $clientPhone = (!empty($schedule['schedule']['client']['phone'])) ? $schedule['schedule']['client']['phone'] : 'phone N/A';
        $eventDate = Carbon::parse($schedule['schedule']['start_date'])->toDateString();

        $description = "Between " . $schedule['schedule']['start_time'] . " - " . $schedule['schedule']['end_time'] . " <br>" . $schedule['schedule']['client']['email'] . " <br> " . $clientPhone;

        $fullDayEvent = false;

        $eventTime = [
            'event_date' => $eventDate,
            'event_start_at' => Carbon::createFromFormat('Y-m-d H:i', $eventDate . ' ' . $schedule['schedule']['start_time'])->toRfc3339String(),
            'event_end_at' => Carbon::createFromFormat('Y-m-d H:i', $eventDate . ' ' . $schedule['schedule']['end_time'])->toRfc3339String(),
        ];        


        if (!empty($schedule['schedule']['start_time']) && !empty($schedule['schedule']['end_time'])) {
            $eventTime = [
                'event_date' => $eventDate,
                'event_start_at' => Carbon::createFromFormat('Y-m-d H:i', $eventDate . ' ' . $schedule['schedule']['start_time'])->toRfc3339String(),
                'event_end_at' => Carbon::createFromFormat('Y-m-d H:i', $eventDate . ' ' . $schedule['schedule']['end_time'])->toRfc3339String(),
            ];
        } else {
            Log::error('Invalid start time or end time.');
            return;  // Or handle the error appropriately
        }
        

        // Prepare the Google Calendar event data
        $postData = [
            'summary' => $eventTitle,
            'description' => $description,
            'start' => [
                'timeZone' => $userTimezone,
            ],
            'end' => [
                'timeZone' => $userTimezone,
            ],
            'colorId' => "6", // Example color ID, you can adjust as needed
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'email', 'minutes' => 24 * 60],
                    ['method' => 'popup', 'minutes' => 10],
                ],
            ],
        ];

        // If it's a full day event
        if ($fullDayEvent) {
            $postData['start']['date'] = $eventTime['event_date'];
            $postData['end']['date'] = $eventTime['event_date'];
        } else {
            $postData['start']['dateTime'] = $eventTime['event_start_at'];
            $postData['end']['dateTime'] = $eventTime['event_end_at'];
        }

        // Get Google Calendar ID
        $googleCalendarID = Setting::query()
            ->where('key', SettingKeyEnum::GOOGLE_CALENDAR_ID)
            ->value('value');

        if (!$googleCalendarID) {
            Log::error('No Google Calendar ID found.');
            return; // Handle the error appropriately
        }

        Log::info("Creating new event in Google Calendar");

        // Create new event
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . $googleCalendarID . '/events';
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $googleAccessToken,
            'Content-Type' => 'application/json',
        ])->post($url, $postData);

        $data = $response->json();
        $http_code = $response->status();

        if ($http_code != 200) {
            Log::error('Failed to save calendar event', ['http_code' => $http_code, 'response' => $data]);
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
            return null;
        }

        return $data['value'];
    }
}
