<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Client;
use App\Models\User;
use App\Models\Contract;
use App\Models\ClientPropertyAddress;
use App\Models\Offer;
use App\Models\Job;
use App\Models\Services;
use App\Models\ServiceSchedule;
use App\Models\JobHours;
use App\Models\JobService;
use App\Enums\SettingKeyEnum;
use App\Traits\GoogleAPI;
use App\Traits\ICountDocument;
use App\Traits\PaymentAPI;
use App\Traits\PriceOffered;
use App\Traits\JobSchedule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use App\Models\ManageTime;
use App\Models\JobStatus;
use App\Models\ParentJobs;
use App\Enums\OrderPaidStatusEnum;
use App\Enums\ContractStatusEnum;
use App\Enums\LeadStatusEnum;
use App\Enums\JobStatusEnum;
use Illuminate\Http\Request;
use Exception;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;


class UpdateExcelSheetWithJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, GoogleAPI, JobSchedule, PriceOffered;

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
    public function __construct($sheetName = null)
    {
        $this->sheetName = $sheetName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */

     public function handle()
     {
         try {
             $this->initGoogleConfig();
             $sheets = [];
             
             if (!$this->sheetName) {
                 $sheets = $this->getAllSheetNames();
                 
                 if (count($sheets) <= 0) {
                     Log::info("No sheet found", ['sheets' => $sheets]);
                     return;
                 }
             } else {
                 $sheets[] = $this->sheetName;
             }
             
             $sheets = array_reverse($sheets); // Start with the latest sheet
             $currentDate = Carbon::createFromFormat('Y-m-d', '2025-02-21');
             $endDate = Carbon::createFromFormat('Y-m-d', '2025-08-31');
             $lastLoggedMonth = $currentDate->format('Y-m'); // Track current month
             $currentSheetName = null; // Initial sheet name
             $lastHighlightedDate = null; // Store last highlighted date to avoid duplicate highlighting
             $nRow = null;
     
             foreach ($sheets as &$sheet) {
                 $sheetId = $this->getSheetId($sheet);
                 Log::info("Processing sheet: {$sheet}");
                 $currentSheetName = $sheet;
     
                 while ($currentDate->lte($endDate)) {
                     if ($currentDate->format('Y-m') !== $lastLoggedMonth) {
                         // Move to the first day of the next month
                         $currentDate->startOfMonth();
                         $lastLoggedMonth = $currentDate->format('Y-m'); // Update last logged month
                         // Create a new sheet for the new month
                         $currentSheetName = $lastLoggedMonth;
                         $this->createNewSheet($this->spreadsheetId, $currentSheetName);
                         $sheetId = $this->getSheetId($currentSheetName);
                     }
     
                     $startOfDay = $currentDate->startOfDay()->format('Y-m-d');
                     $endOfDay = $currentDate->endOfDay()->format('Y-m-d');
                     
                     // Fetch jobs for the current day
                     $jobs = Job::with(['client', 'worker', 'offer.service', 'contract'])
                         ->whereBetween('start_date', [$startOfDay, $endOfDay])
                         ->get();
     
                     if ($jobs->isNotEmpty()) {
                         // Highlight the date only if it's not highlighted yet for the day
                         if ($lastHighlightedDate !== $currentDate->format('Y-m-d')) {
                             $lastHighlightedDate = $currentDate->format('Y-m-d');
                             
                             // Get the last highlighted row and calculate the next available row
                             $nRow = $this->highlightDate($sheetId, $currentDate, $nRow, $currentSheetName);
                             Log::info("Highlighted on row: " . $nRow);
                         }
     
                         foreach ($jobs as $job) {
                             Log::info("Processing Job ID: " . $job->id);
                             
                             // Pass the updated row number to addJobToGoogleSheet
                             $this->addJobToGoogleSheet($job, $currentSheetName, $sheetId, $currentDate, $lastLoggedMonth, $nRow);
                             
                             // Increment row number after each job to prevent overwriting
                             $nRow++; 
                         }
                     } else {
                         Log::info("No jobs for date: " . $currentDate->format('Y-m-d'));
                     }
     
                     // Move to the next day
                     $currentDate->addDay();
                 }
             }
     
         } catch (\Exception $e) {
             Log::error("An error occurred: " . $e->getMessage());
             dd($e);
         }
     }
     
     

     public function highlightDate($sheetId, $date, $nRow, $currentSheetName)
     {
         // Find the last occupied row
         $lastOccupiedRow = $this->findLastHighlightedRow($currentSheetName);
         
         // Move 3 rows below the last occupied row to highlight the date
         $dateRow = $lastOccupiedRow + 3;
         \Log::info("Date Row: " . $dateRow);
         $dateCell = "D{$dateRow}"; // Adjust column D dynamically
         
         // Light Green Background (RGB: 144, 195, 131)
         $rgb = [0.5647, 0.7647, 0.5137];
      
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
         
         $weekdayEnglish = $date->format('l'); // Get weekday in English
         $weekdayHebrew = $hebrewWeekdays[$weekdayEnglish] ?? ''; // Get Hebrew weekday
         $formattedDate = $weekdayHebrew . " " . $date->format('d.m');
     
         // Highlight the date with text in Hebrew
         $this->setCellBackgroundColor($sheetId, $dateCell, $rgb, $formattedDate, $dateRow);
         
         // Return the next available row for job data (after 3 rows below the date)
         return $dateRow + 3; // Jobs should start 3 rows below the highlighted date
     }
     
     
     

    public function addJobToGoogleSheet($job, $sheet, $sheetId, $startDate, $lastLoggedMonth = null, $nRow = null)
    {
        $nextRow = $nRow;

        $sheetName = $sheet;
        $spreadsheetId = $this->spreadsheetId;
        $service = null;
        $serviceName = null;
        $shift = null;
        
        $existingRows = $this->getSheetData($sheet, $spreadsheetId);
    
        // **Ensure we start below the highlighted date**
        if (!$nextRow || $nextRow <= count($existingRows)) {
            $nextRow = count($existingRows) + 1;
        }
        


        // Construct range explicitly to force strict placement
        $updateRange = "{$sheetName}!A{$nextRow}";

        $startTime = Carbon::createFromFormat('H:i:s', $job->start_time);
        $endTime = Carbon::createFromFormat('H:i:s', $job->end_time);
        
        // Determine shift based on start time
        if ($startTime->hour < 12) {
            $shift = "בוקר";
        } elseif ($startTime->hour >= 12 && $startTime->hour < 16) {
            $shift = "צהריים";
        } else {
            $shift = "אחה״צ";
        }

        $jobDate = Carbon::parse($job->start_date)->format('Y-m-d'); 
    
        $client = $job->client ?? null;
        $offer = $job->offer ?? null;
        $contract = $job->contract ?? null;
    
        // Fetch dropdown options
        $workerArr = User::where('status', 1)->get()->pluck('firstname')->toArray();
        $serviceArr = Services::get()->pluck('heb_name')->toArray();
        $frequencyArr = ServiceSchedule::where('status', 1)
            ->get()->pluck('name_heb')->toArray();
        $addressArr = ClientPropertyAddress::where('client_id', $job->client->id)->get()->pluck('address_name')->toArray();
    
        $workerId = ParentJobs::where('id', $job->parent_job_id)->value('worker_id');
        $worker = User::where('id', $workerId)->first();
        $frequency = ServiceSchedule::where('period', $job->schedule)->first();
        $frequencyName = $frequency->name_heb ?? null;
    
        $jsonServices = isset($offer->services) ? json_decode($offer->services, true) : [];
        $serviceId = null;
        if (!empty($jsonServices)) {
            foreach ($jsonServices as $jsonService) {
                if ($jsonService['frequency'] == $frequency->id) {
                    $serviceId = $jsonService['service'];
                }
            }
        }
        if($serviceId) {
            $service = Services::find($serviceId);
            $serviceName = $client->lng == "heb" ? $service->heb_name : $service->name;
        }
    
        $startTime = Carbon::createFromFormat('H:i:s', $job->start_time);
        $endTime = Carbon::createFromFormat('H:i:s', $job->end_time);
        $diffInHours = $startTime->diffInHours($endTime);
        $diffInMinutes = $startTime->diffInMinutes($endTime) % 60;
    
        $addressName = ClientPropertyAddress::where('id', $job->address_id)->value('address_name');
    
    
        $jobData = [
            $client->invoicename ?? "",
            $client->id ?? "",
            $offer->id ?? "",
            "","","","","",
            ($worker->firstname ?? "")." ".($worker->lastname ?? ""), // Default selected worker
            ($worker->firstname ?? "")." ".($worker->lastname ?? ""), // Worker dropdown
            $shift,
            "", // Empty cell
            $serviceName, // Default selected service
            $diffInHours.":".$diffInMinutes, // Duration in HH:MM
            "","",
            $frequencyName ?? "", // Default selected frequency
            $addressName ?? "",
            "", // Empty cell
            $addressName ?? "", // Default selected address
            "","","",
            $job->id ?? ""
        ];
    
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->googleAccessToken,
            'Content-Type' => 'application/json',
        ])->put("{$this->googleSheetEndpoint}{$spreadsheetId}/values/{$updateRange}?valueInputOption=USER_ENTERED", [
            "values" => [$jobData]
        ]);

        sleep(1);

        $updatedRange = json_decode($response->body(), true)['updatedRange'] ?? null;
        if (!$updatedRange) {
            \Log::error("Failed to update job data.");
            return;
        }
    
        // Apply dropdowns & checkboxes
        $fields = [];
    
        $fields[] = [
            'sheetId' => $sheetId,
            'cell' => "F{$nextRow}",
            'type' => 'checkbox',
            'values' => ["TRUE", "FALSE"],
            'value' => "TRUE",
        ];
        $fields[] = [
            'sheetId' => $sheetId,
            'cell' => "G{$nextRow}",
            'type' => 'checkbox',
            'values' => ["TRUE", "FALSE"],
            'value' => "FALSE",
        ];
        $fields[] = [
            'sheetId' => $sheetId,
            'cell' => "J{$nextRow}",
            'type' => 'dropdown',
            'values' => $workerArr,
            'value' => $worker,
        ];
        $fields[] = [
            'sheetId' => $sheetId,
            'cell' => "M{$nextRow}",
            'type' => 'dropdown',
            'values' => $serviceArr,
            'value' => $serviceName,
        ];
        $fields[] = [
            'sheetId' => $sheetId,
            'cell' => "Q{$nextRow}",
            'type' => 'dropdown',
            'values' => $frequencyArr,
            'value' => $frequencyName,
        ];
        $fields[] = [
            'sheetId' => $sheetId,
            'cell' => "T{$nextRow}",
            'type' => 'dropdown',
            'values' => $addressArr,
            'value' => $addressName,
        ];
    
        $res = $this->updateGoogleSheetFields($fields);
        return $sheetName; 
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
    

    public function findLastHighlightedRow($sheetName)
    {
        $spreadsheetId = $this->spreadsheetId;
        $range = "{$sheetName}!A1:Z";  // Check a larger range to capture rows up to 1002
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->googleAccessToken,
            'Content-Type' => 'application/json',
        ])->get("{$this->googleSheetEndpoint}{$spreadsheetId}/values/{$range}?majorDimension=ROWS");
    
        $rows = json_decode($response->body(), true)['values'] ?? [];
        // \Log::info("body: " . $response->body());
    
        $lastOccupiedRow = 0; // Default to 0 if no data is found
    
        // Iterate through rows to find the last one with data
        foreach ($rows as $index => $row) {
            // Check if the row has data in any of its columns
            if (array_filter($row)) {  // `array_filter` removes empty values
                $lastOccupiedRow = $index + 1;  // Since rows are 1-based in Google Sheets
            }
        }
    
        \Log::info("Last occupied row: $lastOccupiedRow");
    
        return $lastOccupiedRow;
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

    private function createNewSheet($spreadsheetId, $sheetTitle)
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


    public function getIcountClientInfo($client)
    {
        // Retrieve iCount credentials from settings
        $iCountCompanyID = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_COMPANY_ID)
            ->value('value');

        $iCountUsername = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_USERNAME)
            ->value('value');

        $iCountPassword = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_PASSWORD)
            ->value('value');

        // iCount API URL
        $url = 'https://api.icount.co.il/api/v3.php/client/info';

        // Request data, including client phone and email
        $requestData = [
            'cid' => $iCountCompanyID,
            'user' => $iCountUsername,
            'pass' => $iCountPassword,
            'email' => $client['email'] ?? null,
            'get_custom_info' => true,
            'get_contacts' => true
        ];

        // Send POST request to iCount API
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $requestData);

        $data = $response->json();
        $http_code = $response->status();


        if ($http_code == 200 && $data['status'] === true) {
            $clientInfo = $data['client_info']; // Get client info from the response
            $propertyAddress = $client->property_addresses()->first();

            if ($clientInfo) {
                $data = [
                    'id' => $clientInfo['id'],
                    'email' => $clientInfo['email'],
                ];

                $needToUpdate = false;
                if (empty($clientInfo['fname'])) {
                    $needToUpdate = true;
                    $data['fname'] = $client['firstname'];
                }

                if (empty($clientInfo['lname'])) {
                    $needToUpdate = true;
                    $data['lname'] = $client['lastname'];
                }

                if ($propertyAddress && empty($clientInfo['bus_street']) && empty($clientInfo['bus_city']) && empty($clientInfo['bus_zip'])) {
                    $needToUpdate = true;
                    $data['bus_street'] = $propertyAddress->geo_address;
                    $data['bus_city'] = $propertyAddress->city ?? null;
                    $data['bus_zip'] = $propertyAddress->zipcode ?? null;
                }
                if ($needToUpdate) {
                    $res = $this->updateClientIcount($data);
                }
            }

            $client->update([
                'firstname' => $clientInfo['fname'] ? $clientInfo['fname'] : $client['firstname'],
                'lastname' => $clientInfo['lname'] ? $clientInfo['lname'] : $client['lastname'],
                'invoicename' => $clientInfo['company_name'] ? $clientInfo['company_name'] : $client['invoicename'],
                'phone' => $clientInfo['phone'] ? $this->fixedPhoneNumber($clientInfo['phone']) : $client['phone'],
            ]);

            // AddGoogleContactJob::dispatch($client);

            return $data;
        } else {
            echo $client->email . PHP_EOL;
        }
    }

    private function createClientIcount($client)
    {

        $iCountCompanyID = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_COMPANY_ID)
            ->value('value');

        $iCountUsername = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_USERNAME)
            ->value('value');

        $iCountPassword = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_PASSWORD)
            ->value('value');

        $url = 'https://api.icount.co.il/api/v3.php/client/create_or_update';

        $requestData = [
            'cid' => $iCountCompanyID,
            'user' => $iCountUsername,
            'pass' => $iCountPassword,
            'client_type_name' => $client['firstname'] ?? null,
            'client_name' => $client['firstname'] ?? null,
            'first_name' => $client['firstname'] ?? null,
            'last_name' => $client['lastname'] ?? null,
            'custom_client_id' => $client['id'] ?? 0,
            'client_id' => $client['id'] ?? 0,
            'phone' => $client['phone'] ?? null,
            'email' => $client['email'] ?? null,
            'vat_id' => $client['vat_number'] ?? null,
            'custom_info' => json_decode(json_encode([
                'status' => $client['status'] ?? null,
                'invoicename' => $client['invoicename'] ? $client['invoicename'] : ($client['firstname'] ?? "" . " " . $client['lastname'] ?? ""),
            ]))
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $requestData);

        $data = $response->json();
        $http_code = $response->status();

        if ($http_code != 200) {
            throw new Exception('Error: Failed to create or update user');
        }

        return $data;
    }

    private function updateClientIcount($data)
    {

        $iCountCompanyID = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_COMPANY_ID)
            ->value('value');

        $iCountUsername = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_USERNAME)
            ->value('value');

        $iCountPassword = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_PASSWORD)
            ->value('value');

        $url = 'https://api.icount.co.il/api/v3.php/client/update';

        $requestData = [
            'cid' => $iCountCompanyID,
            'user' => $iCountUsername,
            'pass' => $iCountPassword,
            'client_id' => $data['id'] ?? 0,
            'email' => $data['email'] ?? null,
            'fname' => $data['fname'] ?? null,
            'lname' => $data['lname'] ?? null,
            'bus_street' => $data['bus_street'] ?? null,
            'bus_city' => $data['bus_city'] ?? null,
            'bus_zip' => $data['bus_zip'] ?? null,
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $requestData);

        $data = $response->json();
        $http_code = $response->status();

        if ($http_code != 200) {
            throw new Exception('Error: Failed to create or update user');
        }

        return $data;
    }

    public function fixedPhoneNumber($phone)
    {
        // $phone = $client->phone;

        // 1. Remove all special characters from the phone number
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // 2. If there's any string or invalid characters in the phone, extract the digits
        if (preg_match('/\d+/', $phone, $matches)) {
            $phone = $matches[0]; // Extract the digits

            // Reapply rules on extracted phone number
            // If the phone number starts with 0, add 972 and remove the first 0
            if (strpos($phone, '0') === 0) {
                $phone = '972' . substr($phone, 1);
            }

            // If the phone number starts with +, remove the +
            if (strpos($phone, '+') === 0) {
                $phone = substr($phone, 1);
            }
        }

        $phoneLength = strlen($phone);
        if (($phoneLength === 9 || $phoneLength === 10) && strpos($phone, '972') !== 0) {
            $phone = '972' . $phone;
        }

        return $phone;
    }
}
