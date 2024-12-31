<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Client;
use App\Enums\SettingKeyEnum;
use App\Traits\GoogleAPI;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use Exception;

class AddGoogleContactJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, GoogleAPI;

    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function handle()
    {
        try {
            $client = $this->client;

            $googleAccessToken = Setting::query()
                ->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)
                ->value('value');

            if (!$googleAccessToken) {
                throw new Exception('Error: Google Access Token not found.');
            }

            $contactData = [
                'names' => [
                    [
                        'givenName' => $client->firstname,
                        'familyName' => $client->lastname,
                    ]
                ],
                'phoneNumbers' => [
                    [
                        'value' => $client->phone
                    ]
                ],
                'emailAddresses' => [
                    [
                        'value' => $client->email
                    ]
                ]
            ];

            if ($client->contactId) {

                $contactDetails = $this->getGoogleContact($client->contactId, $googleAccessToken);

                if (isset($contactDetails['etag'])) {
                    $contactData['etag'] = $contactDetails['etag'];
                }

                $updateResponse = $this->updateGoogleContact($client->contactId, $contactData, $googleAccessToken);

            } else {
                $contactId = $this->createGoogleContact($contactData, $googleAccessToken);

                if ($contactId) {
                    $client->update(['contactId' => $contactId]);
                }
            }
        } catch (\Throwable $th) {
            Log::warning($th->getMessage());
            //throw $th;
        }
    }

    private function createGoogleContact($contactData, $googleAccessToken)
    {
        $url = 'https://people.googleapis.com/v1/people:createContact';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $googleAccessToken,
            'Content-Type' => 'application/json',
        ])->post($url, $contactData);

        $http_code = $response->status();
        $data = $response->json();

        if ($http_code == 401) {
            $this->refreshAccessToken($googleAccessToken);
            return $this->createGoogleContact($contactData, $googleAccessToken);
        } elseif ($http_code != 200) {
            Log::debug($response->body());
            throw new Exception('Error: Failed to create contact in Google Contacts');
        }

        return $data['resourceName'] ?? null;
    }

    private function normalizeContactId($contactId)
    {
        $decodedContactId = urldecode($contactId);

        if (strpos($decodedContactId, 'people/people/') === 0) {
            $decodedContactId = substr($decodedContactId, 7);
        }

        if (strpos($decodedContactId, 'people/') !== 0) {
            $decodedContactId = 'people/' . $decodedContactId;
        }

        return $decodedContactId;
    }

    private function updateGoogleContact($contactId, $contactData, $googleAccessToken)
    {

        $updatePersonFields = 'names,phoneNumbers,emailAddresses';

        $url = 'https://people.googleapis.com/v1/' . $contactId . ':updateContact?updatePersonFields=' . $updatePersonFields;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $googleAccessToken,
                'Content-Type' => 'application/json',
            ])->patch($url, $contactData);

            $http_code = $response->status();
            $data = $response->json();

            if ($http_code == 401) {
                Log::warning('Authorization failed, refreshing access token');
                $this->refreshAccessToken($googleAccessToken);
                return $this->updateGoogleContact($contactId, $contactData, $googleAccessToken);
            } elseif ($http_code != 200) {
                Log::error('Failed to update contact in Google Contacts', [
                    'http_code' => $http_code,
                    'response' => $data,
                ]);
                throw new Exception('Error: Failed to update contact in Google Contacts');
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('Error occurred during updating Google contact', [
                'error' => $e->getMessage(),
                'contactId' => $contactId,
                'contactData' => $contactData,
            ]);
            throw $e;
        }
    }

    private function getGoogleContact($contactId, $googleAccessToken)
    {

        $url = 'https://people.googleapis.com/v1/' . $contactId . '?personFields=names,phoneNumbers,emailAddresses';

        try {
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
                throw new Exception('Error: Failed to retrieve contact details');
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('Error occurred during fetching Google contact', [
                'error' => $e->getMessage(),
                'contactId' => $contactId,
            ]);
            throw $e;
        }
    }

    private function refreshAccessToken(&$googleAccessToken)
    {
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
            Log::error('Failed to refresh access token.');
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
    }
}
