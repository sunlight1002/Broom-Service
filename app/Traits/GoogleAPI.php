<?php

namespace App\Traits;

use App\Enums\SettingKeyEnum;
use App\Models\Setting;

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

        $accessToken = NULL;
        $refreshToken = NULL;
        if (isset($settings[SettingKeyEnum::GOOGLE_ACCESS_TOKEN])) {
            $accessToken = $settings[SettingKeyEnum::GOOGLE_ACCESS_TOKEN];
            $refreshToken = $settings[SettingKeyEnum::GOOGLE_REFRESH_TOKEN];
        }

        // define an application name
        $applicationName = config('app.name');

        // create the client
        $client = new \Google_Client([
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.redirect_uri'), // Replace with your redirect URI
        ]);
        $client->setApplicationName($applicationName);

        if ($accessToken) {
            $client->setAccessToken($accessToken);

            // If the access token is expired, it will automatically refresh using the refresh token
            if ($client->isAccessTokenExpired()) {
                $response = $client->fetchAccessTokenWithRefreshToken($refreshToken);
                $accessToken = $response['access_token'];
                Setting::updateOrCreate(
                    ['key' => SettingKeyEnum::GOOGLE_ACCESS_TOKEN],
                    ['value' => $accessToken]
                );

                $refreshToken = $response['refresh_token'];
                if ($refreshToken) {
                    Setting::updateOrCreate(
                        ['key' => SettingKeyEnum::GOOGLE_REFRESH_TOKEN],
                        ['value' => $refreshToken]
                    );
                }
            }
        }

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
            ]
        );
        // $client->setIncludeGrantedScopes(true);
        return $client;
    }
}
