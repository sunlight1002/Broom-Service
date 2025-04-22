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
use Illuminate\Support\Str;
use App\Jobs\CreateJobOrder;




class SyncExcelSheetAndMakeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, GoogleAPI, JobSchedule, PriceOffered, PaymentAPI;

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
        $serviceMap = [
            '3*' => '3 Star',
            '2' => '2 Star',
            '2*' => '2 Star',
            '3' => '3 Star',
            '4*' => '4 Star',
            '*4' => '4 Star',
            '5*' => '5 Star',
            '5' => '5 Star',
            '4' => '4 Star',
            'משרד' => 'Office Cleaning',
            'ניקיון משרד' => 'Office Cleaning',
            'airbnb' => 'Airbnb',
            'Airbnb' => 'Airbnb',
            'window cleaning' => 'window cleaning',
            'חלונות 8' => 'window cleaning',
            'שיפוץ' => 'Cleaning After Renovation',
            'ניקיון לאחר שיפוץ' => 'Cleaning After Renovation',
            'אחרים' => 'Others',
        ];

        $frequencyMap = [
            'B' => 'On demand',
            'b' => 'On demand',
            '1' => 'Once Time week',
            '2' => 'Twice in week',
            '3' => '3 times a week',
            '4' => '4 times a week',
            '5' => '5 times a week',
            '6' => '6 times a week',
            '0,5' => 'Once in every two weeks',
            '0.5' => 'Once in every two weeks',
            '0.25' => 'Once a month',
            '0,25' => 'Once a month',
            '0.3' => 'Once in every three weeks',
            '0,3' => 'Once in every three weeks',
            '0.33' => 'Once in every three weeks',
            '0,33' => 'Once in every three weeks',
            'o' => 'One Time',
            'O' => 'One Time',
        ];

        $sheetsName = [
            "22" => "ויקטוריה",
            "26" => "גלדיס",
            "45" => "דימה",
            "67" => "ילנה",
            "81" => "סינטיצ",
            "87" => "סאצין",
            "117" => "ארתור",
            "119" => "מיכאלה",
            "120" => "גידלין",
            "122" => "קליטוס",
            "123" => "ויליאם",
            "124" => "יבגניה",
            "125" => "אניה",
            "130" => "לייסן",
            "132" => "אליס",
            "133" => "אינה",
            "142" => "פריאנקה",
            "144" => "איירין",
            "146" => "ולדימיר",
            "147" => "וסיליי",
            "151" => "אופליה",
            "159" => "ולדימיר 2",
            "166" => "אמינה",
            "184" => "ליובה",
            "185" => "ארתיום",
            "186" => "אניה 2",
            "187" => "אוקסנה",
            "188" => "ליידה",
            "189" => "אזת",
            "191" => "חנה",
            "192" => "אניה 3",
            "196" => "טורי",
            "197" => "סיבי",
            "198" => "אטלברט",
            "203" => "מקסים",
            "121" => "יוליה",
            "201" => "טרנס",
            "202" => "דדונו",
        ];

        $serviceArr = Services::get()->pluck('heb_name')->toArray();
        $frequencyArr = ServiceSchedule::where('status', 1)
            ->get()->pluck('name_heb')->toArray();
        $workers = User::where('status', 1)->get();
        $workers = $workers->map(function ($user) use ($sheetsName) {
            // Check if a sheet name exists for this user's id.
            // Casting $user->id to string is optional if you're sure about types.
            $key = (string) $user->id;
            $user->sheet_name = isset($sheetsName[$key]) ? $sheetsName[$key] : null;
            return $user;
        });

        $newJob = [
            'job_ids' => [], // Will cancel job + order
            'job_cancel_ids' => [] // Will cancel job only
        ];

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
            // $sheets = array_reverse($sheets);
            $grouped = [];
            $services = [];
            $client_ids = [];
            $selectedType = null;
            foreach ($sheets as $key => $sheet) {
                if ($sheet == "ינואר" || $sheet == "פברואר") {
                    continue;
                }
                $data = $this->getGoogleSheetData($sheet);
                if (empty($data)) {
                    Log::warning("Sheet $sheet is empty.");
                    continue;
                }
                $sheetId = $this->getSheetId($sheet);
                $currentDate = null;
                foreach ($data as $index => $row) {
                    if ($index == 0) {
                        continue;
                    }
                    // if($index < 1021) {
                    //     continue;
                    // }
                    if (!empty($row[3]) && (
                        preg_match('/(?:יום\s*)?[א-ת]+\s*\d{1,2}\.\d{1,2}/u', $row[3]) ||
                        preg_match('/(?:יום\s*)?[א-ת]+\s*\d{1,2},\d{1,2}/u', $row[3])
                        // preg_match('/(?:יום\s*)?[א-ת]+\s*\d{2}\d{2}/u', $row[3])
                    )) {
                        $currentDate = $this->convertDate($row[3], $sheet);
                        $grouped[$currentDate] = [];
                    }

                    

                    if ($currentDate !== null && !empty($row[1]) && Carbon::parse($currentDate)->greaterThanOrEqualTo(Carbon::parse('2025-04-21'))) {
                       $grouped[$currentDate][] = $row;
                        $id = null;
                        $email = null;
                        if (strpos(trim($row[1]), '#') === 0) {
                            $id = substr(trim($row[1]), 1);
                            // \Log::info("ID found: $id");
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
                            $currentDateObj = Carbon::parse($currentDate);
                            // $aprilFirst = Carbon::createFromDate($currentDateObj->year, 4, 1);

                            // if (!$currentDateObj->greaterThanOrEqualTo($aprilFirst)) {
                            //     continue;
                            // }

                            if ($client) {
                                $rowCount = $index + 1;
                                // if(!empty($row[20])) {
                                //     continue;
                                // }

                                // if(empty($row[22]) && ($row[6] === "TRUE")) {
                                //     echo "Row {$rowCount}: Sheet: {$sheet} Order Not Created, Client name: {$client->firstname} {$client->lastname})" . PHP_EOL . PHP_EOL . PHP_EOL;
                                //     continue;
                                // }

                                // continue;
                                // $client_ids[] = $client->id;
                                // continue;
                               
                                $offerId = trim($row[2] ?? '');
                                // Find offer
                                $offer = Offer::where('id', trim($row[2]))->where('client_id', $client->id)->first();
                                if (!$offer) {
                                    echo "Row {$rowCount}: Sheet: {$sheet} Offer not found in CRM (Offer id in Sheet: {$offerId}, Client name: {$client->firstname} {$client->lastname})" . PHP_EOL . PHP_EOL . PHP_EOL;
                                    continue;
                                }

                                $selectedService = $serviceMap[trim($row[11] ?? null) ?? null] ?? null;
                                if ($selectedService) {
                                    $selectedService = Services::where('name', $selectedService)->first();
                                }

                                $selectedFrequency = $frequencyMap[$row[18] ?? null] ?? null;
                                if ($selectedFrequency) {
                                    $selectedFrequency = ServiceSchedule::where('name', $selectedFrequency)
                                    ->orWhere('name_heb', $selectedFrequency)->first();
                               }

                                $selectedType = (isset($row[23]) && trim($row[23]) == "h") ? "hourly" : "fixed";

                                $selectedAddress = $row[19] ?? null;
                                $workerHours = $row[13] ?? null;
                                $workerHours = str_replace(',', '.', $workerHours);
                                // \Log::info("Worker hours: {$workerHours}");

                                $services = [];
                                $frequencies = [];
                                $selectedOfferDataArr = [];
                                $data = json_decode($offer->services, true);
                                foreach ($data as $d) {
                                    if ($d['template'] == 'airbnb') {
                                        if ($selectedService && (strtolower($d['name']) == strtolower($selectedService->name) || $d['name'] == $selectedService->heb_name) && ($d['freq_name'] == ($selectedFrequency->name ?? null) || $d['freq_name'] == ($selectedFrequency->name_heb ?? null))) {
                                            if (isset($d['sub_services']['sub_service_name'])) {
                                                if ($d['sub_services']['sub_service_name'] == 'Cleaning' || $d['sub_services']['sub_service_name'] == 'Cleaning - ' || $d['sub_services']['sub_service_name'] == 'ניקיון - ' || $d['sub_services']['sub_service_name'] == 'ניקיון') {
                                                    $selectedOfferDataArr[] = $d;
                                                }
                                            } else {
                                                $selectedOfferDataArr[] = $d;
                                            }
                                        }
                                        $s =  $d['name'];
                                        if (isset($d['sub_services']['sub_service_name'])) {
                                            $s .= ' (' . $d['sub_services']['sub_service_name'] . ')';
                                        }
                                        if (empty($selectedService) && $selectedFrequency) {
                                            $selectedServiceStr = trim($row[11] ?? null);
                                            if ($s == $selectedServiceStr && ($d['freq_name'] == ($selectedFrequency->name ?? null) || $d['freq_name'] == ($selectedFrequency->name_heb ?? null)) && ($d['type'] == $selectedType)) {
                                                $selectedOfferDataArr[] = $d;
                                            }
                                        }
                                        $services[] = $s;
                                        $frequencies[] = $d['freq_name'];
                                    } else {
                                        // \Log::info($offerId);
                                        // \Log::info($d['workers'][0]['jobHours']. "offerWorkerHours");
                                        // \Log::info($workerHours . " workerHours");
                                        if ($selectedService && ($d['name'] == $selectedService->name || $d['name'] == $selectedService->heb_name) && ($d['freq_name'] == ($selectedFrequency->name ?? null) || $d['freq_name'] == ($selectedFrequency->name_heb ?? null)) && ($d['type'] == $selectedType)) {
                                           \Log::info("Selected offer data arr ". $offerId);
                                            $selectedOfferDataArr[] = $d;
                                        }
                                        $services[] = $d['name'];
                                        $frequencies[] = $d['freq_name'];
                                    }

                                    // if(($d['workers'][0]['jobHours'] != $workerHours)){
                                    //     echo "Row {$rowCount}: https://crm.broomservice.co.il/admin/offered-price/edit/{$offerId}" . PHP_EOL . "Job hours not match in PO. Sheet: {$sheet} (Client name: {$client->firstname} {$client->lastname})" . PHP_EOL . PHP_EOL . PHP_EOL;
                                    // }
                                }
                                if (empty($selectedService) && $selectedFrequency) {
                                    $selectedService = Services::where('name', 'Airbnb')->first();
                                }

                                
                                // \Log::info($offers);

                                if (empty($selectedOfferDataArr) && ($row[5] === "TRUE" || $row[6] === "TRUE")) {
                                    $sheetService = trim($row[11] ?? null);
                                    $sheetFrequency = $selectedFrequency->name ?? null;
                                    echo "Row {$rowCount}: https://crm.broomservice.co.il/admin/offered-price/edit/{$offerId}" . PHP_EOL . "Frequency and service not match in PO. Sheet: {$sheet} (Client name: {$client->firstname} {$client->lastname}, Sheet Service: {$sheetService}, Sheet Frequency: {$sheetFrequency})" . PHP_EOL . PHP_EOL . PHP_EOL;
                                    continue;
                                }
                                $selectedOfferData = [];
                                $workerHours = $row[13] ?? null;
                                $workerHours = str_replace(',', '.', $workerHours);
                                if (count($selectedOfferDataArr) > 1) {
                                    foreach ($selectedOfferDataArr as $d) {
                                        $jobHours = Arr::pluck($d['workers'], 'jobHours');
                                        $isFound = in_array($workerHours, $jobHours);
                                        if ($isFound) {
                                            $selectedOfferData[] = $d;
                                        }
                                    }
                                } else {
                                    $selectedOfferData[] = $selectedOfferDataArr[0] ?? null;
                                }

                                $newSelectedOfferData = [];
                                if (count($selectedOfferData) > 1) {
                                    foreach ($selectedOfferDataArr as $d) {
                                        if ($d['template'] == 'airbnb' && isset($d['sub_services']['address']) && !empty($d['sub_services']['address'])) {
                                            if (ClientPropertyAddress::where('id', $d['sub_services']['address'])->where('address_name', $selectedAddress)->exists()) {
                                                $newSelectedOfferData[] = $d;
                                            }
                                        } else {
                                            if (ClientPropertyAddress::where('id', $d['address'])->where('address_name', $selectedAddress)->exists()) {
                                                $newSelectedOfferData[] = $d;
                                            }
                                        }
                                    }
                                    $selectedOfferData = $newSelectedOfferData;
                                }

                                if (empty($selectedOfferData) && ($row[5] === "TRUE" || $row[6] === "TRUE")) {
                                    $sheetService = trim($row[11] ?? null);
                                    $sheetFrequency = $selectedFrequency->name ?? null;
                                    echo "Row {$rowCount}: https://crm.broomservice.co.il/admin/offered-price/edit/{$offerId}" . PHP_EOL . "Address not match in PO. Sheet: {$sheet} (Client name: {$client->firstname} {$client->lastname}, Sheet Service: {$sheetService}, Sheet Frequency: {$sheetFrequency})" . PHP_EOL . PHP_EOL . PHP_EOL;
                                    continue;
                                }

                                if (count($selectedOfferData) > 1 && ($row[5] === "TRUE" || $row[6] === "TRUE")) {
                                    $sheetService = trim($row[11] ?? null);
                                    $sheetFrequency = $selectedFrequency->name ?? null;
                                    echo "Row {$rowCount}: https://crm.broomservice.co.il/admin/offered-price/edit/{$offerId}" . PHP_EOL . "Multiple services are available with the same frequency and job hours in PO. Sheet: {$sheet} (Client name: {$client->firstname} {$client->lastname}, Sheet Service: {$sheetService}, Sheet Frequency: {$sheetFrequency})" . PHP_EOL . PHP_EOL . PHP_EOL;
                                    continue;
                                }


                                // Update invoice name or client name in sheet
                                $invoiceName = trim($row[0]);
                                $fields = [];
                                // if (empty($invoiceName)) {
                                //     if (!empty($client->invoicename)) {
                                //         $invoiceName = $client->invoicename;
                                //     } else {
                                //         $invoiceName = $client->firstname . ' ' . $client->lastname;
                                //     }
                                //     $fields[] = [
                                //         'sheetId' => $sheetId, // Sheet ID
                                //         'cell' => "A" . ($index + 1), // Cell location
                                //         'type' => 'text', // Field type
                                //         'value' => trim($invoiceName),
                                //     ];
                                // }
                                $selectedWorker = $row[9] ?? null;
                                $bestMatch = null;


                                foreach ($workers as $worker) {
                                    if ($worker->sheet_name == trim($selectedWorker)) {
                                        $bestMatch = $worker->fullname;
                                    }
                                }

                                $fields[] = [
                                    'sheetId' => $sheetId, // Sheet ID
                                    'cell' => "J" . ($index + 1), // Cell location
                                    'type' => 'dropdown', // Field type
                                    'values' => $workers->pluck('fullname')->toArray(), // Dropdown options
                                    'value' => $bestMatch,
                                ];

                                $fields[] = [
                                    'sheetId' => $sheetId, // Sheet ID
                                    'cell' => "M" . ($index + 1), // Cell location
                                    'type' => 'dropdown', // Field type
                                    'values' => $services, // Dropdown options
                                    'value' => count($services) == 1 ? $services[0] : ($selectedOfferData[0]['name'] ?? null),
                                ];


                                $selectedFrequencyName = null;
                                if ($selectedFrequency) {
                                    if ($client->lng == 'en') {
                                        $selectedFrequencyName = $selectedFrequency->name;
                                    } else {
                                        $selectedFrequencyName = $selectedFrequency->name_heb;
                                    }
                                }

                                $fields[] = [
                                    'sheetId' => $sheetId, // Sheet ID
                                    'cell' => "Q" . ($index + 1), // Cell location
                                    'type' => 'dropdown', // Field type
                                    'values' => $frequencies, // Dropdown options
                                    'value' => count($frequencies) == 1 ? $frequencies[0] : ($selectedFrequencyName ?? null),
                                ];

                                $addresses = $client->property_addresses;
                                $addressesArr = $addresses->pluck('address_name')->toArray();
                                $selectedAddress = '';
                                if (isset($selectedOfferData[0]['address'])) {
                                    $selectedAddress = $addresses->where('id', $selectedOfferData[0]['address'])->first()->address_name ?? '';
                                }

                                $fields[] = [
                                    'sheetId' => $sheetId, // Sheet ID
                                    'cell' => "T" . ($index + 1), // Cell location
                                    'type' => 'dropdown', // Field type
                                    'values' => $addressesArr, // Dropdown options
                                    'value' => count($addressesArr) == 1 ? $addressesArr[0] : $selectedAddress,
                                ];

                                // if ($selectedOfferData) {
                                //     $fields[] = [
                                //         'sheetId' => $sheetId, // Sheet ID
                                //         'cell' => "D" . ($index + 1), // Cell location
                                //         'type' => 'number', // Field type
                                //         'value' => $selectedOfferData[0]['totalamount'] ?? null,
                                //     ];
                                // }



                                $services[] = trim($row[11] ?? '');

                                if ($selectedOfferData && isset($selectedOfferData[0]['type'])) {
                                    $fields[] = [
                                        'sheetId' => $sheetId, // Sheet ID
                                        'cell' => "X" . ($index + 1), // Cell location
                                        'type' => 'text', // Field type
                                        'value' => $selectedOfferData[0]['type'] == "fixed" ? "f" : "h",
                                    ];
                                }

                                // continue;
                                
                                if (isset($selectedOfferData[0])) {
                                    $res = $this->handleJob($row, $offer, $client, $currentDate, $selectedOfferDataArr, $services, $frequencies, $selectedAddress, $selectedFrequency, $selectedService, $index, $sheet, $selectedOfferData[0]);
                                
                                    if (!isset($newJob['job_ids'][$currentDate])) {
                                        $newJob['job_ids'][$currentDate] = [];
                                        $newJob['job_cancel_ids'][$currentDate] = [];
                                    }
                                
                                    if (!empty($res['job_id'])) {
                                        $newJob['job_ids'][$currentDate][] = $res['job_id'];
                                    }
                                
                                    if (!empty($res['job_cancel_id'])) {
                                        $newJob['job_cancel_ids'][$currentDate][] = $res['job_cancel_id'];
                                    }
                                
                                    sleep(3);
                                    echo ($index + 1) . PHP_EOL;
                                }
                                
                                // // \Log::info('Fields', ['fields' => $fields]);
                                // // echo json_encode($fields) . PHP_EOL;
                                $this->initGoogleConfig();
                                $response = $this->updateGoogleSheetFields($fields);
                                // echo $response . PHP_EOL;
                                // sleep(1);
                            }
                        }
                    }
                }
                \Log::info([$newJob]);

                foreach ($newJob['job_ids'] as $date => $jobIdsToKeep) {
                    // Cancel jobs NOT in the list of kept job_ids
                    $jobs = Job::whereDate('start_date', $date)
                        ->whereNotIn('id', $jobIdsToKeep)
                        ->get();
                
                    foreach ($jobs as $job) {
                        $job->update([
                            'status' => JobStatusEnum::CANCEL,
                            'cancelled_by_role' => 'admin',
                            'cancelled_at' => now(),
                        ]);
                
                        // Cancel related order if exists
                        if (isset($job->order)) {
                            $order = $job->order;
                
                            if ($order->status === 'Closed') {
                                continue; // Skip closed orders
                            }
                
                            $closeDocResponse = $this->cancelICountDocument(
                                $order->order_id,
                                'order',
                                'Creating another order'
                            );
                
                            if ($closeDocResponse['status'] !== true) {
                                \Log::error("Order cancel failed: " . $closeDocResponse['reason']);
                                continue;
                            }
                
                            $order->update(['status' => 'Cancelled']);
                
                            $order->jobs()->update([
                                'isOrdered' => 'c',
                                'order_id' => null,
                                'is_order_generated' => false
                            ]);
                        }
                    }
                }
                
                foreach ($newJob['job_cancel_ids'] as $date => $cancelOnlyJobs) {
                    $jobs = Job::whereDate('start_date', $date)
                        ->whereIn('id', $cancelOnlyJobs)
                        ->get();                       
                        
                    foreach ($jobs as $job) {
                
                        $job->update([
                            'status' => JobStatusEnum::CANCEL,
                            'cancelled_by_role' => 'admin',
                            'cancelled_at' => now(),
                        ]);

                    }
                }
                
            }
            dd(implode(',', array_unique($client_ids)));
        } catch (\Exception $e) {
            dd($e);
            Log::error("An error occurred: " . $e->getMessage());
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


    private function handleJob($row, $offer, $client, $currentDate, $selectedOfferDataArr, $services, $frequencies, $selectedAddress, $selectedFrequency, $selectedService, $index = null, $sheet, $selectedOfferData)
    {
        try {
            // Early validations
            if (!$offer) {
                return;
            }
            $rowCount = $index + 1;
            if (!$selectedService || !$selectedFrequency) {
                echo "Row: {$rowCount} No service or frequency selected for offer ID: {$offer->id} Client ID: {$client->id}" . PHP_EOL . PHP_EOL . PHP_EOL;
                return;
            }
            $contract = $offer->contract;
            if (!$contract) {
                echo "Row: {$rowCount} No contract found for offer ID: {$offer->id} Client ID: {$client->id}" . PHP_EOL . PHP_EOL . PHP_EOL;
                return;
            }

            // Set up common variables
            $serviceId       = $selectedService->id;
            $currentDateObj  = Carbon::parse($currentDate);
            $day             = $currentDateObj->format('l');
            $selectedWorker  = trim($row[9] ?? '');
            $startTime       = null;
            $endTime         = null;
            $shift           = "";

            // Cache offer data variables
            $template           = $selectedOfferData['template'] ?? null;
            $frequency          = $selectedOfferData['frequency'] ?? null;
            $service            = $selectedOfferData['service'] ?? null;
            $address            = $selectedOfferData['address'] ?? null;
            $subServices        = $selectedOfferData['sub_services'] ?? [];
            $subServicesId      = $subServices['id'] ?? null;
            $subServicesAddress = $subServices['address'] ?? null;

            $jobData = null;

            // Find worker by matching full name
            $worker = User::with('jobs')
            ->whereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ['%' . $selectedWorker . '%'])
            ->first();
            
            if (!$worker || !in_array($worker->id, ['209','185', '67'])) {
                echo "No worker found matching: " . $selectedWorker . PHP_EOL . PHP_EOL;
                return [
                    "job_cancel_id" => $jobData ? $jobData->id : null
                ];
            }

            if (!empty($row[20])) {
                $jobData = Job::with('workerShifts')->where('id', trim($row[20]))->first();
            
                if ($jobData && $jobData->start_date != $currentDate) {
                    $jobData->start_date = $currentDate;
                    $jobData->save();
                }
            
                if ($jobData) {
                    foreach ($jobData->workerShifts as $shift) {
                        $originalStart = $shift->starting_at;
                        $originalEnd = $shift->ending_at;
            
                        $startTime = Carbon::parse($originalStart)->format('H:i:s');
                        $endTime = Carbon::parse($originalEnd)->format('H:i:s');
            
                        $newStart = "{$currentDate} {$startTime}";
                        $newEnd = "{$currentDate} {$endTime}";
            
                        // Update only if the date part is different
                        if (
                            Carbon::parse($originalStart)->toDateString() !== $currentDate ||
                            Carbon::parse($originalEnd)->toDateString() !== $currentDate
                        ) {
                            $shift->starting_at = $newStart;
                            $shift->ending_at = $newEnd;
                            $shift->save();
            
                            \Log::info("Updated shift for Job ID {$jobData->id} - Shift ID {$shift->id}: {$newStart} to {$newEnd}");
                        }
                    }
                }
            
                \Log::info($jobData);
            }
            

            if(!$jobData){
                // Build the job query with JSON conditions
                $jobData = Job::where('offer_id', $offer->id)
                    ->where('start_date', $currentDate)
                    ->where('client_id', $client->id)
                    ->whereHas('contract', function ($q) {
                        $q->where('status', 'verified');
                    })
                    ->when(!empty($frequency), fn($q) => $q->where('offer_service->frequency', $frequency))
                    ->when(!empty($subServicesId), fn($q) => $q->where('offer_service->sub_services->id', $subServicesId))
                    ->when(!empty($service), fn($q) => $q->where('offer_service->service', $service))
                    ->when($template === 'airbnb' && !empty($subServicesAddress), fn($q) => $q->where('offer_service->sub_services->address', $subServicesAddress))
                    ->when(
                        (($template !== 'airbnb' && !empty($address)) || ($template === 'airbnb' && empty($subServicesAddress))),
                        fn($q) => $q->where('offer_service->address', $address)
                    )
                    ->first();
            }

                
            $tMinutes = 0;

            // Calculate job duration based on row[13]
            $durationRaw = $row[13] ?? 0;
            $durationRaw = str_replace(',', '.', $durationRaw);

            // Check if input contains '+'
            if (strpos($durationRaw, '+') !== false) {
                $parts = explode('+', $durationRaw);

                foreach ($parts as $part) {
                    $value = floatval(trim($part));
                    $wholePart = floor($value);
                    $decimalPart = $value - $wholePart;

                    if ($decimalPart == 0) {
                        $minutesDuration = 0;
                    } elseif ($decimalPart > 0 && $decimalPart <= 0.2) {
                        $minutesDuration = 15;
                    } elseif ($decimalPart > 0.2 && $decimalPart <= 0.5) {
                        $minutesDuration = 30;
                    } elseif ($decimalPart > 0.5 && $decimalPart <= 0.8) {
                        $minutesDuration = 45;
                    } else {
                        $wholePart += 1;
                        $minutesDuration = 0;
                    }

                    $tMinutes += ($wholePart * 60) + $minutesDuration;
                }
            } else {
                // Handle normal single value
                $value = floatval($durationRaw);
                $wholePart = floor($value);
                $decimalPart = $value - $wholePart;

                if ($decimalPart == 0) {
                    $minutesDuration = 0;
                } elseif ($decimalPart > 0 && $decimalPart <= 0.2) {
                    $minutesDuration = 15;
                } elseif ($decimalPart > 0.2 && $decimalPart <= 0.5) {
                    $minutesDuration = 30;
                } elseif ($decimalPart > 0.5 && $decimalPart <= 0.8) {
                    $minutesDuration = 45;
                } else {
                    $wholePart += 1;
                    $minutesDuration = 0;
                }

                $tMinutes = ($wholePart * 60) + $minutesDuration;
            }


            if ($jobData) {
                $this->updateColumnInRow(($index + 1), "U", $jobData->id, $sheet);
                if ($row[5] === "FALSE" && $row[6] === "FALSE") {
                    $jobData->status = JobStatusEnum::CANCEL;
                    $jobData->save();
                    if(isset($jobData->order)) {
                        $order = $jobData->order;
                        if ($order->status == 'Closed') {
                            return response()->json([
                                'message' => 'Job order is already closed',
                            ], 403);
                        }
        
                        $closeDocResponse = $this->cancelICountDocument(
                            $order->order_id,
                            'order',
                            'Creating another order'
                        );
        
                        if ($closeDocResponse['status'] != true) {
                            return response()->json([
                                'message' => $closeDocResponse['reason']
                            ], 500);
                        }
        
                        $order->update(['status' => 'Cancelled']);
        
                        $order->jobs()->update([
                            'isOrdered' => 'c',
                            'order_id' => NULL,
                            'is_order_generated' => false
                        ]);
                    }
                }else if($row[6] === "TRUE" && !$jobData->order) {
                    $jobData->status = JobStatusEnum::COMPLETED;
                    $jobData->actual_time_taken_minutes = $tMinutes;
                    $jobData->is_job_done = true;
                    $jobData->save();
                    $this->updateJobAmount($jobData->id);
                    
                    CreateJobOrder::dispatch($jobData->id)->onConnection('sync');
                    $jobData->refresh();
                    $orderId = $jobData->order ? $jobData->order->order_id : null;

                    if($orderId) {
                        $link = "https://app.icount.co.il/hash/show_doc.php?doctype=order&docnum=$orderId";
                        $this->updateColumnInRow(($index + 1), "W", $link, $sheet);
                    }
                }else if($row[6] === "TRUE" && $row[5] === "TRUE") {
                    if($jobData->status != JobStatusEnum::COMPLETED || !isset($jobData->order)) {
                        $jobData->status = JobStatusEnum::COMPLETED;
                        $jobData->actual_time_taken_minutes = $tMinutes;
                        $jobData->is_job_done = true;
                        $jobData->save();
                        $this->updateJobAmount($jobData->id);
                        $jobData->refresh();

                        if(isset($jobData->order)) {
                            $orderId = $jobData->order ? $jobData->order->order_id : null;
                            if($orderId) {
                                $link = "https://app.icount.co.il/hash/show_doc.php?doctype=order&docnum=$orderId";
                                $this->updateColumnInRow(($index + 1), "W", $link, $sheet);
                            }              
                        }
                    }
                }

                if($tMinutes != $jobData->actual_time_taken_minutes) {
                    $jobData->actual_time_taken_minutes = $tMinutes;
                    $jobData->save();
                }
                $selectedAddress = $row[19] ?? null;
                $selectedOffer = is_array($jobData->offer_service)
                ? $jobData->offer_service
                : json_decode($jobData->offer_service, true);

                $newSelectedOfferData = [];
                
                    if ($selectedOffer['template'] == 'airbnb' && isset($selectedOffer['sub_services']['address']) && !empty($selectedOffer['sub_services']['address'])) {
                        $addressRecord = ClientPropertyAddress::where('id', $selectedOffer['sub_services']['address'])
                            ->where('address_name', $selectedAddress)
                            ->first();
                
                        if ($addressRecord) {
                            // Update the address field
                            $selectedOffer['sub_services']['address'] = $addressRecord->id;
                
                            // Set jobData address_id
                            $jobData->address_id = $addressRecord->id;
                
                            $newSelectedOfferData[] = $selectedOffer;
                        }
                    } else {
                        $addressRecord = ClientPropertyAddress::where('id', $selectedOffer['address'] ?? null)
                            ->where('address_name', $selectedAddress)
                            ->first();
                
                        if ($addressRecord) {
                            // Update the address field
                            $selectedOffer['address'] = $addressRecord->id;
                
                            // Set jobData address_id
                            $jobData->address_id = $addressRecord->id;
                
                            $newSelectedOfferData[] = $selectedOffer;
                        }
                    }
                
                // Save modified offer_service back to jobData
                $jobData->offer_service = $selectedOffer;
                $jobData->save();
                
                \Log::info("Job already exists. ID: {$jobData->id}");
                return [
                    "job_id" => $jobData->id
                ];
            }

            if ($row[5] === "FALSE" && $row[6] === "FALSE") {
                return 0;
            }

            // Determine shift based on client's language and provided row values
            if ($client->lng == 'en') {
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
                        $shift = $row[9];
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
                        $shift = 'צהריים';
                        break;
                    case 'אחהצ':
                    case 'אחה״צ':
                    case 'ערב':
                    case 'אחר״צ':
                        $shift = "אחה״צ";
                        break;
                    default:
                        $shift = $row[10];
                        break;
                }
                // Convert day to Hebrew
                $daysMap = [
                    'Sunday'    => "ראשון",
                    'Monday'    => "שני",
                    'Tuesday'   => "שלישי",
                    'Wednesday' => "רביעי",
                    'Thursday'  => "חמישי",
                    'Friday'    => "שישי",
                    'Saturday'  => "שבת"
                ];
                $day = $daysMap[$day] ?? $day;
            }

            // Determine worker's starting time based on any existing jobs on the current date
            $hasJob = $worker->jobs()->where('start_date', $currentDate)->get();
            if ($hasJob->isNotEmpty()) {
                foreach ($hasJob as $job) {
                    if ($job->end_time) {
                        $startTime = $job->end_time;
                        break;
                    }
                }
            }
            if (!$startTime) {
                $shiftMapping = [
                    "Morning"    => "08:00:00",
                    "בוקר"       => "08:00:00",
                    "Noon"       => "12:00:00",
                    "צהריים"    => "12:00:00",
                    "After noon" => "16:00:00",
                    "Afternoon"  => "16:00:00",
                    "אחה״צ"      => "16:00:00"
                ];
                $startTime = $shiftMapping[$shift] ?? "08:00:00";
            }

            $startDateTime = Carbon::createFromFormat('H:i:s', $startTime);
            $endDateTime   = $startDateTime->copy()->addHours($wholePart)->addMinutes($minutesDuration);
            $endTime       = $endDateTime->format('H:i');


            // Update the offer services by marking one-time services
            $servicesData = is_string($offer->services) ? json_decode($offer->services, true) : $offer->services;
            foreach ($servicesData as &$srv) {
                if ($srv['service'] == 1 || (isset($srv['freq_name']) && in_array($srv['freq_name'], ['One Time', 'חד פעמי']))) {
                    $srv['is_one_time'] = true;
                }
            }
            $offer->services = json_encode($servicesData);
            $offer->save();

            // Prepare scheduling values
            $manageTime      = ManageTime::first();
            $workingWeekDays = json_decode($manageTime->days, true);
            $repeatValue     = $selectedFrequency->period;
            if ($template === 'others') {
                $s_name     = $selectedOfferData['other_title'];
                $s_heb_name = $selectedOfferData['other_title'];
            } else {
                $s_name     = $selectedService->name;
                $s_heb_name = $selectedService->heb_name;
            }
            $s_freq   = $selectedOfferData['freq_name'] ?? null;
            $s_cycle  = $selectedOfferData['cycle'] ?? null;
            $s_period = $selectedOfferData['period'] ?? null;
            $s_id     = $selectedOfferData['service'] ?? null;
            $jobGroupID = null;

            $jobDateObj      = Carbon::parse($currentDate);
            $preferredWeekDay = strtolower($jobDateObj->format('l'));
            $nextJobDate      = $this->scheduleNextJobDate($jobDateObj, $repeatValue, $preferredWeekDay, $workingWeekDays);
            $job_date         = $jobDateObj->toDateString();

            // Merge shift times and calculate total minutes and slot string
            $shiftFormattedArr = [
                [
                    'starting_at' => $startTime,
                    'ending_at'   => $endTime
                ]
            ];
            $mergedContinuousTime = $this->mergeContinuousTimes($shiftFormattedArr);
            $totalMinutes = 0;
            $slotsInString = '';
            foreach ($mergedContinuousTime as $slot) {
                if (!empty($slotsInString)) {
                    $slotsInString .= ',';
                }
                $slotStart = Carbon::parse($slot['starting_at']);
                $slotEnd   = Carbon::parse($slot['ending_at']);
                $slotsInString .= $slotStart->format('H:i') . '-' . $slotEnd->format('H:i');

                while ($slotStart->lt($slotEnd)) {
                    $slotStart->addMinutes(15);
                    $totalMinutes += 15;
                }
            }

            // Calculate total amount based on job type
            if ($selectedOfferData['type'] == 'hourly') {
                $hours = $totalMinutes / 60;
                $total_amount = $selectedOfferData['rateperhour'] * $hours;
            } elseif ($selectedOfferData['type'] == 'squaremeter') {
                $total_amount = $selectedOfferData['ratepersquaremeter'] * $selectedOfferData['totalsquaremeter'];
            } else {
                $total_amount = $selectedOfferData['fixed_price'];
            }

            $status          = JobStatusEnum::SCHEDULED;
            $statusCompleted = JobStatusEnum::COMPLETED;
            if ($this->isJobTimeConflicting($mergedContinuousTime, $job_date, $worker->id)) {
                \Log::info("Job time is conflicting with another job. Job will be unscheduled.");
                $status = JobStatusEnum::UNSCHEDULED;
            }

            $start_time = Carbon::parse($mergedContinuousTime[0]['starting_at'])->toTimeString();
            $end_time   = Carbon::parse(end($mergedContinuousTime)['ending_at'])->toTimeString();

            // Determine the correct address ID based on template
            $addressId = $address;
            if (!empty($subServicesAddress)) {
                $addressId = $subServicesAddress;
            }

            // Create the job and related entries
            $job = Job::create([
                'uuid'          => Str::uuid(),
                'worker_id'          => $worker->id,
                'client_id'          => $contract->client_id,
                'contract_id'        => $contract->id,
                'offer_id'           => $contract->offer_id,
                'start_date'         => $job_date,
                'start_time'         => $start_time,
                'end_time'           => $end_time,
                'shifts'             => $slotsInString,
                'schedule'           => $repeatValue,
                'schedule_id'        => $s_id,
                'status'             => ($row[6] === "TRUE") ? $statusCompleted : $status,
                'subtotal_amount'    => $total_amount,
                'total_amount'       => $total_amount,
                'next_start_date'    => $nextJobDate,
                'address_id'         => $addressId,
                'original_worker_id' => $worker->id,
                'original_shifts'    => $slotsInString,
                'keep_prev_worker'   => true,
                'offer_service'      => $selectedOfferData,
            ]);

            $parentJob = ParentJobs::create([
                'job_id'          => $job->id,
                'client_id'       => $contract->client_id,
                'worker_id'       => $worker->id,
                'offer_id'        => $contract->offer_id,
                'contract_id'     => $contract->id,
                'schedule'        => $repeatValue,
                'schedule_id'     => $s_id,
                'start_date'      => $job_date,
                'next_start_date' => $nextJobDate,
                'status'          => $status,
                'keep_prev_worker' => true
            ]);

            $jobService = JobService::create([
                'job_id'           => $job->id,
                'service_id'       => $s_id,
                'name'             => $s_name,
                'heb_name'         => $s_heb_name,
                'duration_minutes' => $totalMinutes,
                'freq_name'        => $s_freq,
                'cycle'            => $s_cycle,
                'period'           => $s_period,
                'total'            => $total_amount,
                'config'           => [
                    'cycle'             => $selectedFrequency->cycle,
                    'period'            => $selectedFrequency->period,
                    'preferred_weekday' => $preferredWeekDay
                ]
            ]);

            $jobGroupID = $jobGroupID ?: $job->id;
            $job->update([
                'origin_job_id' => $job->id,
                'job_group_id'  => $jobGroupID,
                'parent_job_id' => $parentJob->id
            ]);

            foreach ($mergedContinuousTime as $slot) {
                $job->workerShifts()->create($slot);
            }

            if($row[6] === "TRUE") {
                $job->status = JobStatusEnum::COMPLETED;    
                $job->actual_time_taken_minutes = $tMinutes;
                $job->is_job_done = true;
                $job->save();
                $this->updateJobAmount($job->id);
                CreateJobOrder::dispatch($job->id)->onConnection('sync');
                $job->refresh();
                $orderId = $job->order->order_id;
                if($orderId) {
                    $link = "https://app.icount.co.il/hash/show_doc.php?doctype=order&docnum=$orderId";
                    $this->updateColumnInRow(($index + 1), "W", $link, $sheet);
                }

            }

            $this->updateColumnInRow(($index + 1), "U", $job->id, $sheet);
            ScheduleNextJobOccurring::dispatch($job->id, null)->onConnection('sync');

            // $newLeadStatus = $this->getClientLeadStatusBasedOnJobs($client);
            $newLeadStatus = LeadStatusEnum::ACTIVE_CLIENT;
            if (!$client->lead_status || $client->lead_status->lead_status != $newLeadStatus) {
                $client->lead_status()->updateOrCreate([], ['lead_status' => $newLeadStatus]);
            }

            return [
                "job_id" => $job->id
            ];
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function updateColumnInRow($rowIndex, $columnLetter, $value, $sheet = null)
    {
        $updateRange = "{$sheet}!{$columnLetter}{$rowIndex}";
        $jobData = [
            $value
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->googleAccessToken,
            'Content-Type' => 'application/json',
        ])->put("{$this->googleSheetEndpoint}{$this->spreadsheetId}/values/{$updateRange}?valueInputOption=USER_ENTERED", [
            "values" => [$jobData]
        ]);
        \Log::info($response->body());
        return $response->body();
    }


    public function detectLanguage($text)
    {
        return preg_match('/[\x{0590}-\x{05FF}]/u', $text) ? 'hebrew' : 'english';
    }

    public function updateTextValueInGoogleSheet($cell, $value)
    {
        $metadataUrl = $this->googleSheetEndpoint . $this->spreadsheetId . "/values/{$cell}?valueInputOption=USER_ENTERED";

        $metadataResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->googleAccessToken,
            'Content-Type' => 'application/json',
        ])->put($metadataUrl, ['values' => [[$value]]]);

        if ($metadataResponse->successful()) {
            return $metadataResponse->json();
        }
        return false;
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

    /**
     * Get the Sheet ID dynamically
     */
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