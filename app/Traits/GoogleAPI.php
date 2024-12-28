<?php

namespace App\Traits;

use App\Enums\SettingKeyEnum;
use App\Models\Setting;
use Exception;
use Illuminate\Support\Facades\Mail;

trait GoogleAPI
{
    /**
     * Gets a google client
     *
     * @return \Google_Client
     * INCOMPLETE
     */
    private function getClient(): \Google_Client
    {
        $settings = Setting::query()
    ->whereIn('key', [
        SettingKeyEnum::GOOGLE_ACCESS_TOKEN,
        SettingKeyEnum::GOOGLE_REFRESH_TOKEN
    ])
    ->get()
    ->pluck('value', 'key')
    ->toArray();

$accessToken = null;
$refreshToken = null;

// Check if access token exists in settings
if (isset($settings[SettingKeyEnum::GOOGLE_ACCESS_TOKEN])) {
    $accessToken = $settings[SettingKeyEnum::GOOGLE_ACCESS_TOKEN];
}

// Check if refresh token exists in settings
if (isset($settings[SettingKeyEnum::GOOGLE_REFRESH_TOKEN])) {
    $refreshToken = $settings[SettingKeyEnum::GOOGLE_REFRESH_TOKEN];
}

$client = new \Google_Client([
    'client_id' => config('services.google.client_id'),
    'client_secret' => config('services.google.client_secret'),
    'redirect_uri' => config('services.google.redirect_uri'),
]);

$applicationName = config('app.name');
$client->setApplicationName($applicationName);

if ($accessToken) {
    $client->setAccessToken($accessToken);

    try {
        if ($client->isAccessTokenExpired() && $refreshToken) {
            // Fetch a new access token using the refresh token
            $response = $client->fetchAccessTokenWithRefreshToken($refreshToken);

            if (isset($response['error'])) {
                // Delete the tokens from the DB if refresh token fails
                Setting::query()->whereIn('key', [
                    SettingKeyEnum::GOOGLE_ACCESS_TOKEN,
                    SettingKeyEnum::GOOGLE_REFRESH_TOKEN
                ])->delete();

                // Notify the user about the error
                Mail::raw("Dear user,\n\rThis email is to inform you about a issue with your website's integration with a Google API. Our systems have detected an error (" . $response['error'] . ") with description - '" . $response['error_description'] . "'", function ($message) {
                    $message->to(config('services.app.notify_failed_process_to'))
                        ->subject(config('app.name') . ' : Google API Error (Access Token)');
                });

                throw new Exception('Error: Failed to fetch google access token');
            }

            // Update access token and refresh token in the settings table
            $accessToken = $response['access_token'];
            Setting::updateOrCreate(
                ['key' => SettingKeyEnum::GOOGLE_ACCESS_TOKEN],
                ['value' => $accessToken]
            );

            if (isset($response['refresh_token'])) {
                $refreshToken = $response['refresh_token'];
                Setting::updateOrCreate(
                    ['key' => SettingKeyEnum::GOOGLE_REFRESH_TOKEN],
                    ['value' => $refreshToken]
                );
            }
        }
    } catch (\Throwable $th) {
        Mail::raw("Dear user,\n\rThis email is to inform you about a issue with your website's integration with a Google API. Our systems have detected an error code - " . $th->getCode() . ".", function ($message) use ($th) {
            $message->to(config('services.app.notify_failed_process_to'))
                ->subject(config('app.name') . ' : Google API Error (' . $th->getCode() . ')');
        });
        // Handle the error (optional: rethrow or log)
    }
}

        // $settings = Setting::query()
        //     ->whereIn('key', [
        //         SettingKeyEnum::GOOGLE_ACCESS_TOKEN,
        //         SettingKeyEnum::GOOGLE_REFRESH_TOKEN
        //     ])
        //     ->get()
        //     ->pluck('value', 'key')
        //     ->toArray();

        // $accessToken = NULL;
        // $refreshToken = NULL;
        // if (isset($settings[SettingKeyEnum::GOOGLE_ACCESS_TOKEN])) {
        //     $accessToken = $settings[SettingKeyEnum::GOOGLE_ACCESS_TOKEN];
        //     $refreshToken = $settings[SettingKeyEnum::GOOGLE_REFRESH_TOKEN];
        // }

        // // define an application name
        // $applicationName = config('app.name');

        // // create the client
        // $client = new \Google_Client([
        //     'client_id' => config('services.google.client_id'),
        //     'client_secret' => config('services.google.client_secret'),
        //     'redirect_uri' => config('services.google.redirect_uri'), // Replace with your redirect URI
        // ]);
        // $client->setApplicationName($applicationName);

        // if ($accessToken) {
        //     $client->setAccessToken($accessToken);

        //     try {
        //         // If the access token is expired, it will automatically refresh using the refresh token
        //         if ($client->isAccessTokenExpired()) {
        //             $response = $client->fetchAccessTokenWithRefreshToken($refreshToken);

        //             if (isset($response['error'])) {
        //                 // Remove google token from DB, to re-initiate.
        //                 Setting::query()->whereIn('key', [
        //                         SettingKeyEnum::GOOGLE_ACCESS_TOKEN,
        //                         SettingKeyEnum::GOOGLE_REFRESH_TOKEN
        //                     ])->delete();

        //                 Mail::raw("Dear user,\n\rThis email is to inform you about a issue with your website's integration with a Google API. Our systems have detected an error (" . $response['error'] . ") with description - '" . $response['error_description'] . "'", function ($message) {
        //                     $message->to(config('services.app.notify_failed_process_to'))
        //                         ->subject(config('app.name') . ' : Google API Error (Access Token)');
        //                 });

        //                 throw new Exception('Error : Failed to fetch google access token');
        //             }

        //             $accessToken = $response['access_token'];
        //             Setting::updateOrCreate(
        //                 ['key' => SettingKeyEnum::GOOGLE_ACCESS_TOKEN],
        //                 ['value' => $accessToken]
        //             );

        //             $refreshToken = $response['refresh_token'];
        //             if ($refreshToken) {
        //                 Setting::updateOrCreate(
        //                     ['key' => SettingKeyEnum::GOOGLE_REFRESH_TOKEN],
        //                     ['value' => $refreshToken]
        //                 );
        //             }
        //         }
        //     } catch (\Throwable $th) {
        //         Mail::raw("Dear user,\n\rThis email is to inform you about a issue with your website's integration with a Google API. Our systems have detected an error code - " . $th->getCode() . ".", function ($message) use ($th) {
        //             $message->to(config('services.app.notify_failed_process_to'))
        //                 ->subject(config('app.name') . ' : Google API Error (' . $th->getCode() . ')');
        //         });
        //         // throw $th;
        //     }
        // }

        $client->setAccessType('offline'); // necessary for getting the refresh token
        $client->setApprovalPrompt('force'); // necessary for getting the refresh token

        // scopes determine what google endpoints we can access. keep it simple for now.
        $client->setScopes(
            [
                // \Google\Service\Oauth2::USERINFO_PROFILE,
                // \Google\Service\Oauth2::USERINFO_EMAIL,
                // \Google\Service\Oauth2::OPENID,
                \Google\Service\Calendar::CALENDAR_EVENTS,
                \Google\Service\Calendar::CALENDAR_SETTINGS_READONLY,
                \Google\Service\Calendar::CALENDAR_READONLY,
                \Google\Service\PeopleService::CONTACTS,                // Full access to contacts
                \Google\Service\PeopleService::CONTACTS_READONLY,       // Read-only access to contacts
                \Google\Service\PeopleService::CONTACTS_OTHER_READONLY, // Read-only access to "Other Contacts"
            ]
        );
        // $client->setIncludeGrantedScopes(true);
        return $client;
    }

