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

        $serviceArr = Services::get()->pluck('heb_name')->toArray();
        $frequencyArr = ServiceSchedule::where('status', 1)
            ->get()->pluck('name_heb')->toArray();
        $workers = User::where('status', 1)->get()->pluck('fullname')->toArray();

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
                        preg_match('/(?:יום\s*)?[א-ת]+\s*\d{1,2},\d{1,2}/u', $row[3]) ||
                        preg_match('/(?:יום\s*)?[א-ת]+\s*\d{2}\d{2}/u', $row[3])
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
                                $client_ids[] = $client->id;

                                // \Log::info('Service', ['service' =>$serviceArr]);
                                // \Log::info('Frequency', ['frequency' => $frequencyArr]);
                                // \Log::info('Workers', ['workers' => $workers]);
                                // dd($id, $email, $index, $client, $addresses);
                                $fields = [];

                                $selectedAddress = $row[17] ?? null;


                                $selectedWorker = $row[8] ?? null;
                                $bestMatch = null;
                                $highestSimilarity = 0;

                                foreach ($workers as $worker) {
                                    similar_text($selectedWorker, $worker, $percent);
                                    if ($percent > $highestSimilarity) {
                                        $highestSimilarity = $percent;
                                        $bestMatch = $worker;
                                    }

                                    similar_text($worker, $selectedWorker, $percent);
                                    if ($percent > $highestSimilarity) {
                                        $highestSimilarity = $percent;
                                        $bestMatch = $worker;
                                    }
                                }

                                $fields[] = [
                                    'sheetId' => $sheetId, // Sheet ID
                                    'cell' => "J" . ($index + 1), // Cell location
                                    'type' => 'dropdown', // Field type
                                    'values' => $workers, // Dropdown options
                                    'value' => ($highestSimilarity > 50) ? $bestMatch : null,
                                ];
                                $service = $row[11] ?? null;
                                $workerHours = $row[13] ?? null;
                                $workerHours = str_replace(',', '.', $workerHours);
                                $selectedService = $serviceMap[trim($row[11]) ?? null] ?? null;
                                if ($selectedService) {
                                    $selectedService = Services::where('name', $selectedService)->first();
                                }

                                $selectedFrequency = $frequencyMap[$row[18] ?? null] ?? null;
                                if ($selectedFrequency) {
                                    $selectedFrequency = ServiceSchedule::where('name', $selectedFrequency)->first();
                                }

                                $offer = null;
                                $services = [];
                                $frequencies = [];
                                $selectedOfferDataArr = [];
                                $selectedOfferData = null;

                                if (is_numeric(trim($row[2]))) {
                                    $offer = Offer::where('id', trim($row[2]))->where('client_id', $client->id)->first();

                                    if ($offer) {
                                        $data = json_decode($offer->services, true);
                                        if (count($data) == 1) {
                                            $selectedOfferDataArr[] = $data[0];
                                            $services[] = $data[0]['name'];
                                            $frequencies[] = $data[0]['freq_name'];
                                        } else {
                                            foreach ($data as $d) {
                                                // $jobHours = Arr::pluck($d['workers'], 'jobHours');
                                                // $isFound = in_array($workerHours, $jobHours);
                                                if ($selectedService && ($d['name'] == $selectedService->name || $d['name'] == $selectedService->heb_name) && ($d['freq_name'] == ($selectedFrequency->name ?? null) || $d['freq_name'] == ($selectedFrequency->name_heb ?? null))) {
                                                    $selectedOfferDataArr[] = $d;
                                                }
                                                $services[] = $d['name'];
                                                $frequencies[] = $d['freq_name'];
                                            }
                                        }
                                    }
                                }
                                $offers = $client->offers;
                                foreach ($offers as $offer) {
                                    $data = json_decode($offer->services, true);
                                    if (count($data) == 1) {
                                        $selectedOfferDataArr[] = $data[0];
                                        $services[] = $data[0]['name'];
                                        $frequencies[] = $data[0]['freq_name'];
                                    } else {
                                        foreach ($data as $d) {
                                            // $jobHours = Arr::pluck($d['workers'], 'jobHours');
                                            // $isFound = in_array($workerHours, $jobHours);

                                            if ($selectedService && ($d['name'] == $selectedService->name || $d['name'] == $selectedService->heb_name) && ($d['freq_name'] == ($selectedFrequency->name ?? null) || $d['freq_name'] == ($selectedFrequency->name_heb ?? null))) {
                                                $selectedOfferDataArr[] = $d;
                                            }
                                            $services[] = $d['name'];
                                            $frequencies[] = $d['freq_name'];
                                        }
                                    }
                                }

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

                                if (!empty($services)) {
                                    $fields[] = [
                                        'sheetId' => $sheetId, // Sheet ID
                                        'cell' => "M" . ($index + 1), // Cell location
                                        'type' => 'dropdown', // Field type
                                        'values' => $services, // Dropdown options
                                        'value' => count($services) == 1 ? $services[0] : ($selectedOfferData['name'] ?? null),
                                    ];
                                } else {
                                    $fields[] = [
                                        'sheetId' => $sheetId, // Sheet ID
                                        'cell' => "M" . ($index + 1), // Cell location
                                        'type' => 'dropdown', // Field type
                                        'values' => $serviceArr, // Dropdown options
                                        'value' => count($serviceArr) == 1 ? $serviceArr[0] : ($selectedOfferData['name'] ?? null),
                                    ];
                                }
                                $selectedFrequencyName = null;
                                if($selectedFrequency) {
                                    if($client->lng == 'en') {
                                        $selectedFrequencyName = $selectedFrequency->name;
                                    } else {
                                        $selectedFrequencyName = $selectedFrequency->name_heb;
                                    }
                                }

                                if (!empty($frequencies)) {
                                    $fields[] = [
                                        'sheetId' => $sheetId, // Sheet ID
                                        'cell' => "Q" . ($index + 1), // Cell location
                                        'type' => 'dropdown', // Field type
                                        'values' => $frequencies, // Dropdown options
                                        'value' => count($frequencies) == 1 ? $frequencies[0] : ($selectedFrequencyName ?? null),
                                    ];
                                } else {
                                    $fields[] = [
                                        'sheetId' => $sheetId, // Sheet ID
                                        'cell' => "Q" . ($index + 1), // Cell location
                                        'type' => 'dropdown', // Field type
                                        'values' => $frequencyArr, // Dropdown options
                                        'value' => count($frequencyArr) == 1 ? $frequencyArr[0] : ($selectedFrequencyName ?? null),
                                    ];
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

                                $fields[] = [
                                    'sheetId' => $sheetId, // Sheet ID
                                    'cell' => "T" . ($index + 1), // Cell location
                                    'type' => 'dropdown', // Field type
                                    'values' => $addresses, // Dropdown options
                                    'value' => (count($addresses) == 1) ? $addresses[0] : (($highestSimilarity > 50) ? $bestMatch : null),
                                ];

                                if ($selectedOfferData) {
                                    $fields[] = [
                                        'sheetId' => $sheetId, // Sheet ID
                                        'cell' => "D" . ($index + 1), // Cell location
                                        'type' => 'number', // Field type
                                        'value' => $selectedOfferData['totalamount'] ?? null,
                                    ];
                                }
                                echo json_encode($fields) . PHP_EOL;
                                $response = $this->updateGoogleSheetFields($fields);
                                echo $response . PHP_EOL;
                                sleep(3);
                                echo ($index + 1) . PHP_EOL;
                            }


                            // dd($client);

                            // Decode Offer services
                            // if ($offer) {

                            // $addressesMap = ClientPropertyAddress::whereIn('address_name', array_keys($addresses))->pluck('id', 'address_name')->toArray();
                            //     \Log::info('Addresses Map', ['addressesMap' => $addressesMap]);

                            // $servicesData = json_decode($offer->services, true);
                            // $isMatch = false;

                            // foreach ($servicesData as $serviceData) {
                            //     // Check if address ID exists in the database and matches an address name
                            //     $addressMatch = isset($serviceData['address']) && isset($addressesMap[$serviceData['address']]);
                            //     \Log::info("Address Match", ['addressMatch' => $addressMatch]);

                            //     // Check if the name matches the provided service array
                            //     $serviceMatch = isset($serviceData['name']) && in_array($serviceData['name'], $serviceArr);

                            //     // Check frequency match
                            //     $frequencyMatch = isset($serviceData['freq_name']) && in_array($serviceData['freq_name'], $frequencyArr);

                            //     // Log and process if everything matches
                            //     if ($addressMatch && $serviceMatch && $frequencyMatch) {
                            //         $isMatch = true;

                            //         \Log::info('Matching Offer Record Found', [
                            //             'Offer ID' => $offer->id,
                            //             'Service Data' => $serviceData,
                            //             'Matching Address' => $addressesMap[$serviceData['address']], // Log the matched address name
                            //         ]);

                            //         // Decide what to do with the matched record here
                            //         break;
                            //     }
                            // }

                            //     if (!$isMatch) {
                            //         \Log::warning('No Matching Record Found for Offer ID: ' . $offer->id);
                            //     }
                            // } else {
                            //     \Log::error('No Offer Found for ID: ' . trim($row[2]) . ' and Client ID: ' . $client->id);
                            // }

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

    private function handleJob($contract, $row, &$lastValidDate = null)
    {
        try {
            $workerName = $row[8] ?? null;
            $date = $row[3] ?? null;
            $wedenesday_notified = $row[5] ?? null;
            $jobHours = $row[11] ?? null;
            $actualHours = $row[12] ?? null;

            $weeks = ['יום ראשון', 'יום שני', 'יום שלישי', 'יום רביעי', 'יום חמישי', 'יום שישי', 'יום שבת'];

            if ($date) {
                $parts = explode(' ', $date);
                $res = $parts[1] ?? null;

                if (in_array($res, $weeks)) {
                    $date = $parts[0]; // Use index 0 as the date
                }

                $lastValidDate = $date; // Update the last valid date
            } else {
                $date = $lastValidDate; // Use the last valid date if current date is null
            }

            if (!$workerName || !$date) {
                \Log::warning("Skipping row due to missing worker name or date.");
                return;
            }

            $names = array_filter(explode(" ", trim($workerName)));
            $query = User::query();

            if (isset($names[0])) {
                $query->where('first_name', $names[0]);
            }

            if (isset($names[1])) {
                $query->orWhere('last_name', $names[1]);
            }

            // Search for the worker
            $worker = $query->first();

            if (!$worker) {
                \Log::warning("No matching Worker for Name: $workerName");
                return;
            }

            $addressId = null;
            $offer = $contract->offer;
            $servicesData = json_decode($offer->services, true);

            foreach ($servicesData as $s) {
                $addressId = $s['address'];
            }

            // Create the job
            $job = Job::create([
                'contract_id' => $contract->id,
                'client_id' => $contract->client_id,
                'offer_id' => $contract->offer_id,
                'worker_id' => $worker->id,
                'address_id' => $addressId,
                'start_date' => $date,
                'wednesday_notified' => $wedenesday_notified == "true" || $wedenesday_notified == '1' ? true : false,
            ]);

            if ($job && $jobHours && $actualHours) {
                $job->status = JobStatusEnum::COMPLETED;
                $job->save();
            } elseif ($job && !$actualHours) {
                $job->status = JobStatusEnum::SCHEDULED;
                $job->save();
            }
        } catch (\Throwable $th) {
            \Log::error("Error in handleJob: " . $th->getMessage());
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
