<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Enums\SettingKeyEnum;
use App\Models\Setting;
use App\Models\Client;
use Exception;
use App\Traits\GoogleAPI;
use Illuminate\Support\Facades\Http;
use App\Models\WhatsAppBotActiveClientState;
use Illuminate\Support\Facades\Cache;
use Twilio\Rest\Client as TwilioClient;

class SendJobReviewMessageToClient extends Command
{
    use GoogleAPI;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:job-review-message';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a job review message to a client';

    protected $spreadsheetId;
    protected $googleAccessToken;
    protected $googleRefreshToken;
    protected $twilioAccountSid;
    protected $twilioAuthToken;
    protected $twilioWhatsappNumber;
    protected $twilio;
    protected $googleSheetEndpoint = 'https://sheets.googleapis.com/v4/spreadsheets/';

    protected $message = [
        "en" => "Hello :client_name,
We hope you enjoyed the service provided by our team.
We’d love to hear your feedback about your experience.
Your input is important to us to maintain our high standards and ensure every visit meets your expectations.

We’d love to hear your feedback about the service you received:
7️⃣ If you were satisfied with the service.
8️⃣ If you have any comments or requests for the supervisor.

Please reply with the appropriate number.",
        "heb" => "שלום :client_name,
אנו מקווים שנהניתם מהשירות שניתן על ידי הצוות שלנו.
נשמח לשמוע את דעתכם ועל החוויה שלכם.
המשוב שלכם חשוב לנו כדי לשמור על הסטנדרטים הגבוהים שלנו ולוודא שכל ביקור יעמוד בציפיותיכם.

נשמח לדעת איך התרשמתם מהשירות שקיבלתם:
7️⃣ אם הייתם מרוצים מהשירות שקיבלתם.
8️⃣ אם יש לכם הערות או בקשות שנוכל להעביר למפקח להמשך טיפול.

אנא השיבו עם המספר המתאים."
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->twilioAccountSid = config('services.twilio.twilio_id');
        $this->twilioAuthToken = config('services.twilio.twilio_token');
        $this->twilioWhatsappNumber = config('services.twilio.twilio_whatsapp_number');

        // Initialize the Twilio client
        $this->twilio = new TwilioClient($this->twilioAccountSid, $this->twilioAuthToken);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->initGoogleConfig();
        $sheets = $this->getAllSheetNames();

        if (count($sheets) <= 0) {
            Log::info("No sheet found", ['sheets' => $sheets]);
            return;
        }
        $clientIds = [];
        foreach ($sheets as $key => $sheet) {
            $data = $this->getGoogleSheetData($sheet);
            if (empty($data)) {
                Log::warning("Sheet $sheet is empty.");
                continue;
            }
            $currentDate = null;
            foreach ($data as $index => $row) {
                if ($index == 0) {
                    continue;
                }
                if (!empty($row[3]) && (
                    preg_match('/(?:יום\s*)?[א-ת]+\s*\d{1,2}\.\d{1,2}/u', $row[3]) ||
                    preg_match('/(?:יום\s*)?[א-ת]+\s*\d{1,2},\d{1,2}/u', $row[3]) ||
                    preg_match('/(?:יום\s*)?[א-ת]+\s*\d{2}\d{2}/u', $row[3])
                )) {
                    $currentDate = $this->convertDate($row[3], $sheet);
                    $grouped[$currentDate] = [];
                }
                if ($currentDate !== null && !empty($row[1]) && !empty($row[6]) && $row[6] == 'TRUE') {
                    $grouped[$currentDate][] = $row;
                    $id = null;
                    $email = null;
                    if (strpos(trim($row[1]), '#') === 0) {
                        $id = substr(trim($row[1]), 1);
                    } else if (filter_var(trim($row[1]), FILTER_VALIDATE_EMAIL)) {
                        $email = trim($row[1]);
                    }


                    if ($id || $email) {
                        $client = null;
                        if ($id) {
                            $client = Client::find($id);
                        } else if ($email) {
                            $client = Client::where('email', $email)->first();
                        }
                        if ($client) {
                            if ($client->review_notification == 1 || $client->disable_notification == 1) {
                                echo 'review notification client: ' . $client->id . PHP_EOL;
                                continue;
                            }
                            $previousDate = date('Y-m-d', strtotime('-1 day', strtotime(date('Y-m-d'))));
                            // Check if the previous date is Sunday, then use last Thursday and Friday
                            if (date('N', strtotime($previousDate)) == 6) { // 7 means Sunday
                                $lastThursday = date('Y-m-d', strtotime('last Thursday', strtotime(date('Y-m-d'))));
                                $lastFriday = date('Y-m-d', strtotime('last Friday', strtotime(date('Y-m-d'))));
                                // Compare with last Thursday and Friday
                                if ($currentDate == $lastThursday || $currentDate == $lastFriday) {
                                    $clientIds[] = $client->id;
                                }
                            } else {
                                // Normal condition
                                if ($currentDate == $previousDate) {
                                    $clientIds[] = $client->id;
                                }
                            }
                        }
                    }
                }
            }
        }
        foreach (array_unique($clientIds) as $clientId) {

            $client = Client::where('id', $clientId)
                ->where('review_notification', 0)
                ->where('disable_notification', 0)
                ->first();

            if ($client) {

                WhatsAppBotActiveClientState::where('client_id', $client->id)->delete();
                $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));
                $sid = $client->lng == "heb" ? "HX1c07428ae8fa5b4688d71e11fa8101bb" : "HX50f1761f4d412c4f338093a4ff63689b";
                try {
                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$client->phone",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "contentSid" => $sid,
                            "contentVariables" => json_encode([
                                "1" => $clientName,
                            ]),
                        ]
                    );
                } catch (\Exception $e) {
                    \Log::error('Exception during WhatsApp message send: ' . $e->getMessage());
                }
                $personalizedMessage = str_replace(':client_name', $clientName, $this->message[$client->lng]);

                StoreWebhookResponse($twi->body ?? "", $client->phone, $twi->toArray());

                echo $personalizedMessage . PHP_EOL . PHP_EOL . PHP_EOL;
                // sendClientWhatsappMessage($client->phone, ['name' => '', 'message' => $personalizedMessage]);
                Cache::put('client_review' . $client->id, 'client_review', now()->addHours(12));
                sleep(1);
            }
        }

        return 0;
    }

    public function initGoogleConfig()
    {
        // Retrieve the Google Sheet ID from settings
        $this->spreadsheetId = Setting::query()
            ->where('key', SettingKeyEnum::GOOGLE_SHEET_ID)
            ->value('value');

        $this->googleRefreshToken = Setting::query()
            ->where('key', SettingKeyEnum::GOOGLE_REFRESH_TOKEN)
            ->value('value');

        if (!$this->googleRefreshToken) {
            throw new Exception('Error: Google Refresh Token not found.');
        }

        // Refresh the access token
        $googleClient = $this->getClient();
        $googleClient->refreshToken($this->googleRefreshToken);
        $response = $googleClient->fetchAccessTokenWithRefreshToken($this->googleRefreshToken);
        $this->googleAccessToken = $response['access_token'];

        // Save the new access token
        Setting::updateOrCreate(
            ['key' => SettingKeyEnum::GOOGLE_ACCESS_TOKEN],
            ['value' => $this->googleAccessToken]
        );

        if (!$this->googleAccessToken) {
            throw new Exception('Error: Google Access Token not found.');
        }
    }

    public function getAllSheetNames()
    {
        // Google Sheets API endpoint to fetch spreadsheet metadata
        $metadataUrl = $this->googleSheetEndpoint . $this->spreadsheetId;
        try {
            // Fetch metadata to get sheet names
            $metadataResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->googleAccessToken,
                'Content-Type' => 'application/json',
            ])->get($metadataUrl);

            if ($metadataResponse->successful()) {
                $metadata = $metadataResponse->json();
                $sheets = $metadata['sheets'] ?? [];
                return array_map(fn($sheet) => $sheet['properties']['title'], $sheets);
            } else {
                return [];
            }
        } catch (\Exception $e) {
            Log::error('Error occurred during fetching Google sheet', [
                'error' => $e->getMessage(),
                'spreadsheetId' => $this->spreadsheetId,
            ]);
            throw $e;
        }
    }

    public function getGoogleSheetData($sName = null)
    {
        try {
            if (!$sName) {
                return [];
            }
            $range = $sName . '!A:Z'; // Adjust range as needed
            $url = $this->googleSheetEndpoint . $this->spreadsheetId . '/values/' . $range;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->googleAccessToken,
                'Content-Type' => 'application/json',
            ])->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $rows = $data['values'] ?? [];
                return $rows;
            } else {
                return [];
            }
        } catch (\Exception $e) {
            Log::error('Error occurred during fetching Google sheet', [
                'error' => $e->getMessage(),
                'spreadsheetId' => $this->spreadsheetId,
            ]);
            throw $e;
        }
    }

    public function convertDate($dateString, $sheet)
    {
        // Extract year from the sheet (assumes format: "Month Year" e.g., "ינואר 2025" or "דצמבר 2024")
        preg_match('/\d{4}/', $sheet, $yearMatch);
        $year = $yearMatch[0] ?? date('Y'); // Default to current year if no match

        // Normalize different formats (convert ',' to '.')
        $dateString = str_replace(',', '.', $dateString);

        // Extract day and month
        if (preg_match('/(\d{1,2})\.(\d{1,2})/', $dateString, $matches)) {
            // Format: 12.01 → day = 12, month = 01
            $day = sprintf('%02d', $matches[1]);
            $month = sprintf('%02d', $matches[2]);
        } elseif (preg_match('/(\d{2})(\d{2})/', $dateString, $matches)) {
            // Format: 0401 → day = 04, month = 01
            $day = sprintf('%02d', $matches[1]);
            $month = sprintf('%02d', $matches[2]);
        } elseif (preg_match('/(\d{1,2})\s*,\s*(\d{1,2})/', $dateString, $matches)) {
            // Format: 3,1 → day = 3, month = 1
            $day = sprintf('%02d', $matches[1]);
            $month = sprintf('%02d', $matches[2]);
        } else {
            return false;
        }

        // Return formatted date
        return "$year-$month-$day";
    }
}
