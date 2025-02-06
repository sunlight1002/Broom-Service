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
            $sheets = array_reverse($sheets);
            foreach ($sheets as $key => $sheet) {
                $sheetId = $this->getSheetId($sheet);

                $currentDate = "2025-02-08";
                $jobs = Job::with(['client', 'worker', 'offer.service', 'contract'])
                        ->where('start_date', '>=', $currentDate)
                        ->get();
                foreach($jobs as $job) {
                    $this->addJobToGoogleSheet($job, $sheet, $sheetId);
                }
            }
        } catch (\Exception $e) {
            dd($e);
            Log::error("An error occurred: " . $e->getMessage());
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


    public function addJobToGoogleSheet($job, $sheetName , $sheetId)
    {
        $spreadsheetId = $this->spreadsheetId;
        $fields = [];

        // Google Sheets API endpoint for appending values
        $appendEndpoint = "{$this->googleSheetEndpoint}{$spreadsheetId}/values/{$sheetName}!A1:Z1000:append?valueInputOption=USER_ENTERED";

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
            foreach ($jsonServices as $key => $jsonService) {
                if($jsonService['frequency'] == $frequency->id) {
                    // \Log::info($jsonService['service']);
                    $serviceId = $jsonService['service'];
                }
            }
        }
        $service = Services::find($serviceId);

        $startTime = Carbon::createFromFormat('H:i:s', $job->start_time);
        $endTime = Carbon::createFromFormat('H:i:s', $job->end_time);
        $diffInHours = $startTime->diffInHours($endTime);
        $diffInMinutes = $startTime->diffInMinutes($endTime) % 60;

        $addressName = ClientPropertyAddress::where('id', $job->address_id)->value('address_name');

        $serviceName = $client->lng == "heb" ? $service->heb_name : $service->name;

        $jobData = [
            $client->invoicename ?? "",
            $client->id ?? "",
            $offer->id ?? "",
            "", // Empty cell
            "", // Empty cell
            "TRUE", // Checkbox
            "FALSE", // Checkbox
            "", // Empty cell
            ($worker->firstname ?? "")." ".($worker->lastname ?? ""), // Default selected worker
            ($worker->firstname ?? "")." ".($worker->lastname ?? ""), // Worker dropdown
            ($job->start_time ?? "")." - ".($job->end_time ?? ""),
            "", // Empty cell
            $serviceName, // Default selected service
            $diffInHours.":".$diffInMinutes, // Duration in HH:MM
            "", // Empty cell
            "", // Empty cell
            $frequencyName ?? "", // Default selected frequency
            $addressName ?? "",
            "", // Empty cell
            $addressName ?? "", // Default selected address
            "", // Empty cell
            "", // Empty cell
            "", // Empty cell
            $job->id ?? ""
        ];


        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->googleAccessToken,
            'Content-Type' => 'application/json',
        ])->post($appendEndpoint, [
            "values" => [$jobData]
        ]);


        $updatedRange = json_decode($response->body(), true)['updates']['updatedRange'] ?? null;
        if (!$updatedRange) {
            \Log::error("Failed to append job data.");
            return;
        }

        // Extract row number
        preg_match('/\d+$/', $updatedRange, $matches);
        $rowNumber = $matches[0] ?? null;
        \Log::info("Row number: " . $rowNumber);

        if (!$rowNumber) {
            \Log::error("Could not determine row number.");
            return;
        }


        $fields[] = [
            'sheetId' => $sheetId, // Sheet ID
            'cell' => "J".$rowNumber, // Cell location
            'type' => 'dropdown', // Field type
            'values' => $workerArr, // Dropdown options
            'value' => $worker,
        ];


        $fields[] = [
            'sheetId' => $sheetId, // Sheet ID
            'cell' => "M".$rowNumber, // Cell location
            'type' => 'dropdown', // Field type
            'values' => $serviceArr, // Dropdown options
            'value' => $serviceName,
        ];

        $fields[] = [
            'sheetId' => $sheetId, // Sheet ID
            'cell' => "Q".$rowNumber, // Cell location
            'type' => 'dropdown', // Field type
            'values' => $frequencyArr, // Dropdown options
            'value' => $frequencyName,
        ];

        $fields[] = [
            'sheetId' => $sheetId, // Sheet ID
            'cell' => "T".$rowNumber, // Cell location
            'type' => 'dropdown', // Field type
            'values' => $addressArr, // Dropdown options
            'value' => $addressName,
        ];

        // Apply dropdowns & checkboxes
        $res = $this->updateGoogleSheetFields($fields);
        \Log::info([$res]);

        \Log::info("Added Job ID {$job->id} to Google Sheet: " . $response->body());
        return $response->body();
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
            \Log::info("Cell {$cell} converted to:", $this->convertRowCol($cell));


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

    private function createNewSheet($spreadsheetId, $sheetTitle)
    {
        $endpoint = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}:batchUpdate";

        $data = [
            "requests" => [
                [
                    "addSheet" => [
                        "properties" => [
                            "title" => $sheetTitle
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
