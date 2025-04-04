<?php

namespace App\Http\Controllers;

use App\Enums\SettingKeyEnum;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Admin;
use App\Models\UserSetting;
use App\Traits\GoogleAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GoogleController extends Controller
{
    use GoogleAPI;

    public function callback(Request $request)
    {
        $code = $request->get('code');
        $admin = null;

        // Try retrieving the cached admin ID directly
        $getAdmin = Cache::get('google_connected');

        if ($getAdmin) {
            $admin = Admin::find($getAdmin);
        }

        // $state = $request->get('state');

        // if (Str::startsWith($state, 'SCH-')) {
        //     $scheduleID = Str::replace('SCH-', '', $state);

        //     $schedule = Schedule::find($scheduleID);
        //     if (!$schedule) {
        //         return abort(404);
        //     }

            // Initializes Google Client object
            $googleClient = $this->getClient($admin ? $admin : null);

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

           if(!$admin){
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
           }else{
                UserSetting::updateOrCreate(
                    ['admin_id' => $admin->id, 'key' => SettingKeyEnum::GOOGLE_ACCESS_TOKEN],
                    ['value' => $googleAccessToken]
                );

                if ($refreshToken) {
                    UserSetting::updateOrCreate(
                        ['admin_id' => $admin->id, 'key' => SettingKeyEnum::GOOGLE_REFRESH_TOKEN],
                        ['value' => $refreshToken]
                    );
                }
           }

            return redirect('admin/settings/');
        // } else {
        //     return abort(404);
        // }
    }

    
    

    public function auth(Request $request)
    {
        $admin = auth()->user();

        if($admin->role == 'hr'){
            $googleAccessToken = UserSetting::where('admin_id', $admin->id)
            ->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)
            ->value('value');
            Cache::put('google_connected', $admin->id, now()->addMinute(10));
        }else{
            $googleAccessToken = Setting::query()
            ->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)
            ->value('value');
        }

        $googleClient = $this->getClient($admin);

        $scheduleId = $request->get('schedule_id'); 
        $schedule = Schedule::find($scheduleId);

        if (!$googleAccessToken) {
            // Pass 'state' with schedule ID if it exists
            $authUrl = $googleClient->createAuthUrl(null, [
                'state' => $schedule ? 'SCH-' . $schedule->id : null
            ]);

            return response()->json([
                'action' => 'redirect',
                'url' => $authUrl,
            ]);
        }


        return response()->json([
            'action' => 'connected',
            'message' => 'Google Calendar is already connected.'
        ]);
    }


    public function disconnect()
    {
        $admin = auth()->user();

        if($admin->role == 'hr'){
            UserSetting::where('admin_id', $admin->id)->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)->delete();
            UserSetting::where('admin_id', $admin->id)->where('key', SettingKeyEnum::GOOGLE_REFRESH_TOKEN)->delete();
        }else{
            Setting::where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)->delete();
            Setting::where('key', SettingKeyEnum::GOOGLE_REFRESH_TOKEN)->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Google Calendar has been disconnected and tokens have been removed.'
        ]);
    }
}
