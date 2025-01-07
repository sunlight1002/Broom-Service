<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Http;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use App\Enums\SettingKeyEnum;
use App\Models\Client;
use App\Models\User;

class ContactsSyncGoogle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contacts:sync-google';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync contacts with Google Contacts for Clients and Users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $googleAccessToken = Setting::query()
            ->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)
            ->value('value');

        if (!$googleAccessToken) {
            throw new \Exception('Error: Google Access Token not found.');
        }

        $googleContacts = $this->getGoogleContacts($googleAccessToken);
        $this->syncContacts(Client::all(), $googleContacts, $googleAccessToken, 'Client');
        $this->syncContacts(User::all(), $googleContacts, $googleAccessToken, 'User');

        return 0;
    }

    public function getGoogleContacts($googleAccessToken)
    {
        $url = 'https://people.googleapis.com/v1/people/me/connections?personFields=names,phoneNumbers,emailAddresses';
        $contacts = [];
        try {
            do {
                // Make the GET request to Google People API
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $googleAccessToken,
                    'Content-Type' => 'application/json',
                ])->get($url);
                
                $http_code = $response->status();
                $data = $response->json();

                \Log::info(['data' => $data]);
                
                if ($http_code != 200) {
                    Log::error("Failed to fetch contacts", [
                        'http_code' => $http_code,
                        'response' => $data
                    ]);
                    throw new Exception('Error: Failed to retrieve contacts');
                }
                
                // Extract connections and add to contacts array
                if (isset($data['connections'])) {
                    foreach ($data['connections'] as $connection) {
                        $contacts[] = [
                            'contactId' => $connection['resourceName'] ?? null,
                            'name' => $connection['names'][0]['displayName'] ?? 'No Name',
                            'email' => $connection['emailAddresses'][0]['value'] ?? 'No Email',
                            'phone' => $connection['phoneNumbers'][0]['value'] ?? 'No Phone',
                        ];
                    }
                }
                
                // Get the nextPageToken for pagination
                $nextPageToken = $data['nextPageToken'] ?? null;
                if ($nextPageToken) {
                    $url = 'https://people.googleapis.com/v1/people/me/connections?personFields=names,phoneNumbers,emailAddresses&pageToken=' . $nextPageToken;
                }
                
            } while ($nextPageToken);

            return $contacts;

        } catch (\Exception $e) {
            Log::error("Error fetching contacts: " . $e->getMessage());
            throw $e;
        }
    }

    public function syncContacts($databaseContacts, $googleContacts, $googleAccessToken, $modelType)
    {
        $googleContactMap = collect($googleContacts)->keyBy('phone');
        $updated = 0;
        $added = 0;

        foreach ($databaseContacts as $contact) {
            $phone = $contact->phone; // Adjust field name if necessary
            if ($googleContactMap->has($phone)) {
                // Update contactId in the database
                $googleContact = $googleContactMap->get($phone);
                $contact->update(['contactId' => $googleContact['contactId']]);
                $updated++;
            } else {
                // Add contact to Google
                $this->addGoogleContact($contact, $googleAccessToken);
                $added++;
            }
        }

        Log::info("Contacts sync for {$modelType} completed: {$updated} updated, {$added} added.");
    }

    public function addGoogleContact($contact, $googleAccessToken)
    {
        $url = 'https://people.googleapis.com/v1/people:createContact';

        $data = [
            'names' => [['givenName' => $contact->name]], // Adjust field name
            'emailAddresses' => [['value' => $contact->email]], // Adjust field name
            'phoneNumbers' => [['value' => $contact->phone]], // Adjust field name
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $googleAccessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $data);

            if ($response->status() != 200) {
                Log::error("Failed to add contact to Google", [
                    'response' => $response->json(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error adding contact to Google: " . $e->getMessage());
        }
    }
}
