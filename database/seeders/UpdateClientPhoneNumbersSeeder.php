<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;

class UpdateClientPhoneNumbersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Fetch all clients
        $clients = Client::all();

        foreach ($clients as $client) {
            $phone = $client->phone;

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
                if (strpos($client->phone, '+') === 0) {
                    $phone = substr($client->phone, 1);
                }
            }

            $phoneLength = strlen($phone);
            if (($phoneLength === 9 || $phoneLength === 10) && strpos($phone, '972') !== 0) {
                $phone = '972' . $phone;
            }

            // Update the client's phone number in the database if changed
            $client->update(['phone' => $phone]);
        }
    }
}
