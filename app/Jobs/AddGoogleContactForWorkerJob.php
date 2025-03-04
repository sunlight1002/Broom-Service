<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Admin;
use App\Models\UserGoogleContact;
use App\Enums\SettingKeyEnum;
use App\Traits\GoogleAPI;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use App\Models\UserSetting;
use Exception;

class AddGoogleContactForWorkerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, GoogleAPI;

    protected $worker;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $worker)
    {
        $this->worker = $worker;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $worker = $this->worker;

        $googleAccessToken = Setting::query()
            ->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)
            ->value('value');


        // Fetch all HRs and their individual Google Access Tokens
        $allHr = Admin::where('role', 'hr')->get();
        $hrGoogleAccessTokens = [];

        foreach ($allHr as $hr) {
            $token = UserSetting::where('admin_id', $hr->id)
                ->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)
                ->value('value');

            if ($token) {
                $hrGoogleAccessTokens[$hr->id] = $token;  // Store with HR ID as key
            }
        }


        // if (!$googleAccessToken) {
        //     throw new Exception('Error: Google Access Token not found.');
        // }

        $contactData = [
            'names' => [
                [
                    'givenName' => $worker->firstname,
                    'familyName' => $worker->lastname,
                ]
            ],
            'phoneNumbers' => [
                [
                    'value' => $worker->phone
                ]
            ],
            'emailAddresses' => [
                [
                    'value' => $worker->email
                ]
            ]
        ];

        if ($worker->contactId) {
            $contactDetails = $this->getGoogleContact($worker->contactId, $googleAccessToken);
    
            if (isset($contactDetails['etag'])) {
                $contactData['etag'] = $contactDetails['etag'];
            }
    
            $updateResponse = $this->updateGoogleContact($worker->contactId, $contactData, $googleAccessToken);
    
        } else {
            $contactId = $this->createGoogleContact($contactData, $googleAccessToken);
            if ($contactId) {
                $worker->update(['contactId' => $contactId]);
            }
        }   

         // Process for each HR's Google account
        foreach ($hrGoogleAccessTokens as $hrId => $hrGoogleAccessToken) {

            if (UserGoogleContact::where('user_id', $worker->id)->where('admin_id', $hrId)->exists()){
                $contactDetails = $this->getGoogleContact($worker->contactId, $hrGoogleAccessToken, $hrId);
                if (isset($contactDetails['etag'])) {
                    $contactData['etag'] = $contactDetails['etag'];
                }
                $this->updateGoogleContact($worker->contactId, $contactData, $hrGoogleAccessToken, $hrId);
            } else {
                \Log::info("else for HR ID: " . $hrId);
                $contactId = $this->createGoogleContact($contactData, $hrGoogleAccessToken, $hrId);
                if ($contactId) {
                    UserGoogleContact::updateOrCreate(
                        ['user_id' => $worker->id, 'admin_id' => $hrId], // NULL for default Google account
                        ['contact_id' => $contactId]
                    );                    
                }
            }
        }
    }
    
    private function createGoogleContact($contactData, $googleAccessToken, $hrId = null)
    {
        \Log::info("Creating Google contact for HR ID: " . $hrId);
        $url = 'https://people.googleapis.com/v1/people:createContact';
    
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $googleAccessToken,
            'Content-Type' => 'application/json',
        ])->post($url, $contactData);
    
        $http_code = $response->status();
        $data = $response->json();

        if ($http_code == 401) {
            $newToken = $this->refreshAccessToken($googleAccessToken, $hrId ?? null);
            if ($newToken) {
                $googleAccessToken = $newToken;
                return $this->createGoogleContact($contactData, $googleAccessToken, $hrId ?? null);
            }
        } elseif ($http_code != 200) {
            throw new Exception('Error: Failed to create contact in Google Contacts');
        }

        return $data['resourceName'] ?? null;
    }
    
    private function updateGoogleContact($contactId, $contactData, $googleAccessToken, $hrId = null)
    {

        $updatePersonFields = 'names,phoneNumbers,emailAddresses';
        $url = 'https://people.googleapis.com/v1/' . $contactId . ':updateContact?updatePersonFields=' . $updatePersonFields;
    
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $googleAccessToken,
            'Content-Type' => 'application/json',
        ])->patch($url, $contactData);    
    
        $http_code = $response->status();
        $data = $response->json();
    
        if ($http_code == 401) {
            $this->refreshAccessToken($googleAccessToken, $hrId ?? null);
            return $this->updateGoogleContact($contactId, $contactData, $googleAccessToken, $hrId);
        } elseif ($http_code != 200) {
            throw new Exception('Error: Failed to update contact in Google Contacts');
        }
    
        return $data;
    }

    private function getGoogleContact($contactId, $googleAccessToken, $hrId = null)
    {
        $url = 'https://people.googleapis.com/v1/' . $contactId . '?personFields=names,phoneNumbers,emailAddresses';
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $googleAccessToken,
                'Content-Type' => 'application/json',
            ])->get($url);
            
            $http_code = $response->status();
            $data = $response->json();
            
            if ($http_code != 200) {
                Log::error("Failed to fetch contact details", [
                    'http_code' => $http_code,
                    'response' => $data
                ]);
            }

            return $data;
    }

    private function refreshAccessToken($googleAccessToken, $hrId = null)
    {
        \Log::info('Refreshing access token for Google account ID: ' . $hrId);
        $refreshToken = null;
        if ($hrId) {
            $refreshToken = UserSetting::where('admin_id', $hrId)
                ->where('key', SettingKeyEnum::GOOGLE_REFRESH_TOKEN)
                ->value('value');
        } else {
            $refreshToken = Setting::query()
                ->where('key', SettingKeyEnum::GOOGLE_REFRESH_TOKEN)
                ->value('value');
        }

        if (!$refreshToken) {
            \Log::error('Error: Refresh token not found.');
            return null;
        }

        $googleClient = $this->getClient($hrId);
        $googleClient->refreshToken($refreshToken);
        $newAccessToken = $googleClient->getAccessToken();
        $newRefreshToken = $googleClient->getRefreshToken();

        if (!$newAccessToken) {
            Log::error('Failed to refresh access token.');
            throw new Exception('Error: Failed to refresh access token.');
        }

        // Update token in database
        if ($hrId) {
            UserSetting::updateOrCreate(
                ['admin_id' => $hrId, 'key' => SettingKeyEnum::GOOGLE_ACCESS_TOKEN],
                ['value' => $newAccessToken['access_token']]
            );
        } else {
            Setting::updateOrCreate(
                ['key' => SettingKeyEnum::GOOGLE_ACCESS_TOKEN],
                ['value' => $newAccessToken['access_token']]
            );
        }

        if ($newRefreshToken) {
            if ($hrId) {
                UserSetting::updateOrCreate(
                    ['admin_id' => $hrId, 'key' => SettingKeyEnum::GOOGLE_REFRESH_TOKEN],
                    ['value' => $newRefreshToken]
                );
            } else {
                Setting::updateOrCreate(
                    ['key' => SettingKeyEnum::GOOGLE_REFRESH_TOKEN],
                    ['value' => $newRefreshToken]
                );
            }
        }

        return $newAccessToken['access_token']; // Return new token
    }

}
