<?php

namespace App\Imports;

use App\Enums\LeadStatusEnum;
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

class ClientImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    use PaymentAPI;
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        $failedImports = collect([]);
        foreach ($collection as $row) {
            try {
                $validator = Validator::make($row->toArray(), [
                    'first_name' => ['required', 'string', 'max:255'],
                    'primary_phone'     => ['required'],
                    'status'    => ['required'],
                    'password'  => ['required', 'string', 'min:6',],
                    'primary_email'     => ['required', 'string', 'email', 'max:255'],
                ]);

                if ($validator->fails()) {
                    $failedImports->push($row);
                    continue;
                }

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

                $status = [
                    'Lead' => 0,
                    'Potential Customer' => 1,
                    'Customer' => 2,
                ];
                if (!in_array(Str::title($row['status'] ?? ''), array_keys($status))) {
                    throw new Exception('Invalid client status');
                }
                $clientData = [
                    'firstname' => $row['first_name'] ?? '',
                    'lastname' => $row['last_name'] ?? '',
                    'invoicename' => $row['invoice_name'] ?? '',
                    'dob' => date('Y-m-d', strtotime($row['date_of_birth'] ?? '')),
                    'phone'     => $row['primary_phone'] ?? '',
                    'status'    => $status[Str::title($row['status'] ?? '')],
                    'passcode'  => $row['password'] ?? '',
                    'password'  => Hash::make($row['password'] ?? ''),
                    'email'     => $row['primary_email'] ?? '',
                    'extra' => json_encode($extra),
                    'lng'     => $row['language'] ?? '',
                    'color'     => $row['color'] ?? '',
                    'payment_method'     => $row['payment_method'] ?? '',
                ];

                $client = Client::where('phone', $clientData['phone'])
                    ->orWhere('email', $clientData['email'])
                    ->first();

                if (empty($client)) {
                    $client = Client::create($clientData);

                    $client->lead_status()->updateOrCreate(
                        [],
                        ['lead_status' => LeadStatusEnum::PENDING_LEAD]
                    );
                }

                // Create client address if not already exists
                if (!ClientPropertyAddress::where('client_id', $client->id)->exists()) {
                    if (empty($row['full_address'])) {
                        throw new Exception('Invalid address');
                    }

                    $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                        'address' => $row['full_address'],
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

                    ClientPropertyAddress::create([
                        'address_name' => $result->formatted_address ?? null,
                        'city' => $city ?? NULL,
                        'floor' => NULL,
                        'apt_no' => null,
                        'entrence_code' => null,
                        'zipcode' => $zipcode ?? NULL,
                        'geo_address' => $result->formatted_address ?? NULL,
                        'latitude' => $result->geometry->location->lat ?? NULL,
                        'longitude' => $result->geometry->location->lng ?? NULL,
                        'client_id' => $client->id,
                        'prefer_type' => $row['prefered_type'] ?? '',
                        'is_dog_avail' => strtolower($row['dog_in_the_property'] ?? '') == 'yes' ? 1 : 0,
                        'is_cat_avail' => strtolower($row['cat_in_the_property'] ?? '') == 'yes' ? 1 : 0,
                    ]);
                }

                if ($row['has_offer'] == "Yes") {
                    $clientpropertyaddress = ClientPropertyAddress::Where('client_id', $client->id)
                        ->first();

                    $offer = Offer::where('client_id', $client->id)->where('status', 'sent')->first();

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
                            $total_amount += $worker['jobHours'] * $row['fixed_price'];
                        }
                    } else {
                        $total_amount += $row['fixed_price'];
                    }

                    if (!in_array($row['service_name'], $existing_services_names)) {
                        $services = [
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
                            'services' => json_encode($existing_services),
                            'subtotal' => $subtotal,
                            'total' => ($subtotal + $tax),
                            'status' => 'sent',
                        ]);
                    } else {
                        $offer->update([
                            'services' => json_encode($existing_services),
                            'subtotal' => $subtotal,
                            'total' => ($subtotal + $tax),
                            'status' => 'sent',
                        ]);
                    }

                    $client->lead_status()->updateOrCreate(
                        [],
                        ['lead_status' => LeadStatusEnum::POTENTIAL_CLIENT]
                    );

                    if ($row['has_contract'] == "Yes") {
                        $hash = md5($client->email . $offer->id);

                        $offer->update([
                            'status' => 'accepted'
                        ]);

                        $contract = Contract::where('unique_hash', $hash)->first();

                        if (!$contract) {
                            $contract = Contract::create([
                                'offer_id' => $offer->id,
                                'client_id' => $client->id,
                                'additional_address' => $row['additional_address'],
                                'status' => 'verified',
                                'unique_hash' => $hash
                            ]);
                        } else {
                            $contract->update([
                                'offer_id' => $offer->id,
                                'client_id' => $client->id,
                                'additional_address' => $row['additional_address'],
                                'status' => 'verified',
                                'unique_hash' => $hash
                            ]);
                        }

                        $client->lead_status()->updateOrCreate(
                            [],
                            ['lead_status' => LeadStatusEnum::FREEZE_CLIENT]
                        );

                        $card = ClientCard::query()
                            ->where('client_id', $client->id)
                            ->first();

                        if (
                            config('services.app.old_contract') == true ||
                            (config('services.app.old_contract') == false && !empty($card))
                        ) {
                            $client->update(['status' => 2]);
                        }
                    }
                }

                $validDate = explode("/", $row['valid'])[0] . substr(explode("/", $row['valid'])[1], -2);
                $validateResponse = $this->validateCard([
                    'card_number' => $row['card_number'],
                    'card_exp' => $validDate
                ]);

                if (!$validateResponse['HasError']) {
                    $isdefault = ClientCard::where('client_id', $client->id)->where('is_default', 1)->first();
                    $existingCard = ClientCard::where('client_id', $client->id)->where('card_number', $row['card_number'])->first();

                    if ($existingCard) {
                        if ($existingCard->is_default == 0 && !$isdefault) {
                            $existingCard->update(['is_default' => 1]);
                        }
                    } else {
                        $card = ClientCard::create([
                            'client_id'   => $client->id,
                            'card_number' => $row['card_number'],
                            'card_type'   => $row['card_type'],
                            'card_holder_id' => $row['card_holder_id'],
                            'card_holder_name' => $row['card_holder_name'],
                            'valid'       => $row['valid'],
                            'cvv'       => $row['cvv'],
                            'card_token'  => $validateResponse['Token'],
                            'is_default'  => $isdefault ? 0 : 1
                        ]);
                    }
                }
            } catch (Exception $e) {
                Log::error($e);
                $failedImports->push($row);
                continue;
            }
        }
    }
}
