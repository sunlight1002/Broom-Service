<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\ClientPropertyAddress;
use Illuminate\Support\Facades\Http;

class PropertyAddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $clients = Client::all();
        foreach ($clients as $key => $client) {
            if (!empty($client->geo_address)) {
                $address = ClientPropertyAddress::firstOrCreate(
                        [
                            'client_id' => $client->id,
                        ],
                        [
                            'address_name' => $client->geo_address ? $client->geo_address : NULL,
                            'city' => $client->city ? $client->city : NULL,
                            'floor' => $client->floor ? $client->floor : NULL,
                            'apt_no' => $client->apt_no ? $client->apt_no : NULL,
                            'entrence_code' => $client->entrence_code ? $client->entrence_code : NULL,
                            'zipcode' => $client->zipcode ? $client->zipcode : NULL,
                            'geo_address' => $client->geo_address ? $client->geo_address : NULL,
                            'latitude' => $client->latitude ? $client->latitude : NULL,
                            'longitude' => $client->longitude ? $client->longitude : NULL,
                            'prefer_type' => 'both',
                        ]
                );
            }
            else {
                if(!empty($client->city) && !empty($client->street_n_no)) {
                    $clientAddress = $client->city . ", " . $client->street_n_no;
                }
                elseif (!empty($client->city)) {
                    $clientAddress = $client->city;
                }
                elseif (!empty($client->street_n_no)) {
                    $clientAddress = $client->street_n_no;
                }

                if(!empty($client->latitude) && !empty($client->longitude)) {
                    $latlng = $client->latitude . "," . $client->longitude;
                }

                $langauge = $client->lng == "heb" ? 'he' : $client->lng;

                if(isset($clientAddress)) {
                    $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                                'address' => $clientAddress,
                                'language' => $langauge,
                                'key' => config('services.google.map_key'),
                            ] + (!empty($latlng) ? ['latlng' => $latlng] : []));

                    if ($response->successful()) {
                        $data = $response->object();
                        $result = $data->results[0] ?? null;

                        if ($result) {
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

                          $address = ClientPropertyAddress::firstOrCreate(
                                [
                                    'client_id' => $client->id,
                                ],
                                [
                                    'address_name' => $result->formatted_address ?? null,
                                    'city' => $city ?? NULL,
                                    'floor' => $client->floor,
                                    'apt_no' => $client->apt_no,
                                    'entrence_code' => $client->entrence_code,
                                    'zipcode' => $zipcode ?? NULL,
                                    'geo_address' => $result->formatted_address ?? NULL,
                                    'latitude' => $result->geometry->location->lat ?? NULL,
                                    'longitude' => $result->geometry->location->lng ?? NULL,
                                    'prefer_type' => 'both',
                                ]
                            );
                        }
                    }
                }
            }

            if(isset($address)) {
                // Update address_id in schedules
                $schedules = $client->schedules()->get();
                if($schedules->isNotEmpty())
                {
                    foreach ($schedules as $key => $schedule) {
                        $schedule->update(['address_id' => $address->id]);
                    }
                }

                // Update address_id in services of client's offer
                $offers = $client->offers()->get();
                if($offers->isNotEmpty())
                {
                    foreach ($offers as $key => $offer) {
                        $clientServices = json_decode($offer->services);
                        foreach ($clientServices as $key => $service) {
                            $clientServices[$key]->address = $address->id;
                        }

                        $offer->update(['services' => json_encode($clientServices, JSON_UNESCAPED_UNICODE)]);
                    }
                }
            }
        }
    }
}
