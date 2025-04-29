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
use Carbon\Carbon;
use Twilio\Rest\Client as TwilioClient;

class SendJobConfirmMessageToClient extends Command
{
    use GoogleAPI;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:job-confirm-message';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a job confirm message to a client';

    protected $spreadsheetId;
    protected $googleAccessToken;
    protected $googleRefreshToken;
    protected $twilioAccountSid;
    protected $twilioAuthToken;
    protected $twilioWhatsappNumber;
    protected $twilio;
    protected $googleSheetEndpoint = 'https://sheets.googleapis.com/v4/spreadsheets/';

    protected $message = [
        "en" => "Hi :client_name, How are you?
This is a reminder about your schedule for next week.

:next_week_schedule

If you have any changes or special requests, please update us by the end of the day.
Please note that cancellations or changes after today will be charged according to our policy.
To send a message with a request or change, please reply with the number 1.

Best regards,
The Broom Service Team 🌹
www.broomservice.co.il
Phone: 03-525-70-60
Email: office@broomservice.co.il
If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",
        "heb" => "היי :client_name, מה שלומך?
זוהי תזכורת לגבי סידור העבודה שלכם לשבוע הבא.

:next_week_schedule

במידה ויש לכם שינויים או בקשות מיוחדות, ניתן לעדכן אותנו עד סוף היום.
שימו לב שלאחר היום, ביטולים או שינויים יחויבו בעלות לפי המדיניות שלנו.
לשליחת הודעה על בקשה או שינוי נא להשיב בהודעה עם הספרה 1

בברכה,
צוות ברום סרוויס 🌹
www.broomservice.co.il
טלפון: 03-525-70-60
דוא\"ל: office@broomservice.co.il
אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת."
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
        $currentDate = null;
        $dates = [];
        $shifts = [];
        foreach ($sheets as $key => $sheet) {
            $data = $this->getGoogleSheetData($sheet);
            if (empty($data)) {
                Log::warning("Sheet $sheet is empty.");
                continue;
            }

            foreach ($data as $index => $row) {
                if ($index == 0) {
                    continue;
                }
                if (!empty($row[3]) && (
                    preg_match('/(?:יום\s*)?[א-ת]+\s*\d{1,2}\.\d{1,2}/u', $row[3]) ||
                    preg_match('/(?:יום\s*)?[א-ת]+\s*\d{1,2},\d{1,2}/u', $row[3])
                    // preg_match('/(?:יום\s*)?[א-ת]+\s*\d{2}\d{2}/u', $row[3])
                )) {
                    $currentDate = $this->convertDate($row[3], $sheet);
                    $grouped[$currentDate] = [];
                }
                if ($currentDate !== null && !empty($row[1]) && !empty($row[5]) && $row[5] == 'TRUE') {
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
                        $shifts[] = trim($row[10] ?? '');
                        if ($client) {
                            $currentDateObj = Carbon::parse($currentDate); // Current date
                            $nextWeekStart = Carbon::now()->next(Carbon::SUNDAY); // Next week's Sunday
                            $nextWeekEnd = $nextWeekStart->copy()->addDays(6); // Next week's Saturday

                            if ($currentDateObj->between($nextWeekStart, $nextWeekEnd)) {
                                $shift = "";
                                $day = $currentDateObj->format('l');
                                if($client->lng == 'en') {
                                    switch (trim($row[10])) {
                                        case 'יום':
                                        case 'בוקר':
                                        case '7 בבוקר':
                                        case 'בוקר 11':
                                        case 'בוקר מוקדם':
                                        case 'בוקר 6':
                                            $shift = "Morning";
                                            break;

                                        case 'צהריים':
                                        case 'צהריים 14':
                                            $shift = "Noon";
                                            break;

                                        case 'אחהצ':
                                        case 'אחה״צ':
                                        case 'ערב':
                                        case 'אחר״צ':
                                            $shift = "After noon";
                                            break;

                                        default:
                                            $shift = $row[10];
                                            break;
                                    }
                                } else {
                                    switch (trim($row[10])) {
                                        case 'יום':
                                        case 'בוקר':
                                        case '7 בבוקר':
                                        case 'בוקר 11':
                                        case 'בוקר מוקדם':
                                        case 'בוקר 6':
                                            $shift = "בוקר";
                                            break;

                                        case 'צהריים':
                                        case 'צהריים 14':
                                            $shift = 'אחה"צ';
                                            break;

                                        case 'אחהצ':
                                        case 'אחה״צ':
                                        case 'ערב':
                                        case 'אחר״צ':
                                            $shift = "אחהצ";
                                            break;

                                        default:
                                            $shift = $row[10];
                                            break;
                                    }
                                    switch ($day) {
                                        case 'Sunday':
                                            $day = "ראשון";
                                            break;
                                        case 'Monday':
                                            $day = "שני";
                                            break;
                                        case 'Tuesday':
                                            $day = "שלישי";
                                            break;
                                        case 'Wednesday':
                                            $day = "רביעי";
                                            break;
                                        case 'Thursday':
                                            $day = "חמישי";
                                            break;
                                        case 'Friday':
                                            $day = "שישי";
                                            break;
                                        case 'Saturday':
                                            $day = "שבת";
                                            break;
                                    }

                                }

                                $clientIds[$client->id][] = [
                                    "shift" => $shift,
                                    "dayName" => $day,
                                    "currentDate" => $currentDateObj->format('j.n.y')
                                ];
                            }
                        }
                    }
                }
            }
        }
        foreach($clientIds as $clientId => $c) {
            $client = Client::find($clientId);
            if($client) {
                // \Log::info('client: ' .$c . $client->id);
                if($client->wednesday_notification == 1 || $client->disable_notification == 1){
                    \Log::info('wednesday notification client: ' . $client->id);
                    continue;
                }

                WhatsAppBotActiveClientState::where('client_id', $clientId)->delete();
                $clientName = ($client->firstname ?? '') . ' ' . ($client->lastname ?? '');
                $personalizedMessage = str_replace(':client_name', $clientName, $this->message[$client->lng]);

                if($client->lng == 'en') {
                    $msg = "";
                    if(count($c) > 1) {
                        $msg = "Next week, we will arrive on: \n";
                    } else {
                        $msg = "Next week, we will arrive on ";
                    }
                    $shiftMsgs = [];
                    foreach($c as $d) {
                        $shiftMsgs[] = $d['dayName'] . " " . $d['currentDate'] . " during the " . $d['shift'];
                    }
                    if(count($shiftMsgs) > 1) {
                        $msg .= implode("\nAnd on ", $shiftMsgs);
                    } else {
                        $msg .= $shiftMsgs[0] ?? '';
                    }
                    $personalizedMessage = str_replace(':next_week_schedule', $msg, $personalizedMessage);
                } else {
                    $msg = "";
                    foreach($c as $d) {
                        if (count($c) == 1) {
                            $d = $c[0];
                            $msg = "בשבוע הבא נגיע אליכם ביום " . $d['dayName'] . " " . $d['currentDate'] . " " . $d['shift'] . ".";
                        } else {
                            $msg = "בשבוע הבא נגיע אליכם";
                            foreach ($c as $index => $d) {
                                if ($index > 0) {
                                    $msg .= "\nוגם ביום " . $d['dayName'] . " " . $d['currentDate'] . " " . $d['shift'];
                                } else {
                                    $msg .= "\nביום " . $d['dayName'] . " " . $d['currentDate'] . " " . $d['shift'];
                                }
                            }
                        }
                    }
                    $personalizedMessage = str_replace(':next_week_schedule', $msg, $personalizedMessage);
                }

                $sid = $client->lng == "heb" ? "HX24ce33a6a7f5ba297f6756127e3d80e0" : "HXe77a7ad3eb2c4394e74c52307c89c8a7";

                $twi = $this->twilio->messages->create(
                    "whatsapp:+$client->phone",
                    [
                        "from" => $this->twilioWhatsappNumber,
                        "contentSid" => $sid,
                        "contentVariables" => json_encode([
                            '1' => (($client->firstname ?? '') . ' ' . ($client->lastname ?? '')),
                            '2' => preg_replace("/[\n\r\t]+/", " ", $msg)
                        ]),
                        "statusCallback" => config("services.twilio.webhook") . "/twilio/status-callback",
                    ]
                );

                StoreWebhookResponse($personalizedMessage, $client->phone, $twi->toArray());

                echo $personalizedMessage . PHP_EOL . PHP_EOL . PHP_EOL;
                // sendClientWhatsappMessage($client->phone, ['name' => '', 'message' => $personalizedMessage]);
                Cache::put('client_job_confirm_msg' . $client->id, 'main_msg', now()->addHours(20));
                sleep(2);
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