    private function notifyError($http_code)
    {
        try {
            // notify about Google API failure
            if ($http_code == 401) {
                Mail::raw("Dear user,\n\rThis email is to inform you about a potential issue with your website's integration with a Google API. Our systems have detected an error code 401 (Unauthorized), which indicates that the API is unable to authenticate your website's access.", function ($message) use ($http_code) {
                    $message->to(config('services.app.notify_failed_process_to'))
                        ->subject(config('app.name') . ' : Google API Error (' . $http_code . ' - Unauthorized)');
                });
            } else if ($http_code == 403) {
                Mail::raw("Dear user,\n\rThis email is to inform you about a potential issue with your website's interaction with a Google API. We've detected an error code 403 (Rate Limit Exceeded), which indicates your website has exceeded the Google Calendar API's maximum request rate per calendar or per authenticated user.", function ($message) use ($http_code) {
                    $message->to(config('services.app.notify_failed_process_to'))
                        ->subject(config('app.name') . ' : Google API Error (' . $http_code . ' - Rate Limit Exceeded)');
                });
            } else if ($http_code == 404) {
                Mail::raw("Dear user,\n\rThis email is to inform you about a issue with your website's interaction with a Google API. It states that specified resource not found.", function ($message) use ($http_code) {
                    $message->to(config('services.app.notify_failed_process_to'))
                        ->subject(config('app.name') . ' : Google API Error (' . $http_code . ' - Not Found)');
                });
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
