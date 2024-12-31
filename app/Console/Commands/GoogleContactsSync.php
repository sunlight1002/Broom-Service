<?php

use Illuminate\Support\Facades\Http;

use Illuminate\Console\Command;
use App\Models\Setting;
use App\Enums\SettingKeyEnum;
use App\Models\Client;

class GoogleContactsSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google-contact-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Google Contacts';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $googleAccessToken = Setting::query()
                ->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)
                ->value('value');

        if (!$googleAccessToken) {
            throw new Exception('Error: Google Access Token not found.');
        }else{
            $contacts = $this->getGoogleContacts($googleAccessToken);
            \Log::info(['contacts' => $contacts]);
        }

        return 0;
    }

    private function getGoogleContacts($googleAccessToken)
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
}
