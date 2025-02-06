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


class SyncExcelSheetAndMakeJob implements ShouldQueue
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
        $serviceMap = [
            '3*' => '3 Star',
            '3' => '3 Star',
            '4*' => '4 Star',
            '5*' => '5 Star',
            '5' => '5 Star',
            '4' => '4 Star',
            'משרד' => 'Office Cleaning',
        ];

        $frequencyMap = [
            'B' => 'On demand',
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
            $grouped = [];
            $services = [];
            $client_ids = [];
            foreach ($sheets as $key => $sheet) {
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
                    if (!empty($row[3]) && (
                        preg_match('/(?:יום\s*)?[א-ת]+\s*\d{1,2}\.\d{1,2}/u', $row[3]) ||
                        preg_match('/(?:יום\s*)?[א-ת]+\s*\d{1,2},\d{1,2}/u', $row[3])
                        // preg_match('/(?:יום\s*)?[א-ת]+\s*\d{2}\d{2}/u', $row[3])
                    )) {
                        $currentDate = $this->convertDate($row[3], $sheet);
                        $grouped[$currentDate] = [];
                    }

                    if ($currentDate !== null && !empty($row[1])) {
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

                            if ($client) {
                                $client_ids[] = $client->id;
                                $fields = [];

                                $selectedAddress = $row[17] ?? null;

                                $selectedWorker = $row[8] ?? null;
                                $bestMatch = null;
                                $highestSimilarity = 0;

                                
                                $service = $row[11] ?? null;
                                $workerHours = $row[13] ?? null;
                                $workerHours = str_replace(',', '.', $workerHours);
                                // $selectedService = $serviceMap[trim($row[12] ?? null) ?? null] ?? null;
                                $selectedService = trim($row[12] ?? null) ?? null;
                                \Log::info("Selected Service: $selectedService");
                                if ($selectedService) {
                                    $selectedService = Services::where('name', $selectedService)
                                    ->orWhere('heb_name', $selectedService)
                                    ->first();
                                }

                                // $selectedFrequency = $frequencyMap[$row[18] ?? null] ?? null;
                                $selectedFrequency = $row[16] ?? null;
                                if ($selectedFrequency) {
                                    $selectedFrequency = ServiceSchedule::where('name', $selectedFrequency)
                                    ->orWhere('name_heb', $selectedFrequency)
                                    ->first();
                                }

                                $offer = null;
                                $services = [];
                                $frequencies = [];
                                $selectedOfferDataArr = [];
                                $selectedOfferData = null;
                                if (is_numeric(trim($row[2]))) {
                                    $offer = Offer::where('id', trim($row[2]))->where('client_id', $client->id)->first();
                                    if ($offer) {
                                        // \Log::info("Offer ID: " . $offer->id);
                                        $data = json_decode($offer->services, true);
                                        // \Log::info("Services: ");
                                        // \Log::info($data);
                                        if (count($data) == 1) {
                                            $selectedOfferDataArr[] = $data[0];
                                            $services[] = $data[0]['name'];
                                            $frequencies[] = $data[0]['freq_name'];
                                        } else {
                                            foreach ($data as $d) {
                                                // \Log::info($d);
                                                // $jobHours = Arr::pluck($d['workers'], 'jobHours');
                                                // $isFound = in_array($workerHours, $jobHours);
                                                if ($selectedService && ($d['name'] == $selectedService->name || $d['name'] == $selectedService->heb_name) && ($d['freq_name'] == ($selectedFrequency->name ?? null) || $d['freq_name'] == ($selectedFrequency->name_heb ?? null))) {
                                                    $selectedOfferDataArr[] = $d;
                                                }
                                                $services[] = $d['name'];
                                                $frequencies[] = $d['freq_name'];
                                            }
                                        }

                                        if($row[5] == TRUE){
                                            $this->handleJob($row, $offer, $client, $currentDate,$selectedOfferDataArr, $services, $frequencies, $selectedAddress, $selectedFrequency, $selectedService, );
                                        }
                                    }
                                } else {
                                    $offers = $client->offers;
                                    foreach ($offers as $offer) {
                                        $data = json_decode($offer->services, true);
                                        if (count($data) == 1) {
                                            $selectedOfferDataArr[] = $data[0];
                                            $services[] = $data[0]['name'];
                                            $frequencies[] = $data[0]['freq_name'];
                                        } else {
                                            foreach ($data as $d) {
                                                if ($selectedService && ($d['name'] == $selectedService->name || $d['name'] == $selectedService->heb_name) && ($d['freq_name'] == ($selectedFrequency->name ?? null) || $d['freq_name'] == ($selectedFrequency->name_heb ?? null))) {
                                                    $selectedOfferDataArr[] = $d;
                                                }
                                                $services[] = $d['name'];
                                                $frequencies[] = $d['freq_name'];
                                            }
                                        }
                                    }
                                }
                                // \Log::info($offers);

                                if(count($selectedOfferDataArr) > 1) {
                                    foreach($selectedOfferDataArr as $d) {
                                        $jobHours = Arr::pluck($d['workers'], 'jobHours');
                                        $isFound = in_array($workerHours, $jobHours);
                                        if($isFound) {
                                            $selectedOfferData = $d;
                                        }
                                    }
                                } else {
                                    $selectedOfferData = $selectedOfferDataArr[0] ?? null;
                                }

                                $selectedFrequencyName = null;
                                if($selectedFrequency) {
                                    if($client->lng == 'en') {
                                        $selectedFrequencyName = $selectedFrequency->name;
                                    } else {
                                        $selectedFrequencyName = $selectedFrequency->name_heb;
                                    }
                                }

                                $addresses = $client->property_addresses()->when($selectedOfferData, function($q) use ($selectedOfferData) {
                                    $q->where('id', $selectedOfferData['address']);
                                })->get()->pluck('address_name')->toArray();
                                $bestMatch = null;
                                $highestSimilarity = 0;

                                foreach ($addresses as $address) {
                                    similar_text($selectedAddress, $address, $percent);
                                    if ($percent > $highestSimilarity) {
                                        $highestSimilarity = $percent;
                                        $bestMatch = $address;
                                    }

                                    similar_text($address, $selectedAddress, $percent);
                                    if ($percent > $highestSimilarity) {
                                        $highestSimilarity = $percent;
                                        $bestMatch = $address;
                                    }
                                }

                                sleep(3);
                                echo ($index + 1) . PHP_EOL;
                            }
                            $serviceName = $serviceMap[trim($service)] ?? null;
                            if (!empty($serviceName)) {
                            }
                            if (!$offer && $client) {
                                // $offer = $client->offers()->where('status', 'accepted')->orderBy('created_at', 'DESC')->first();
                            }
                            if (!$offer) {

                                $services = [];
                            }
                        }
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


    private function handleJob($row, $offer, $client, $currentDate, $selectedOfferDataArr, $services, $frequencies, $selectedAddress, $selectedFrequency, $selectedService)
    {
        try {
            if ($offer) {
                if(!$selectedService || !$selectedFrequency) {
                    return;
                }

                // if($row[5] == FALSE && $row[6] == FALSE){
                //     return;
                // }
                \Log::info("firstOffer ID: " . $offer->id);
                \Log::info("firstClient ID: " . $offer->client_id);

                $contract = $offer->contract;
                // \Log::info($contract->id);
                $Service = $offer->service;
                $serviceId = $selectedService->id;
                $currentDateObj = Carbon::parse($currentDate); // Current date
                $selectedWorker = $row[9] ?? null;
                $startTime = null;
                $endTime = null;
                $shift = "";
                $day = $currentDateObj->format('l');
                $properHours = null;


                $jobData = Job::where('offer_id', $offer->id)
                        ->where('start_date', $currentDate)
                        ->where('client_id', $client->id)
                        ->whereHas('contract', function ($q) {
                            $q->where('status', 'verified');                  
                        })
                        ->whereHas('offer', function ($q) use ($selectedFrequency, $serviceId) {
                            $q->whereRaw("
                                EXISTS (
                                    SELECT 1 
                                    FROM JSON_TABLE(offers.services, '$[*]' 
                                        COLUMNS (
                                            service INT PATH '$.service',
                                            frequency INT PATH '$.frequency'
                                        )
                                    ) AS services_table
                                    WHERE services_table.service = ? 
                                    AND services_table.frequency = ?
                                )
                            ", [$serviceId, $selectedFrequency->id]);
                        })
                        ->first();
            

                        // \Log::info($selectedFrequency->id);
                        // \Log::info($serviceId);

                    if ($jobData) {
                        \Log::info("Job already exists. $jobData->id");
                        return;
                    }

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

                    $worker = User::with('jobs')
                        ->where('status', 1)
                        ->whereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ['%' . trim($selectedWorker) . '%'])
                        ->first();

                    if ($worker) {
                        // Check if the worker has a job for the given date
                        $hasJob = $worker->jobs()->where('start_date', $currentDate)->get();

                        if (count($hasJob) > 0) {
                            foreach ($hasJob as $job) {
                                if ($job->end_time) {
                                    $startTime = $job->end_time;
                                }
                            }
                            // \Log::info("Worker found and has a job on $currentDate.");
                        }else{
                            // Default start time based on shift
                            switch ($shift) {
                                case "Morning":
                                case "בוקר":
                                    $startTime = "08:00:00";
                                    break;
                        
                                case "Noon":
                                case "צהריים":
                                    $startTime = "12:00:00";
                                    break;
                        
                                case "After noon":
                                case "אחה״צ":
                                    $startTime = "16:00:00";
                                    break;
                        
                                default:
                                    $startTime = "08:00:00";
                                    break;
                            }
                        }
                        // \Log::info("Worker found start time: $startTime.");
                        \Log::info("Offer ID: " . $offer->id);
                        \Log::info("Client ID: " . $offer->client_id);

                        $value = str_replace(',', '.', $row[13]);
                        $value = floatval($value); // Convert to float
                        
                        $wholePart = floor($value); // Extract whole number part
                        $decimalPart = $value - $wholePart; // Extract decimal part
                        
                        // Convert decimal part to minutes
                        if ($decimalPart == 0) {
                            $minutes = 0;
                        } elseif ($decimalPart > 0 && $decimalPart <= 0.2) {
                            $minutes = 15;
                        } elseif ($decimalPart > 0.2 && $decimalPart <= 0.5) {
                            $minutes = 30;
                        } elseif ($decimalPart > 0.5 && $decimalPart <= 0.8) {
                            $minutes = 45;
                        } else {
                            // Round up to the next hour
                            $wholePart += 1;
                            $minutes = 0;
                        }
                        
                        // Calculate end time using Carbon
                        $startDateTime = Carbon::createFromFormat('H:i:s', $startTime);
                        $endDateTime = $startDateTime->copy()->addHours($wholePart)->addMinutes($minutes);
                        
                        $endTime = $endDateTime->format('H:i');

                        // \Log::info("Worker found end time: $endTime.");
                        

                        if (!$client) {
                            return response()->json([
                                'message' => 'Client not found'
                            ], 404);
                        }

                        // Decode services (if stored as JSON)
                        $services = is_string($offer->services) ? json_decode($offer->services, true) : $offer->services;

                        // Locate the service and add is_one_time field
                        foreach ($services as &$service) {
                            if (($service['service'] == 1) || isset($service['freq_name']) && (in_array($service['freq_name'], ['One Time', 'חד פעמי']))) {
                                $service['is_one_time'] = true; // Add the field
                            }
                        }

                        // Save updated services back to the offer
                        $offer->services = json_encode($services);
                        $offer->save();


                        $manageTime = ManageTime::first();
                        $workingWeekDays = json_decode($manageTime->days);


                        $offerServices = $this->formatServices($offer, false);
                        $filtered = Arr::where($offerServices, function ($value, $key) use ($selectedService) {
                            return $value['service'] == $selectedService->id;
                        });

                        $selectedService = head($filtered);
                        // \Log::info($selectedService);

                        $service = Services::find($serviceId);
                        $serviceSchedule = ServiceSchedule::find($selectedFrequency->id);

                        $repeat_value = $serviceSchedule->period;
                        if ($selectedService['template'] == 'others') {
                            $s_name = $selectedService['other_title'];
                            $s_heb_name = $selectedService['other_title'];
                        } else {
                            $s_name = $service->name;
                            $s_heb_name = $service->heb_name;
                        }
                        $s_freq   = $selectedService['freq_name'];
                        $s_cycle  = $selectedService['cycle'];
                        $s_period = $selectedService['period'];
                        $s_id     = $selectedService['service'];

                        $jobGroupID = NULL;

                        
                        $job_date = Carbon::parse($currentDate);
                        $preferredWeekDay = strtolower($job_date->format('l'));
                        $next_job_date = $this->scheduleNextJobDate($job_date, $repeat_value, $preferredWeekDay, $workingWeekDays);

                        $job_date = $job_date->toDateString();

                        $shiftFormattedArr = [];

                        $shiftFormattedArr[0] = [
                            'starting_at' => $startTime,
                            'ending_at' => $endTime
                        ];

                        $mergedContinuousTime = $this->mergeContinuousTimes($shiftFormattedArr);

                        $minutes = 0;
                        $slotsInString = '';
                        foreach ($mergedContinuousTime as $key => $slot) {
                            if (!empty($slotsInString)) {
                                $slotsInString .= ',';
                            }

                            $slotsInString .= Carbon::parse($slot['starting_at'])->format('H:i') . '-' . Carbon::parse($slot['ending_at'])->format('H:i');

                            // Calculate duration in 15-minute slots
                            $start = Carbon::parse($slot['starting_at']);
                            $end = Carbon::parse($slot['ending_at']);
                            $interval = 15; // in minutes
                            while ($start < $end) {
                                $start->addMinutes($interval);
                                $minutes += $interval;
                            }
                        }

                        if ($selectedService['type'] == 'hourly') {
                            $hours = ($minutes / 60);
                            $total_amount = ($selectedService['rateperhour'] * $hours);
                        } else if($selectedService['type'] == 'squaremeter') {
                            $total_amount = ($selectedService['ratepersquaremeter'] * $selectedService['totalsquaremeter']);
                        } else {
                            $total_amount = ($selectedService['fixed_price']);
                        }

                        $status = JobStatusEnum::SCHEDULED;

                        if ($this->isJobTimeConflicting($mergedContinuousTime, $job_date, $worker->id)) {
                            \Log::info("Job time is conflicting with another job. Job will be unscheduled.");
                            $status = JobStatusEnum::UNSCHEDULED;
                        }

                        $start_time = Carbon::parse($mergedContinuousTime[0]['starting_at'])->toTimeString();
                        $end_time = Carbon::parse($mergedContinuousTime[count($mergedContinuousTime) - 1]['ending_at'])->toTimeString();

                        $job = Job::create([
                            'worker_id'     => $worker->id,
                            'client_id'     => $contract->client_id,
                            'contract_id'   => $contract->id,
                            'offer_id'      => $contract->offer_id,
                            'start_date'    => $job_date,
                            'start_time'    => $start_time,
                            'end_time'      => $end_time,
                            'shifts'        => $slotsInString,
                            'schedule'      => $repeat_value,
                            'schedule_id'   => $s_id,
                            'status'        => $status,
                            'subtotal_amount'  => $total_amount,
                            'total_amount'  => $total_amount,
                            'next_start_date'   => $next_job_date,
                            'address_id'        => $selectedService['address']['id'],
                            'original_worker_id'     => $worker->id,
                            'original_shifts'        => $slotsInString,
                        ]);

                        // Create entry in ParentJobs
                        $parentJob = ParentJobs::create([
                            'job_id' => $job->id,
                            'client_id' => $contract->client_id,
                            'worker_id' => $worker->id,
                            'offer_id' => $contract->offer_id,
                            'contract_id' => $contract->id,
                            'schedule'      => $repeat_value,
                            'schedule_id'   => $s_id,
                            'start_date' => $job_date,
                            'next_start_date'   => $next_job_date,
                            'status' => $status, // You can set this according to your needs
                        ]);



                        $jobser = JobService::create([
                            'job_id'            => $job->id,
                            'service_id'        => $s_id,
                            'name'              => $s_name,
                            'heb_name'          => $s_heb_name,
                            'duration_minutes'  => $minutes,
                            'freq_name'         => $s_freq,
                            'cycle'             => $s_cycle,
                            'period'            => $s_period,
                            'total'             => $total_amount,
                            'config'            => [
                                'cycle'             => $serviceSchedule->cycle,
                                'period'            => $serviceSchedule->period,
                                'preferred_weekday' => $preferredWeekDay
                            ]
                        ]);

                        $jobGroupID = $jobGroupID ? $jobGroupID : $job->id;

                        $job->update([
                            'origin_job_id' => $job->id,
                            'job_group_id' => $jobGroupID,
                            'parent_job_id' => $parentJob->id
                        ]);

                        foreach ($mergedContinuousTime as $key => $shift) {
                            $job->workerShifts()->create($shift);
                        }

                        // $job->load(['client', 'worker', 'jobservice', 'propertyAddress', 'offer']);

                        // // Send notification to client
                        // $jobData = $job->toArray();

                        ScheduleNextJobOccurring::dispatch($job->id, null);


                        $newLeadStatus = $this->getClientLeadStatusBasedOnJobs($client);

                        if (!$client->lead_status || $client->lead_status->lead_status != $newLeadStatus) {
                            $client->lead_status()->updateOrCreate(
                                [],
                                ['lead_status' => $newLeadStatus]
                            );

                        }
                    } else {
                        \Log::info("No worker found matching: " . $selectedWorker);
                    }
                }
        } catch (\Throwable $th) {
            throw $th;
        }

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
