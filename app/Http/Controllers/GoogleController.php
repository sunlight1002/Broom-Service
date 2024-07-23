<?php

namespace App\Http\Controllers;

use App\Enums\SettingKeyEnum;
use App\Models\Schedule;
use App\Models\Setting;
use App\Traits\GoogleAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    use GoogleAPI;

    public function callback(Request $request)
    {
        $code = $request->get('code');
        $state = $request->get('state');

        if (Str::startsWith($state, 'SCH-')) {
            $scheduleID = Str::replace('SCH-', '', $state);

            $schedule = Schedule::find($scheduleID);
            if (!$schedule) {
                return abort(404);
            }

            // Initializes Google Client object
            $googleClient = $this->getClient();

            /**
             * Exchange auth code for access token
             * Note: if we set 'access type' to 'force' and our access is 'offline', we get a refresh token. we want that.
             */
            $response = $googleClient->fetchAccessTokenWithAuthCode($code);
            $googleAccessToken = $response['access_token'];

            if (!$googleAccessToken) {
                throw new Exception('Error: Google access token not found.');
            }

            $refreshToken = $googleClient->getRefreshToken();

            Setting::updateOrCreate(
                ['key' => SettingKeyEnum::GOOGLE_ACCESS_TOKEN],
                ['value' => $googleAccessToken]
            );

            if ($refreshToken) {
                Setting::updateOrCreate(
                    ['key' => SettingKeyEnum::GOOGLE_REFRESH_TOKEN],
                    ['value' => $refreshToken]
                );
            }

            return redirect('admin/schedule/view/' . $schedule->client_id . '?sid=' . $schedule->id . '&action=create-calendar-event');
        } else {
            return abort(404);
        }
    }
}
