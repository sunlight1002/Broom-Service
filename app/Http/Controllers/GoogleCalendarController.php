<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Setting;
use App\Enums\SettingKeyEnum;
use Exception;

class GoogleCalendarController extends Controller
{
    public function getGoogleCalendarList()
    {
        try {
            // Fetch access token and refresh token from the database
            $googleAccessToken = Setting::query()
                ->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)
                ->value('value');

            $googleRefreshToken = Setting::query()
                ->where('key', SettingKeyEnum::GOOGLE_REFRESH_TOKEN)
                ->value('value');

            $url = 'https://www.googleapis.com/calendar/v3/users/me/calendarList';

            // Try to get the calendar list with the current access token
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $googleAccessToken,
                'Content-Type' => 'application/json',
            ])->get($url);

            $http_code = $response->status();

            // If the token is expired (HTTP 401 or 403), refresh the token
            if ($http_code == 401 || $http_code == 403) {
                // Refresh the access token
                $googleClient = $this->getClient();
                $googleClient->refreshToken($googleRefreshToken);
                $response = $googleClient->fetchAccessTokenWithRefreshToken($googleRefreshToken);
                $googleAccessToken = $response['access_token'];

                // Save the new access token
                Setting::updateOrCreate(
                    ['key' => SettingKeyEnum::GOOGLE_ACCESS_TOKEN],
                    ['value' => $googleAccessToken]
                );

                // Retry the request with the new access token
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $googleAccessToken,
                    'Content-Type' => 'application/json',
                ])->get($url);

                $http_code = $response->status();
            }

            if ($http_code != 200) {
                Log::error('Failed to retrieve calendar list', [
                    'http_code' => $http_code,
                    'response' => $response->json(),
                ]);

                throw new Exception('Error: Failed to retrieve calendar list');
            }

            $googleCalendarId = Setting::query()
                ->where('key', SettingKeyEnum::GOOGLE_CALENDAR_ID)
                ->value('value');

            $calendarList = $response->json();

            return response()->json(['items' => $calendarList['items'], 'selectedCalendarId' => $googleCalendarId], 200);
        } catch (Exception $e) {
            Log::error('Error retrieving calendar list: ' . $e->getMessage());
            throw $e;
        }
    }


    public function saveCalendar(Request $request)
    {
        $request->validate([
            'calendarId' => 'required|string',
        ]);

        try {
            Setting::updateOrCreate(
                ['key' => SettingKeyEnum::GOOGLE_CALENDAR_ID],
                ['value' => $request->calendarId]
            );

            return response()->json(['message' => 'Calendar saved successfully.'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to save calendar.'], 500);
        }
    }
}
