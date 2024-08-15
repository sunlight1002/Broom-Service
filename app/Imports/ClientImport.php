<?php

namespace App\Imports;

use App\Enums\ContractStatusEnum;
use App\Enums\LeadStatusEnum;
use App\Events\ClientLeadStatusChanged;
use App\Events\OfferAccepted;
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

class ClientImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    use PaymentAPI;
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
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

                if (!in_array($row['payment_method'], array_keys($paymentMethodOptions))) {
                    throw new Exception('Invalid payment method');
                }

                if (!in_array($row['language'], array_keys($languageOptions))) {
                    throw new Exception('Invalid language');
                }

                if (!in_array($row['color'], array_keys($colorOptions))) {
                    throw new Exception('Invalid color');
                }

                if (!in_array($row['status'], array_keys($statusOptions))) {
                    throw new Exception('Invalid client status');
                }

                $clientData = [
                    'firstname' => $row['first_name'] ?? '',
                    'lastname'  => $row['last_name'] ?? '',
                    'invoicename' => $row['invoice_name'] ?? '',
                    'dob'       => date('Y-m-d', strtotime($row['date_of_birth'] ?? '')),
                    'phone'     => $row['primary_phone'] ?? '',
                    'status'    => $statusOptions[$row['status']],
                    'passcode'  => $row['password'] ?? '',
                    'password'  => Hash::make($row['password'] ?? ''),
                    'email'     => $row['primary_email'] ?? '',
                    'extra'     => json_encode($extra),
                    'lng'       => $languageOptions[$row['language']],
                    'color'     => $colorOptions[$row['color']],
                    'payment_method'     => $paymentMethodOptions[$row['payment_method']],
                ];

                $client = Client::where('phone', $clientData['phone'] ?? '')
                    ->orWhere('email', $clientData['email'] ?? '')
                    ->first();

                if (empty($client)) {
                    $client = Client::create($clientData);

                    $client->lead_status()->updateOrCreate(
                        [],
                        ['lead_status' => LeadStatusEnum::PENDING]
                    );
                } else {
                    $client->update($clientData);
                }

                // Create client address if not already exists
                // if (!ClientPropertyAddress::where('client_id', $client->id)->exists()) {
                if (empty($row['full_address'])) {
                    throw new Exception('Invalid address');
                }

                if (!in_array($row['prefered_type'], array_keys($preferTypeOptions))) {
                    throw new Exception('Invalid prefered type');
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

                ClientPropertyAddress::updateOrCreate([
                    'address_name' => $row['property_name'] ?? null,
                    'client_id' => $client->id,
                ], [
                    'address_name' => $row['property_name'] ?? null,
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
                // }
                $offer = null;
                if (!empty($row['worker_hours']) && !empty($row['service_name']) && !empty($row['frequency']) && !empty($row['type'])) {
                    $clientpropertyaddress = ClientPropertyAddress::where('client_id', $client->id)->where('address_name', $row['property_name'])
                        ->first();

                    if(isset($row['offer_id']) && !empty($row['offer_id'])) {
                        $offer = Offer::find($row['offer_id'])->where('status', 'sent')->first();
                    } else {
                        $offer = Offer::where('client_id', $client->id)->where('status', 'sent')->first();
                    }

                    $existing_services = [];
                    if ($offer) {
                        $existing_services = json_decode($offer->services, true);

                        $message = " שלום {$client->firstname},

                            אנו שמחים להודיע על המעבר למערכת חדשה ויעילה שתשפר את תהליך העבודה שלנו מולכם. 
                            בקרוב ישלח אליכם הסכם חדש לחתימה דרך המערכת החדשה.

                            שימו לב, בהסכם החדש תתבקשו להזין פרטי כרטיס אשראי בצורה מאובטחת, אשר יחוייב אחת לחודש, לאחר קבלת השירות האחרון שלכם מאיתנו באותו חודש.

                            נשמח לעמוד לרשותכם בכל שאלה או בקשה.

                            בברכה,
                            צוות ברום סרוויס";
                    } else {
                        $message = " שלום {$client->firstname},

                            אנו שמחים להודיע על המעבר למערכת חדשה ויעילה שתשפר את תהליך העבודה שלנו מולכם. 
                            בקרוב תישלח אליכם הצעת מחיר חדשה לאישורכם. לאחר אישור ההצעה, ישלח אליכם הסכם לחתימה.

                            בהסכם החדש תתבקשו להזין פרטי כרטיס אשראי בצורה מאובטחת, אשר יחוייב אחת לחודש, לאחר קבלת השירות האחרון שלכם מאיתנו באותו חודש.

                            נשמח לעמוד לרשותכם בכל שאלה או בקשה.

                            בברכה,
                            צוות ברום סרוויס";
                    }

                    $this->sendWhatsAppMessage($client->phone, $message);

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
                            'service' => $service->id ?? '',
                            'name' => $row['service_name'] ?? '',
                            'type' => $row['type'] ?? '',
                            'rateperhour' => ($row['type'] == 'hourly') ? $row['rateperhour'] : '',
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
                            'status' => 'sent',
                        ]);

                        $offer->load(['client', 'service']);

                        $client->lead_status()->updateOrCreate(
                            [],
                            ['lead_status' => LeadStatusEnum::POTENTIAL_CLIENT]
                        );

                        event(new ClientLeadStatusChanged($client, LeadStatusEnum::POTENTIAL_CLIENT));
                        event(new OfferSaved($offer->toArray()));
                    } else {
                        $offer->update([
                            'services' => json_encode($existing_services, JSON_UNESCAPED_UNICODE),
                            'subtotal' => $subtotal,
                            'total' => ($subtotal + $tax),
                            'status' => 'sent',
                        ]);
                    }
                } else {
                    if (empty($row['offer_id'])) {
                        throw new Exception('Offer ID required.');
                    }

                    $offer = Offer::find($row['offer_id'])->first();
                }

                if ($row['has_contract'] == "No" && $offer && $offer->status == 'accepted') {
                    $hash = md5($client->email . $offer->id);

                    $contract = null;
                    if(isset($row['contract_id']) && !empty($row['contract_id'])) {
                        $contract = Contract::find($row['contract_id']);
                    } else {
                        $contract = Contract::where('unique_hash', $hash)->first();
                    }

                    if (!$contract) {
                        $contract = Contract::create([
                            'offer_id' => $offer->id,
                            'client_id' => $client->id,
                            'status' => ContractStatusEnum::VERIFIED,
                            'unique_hash' => $hash
                        ]);
                        $ofr = $offer->toArray();
                        $ofr['contract_id'] = $hash;

                        event(new OfferAccepted($ofr));
                    } else {
                        $contract->update([
                            'offer_id' => $offer->id,
                            'client_id' => $client->id,
                            'status' => ContractStatusEnum::VERIFIED,
                            'unique_hash' => $hash
                        ]);
                    }

                    $client->lead_status()->updateOrCreate(
                        [],
                        ['lead_status' => LeadStatusEnum::FREEZE_CLIENT]
                    );

                    event(new ClientLeadStatusChanged($client, LeadStatusEnum::FREEZE_CLIENT));

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

                if (
                    !empty($row['card_number']) &&
                    !empty($row['valid'])
                ) {
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

                            Contract::query()
                                ->where('client_id', $client->id)
                                ->where('status', ContractStatusEnum::VERIFIED)
                                ->whereNull('card_id')
                                ->update([
                                    'card_id' => $card->id
                                ]);
                        }
                    }
                }
            } catch (Exception $e) {
                Log::error($e);
                $failedImports->push($row);
                Log::error($failedImports);
                continue;
            }
        }
    }

    public function sendWhatsAppMessage($phoneNumber, $message)
    {
        try {
            $whapiApiEndpoint = config('services.whapi.url');
            $whapiApiToken = config('services.whapi.token');

            $response = Http::withToken($whapiApiToken)
                            ->post($whapiApiEndpoint . 'messages/text', [
                                'to' => $phoneNumber . '@s.whatsapp.net',
                                'body' => $message
                            ]);
            Log::info($response->json());
        } catch (\Throwable $th) {
            Log::alert('WA NOTIFICATION ERROR');
            Log::alert($th->getMessage());
        }
    }
}
