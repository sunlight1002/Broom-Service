<?php

namespace App\Jobs;

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
use App\Models\Services;
use App\Models\ServiceSchedule;
use App\Enums\SettingKeyEnum;
use App\Traits\GoogleAPI;
use App\Traits\ICountDocument;
use App\Traits\PaymentAPI;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use App\Enums\ContractStatusEnum;
use App\Enums\LeadStatusEnum;
use App\Enums\JobStatusEnum;
use Illuminate\Http\Request;
use Exception;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Arr;


class SyncGoogleSheetDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, GoogleAPI;

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
            'airbnb' => 'Airbnb',
            'window cleaning' => 'window cleaning',
            'חלונות 8' => 'window cleaning',
            'שיפוץ' => 'Cleaning After Renovation',
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

        // $filePath = storage_path('crm_client.xlsx');

        // if (!file_exists($filePath)) {
        //     Log::error('File not found at: ' . $filePath);
        //     return;
        // }
        // $data = Excel::toArray([], $filePath);
        // $firstSheet = $data[0] ?? [];

        // if (empty($firstSheet)) {
        //     Log::error("The first sheet is empty.");
        //     return;
        // }

        // $limitedRows = array_slice($firstSheet, 0, 1050);
        // $updateData = [];
        // foreach ($limitedRows as $rowIndex => $row) {
        //     if ($rowIndex == 0) {
        //         continue;
        //     }
        //     if (!empty($row[0]) || !empty($row[4])) {
        //         $updateData[] = $row;
        //     }
        // }
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

                    // if($index < 21) {
                    //     continue;
                    // }

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
                            if (!$client && !empty($email)) {
                                $client = Client::create([
                                    'email' => $email,
                                    'invoicename' => trim($row[0]),
                                ]);
                            }
                            // $IcountData = $this->getIcountClientInfo($client);
                            if ($client) {
                                $jobConfirm = trim($row[5] ?? '');
                                $jobCompleted = trim($row[6] ?? '');

                                if($jobConfirm != 'TRUE' && $jobCompleted != 'TRUE') {
                                    continue;
                                }

                                $client_ids[] = $client->id;
                                $rowCount = $index + 1;
                                $offerId = trim($row[2] ?? '');
                                // Find offer
                                $offer = Offer::where('id', trim($row[2]))->where('client_id', $client->id)->first();
                                if (!$offer) {
                                    echo "Row {$rowCount}: Offer not found in CRM (Offer id in Sheet: {$offerId}, Client name: {$client->firstname} {$client->lastname})" . PHP_EOL . PHP_EOL . PHP_EOL;
                                    continue;
                                }

                                $selectedService = $serviceMap[trim($row[11] ?? null) ?? null] ?? null;
                                if ($selectedService) {
                                    $selectedService = Services::where('name', $selectedService)->first();
                                }

                                $selectedFrequency = $frequencyMap[$row[18] ?? null] ?? null;
                                if ($selectedFrequency) {
                                    $selectedFrequency = ServiceSchedule::where('name', $selectedFrequency)->first();
                                }

                                $services = [];
                                $frequencies = [];
                                $selectedOfferDataArr = [];

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

                                if (empty($selectedOfferDataArr)) {
                                    $sheetService = trim($row[11] ?? null);
                                    $sheetFrequency = $selectedFrequency->name ?? null;
                                    echo "Row {$rowCount}: https://crm.broomservice.co.il/admin/offered-price/edit/{$offerId}" . PHP_EOL . "Frequency and service not match in PO (Client name: {$client->firstname} {$client->lastname}, Sheet Service: {$sheetService}, Sheet Frequency: {$sheetFrequency})" . PHP_EOL . PHP_EOL . PHP_EOL;
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



                                if(count($selectedOfferData) > 1) {
                                    $sheetService = trim($row[11] ?? null);
                                    $sheetFrequency = $selectedFrequency->name ?? null;
                                    echo "Row {$rowCount}: https://crm.broomservice.co.il/admin/offered-price/edit/{$offerId}" . PHP_EOL . "Multiple services are available with the same frequency and job hours in PO (Client name: {$client->firstname} {$client->lastname}, Sheet Service: {$sheetService}, Sheet Frequency: {$sheetFrequency})" . PHP_EOL . PHP_EOL . PHP_EOL;
                                    continue;
                                }

                                // Update invoice name or client name in sheet
                                $invoiceName = trim($row[0]);
                                $fields = [];
                                if (empty($invoiceName)) {
                                    if (!empty($client->invoicename)) {
                                        $invoiceName = $client->invoicename;
                                    } else {
                                        $invoiceName = $client->firstname . ' ' . $client->lastname;
                                    }
                                    $fields[] = [
                                        'sheetId' => $sheetId, // Sheet ID
                                        'cell' => "A" . ($index + 1), // Cell location
                                        'type' => 'text', // Field type
                                        'value' => trim($invoiceName),
                                    ];
                                }
                                $selectedAddress = $row[17] ?? null;
                                $selectedWorker = $row[8] ?? null;
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
                                if(isset($selectedOfferData[0]['address'])) {
                                    $selectedAddress = $addresses->where('id', $selectedOfferData[0]['address'])->first()->address_name ?? '';
                                }

                                $fields[] = [
                                    'sheetId' => $sheetId, // Sheet ID
                                    'cell' => "T" . ($index + 1), // Cell location
                                    'type' => 'dropdown', // Field type
                                    'values' => $addressesArr, // Dropdown options
                                    'value' => count($addressesArr) == 1 ? $addressesArr[0] : $selectedAddress,
                                ];

                                if ($selectedOfferData) {
                                    $fields[] = [
                                        'sheetId' => $sheetId, // Sheet ID
                                        'cell' => "D" . ($index + 1), // Cell location
                                        'type' => 'number', // Field type
                                        'value' => $selectedOfferData[0]['totalamount'] ?? null,
                                    ];
                                }



                                $services[] = trim($row[11] ?? '');
                                // continue;


                                // \Log::info('Fields', ['fields' => $fields]);
                                // echo json_encode($fields) . PHP_EOL;
                                // $response = $this->updateGoogleSheetFields($fields);
                                // echo $response . PHP_EOL;
                                // sleep(1);
                                // echo ($index + 1) . PHP_EOL;
                            }
                        }
                    }
                }
                dd(array_unique($services));
                dd(implode(',', array_unique($client_ids)));
            }


            $rows = [];
            $sheetName = '';

            foreach ($rows as $sheet => $sheetRows) {
                Log::info("Processing sheet: $sheet");

                $sheetName = $sheet;

                // Skip empty sheets
                if (empty($sheetRows)) {
                    Log::warning("Sheet $sheet is empty.");
                    continue;
                }

                foreach ($sheetRows as $rowIndex => $row) {
                    Log::info("Processing row $rowIndex from sheet $sheet", ['row' => $row]);

                    // Example: Handle each row by passing to your methods
                    $client = $this->createClient($row);
                    if ($client) {
                        $this->handleOfferData($row, $client);
                    }
                }
            }
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

    public function createClient($row)
    {
        try {
            $clientName = $row[0] ?? null;
            $identifier = $row[1] ?? null;
            $date = $row[3] ?? null;
            $address = $row[14] ?? null;

            if ($identifier && filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                return $this->createOrUpdateClientByEmail($identifier, $clientName, $address);
            }

            if ($identifier && strpos($identifier, '#') === 0) {
                return $this->findClientById(substr($identifier, 1), $clientName, $address);
            }

            \Log::warning("No valid client data found in the row.");
            return null;
        } catch (\Throwable $th) {
            \Log::error('Error in createClient function: ' . $th);
            throw $th;
        }
    }

    private function createOrUpdateClientByEmail($email, $clientName, $address)
    {
        $existingClient = Client::where('email', $email)->first();

        if (!$existingClient) {
            $firstName = $clientName ?? explode('@', $email)[0];
            \Log::info("Creating new client: $firstName");
            $newClient = Client::create([
                'firstname' => $firstName,
                'email' => $email,
            ]);

            if ($clientName) {
                $newClient->invoicename = $clientName;
                $newClient->save();
            }
            $iCountResponse = $this->createClientIcount($newClient);

            \Log::info("New client created in database as well as iCount: $email");

            if ($address) {
                $this->addAddressToClient($newClient, $address);
            }

            return $newClient;
        } else {
            if ($clientName) {
                $existingClient->invoicename = $clientName;
                $existingClient->save();
            }

            if ($address) {
                $this->addAddressToClient($existingClient, $address);
            }

            $iCountResponse = $this->createClientIcount($existingClient);

            \Log::info("Client already exists: $email");
            return $existingClient;
        }
    }

    private function findClientById($id, $clientName, $address)
    {
        if (is_numeric($id)) {
            $client = Client::find($id);

            if ($client) {
                if ($address) {
                    $this->addAddressToClient($client, $address);
                }

                if ($clientName) {
                    $client->invoicename = $clientName;
                    $client->save();
                }

                $IcountData = $this->getIcountClientInfo($client);

                if ($IcountData) {
                    \Log::info("Icount Data: " . json_encode($IcountData));
                }

                \Log::info("Client found with ID: $id");
                return $client;
            }

            \Log::warning("Client with ID: $id not found.");
        } else {
            \Log::error("Invalid ID format: $id");
        }

        return null;
    }

    private function addAddressToClient($client, $address)
    {
        $language = $this->detectLanguage($address);
        $languageParam = ($language === 'hebrew') ? 'he' : 'en';

        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $address,
            'key' => config('services.google.map_key'),
            'language' => $languageParam
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $fullAddress = $data['results'][0]['formatted_address'] ?? null;

            foreach ($client->property_addresses as $propertyAddress) {
                if ($propertyAddress->geo_address === $fullAddress) {
                    \Log::info("Address already exists for offer.");
                    return;
                }
            }

            // $client->property_addresses()->create([
            //     'geo_address' => $fullAddress,
            //     'latitude' => $data['results'][0]['geometry']['location']['lat'] ?? null,
            //     'longitude' => $data['results'][0]['geometry']['location']['lng'] ?? null,
            //     'address_name' => $data['results'][0]['address_components'][2]['long_name'] ?? null,
            // ]);

            \Log::info("New address added to offer.");
        } else {
            \Log::error("Failed to fetch geocoding data for address: $address");
        }
    }

    public function handleOfferData($row, $client)
    {
        try {
            $offerIdentifier = $row[2] ?? null;
            $address = $row[14] ?? null;
            $jobHours = $row[11] ?? null;
            $serviceIdentifier = $row[10] ?? null;
            \Log::info("offerIdentifier: $offerIdentifier, serviceIdentifier: $serviceIdentifier");
            $service = null;

            if (empty($offerIdentifier) || strtoupper($offerIdentifier) === 'V') {
                \Log::info("No valid offer identifier found, creating a new offer for client ID: $client->id");

                $services = [
                    '2*' => '2 כוכבים',
                    '3*' => '3 כוכבים',
                    '3' => '3 כוכבים',
                    '3.5' => '4 כוכבים',
                    "4*" => '4 כוכבים',
                    "5*" => '5 כוכבים',
                    'משרד' => 'ניקיון משרד'
                ];

                if ($serviceIdentifier) {
                    // Find the key (Hebrew name) corresponding to the numeric value
                    $hebName = $services[$serviceIdentifier] ?? null;

                    if ($hebName) {
                        // Check if the service exists with the found Hebrew name
                        $service = Services::where('heb_name', $hebName)->first();

                        if ($service) {
                            \Log::info("Service found: " . json_encode($services));
                        } else {
                            \Log::warning("No service found with heb_name: $hebName");
                        }
                    } else {
                        \Log::warning("No matching key found for serviceIdentifier: $serviceIdentifier");
                    }
                } else {
                    // Directly check if the serviceIdentifier matches `heb_name`
                    $service = Services::where('heb_name', $serviceIdentifier)->first();

                    if ($service) {
                        \Log::info("Direct Service found: " . json_encode($services));
                    } else {
                        \Log::warning("No direct service found with heb_name: $serviceIdentifier");
                    }
                }


                if ($service) {
                    // Construct the services data for the Offer
                    $offerServices = [
                        [
                            "service" => $service->id,
                            "name" => $service->name,
                            "template" => $service->template,
                            "cycle" => "1",
                            "period" => "2w", // Example period
                            "address" => null, // Example address ID
                            'type' => 'hourly',
                            "workers" => [
                                [
                                    "jobHours" => $jobHours ?? null
                                ]
                            ],
                        ]
                    ];

                    // Create the Offer with the services data
                    $newOffer = Offer::create([
                        'client_id' => $client->id,
                        'services' => json_encode($offerServices), // Store services as JSON
                        'status' => 'accepted'
                    ]);

                    \Log::info("Offer created successfully with services: " . json_encode($offerServices));

                    if ($address) {
                        $propertyAddress = $this->checkAndAddOfferAddress(Offer::find($newOffer->id), $address);

                        if ($propertyAddress) {
                            // Decode the existing services field
                            $servicesData = json_decode($newOffer->services, true);

                            // Update the address field in each service entry
                            foreach ($servicesData as &$s) {
                                $s['address'] = $propertyAddress->id;
                            }

                            // Save the updated services back to the Offer
                            $newOffer->services = json_encode($servicesData);
                            $newOffer->save();
                        }
                    }

                    $job = $this->handleContract($newOffer, $row);
                }

                return;
            }

            // Proceed with processing the existing offer
            $this->processOffer($offerIdentifier, $client, $address);
        } catch (\Throwable $th) {
            \Log::error('Error in handleOfferData function: ' . $th->getMessage());
            throw $th;
        }
    }

    private function processOffer($offerIdentifier, $client, $address)
    {
        $offer = Offer::with(['client.property_addresses'])
            ->where('id', $offerIdentifier)
            ->where('client_id', $client->id)
            ->first();

        if (!$offer) {
            \Log::warning("No matching Offer for ID: $offerIdentifier and Client ID: $client->id");
            return;
        }

        \Log::info("Offer with ID: $offerIdentifier matches Client ID: $client->id");

        if ($address) {
            $this->checkAndAddOfferAddress($offer, $address);
        }
    }

    private function checkAndAddOfferAddress($offer, $address)
    {
        $language = $this->detectLanguage($address);
        $languageParam = ($language === 'hebrew') ? 'he' : 'en';

        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $address,
            'key' => config('services.google.map_key'),
            'language' => $languageParam
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $fullAddress = $data['results'][0]['formatted_address'] ?? null;

            foreach ($offer->client->property_addresses as $propertyAddress) {
                if ($propertyAddress->geo_address === $fullAddress) {
                    \Log::info("Address already exists for offer.");
                    return $propertyAddress;
                }
            }

            // $offer->client->property_addresses()->create([
            //     'geo_address' => $fullAddress,
            //     'latitude' => $data['results'][0]['geometry']['location']['lat'] ?? null,
            //     'longitude' => $data['results'][0]['geometry']['location']['lng'] ?? null,
            //     'address_name' => $data['results'][0]['address_components'][2]['long_name'] ?? null,
            // ]);

            \Log::info("New address added to offer.");
        } else {
            \Log::error("Failed to fetch geocoding data for address: $address");
        }
    }

    private function handleContract($offer, $row)
    {
        try {

            $hash = md5(isset($offer['client']['email']) ? $offer['client']['email'] : $offer['client']['firstname'] . $offer['id']);

            $contract = Contract::create([
                'offer_id' => $offer->id,
                'client_id' => $offer->client_id,
                'unique_hash' => $hash,
                'consent_to_ads' => true,
                'status' => ContractStatusEnum::VERIFIED
            ]);

            $this->handelJob($contract, $row);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    private function handleJob($offer, $selectedOfferDataArr, $services, $frequencies, $selectedAddress, $selectedFrequency, $selectedService)
    {
        try {
            if ($offer) {
                \Log::info("Offer ID: " . $offer->id);
                \Log::info("Client ID: " . $offer->client_id);
                \Log::info("selectedOfferDataArr: ");
                \Log::info($selectedOfferDataArr);
                \Log::info("Services: ");
                \Log::info($services);
                \Log::info("Frequencies: ");
                \Log::info($frequencies);
                // \Log::info("selectedAddress: ");
                // \Log::info($selectedAddress);
                // \Log::info("selectedFrequency: ");
                // \Log::info($selectedFrequency);
                // \Log::info("selectedService: ");
                // \Log::info($selectedService);

                $ServiceFrequency = ServiceSchedule::where('name', $frequencies[0])
                    ->orWhere('name_heb', $frequencies[0])
                    ->first();

                $contract = Contract::with('offer')
                    ->where('offer_id', $offer->id)
                    // ->whereHas('offer', function ($query) use ($ServiceFrequency) {
                    //     $query->whereJsonContains('services->frequency', $ServiceFrequency->id); // Correct JSON path
                    // })
                    ->get(); // Fetch all matching results

                \Log::info($contract->offer->toArray()); // Convert to array before logging

                if (!$contract) {
                    return response()->json([
                        'message' => 'Contract not found'
                    ], 404);
                }
            }

            //     $client = $contract->client;
            //     if (!$client) {
            //         return response()->json([
            //             'message' => 'Client not found'
            //         ], 404);
            //     }

            //     // Fetch the offer
            //     $offer = $contract->offer;
            //     if (!$offer) {
            //         return response()->json([
            //             'message' => 'Offer not found'
            //         ], 404);
            //     }

            //     // Decode services (if stored as JSON)
            //     $services = is_string($offer->services) ? json_decode($offer->services, true) : $offer->services;

            //     // Locate the service and add is_one_time field
            //     foreach ($services as &$service) {
            //         if (($service['service'] == 1) || isset($service['freq_name']) && (in_array($service['freq_name'], ['One Time', 'חד פעמי']))) {
            //             $service['is_one_time'] = true; // Add the field
            //         }
            //     }

            //     // Save updated services back to the offer
            //     $offer->services = json_encode($services);
            //     $offer->save();


            //     $manageTime = ManageTime::first();
            //     $workingWeekDays = json_decode($manageTime->days);

            //     if (isset($data['updatedJobs'])) {
            //         foreach ($data['updatedJobs'] as $updateJob) {
            //             $editJob = Job::find($updateJob['job_id']);

            //             $repeat_value = $editJob->jobservice->period;

            //             $job_date = Carbon::parse($updateJob['date']);
            //             $preferredWeekDay = strtolower($job_date->format('l'));
            //             $next_job_date = $this->scheduleNextJobDate($job_date, $repeat_value, $preferredWeekDay, $workingWeekDays);

            //             $job_date = $job_date->toDateString();

            //             $slots = explode(',', $updateJob['shifts']);
            //             // sort slots in ascending order of time before merging for continuous time
            //             sort($slots);

            //             $shiftFormattedArr = [];
            //             foreach ($slots as $key => $shift) {
            //                 $timing = explode('-', $shift);

            //                 $start_time = Carbon::createFromFormat('H:i', $timing[0])->toTimeString();
            //                 $end_time = Carbon::createFromFormat('H:i', $timing[1])->toTimeString();

            //                 $shiftFormattedArr[$key] = [
            //                     'starting_at' => Carbon::parse($job_date . ' ' . $start_time)->toDateTimeString(),
            //                     'ending_at' => Carbon::parse($job_date . ' ' . $end_time)->toDateTimeString()
            //                 ];
            //             }

            //             $mergedContinuousTime = $this->mergeContinuousTimes($shiftFormattedArr);

            //             $minutes = 0;
            //             $slotsInString = '';
            //             foreach ($mergedContinuousTime as $key => $slot) {
            //                 if (!empty($slotsInString)) {
            //                     $slotsInString .= ',';
            //                 }

            //                 $slotsInString .= Carbon::parse($slot['starting_at'])->format('H:i') . '-' . Carbon::parse($slot['ending_at'])->format('H:i');

            //                 $minutes += Carbon::parse($slot['ending_at'])->diffInMinutes(Carbon::parse($slot['starting_at']));
            //             }

            //             $status = JobStatusEnum::SCHEDULED;

            //             if ($this->isJobTimeConflicting($mergedContinuousTime, $job_date, $editJob->worker_id, $editJob->id)) {
            //                 $status = JobStatusEnum::UNSCHEDULED;
            //             }

            //             $start_time = Carbon::parse($mergedContinuousTime[0]['starting_at'])->toTimeString();
            //             $end_time = Carbon::parse($mergedContinuousTime[count($mergedContinuousTime) - 1]['ending_at'])->toTimeString();

            //             $jobData = [
            //                 'start_date'    => $job_date,
            //                 'start_time'    => $start_time,
            //                 'end_time'      => $end_time,
            //                 'shifts'        => $slotsInString,
            //                 'status'        => $status,
            //                 'next_start_date'   => $next_job_date,
            //             ];

            //             $jobData['previous_shifts'] = $editJob->shifts;
            //             $jobData['previous_shifts_after'] = NULL;

            //             $editJob->update($jobData);

            //             $editJob->jobservice()->update([
            //                 'duration_minutes'  => $minutes,
            //                 'config'            => [
            //                     'cycle'             => $editJob->jobservice->cycle,
            //                     'period'            => $editJob->jobservice->period,
            //                     'preferred_weekday' => $preferredWeekDay
            //                 ]
            //             ]);

            //             $editJob->workerShifts()->delete();
            //             foreach ($mergedContinuousTime as $key => $shift) {
            //                 $editJob->workerShifts()->create($shift);
            //             }

            //             $editJob->load(['client', 'worker', 'jobservice', 'propertyAddress', 'offer']);

            //             event(new JobShiftChanged($editJob, $mergedContinuousTime[0]['starting_at']));
            //         }
            //     }

            //     $offerServices = $this->formatServices($contract->offer, false);
            //     $filtered = Arr::where($offerServices, function ($value, $key) use ($data) {
            //         return $value['service'] == $data['service_id'];
            //     });

            //     $selectedService = head($filtered);

            //     $service = Services::find($data['service_id']);
            //     $serviceSchedule = ServiceSchedule::find($selectedService['frequency']);

            //     $repeat_value = $serviceSchedule->period;
            //     if ($selectedService['service'] == 10) {
            //         $s_name = $selectedService['other_title'];
            //         $s_heb_name = $selectedService['other_title'];
            //     } else {
            //         $s_name = $service->name;
            //         $s_heb_name = $service->heb_name;
            //     }
            //     $s_freq   = $selectedService['freq_name'];
            //     $s_cycle  = $selectedService['cycle'];
            //     $s_period = $selectedService['period'];
            //     $s_id     = $selectedService['service'];

            //     $jobGroupID = NULL;

            //     $workerIDs = array_values(array_unique(data_get($data, 'workers.*.worker_id')));
            //     foreach ($workerIDs as $workerID) {
            //         $workerDates = Arr::where($data['workers'], function ($value) use ($workerID) {
            //             return $value['worker_id'] == $workerID;
            //         });

            //         $workerDates = array_values($workerDates);
            //         foreach ($workerDates as $workerIndex => $workerDate) {
            //             $job_date = Carbon::parse($workerDate['date']);
            //             $preferredWeekDay = strtolower($job_date->format('l'));
            //             $next_job_date = $this->scheduleNextJobDate($job_date, $repeat_value, $preferredWeekDay, $workingWeekDays);

            //             $job_date = $job_date->toDateString();

            //             $slots = explode(',', $workerDate['shifts']);
            //             sort($slots);

            //             $shiftFormattedArr = [];
            //             foreach ($slots as $key => $shift) {
            //                 $timing = explode('-', $shift);

            //                 $start_time = Carbon::createFromFormat('H:i', $timing[0])->toTimeString();
            //                 $end_time = Carbon::createFromFormat('H:i', $timing[1])->toTimeString();

            //                 $shiftFormattedArr[$key] = [
            //                     'starting_at' => Carbon::parse($job_date . ' ' . $start_time)->toDateTimeString(),
            //                     'ending_at' => Carbon::parse($job_date . ' ' . $end_time)->toDateTimeString()
            //                 ];
            //             }

            //             $mergedContinuousTime = $this->mergeContinuousTimes($shiftFormattedArr);

            //             $minutes = 0;
            //             $slotsInString = '';
            //             foreach ($mergedContinuousTime as $key => $slot) {
            //                 if (!empty($slotsInString)) {
            //                     $slotsInString .= ',';
            //                 }

            //                 $slotsInString .= Carbon::parse($slot['starting_at'])->format('H:i') . '-' . Carbon::parse($slot['ending_at'])->format('H:i');

            //                 // Calculate duration in 15-minute slots
            //                 $start = Carbon::parse($slot['starting_at']);
            //                 $end = Carbon::parse($slot['ending_at']);
            //                 $interval = 15; // in minutes
            //                 while ($start < $end) {
            //                     $start->addMinutes($interval);
            //                     $minutes += $interval;
            //                 }
            //             }

            //             if ($selectedService['type'] == 'hourly') {
            //                 $hours = ($minutes / 60);
            //                 $total_amount = ($selectedService['rateperhour'] * $hours);
            //             } else if($selectedService['type'] == 'squaremeter') {
            //                 $total_amount = ($selectedService['ratepersquaremeter'] * $selectedService['totalsquaremeter']);
            //             } else {
            //                 $total_amount = ($selectedService['fixed_price']);
            //             }

            //             $status = JobStatusEnum::SCHEDULED;

            //             if ($this->isJobTimeConflicting($mergedContinuousTime, $job_date, $workerDate['worker_id'])) {
            //                 $status = JobStatusEnum::UNSCHEDULED;
            //             }

            //             $start_time = Carbon::parse($mergedContinuousTime[0]['starting_at'])->toTimeString();
            //             $end_time = Carbon::parse($mergedContinuousTime[count($mergedContinuousTime) - 1]['ending_at'])->toTimeString();

            //             $job = Job::create([
            //                 'worker_id'     => $workerDate['worker_id'],
            //                 'client_id'     => $contract->client_id,
            //                 'contract_id'   => $contract->id,
            //                 'offer_id'      => $contract->offer_id,
            //                 'start_date'    => $job_date,
            //                 'start_time'    => $start_time,
            //                 'end_time'      => $end_time,
            //                 'shifts'        => $slotsInString,
            //                 'schedule'      => $repeat_value,
            //                 'schedule_id'   => $s_id,
            //                 'status'        => $status,
            //                 'subtotal_amount'  => $total_amount,
            //                 'total_amount'  => $total_amount,
            //                 'next_start_date'   => $next_job_date,
            //                 'address_id'        => $selectedService['address']['id'],
            //                 'keep_prev_worker'  => isset($data['prevWorker']) ? $data['prevWorker'] : false,
            //                 'original_worker_id'     => $workerDate['worker_id'],
            //                 'original_shifts'        => $slotsInString,
            //             ]);

            //             // Create entry in ParentJobs
            //             $parentJob = ParentJobs::create([
            //                 'job_id' => $job->id,
            //                 'client_id' => $contract->client_id,
            //                 'worker_id' => $workerDate['worker_id'],
            //                 'offer_id' => $contract->offer_id,
            //                 'contract_id' => $contract->id,
            //                 'schedule'      => $repeat_value,
            //                 'schedule_id'   => $s_id,
            //                 'start_date' => $job_date,
            //                 'next_start_date'   => $next_job_date,
            //                 'keep_prev_worker'  => isset($data['prevWorker']) ? $data['prevWorker'] : false,
            //                 'status' => $status, // You can set this according to your needs
            //             ]);



            //             $jobser = JobService::create([
            //                 'job_id'            => $job->id,
            //                 'service_id'        => $s_id,
            //                 'name'              => $s_name,
            //                 'heb_name'          => $s_heb_name,
            //                 'duration_minutes'  => $minutes,
            //                 'freq_name'         => $s_freq,
            //                 'cycle'             => $s_cycle,
            //                 'period'            => $s_period,
            //                 'total'             => $total_amount,
            //                 'config'            => [
            //                     'cycle'             => $serviceSchedule->cycle,
            //                     'period'            => $serviceSchedule->period,
            //                     'preferred_weekday' => $preferredWeekDay
            //                 ]
            //             ]);

            //             $jobGroupID = $jobGroupID ? $jobGroupID : $job->id;

            //             $job->update([
            //                 'origin_job_id' => $job->id,
            //                 'job_group_id' => $jobGroupID,
            //                 'parent_job_id' => $parentJob->id
            //             ]);

            //             foreach ($mergedContinuousTime as $key => $shift) {
            //                 $job->workerShifts()->create($shift);
            //             }

            //             $job->load(['client', 'worker', 'jobservice', 'propertyAddress', 'offer']);

            //             // Send notification to client
            //             $jobData = $job->toArray();

            //             ScheduleNextJobOccurring::dispatch($job->id, null);

            //         }
            //     }

            //     $newLeadStatus = $this->getClientLeadStatusBasedOnJobs($client);

            //     if (!$client->lead_status || $client->lead_status->lead_status != $newLeadStatus) {
            //         $client->lead_status()->updateOrCreate(
            //             [],
            //             ['lead_status' => $newLeadStatus]
            //         );

            //     }
            // }
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
