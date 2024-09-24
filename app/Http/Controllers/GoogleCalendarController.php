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
            $googleAccessToken = Setting::query()
                ->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)
                ->value('value');

            $url = 'https://www.googleapis.com/calendar/v3/users/me/calendarList';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $googleAccessToken,
                'Content-Type' => 'application/json',
            ])->get($url);

            $http_code = $response->status();

            if ($http_code != 200) {
                Log::error('Failed to retrieve calendar list', [
                    'http_code' => $http_code,
                    'response' => $response->json(),
                ]);

                throw new Exception('Error: Failed to retrieve calendar list');
            }

            $calendarList = $response->json();

            return $calendarList['items'];

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
                ['key' => 'google_calendar_id'],
                ['value' => $request->calendarId]
            );

            return response()->json(['message' => 'Calendar saved successfully.'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to save calendar.'], 500);
        }
    }
}
