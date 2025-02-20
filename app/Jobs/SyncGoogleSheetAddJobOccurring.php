<?php

namespace App\Jobs;

use App\Enums\JobStatusEnum;
use App\Enums\SettingKeyEnum;
use App\Models\Job;
use App\Models\ManageTime;
use App\Models\Client;
use App\Models\User;
use App\Models\Contract;
use App\Models\ClientPropertyAddress;
use App\Models\Offer;
use App\Models\Services;
use App\Models\ServiceSchedule;
use App\Models\JobHours;
use App\Models\JobService;
use App\Models\Setting;
use App\Models\Notification;
use App\Traits\JobSchedule;
use App\Traits\GoogleAPI;
use App\Traits\PriceOffered;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\JobNotificationToAdmin;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Enums\NotificationTypeEnum;
use App\Jobs\SendUninterestedClientEmail;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Http;

class SyncGoogleSheetAddJobOccurring implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, GoogleAPI, JobSchedule, PriceOffered;

    protected $syncJob;
    protected $spreadsheetId;
    protected $googleAccessToken;
    protected $googleRefreshToken;
    protected $googleSheetEndpoint = 'https://sheets.googleapis.com/v4/spreadsheets/';
    protected $sheetName = null;

    /**
     * Create a new job instance.
     *
     * @param $sheetName
     */
    public function __construct($nextJob, $sheetName = null)
    {
        $this->sheetName = $sheetName;
        $this->syncJob = $nextJob; // Use renamed property
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $job = $this->syncJob; // Use renamed property
        \Log::info("Job ID: $job->id");
        try {
            $months = [
                "ינואר", "פברואר", "מרץ", "אפריל", "מאי", "יוני",
                "יולי", "אוגוסט", "ספטמבר", "אוקטובר", "נובמבר", "דצמבר"
            ];
            // Convert date to Hebrew weekday format
            $hebrewWeekdays = [
                "Sunday" => "ראשון",
                "Monday" => "שני",
                "Tuesday" => "שלישי",
                "Wednesday" => "רביעי",
                "Thursday" => "חמישי",
                "Friday" => "שישי",
                "Saturday" => "שבת"
            ];
    
            $this->initGoogleConfig();
            $job_start_date = $job->start_date;
    
            if (!$job_start_date) {
                \Log::error("Job start date is missing.");
                return;
            }

            $jobStartDate = Carbon::parse($job_start_date);
    
            \Log::info("Job start date: $job_start_date");
    
            // Extract month from job start date
            $monthNumber = date('n', strtotime($job_start_date)) - 1;
            $sheet = $months[$monthNumber] ?? null;
    
            \Log::info("Target sheet: $sheet");
    
            // Check if the sheet exists; if not, create it
            $sheetId = $this->getSheetId($sheet);
            $isNewSheet = false;
    
            if (!$sheetId) {
                \Log::info("Sheet $sheet not found. Creating new sheet...");
                $this->createNewSheet($this->spreadsheetId, $sheet);
                sleep(1);
                $sheetId = $this->getSheetId($sheet);
                $isNewSheet = true;
    
                // Light Green Background (RGB: 144, 195, 131)
                $rgb = [0.5647, 0.7647, 0.5137];

                $weekdayEnglish = $jobStartDate->format('l'); // Get weekday in English
                $weekdayHebrew = $hebrewWeekdays[$weekdayEnglish] ?? ''; // Get Hebrew weekday
                $formattedDate = $weekdayHebrew . " " . $jobStartDate->format('d.m');
    
                // **Highlight the first row with the job start date**
                $highlightedRowIndex = 1;
                $this->setCellBackgroundColor($sheetId, "D{$highlightedRowIndex}", $rgb, $formattedDate, $highlightedRowIndex);
                \Log::info("Inserted highlighted date row for $formattedDate at row $highlightedRowIndex");
            }
    
            // Retrieve sheet data
            $data = $this->getGoogleSheetData($sheet);
            if (empty($data)) {
                \Log::warning("Sheet $sheet is empty.");
            }
    
            $insertRowIndex = null;
    
            // If it's a new sheet, insert job record after the highlighted row
            if ($isNewSheet) {
                $insertRowIndex = 2;
            } else {
                foreach ($data as $index => $row) {
                    if ($index == 0) continue;
    
                    // Check if column 4 (index 3) contains a date pattern
                    if (!empty($row[3]) && (
                        preg_match('/(?:יום\s*)?[א-ת]+\s*\d{1,2}\.\d{1,2}/u', $row[3]) ||
                        preg_match('/(?:יום\s*)?[א-ת]+\s*\d{1,2},\d{1,2}/u', $row[3])
                    )) {
                        $currentDate = $this->convertDate($row[3], $sheet);
                        \Log::info("Extracted current date: $currentDate");
    
                        if ($currentDate == $job_start_date) {
                            \Log::info("Matching date found at row $index");
                            $insertRowIndex = $index + 1;
                            break;
                        }
                    }
                }
            }
    
            if ($insertRowIndex !== null) {
                $row_index = $this->findNextDateRowIndex($data, $insertRowIndex) ?? $insertRowIndex;
            } else {
                $lastOccupiedRow = $this->findLastHighlightedRow($sheet);
                \Log::info("Last occupied row: $lastOccupiedRow");
                $row_index = $lastOccupiedRow + 3;

                $weekdayEnglish = $jobStartDate->format('l'); // Get weekday in English
                $weekdayHebrew = $hebrewWeekdays[$weekdayEnglish] ?? ''; // Get Hebrew weekday
                $formattedDate = $weekdayHebrew . " " . $jobStartDate->format('d.m');
                $rgb = [0.5647, 0.7647, 0.5137];

                $this->setCellBackgroundColor($sheetId, "D{$row_index}", $rgb, $formattedDate, $row_index);
                $row_index +=2;

            }
    
            \Log::info("Final row index: $row_index");
            $data1 = $this->insertRowAbove($sheet, $row_index, $job, $sheetId);
            \Log::info(['data' => $data1]);
    
        } catch (\Exception $e) {
            \Log::error("An error occurred: " . $e->getMessage());
        }
    }


    public function findNextDateRowIndex($sheetData, $startIndex)
    {
        $pattern1 = '/(?:יום\s*)?[א-ת]+\s*\d{1,2}\.\d{1,2}/u';
        $pattern2 = '/(?:יום\s*)?[א-ת]+\s*\d{1,2},\d{1,2}/u';
    
        for ($i = $startIndex + 1; $i < count($sheetData); $i++) {
            if (!empty($sheetData[$i][3]) && 
                (preg_match($pattern1, $sheetData[$i][3]) || preg_match($pattern2, $sheetData[$i][3]))) {
                return $i; // Return the row index if a match is found
            }
        }
        
        return $startIndex; // If no date row is found, use the provided row index
    }
    


    public function insertRowAbove($sheetName, $rowIndex, $job, $sheetId = null)
    {
        $spreadsheetId = $this->spreadsheetId;
        $shift = null;
        $client = $job->client;
        $offer = $job->offer;
        $contract = $job->contract;
        $worker = $job->worker;
        $workerName = ($worker->firstname ?? null)." ".($worker->lastname ?? null);
    
        // Fetch dropdown options
        $workerArr = User::where('status', 1)->get()->pluck('firstname')->toArray();
        $serviceArr = Services::get()->pluck('heb_name')->toArray();
        $frequencyArr = ServiceSchedule::where('status', 1)->get()->pluck('name_heb')->toArray();
        $addressArr = ClientPropertyAddress::where('client_id', $client->id)->get()->pluck('address_name')->toArray();
    
        // Fetch service and frequency details
        $jsonServices = isset($offer->services) ? json_decode($offer->services, true) : [];
        $serviceId = null;
        $frequencyId = null;
        if (!empty($jsonServices)) {
            foreach ($jsonServices as $jsonService) {
                $serviceId = $jsonService['service'];
                $frequencyId = $jsonService['frequency'];
            }
        }
        if ($serviceId) {
            $service = Services::find($serviceId);
            $serviceName = $client->lng == "heb" ? $service->heb_name : $service->name;
        }
    
        $frequencyName = ServiceSchedule::where('id', $frequencyId)->value('name_heb');
        $startTime = Carbon::createFromFormat('H:i:s', $job->start_time);
        $endTime = Carbon::createFromFormat('H:i:s', $job->end_time);
        $diffInHours = $startTime->diffInHours($endTime);
        $diffInMinutes = $startTime->diffInMinutes($endTime) % 60;
    
        // Determine shift based on start time
        if ($startTime->hour < 12) {
            $shift = "בוקר";
        } elseif ($startTime->hour >= 12 && $startTime->hour < 16) {
            $shift = "צהריים";
        } else {
            $shift = "אחה״צ";
        }
    
        $addressName = ClientPropertyAddress::where('id', $job->address_id)->value('address_name');
    
        // 🔹 **Get Current Sheet Size**
        $sheetMetadata = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->googleAccessToken,
            'Content-Type' => 'application/json',
        ])->get("{$this->googleSheetEndpoint}{$spreadsheetId}?fields=sheets.properties");
    
        $sheets = json_decode($sheetMetadata->body(), true)['sheets'] ?? [];
        $currentSheet = collect($sheets)->firstWhere('properties.title', $sheetName);
        $currentRowCount = $currentSheet['properties']['gridProperties']['rowCount'] ?? 1000;
    
        \Log::info("Current sheet row count: {$currentRowCount}");
    
        // 🔹 **If rowIndex > sheet size, expand the sheet**
        if ($rowIndex > $currentRowCount) {
            $resizeRequest = [
                "requests" => [
                    [
                        "updateSheetProperties" => [
                            "properties" => [
                                "sheetId" => $this->getSheetId($sheetName),
                                "gridProperties" => ["rowCount" => $rowIndex] // Set new row count
                            ],
                            "fields" => "gridProperties.rowCount"
                        ]
                    ]
                ]
            ];
    
            Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->googleAccessToken,
                'Content-Type' => 'application/json',
            ])->post("{$this->googleSheetEndpoint}{$spreadsheetId}:batchUpdate", $resizeRequest);
    
            \Log::info("Resized sheet {$sheetName} to {$rowIndex} rows");
        }
    
        // 🔹 **Insert a New Row**
        $requests = [
            [
                "insertDimension" => [
                    "range" => [
                        "sheetId" => $this->getSheetId($sheetName),
                        "dimension" => "ROWS",
                        "startIndex" => $rowIndex - 1, // Zero-based index
                        "endIndex" => $rowIndex
                    ],
                    "inheritFromBefore" => false
                ]
            ]
        ];
    
        $updateResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->googleAccessToken,
            'Content-Type' => 'application/json',
        ])->post("{$this->googleSheetEndpoint}{$spreadsheetId}:batchUpdate", ["requests" => $requests]);
    
        // 🔹 **Insert Job Data into the Row**
        $values = [
            [
                $client->invoicename ?? "",
                "#".$client->id ?? "",
                $offer->id ?? "",
                "","","","","",
                $workerName ?? "",
                $workerName ?? "",
                $shift ?? "",
                "", // Empty cell
                $serviceName ?? "", // Default selected service
                $diffInHours.":".$diffInMinutes, // Duration in HH:MM
                "","",
                $frequencyName ?? "", // Default selected frequency
                $addressName ?? "",
                "", // Empty cell
                $addressName ?? "", // Default selected address
                "","","",
                $job->id ?? ""
            ]
        ];
    
        $body = [
            "range" => "{$sheetName}!A{$rowIndex}",
            "majorDimension" => "ROWS",
            "values" => $values
        ];
    
        $insertResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->googleAccessToken,
            'Content-Type' => 'application/json',
        ])->put("{$this->googleSheetEndpoint}{$spreadsheetId}/values/{$sheetName}!A{$rowIndex}?valueInputOption=USER_ENTERED", $body);
    
        // 🔹 **Apply Checkboxes & Dropdowns**
        $fields = [];
    
        $fields[] = [
            'sheetId' => $sheetId,
            'cell' => "F{$rowIndex}",
            'type' => 'checkbox',
            'values' => ["TRUE", "FALSE"],
            'value' => "TRUE",
        ];
        $fields[] = [
            'sheetId' => $sheetId,
            'cell' => "G{$rowIndex}",
            'type' => 'checkbox',
            'values' => ["TRUE", "FALSE"],
            'value' => "FALSE",
        ];
        $fields[] = [
            'sheetId' => $sheetId,
            'cell' => "J{$rowIndex}",
            'type' => 'dropdown',
            'values' => $workerArr,
            'value' => $workerName,
        ];
        $fields[] = [
            'sheetId' => $sheetId,
            'cell' => "M{$rowIndex}",
            'type' => 'dropdown',
            'values' => $serviceArr,
            'value' => $serviceName,
        ];
        $fields[] = [
            'sheetId' => $sheetId,
            'cell' => "Q{$rowIndex}",
            'type' => 'dropdown',
            'values' => $frequencyArr,
            'value' => $frequencyName,
        ];
        $fields[] = [
            'sheetId' => $sheetId,
            'cell' => "T{$rowIndex}",
            'type' => 'dropdown',
            'values' => $addressArr,
            'value' => $addressName,
        ];
    
        $this->updateGoogleSheetFields($fields);
        \Log::info("Dropdowns & Checkboxes applied to row {$rowIndex} in sheet {$sheetName}");
    
        return ["updateResponse" => $updateResponse->body(), "insertResponse" => $insertResponse->body()];
    }
    
    

    public function findLastHighlightedRow($sheetName, $excludedColumns = [5,6])
    {
        $spreadsheetId = $this->spreadsheetId;
        $range = "{$sheetName}!A1:Z";  // Adjust if needed
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->googleAccessToken,
            'Content-Type' => 'application/json',
        ])->get("{$this->googleSheetEndpoint}{$spreadsheetId}/values/{$range}?majorDimension=ROWS");
    
        $rows = json_decode($response->body(), true)['values'] ?? [];
        
        $lastOccupiedRow = 0; // Default to 0 if no data is found
    
        foreach ($rows as $index => $row) {
            $filteredRow = array_diff_key($row, array_flip($excludedColumns));
    
            if (array_filter($filteredRow)) {  // Check if the row still has meaningful data
                $lastOccupiedRow = $index + 1; // Convert to 1-based index
            }
        }
    
        return $lastOccupiedRow;
    }
    

    public function getSheetData($sheetName, $spreadsheetId){

        $checkRange = "{$sheetName}!A:A"; 
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->googleAccessToken,
            'Content-Type' => 'application/json',
        ])->get("{$this->googleSheetEndpoint}{$spreadsheetId}/values/{$checkRange}?majorDimension=ROWS");
    
        $existingRows = json_decode($response->body(), true)['values'] ?? [];
    
        return $existingRows;
    }


    public function updateGoogleSheetFields($fields)
    {
        $endpoint = "{$this->googleSheetEndpoint}{$this->spreadsheetId}:batchUpdate";

        $requests = [];

        foreach ($fields as $field) {
            $sheetId = $field['sheetId']; // Sheet ID
            $cell = $field['cell']; // e.g., "A1"
            $fieldType = $field['type']; // 'dropdown', 'text', 'checkbox', 'date', 'number'
            $values = $field['values'] ?? []; // For dropdown options
            $value = $field['value'] ?? null; // Value to update the cell with (optional)

            $range = [
                "sheetId" => $sheetId,
                "startRowIndex" => $this->convertRowCol($cell)["row"] - 1,
                "endRowIndex" => $this->convertRowCol($cell)["row"],
                "startColumnIndex" => $this->convertRowCol($cell)["col"] - 1,
                "endColumnIndex" => $this->convertRowCol($cell)["col"]
            ];

            switch ($fieldType) {
                case 'dropdown':
                    // Set dropdown options
                    $requests[] = [
                        "setDataValidation" => [
                            "range" => $range,
                            "rule" => [
                                "condition" => [
                                    "type" => "ONE_OF_LIST",
                                    "values" => array_map(fn($option) => ["userEnteredValue" => $option], $values)
                                ],
                                "showCustomUi" => true,
                                "strict" => true
                            ]
                        ]
                    ];

                    // Set initial value for dropdown
                    if ($value && in_array($value, $values, true)) {
                        $requests[] = [
                            "repeatCell" => [
                                "range" => $range,
                                "cell" => [
                                    "userEnteredValue" => ["stringValue" => $value]
                                ],
                                "fields" => "userEnteredValue"
                            ]
                        ];
                    }
                    break;

                case 'text':
                    $requests[] = [
                        "repeatCell" => [
                            "range" => $range,
                            "cell" => [
                                "userEnteredValue" => ["stringValue" => $value]
                            ],
                            "fields" => "userEnteredValue"
                        ]
                    ];
                    break;

                case 'checkbox':
                    $requests[] = [
                        "repeatCell" => [
                            "range" => $range,
                            "cell" => [
                                "userEnteredValue" => ["boolValue" => filter_var($value, FILTER_VALIDATE_BOOLEAN)],
                                "dataValidation" => [
                                    "condition" => [
                                        "type" => "BOOLEAN"
                                    ],
                                    "strict" => true,
                                    "showCustomUi" => true
                                ]
                            ],
                            "fields" => "userEnteredValue,dataValidation"
                        ]
                    ];
                    break;

                case 'date':
                    $requests[] = [
                        "repeatCell" => [
                            "range" => $range,
                            "cell" => [
                                "userEnteredValue" => ["numberValue" => $value], // Date as a numeric value
                                "userEnteredFormat" => [
                                    "numberFormat" => [
                                        "type" => "DATE",
                                        "pattern" => "yyyy-mm-dd"
                                    ]
                                ]
                            ],
                            "fields" => "userEnteredValue,userEnteredFormat.numberFormat"
                        ]
                    ];
                    break;

                case 'number':
                    $requests[] = [
                        "repeatCell" => [
                            "range" => $range,
                            "cell" => [
                                "userEnteredValue" => ["numberValue" => $value]
                            ],
                            "fields" => "userEnteredValue"
                        ]
                    ];
                    break;

                default:
                    throw new \Exception("Unsupported field type: $fieldType");
            }
        }

        $requestBody = [
            "requests" => $requests
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->googleAccessToken,
            'Content-Type' => 'application/json',
        ])->post($endpoint, $requestBody);

        return $response->body();
    }



    private function getSheetId($sheetName)
    {
        $endpoint = "{$this->googleSheetEndpoint}{$this->spreadsheetId}";
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->googleAccessToken,
            'Content-Type' => 'application/json',
        ])->get($endpoint)->json();

        foreach ($response["sheets"] as $sheet) {
            if ($sheet["properties"]["title"] === $sheetName) {
                return $sheet["properties"]["sheetId"];
            }
        }

        return null;
    }

    private function getSheetIdByName($spreadsheetId, $sheetName)
    {
        $endpoint = "{$this->googleSheetEndpoint}{$spreadsheetId}?fields=sheets.properties";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->googleAccessToken,
            'Content-Type' => 'application/json',
        ])->get($endpoint);

        $data = $response->json();

        if (!isset($data['sheets'])) {
            \Log::error("Failed to retrieve sheet properties.");
            return null;
        }

        foreach ($data['sheets'] as $sheet) {
            if ($sheet['properties']['title'] === $sheetName) {
                return $sheet['properties']['sheetId'];
            }
        }

        \Log::error("Sheet with name '{$sheetName}' not found.");
        return null;
    }

    /**
     * Convert A1 notation (e.g., "D8") to row/column indices
     */
    private function convertRowCol($cell)
    {
        preg_match('/([A-Z]+)(\d+)/', $cell, $matches);
        $column = $matches[1];
        $row = intval($matches[2]);

        $colIndex = 0;
        foreach (str_split($column) as $char) {
            $colIndex = $colIndex * 26 + (ord($char) - ord('A') + 1);
        }

        return ["row" => $row, "col" => $colIndex];
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

    public function setCellBackgroundColor($sheetId, $cell, $rgb, $highlightDate, $nRow)
    {
        $spreadsheetId = $this->spreadsheetId;
    
        // Prepare the range for the entire row
        $range = [
            "sheetId" => $sheetId,
            "startRowIndex" => $this->convertRowCol($cell)["row"] - 1,
            "endRowIndex" => $this->convertRowCol($cell)["row"],
            "startColumnIndex" => 0, // Start from the first column
            "endColumnIndex" => 40, // Adjust based on the number of columns you have
        ];

        $dateCellRange = [
            "sheetId" => $sheetId,
            "startRowIndex" => $this->convertRowCol($cell)["row"] - 1,
            "endRowIndex" => $this->convertRowCol($cell)["row"],
            "startColumnIndex" => 3,  // Column D is index 3 (0-based index)
            "endColumnIndex" => 4,    // End at column D
        ];
    
        // Create the request body to update both the background color and cell value
        $requestBody = [
            "requests" => [
                [
                    "repeatCell" => [
                        "range" => $range,
                        "cell" => [
                            "userEnteredFormat" => [
                                "backgroundColor" => [
                                    "red" => $rgb[0],
                                    "green" => $rgb[1],
                                    "blue" => $rgb[2],
                                ],
                            ],
                        ],
                        "fields" => "userEnteredFormat.backgroundColor"  // Include both formatting and value fields
                    ],
                ],
                [
                    "repeatCell" => [
                        "range" => $dateCellRange,
                        "cell" => [
                            "userEnteredValue" => ["stringValue" => $highlightDate],  // Store the date in D column
                        ],
                        "fields" => "userEnteredValue",
                    ],
                ],
            ],
        ];
    
        // Send the batch update request to Google Sheets
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->googleAccessToken,
            'Content-Type' => 'application/json',
        ])->post("{$this->googleSheetEndpoint}{$spreadsheetId}:batchUpdate", $requestBody);
    
        // Log response or handle any errors
        if ($response->failed()) {
            \Log::error("Failed to update background color and value: " . $response->body());
        } else {
            \Log::info("Successfully updated background color and value.");
        }

        return $response->body();
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

    public function createNewSheet($spreadsheetId, $sheetTitle)
    {
        $endpoint = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}:batchUpdate";

        $data = [
            "requests" => [
                [
                    "addSheet" => [
                        "properties" => [
                            "title" => $sheetTitle,
                            "rightToLeft" => true
                        ]
                    ]
                ]
            ]
        ];

        $response = Http::withToken($this->googleAccessToken)->post($endpoint, $data);

        if ($response->successful()) {
            \Log::info("New sheet '$sheetTitle' created successfully.");
        } else {
            \Log::error("Failed to create a new sheet: " . $response->body());
        }
    }

    public function convertDate($dateString, $sheet=null)
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
