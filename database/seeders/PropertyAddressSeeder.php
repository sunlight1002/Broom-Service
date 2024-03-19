<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\ClientPropertyAddress;


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
            if ($client->geo_address) {
                ClientPropertyAddress::create(
                    [
                        'client_id' => $client->id,
                        'address_name' => $client->geo_address ? $client->geo_address : NULL,
                        'city' => $client->city ? $client->city : NULL,
                        'floor' => $client->floor ? $client->floor : NULL,
                        'apt_no' => $client->apt_no ? $client->apt_no : NULL,
                        'entrence_code' => $client->entrence_code ? $client->entrence_code : NULL,
                        'zipcode' => $client->zipcode ? $client->zipcode : NULL,
                        'geo_address' => $client->geo_address ? $client->geo_address : NULL,
                        'latitude' => $client->latitude ? $client->latitude : NULL,
                        'longitude' => $client->longitude ? $client->longitude : NULL,
                    ]
                );
            }
        }
    }
}
