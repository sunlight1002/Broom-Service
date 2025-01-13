<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Enums\ContractStatusEnum;
use App\Enums\LeadStatusEnum;
use App\Events\OfferAccepted;
use App\Events\ClientLeadStatusChanged;
use App\Models\Client;
use App\Models\ClientPropertyAddress;
use App\Models\Offer;
use App\Models\ServiceSchedule;
use App\Models\Services;
use App\Models\Contract;
use App\Models\ClientCard;
use App\Models\LeadStatus;
use App\Traits\PaymentAPI;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Events\OfferSaved;
use Maatwebsite\Excel\Facades\Excel;

class ImportClientInformationFromExcel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:import-client-information-from-excel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import client information from Excel';

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

        $paymentMethodOptions = [
            'Credit Card'       => 'cc',
            'Money Transfer'    => 'mt',
            'By Cheque'         => 'cheque',
            'By Cash'           => 'cash',
        ];

        $languageOptions = [
            'Hebrew'    => 'heb',
            'English'   => 'en'
        ];

        $colorOptions = [
            'White'     => '#fff',
            'Green'     => '#28a745',
            'Blue'      => '#007bff',
            'Violet'    => '#6f42c1',
            'Red'       => '#dc3545',
            'Orange'    => '#fd7e14',
            'Yellow'    => '#ffc107'
        ];

        $statusOptions = [
            'Lead' => 0,
            'Potential Customer' => 1,
            'Customer' => 2,
        ];

        $preferTypeOptions = [
            'Female'    => 'female',
            'Male'      => 'male',
            'Both'      => 'both'
        ];

        $failedImports = collect([]);

        $filePath = storage_path('v_properties.xlsx');

        if (!file_exists($filePath)) {
            Log::error('File not found at: ' . $filePath);
            return;
        }
        $data = Excel::toArray([], $filePath);
        $firstSheet = $data[0] ?? [];

        if (empty($firstSheet)) {
            Log::error("The first sheet is empty.");
            return;
        }

        $limitedRows = array_slice($firstSheet, 0, 1050);
        $updateData = [];
        foreach ($limitedRows as $rowIndex => $row) {
            if ($rowIndex == 0) {
                continue;
            }

            if (!empty($row[0]) || !empty($row[4])) {
                $updateData[] = [
                    'id' => substr(trim($row[0]), 1),
                    'first_name' => $row[1],
                    'last_name' => $row[2],
                    'invoice_name' => $row[3],
                    'primary_email' => $row[4],
                    'password' => $row[5],
                    'primary_phone' => $row[6],
                    'alternate_email_1' => $row[7],
                    'person_name_1' => $row[8],
                    'alternate_phone_1' => $row[9],
                    'alternate_email_2' => $row[10],
                    'person_name_2' => $row[11],
                    'alternate_phone_2' => $row[12],
                    'date_of_birth' => $row[13],
                    'payment_method' => $row[14],
                    'language' => $row[15],
                    'color' => $row[16],
                    'status' => $row[17],
                    'full_address' => $row[18],
                    'property_name' => $row[19],
                    'floor' => $row[20],
                    'apt_number' => $row[21],
                    'key' => $row[22],
                    'address_comment' => $row[23],
                    'lobby' => $row[24],
                    'parking' => $row[25],
                    'dog_in_the_property' => $row[26],
                    'cat_in_the_property' => $row[27],
                    'prefered_type' => 'Both',
                    'has_offer' => $row[29],
                    'service_name' => $row[30],
                    'frequency' => $row[31],
                    'type' => $row[32],
                    'fixed_price' => $row[33],
                    'rateperhour' => $row[34],
                    'other_title' => $row[35],
                    'worker_hours' => $row[36],
                    'has_contract' => $row[37],
                ];
            }
        }
        foreach ($updateData as $row) {
            try {

                $extra = [];
                if (!empty($row['alternate_email_1']) || !empty($row['person_name_1']) || !empty($row['alternate_phone_1'])) {
                    $extra[] = [
                        'email' => $row['alternate_email_1'] ?? '',
                        'name' => $row['person_name_1'] ?? '',
                        'phone' => $row['alternate_phone_1'] ?? '',
                    ];
                }
                if (!empty($row['alternate_email_2']) || !empty($row['person_name_2']) || !empty($row['alternate_phone_2'])) {
                    $extra[] = [
                        'email' => $row['alternate_email_2'] ?? '',
                        'name' => $row['person_name_2'] ?? '',
                        'phone' => $row['alternate_phone_2'] ?? '',
                    ];
                }


                $client = Client::find($row['id']);
                // dd($client);

                $clientData = [
                    'firstname' => $row['first_name'] ?? $client->firstname ?? '',
                    'lastname'  => $row['last_name'] ?? $client->lastname ?? '',
                    'invoicename' => $row['invoice_name'] ?? $client->invoicename ?? '',
                    'dob'       => date('Y-m-d', strtotime($row['date_of_birth']  ?? $client->dob ?? '')),
                    'phone'     => $row['primary_phone'] ?? $client->phone ?? '',
                    'status'    => $statusOptions['Customer'],
                    'email'     => $row['primary_email'] ?? $client->email ?? '',
                    'extra'     => json_encode($extra),
                    'lng'       => $languageOptions['Hebrew'],
                    'color'     => $colorOptions['White'],
                    'payment_method'     => $paymentMethodOptions[$row['payment_method']],
                ];

                $client = Client::where('email', $clientData['email'] ?? '')
                    ->first();

                if (empty($client)) {
                    $client = Client::create($clientData);

                    $client->lead_status()->updateOrCreate(
                        [],
                        ['lead_status' => LeadStatusEnum::ACTIVE_CLIENT]
                    );
                } else {
                    $client->update($clientData);
                }
                $clientpropertyaddress = null;
                // Create client address if not already exists
                // if (!ClientPropertyAddress::where('client_id', $client->id)->exists()) {
                if (!empty($row['full_address'])) {
                    $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                        'address' => $row['full_address'],
                        'language' => 'he',
                        'key' => config('services.google.map_key')
                    ]);

                    if (!$response->successful()) {
                        throw new Exception('Invalid address');
                    }

                    $data = $response->object();
                    $result = $data->results[0] ?? null;

                    if (!$result) {
                        throw new Exception('Invalid address');
                    }
                    $zipcode = null;
                    $city = null;

                    foreach ($result->address_components ?? [] as $key => $address_component) {
                        if (in_array('locality', $address_component->types)) {
                            $city = $address_component->long_name;
                        }

                        if (in_array('postal_code', $address_component->types)) {
                            $zipcode = $address_component->long_name;
                        }
                    }
                    $clientpropertyaddress = ClientPropertyAddress::updateOrCreate([
                        'address_name' => $row['property_name'] ?? $result->formatted_address,
                        'client_id' => $client->id,
                        'geo_address' => $result->formatted_address ?? NULL,
                        'apt_no' => $row['apt_number'] ?? NULL,
                    ], [
                        'address_name' => $row['property_name'] ?? $result->formatted_address,
                        'city' => $city ?? NULL,
                        'floor' => $row['floor'] ?? NULL,
                        'apt_no' => $row['apt_number'] ?? NULL,
                        'entrence_code' => $row['enterance'] ?? NULL,
                        'zipcode' => $zipcode ?? NULL,
                        'geo_address' => $result->formatted_address ?? NULL,
                        'latitude' => $result->geometry->location->lat ?? NULL,
                        'longitude' => $result->geometry->location->lng ?? NULL,
                        'client_id' => $client->id,
                        'prefer_type' => $preferTypeOptions[$row['prefered_type']],
                        'is_dog_avail' => $row['dog_in_the_property'] == 'Yes' ? 1 : 0,
                        'is_cat_avail' => $row['cat_in_the_property'] == 'Yes' ? 1 : 0,
                        'parking' => $row['parking'] ?? NULL
                    ]);
                }

                // }
                $offer = null;
                if (!empty($row['worker_hours']) && !empty($row['service_name']) && !empty($row['frequency']) && !empty($row['type'])) {
                    if(!$clientpropertyaddress) {

                        $clientpropertyaddress = ClientPropertyAddress::where('client_id', $client->id)->where('address_name', $row['property_name'])
                            ->first();
                    }

                    if(!$clientpropertyaddress) {
                        $clientpropertyaddress = ClientPropertyAddress::where('client_id', $client->id)->first();
                    }



                    $offer = Offer::with('client')->where('client_id', $client->id)->whereJsonContains('services', ['address' => $clientpropertyaddress->id])->first();


                    $existing_services = [];
                    if ($offer) {
                        $existing_services = json_decode($offer->services, true);
                    }


                    $existing_services_names = array_column($existing_services, 'name');

                    $service = Services::Where('name', $row['service_name'])->first();
                    $serviceschedule = ServiceSchedule::Where('name', $row['frequency'])->first();

                    $total_amount = 0;

                    $workerJobHours = [];
                    if (!empty($row['worker_hours'])) {
                        $workerhours  = explode(',', $row['worker_hours']);
                        foreach ($workerhours as $workerhour) {
                            array_push($workerJobHours,  array('jobHours' => $workerhour));
                        }
                    }

                    if ($row['type'] == 'hourly') {
                        foreach ($workerJobHours as $key => $worker) {
                            $total_amount += $worker['jobHours'] * $row['rateperhour'];
                        }
                    } else {
                        $total_amount += $row['fixed_price'];
                    }

                    if (!in_array($row['service_name'], $existing_services_names)) {
                        $services = [
                            "sub_services" => $row['service_name'] == 'AIRBNB' ? [
                                "id" => 13,
                                "address" => $clientpropertyaddress->id,
                                "address_name" => $clientpropertyaddress->address_name,
                                "sub_service_name" => "ניקיון"
                            ] : [
                                "id" => '',
                                "address" => '',
                                "address_name" => '',
                                "sub_service_name" => ""
                            ],
                            'service' => $service->id ?? '',
                            'name' => $row['service_name'] ?? '',
                            'type' => $row['type'] ?? '',
                            'rateperhour' => ($row['type'] == 'hourly') ? $row['fixed_price'] : '',
                            'freq_name' => $row['frequency'] ?? '',
                            'frequency' => $serviceschedule->id ?? '',
                            'fixed_price' => ($row['type'] == 'fixed') ? $row['fixed_price'] : '',
                            'other_title' => ($row['frequency'] == 'Others') ? $row['other_title'] : '',
                            'totalamount' => $total_amount,
                            'template' => $service->template ?? '',
                            'cycle' => $serviceschedule->cycle ?? '',
                            'period' => $serviceschedule->period ?? '',
                            'address' => $clientpropertyaddress->id ?? '',
                            'workers' => $workerJobHours,
                            "weekdays" => [],
                            "weekday_occurrence" => "1",
                            "weekday" => "sunday",
                            "month_occurrence" => 1,
                            "month_date" => 1,
                            "monthday_selection_type" => "weekday"
                        ];

                        $existing_services[] = $services;
                    }

                    $subtotal = 0;
                    $tax_percentage = config('services.app.tax_percentage');

                    foreach ($existing_services as $existing_service) {
                        if (isset($existing_service['type']) && $existing_service['type'] == 'hourly') {
                            foreach ($workerJobHours as $key => $worker) {
                                if (
                                    isset($worker['jobHours']) && is_numeric($worker['jobHours']) &&
                                    isset($existing_service['rateperhour']) && is_numeric($existing_service['rateperhour'])
                                ) {
                                    $subtotal += ($worker['jobHours'] * $existing_service['rateperhour']);
                                }
                            }
                        } else {
                            if (isset($existing_service['fixed_price']) && is_numeric($existing_service['fixed_price'])) {
                                $subtotal += ($existing_service['fixed_price'] * count($workerJobHours));
                            }
                        }
                    }

                    $tax = ($tax_percentage / 100) * $subtotal;

                    if (!$offer) {
                        $offer = Offer::create([
                            'client_id' => $client->id,
                            'services' => json_encode($existing_services, JSON_UNESCAPED_UNICODE),
                            'subtotal' => $subtotal,
                            'total' => ($subtotal + $tax),
                            'status' => 'accepted',
                        ]);

                        $offer->load(['client', 'service']);

                        $client->lead_status()->updateOrCreate(
                            [],
                            ['lead_status' => LeadStatusEnum::ACTIVE_CLIENT]
                        );
                    } else {
                        $offer->update([
                            'services' => json_encode($existing_services, JSON_UNESCAPED_UNICODE),
                            'subtotal' => $subtotal,
                            'total' => ($subtotal + $tax),
                            'status' => 'accepted',
                        ]);
                    }
                }



                if ($row['has_contract'] == "No" && $offer && !$offer->contract && $offer->status == 'accepted') {
                    $hash = md5($client->email . $offer->id);

                    $contract = null;
                    if (isset($row['contract_id']) && !empty($row['contract_id'])) {
                        $contract = Contract::find($row['contract_id']);
                    }

                    if (!$contract) {
                        $contract = Contract::create([
                            'offer_id' => $offer->id,
                            'client_id' => $client->id,
                            'status' => ContractStatusEnum::VERIFIED,
                            'unique_hash' => $hash,
                            'signed_at' => now()
                        ]);
                        $ofr = $offer->toArray();
                        $ofr['contract_id'] = $hash;
                        logger($ofr);
                    }

                    $client->lead_status()->updateOrCreate(
                        [],
                        ['lead_status' => LeadStatusEnum::ACTIVE_CLIENT]
                    );

                }
            } catch (Exception $e) {
                Log::error($e);
                $failedImports->push($row);
                Log::error($failedImports);
                continue;
            }
        }
        return 0;
    }


}
