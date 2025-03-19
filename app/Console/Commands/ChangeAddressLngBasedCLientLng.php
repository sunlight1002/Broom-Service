<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\Setting;
use App\Enums\SettingKeyEnum;
use App\Models\ClientPropertyAddress;
use Illuminate\Support\Facades\Http;

class ChangeAddressLngBasedCLientLng extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change:address-lng';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change address lng based on client lng';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    protected $googleMapsApiKey;


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->googleMapsApiKey = config('services.google.map_key');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $clients = Client::all();
        
        foreach ($clients as $client) {
            $clientPropertyAddresses = ClientPropertyAddress::where('client_id', $client->id)->get();
    
            foreach ($clientPropertyAddresses as $clientPropertyAddress) {
                // Check if the current address is already in the correct language
                if ($this->isAddressInCorrectLanguage($client, $clientPropertyAddress->address_name) ||
                    $this->isAddressInCorrectLanguage($client, $clientPropertyAddress->geo_address)) {
                    \Log::info("Skipping update for address ID: {$clientPropertyAddress->id} as it is already in the correct language.");
                    continue; 
                }
    
                // Fetch and update address if needed
                $addressData = $this->fetchAddressFromGoogleMaps($client, $clientPropertyAddress->latitude, $clientPropertyAddress->longitude);
    
                if ($addressData) {
                    \Log::info("Updating address ID: {$clientPropertyAddress->id} with new data: " . json_encode($addressData));
                    $clientPropertyAddress->update($addressData);
                }
            }
        }
        return 0;
    }
    
    /**
     * Check if the given address matches the client's preferred language.
     *
     * @param Client $client
     * @param string|null $address
     * @return bool
     */
    private function isAddressInCorrectLanguage($client, $address)
    {
        if (!$address) return false; // If address is empty, assume incorrect
    
        // Detect language based on client's preferred language
        if ($client->lng == "heb") {
            return preg_match('/[א-ת]/u', $address); // Check for Hebrew letters
        } elseif ($client->lng == "en") {
            return preg_match('/[a-zA-Z]/', $address); // Check for English letters
        }
    
        return false; // Default case, assume incorrect
    }

    /**
     * Fetch updated address data from Google Maps API.
     *
     * @param string $latitude
     * @param string $longitude
     * @return array|null
     */
    private function fetchAddressFromGoogleMaps($client, $latitude, $longitude)  
    {
        $url = "https://maps.googleapis.com/maps/api/geocode/json";
        $lng = null;
        if ($client->lng == "heb") {
            $lng = "he";
        }else if ($client->lng == "en") {
            $lng = "en";
        }
        try {
            $response = Http::get($url, [
                'latlng' => "{$latitude},{$longitude}",
                'language' => $lng, 
                'key' => $this->googleMapsApiKey,
            ]);

            $data = $response->json();

            if (isset($data['results'][0])) {
                $result = $data['results'][0];

                // Extract the street name and number
                $streetName = $this->getStreetNameFromAddressComponents($result['address_components']);

                // Extract the city
                $city = $this->getCityFromAddressComponents($result['address_components']);

                return [
                    'address_name' => $streetName ?? $result['formatted_address'], // Street name and number
                    'geo_address' => $result['formatted_address'],
                    'latitude' => $result['geometry']['location']['lat'],
                    'longitude' => $result['geometry']['location']['lng'],
                    'city' => $city,
                ];
            }

            return null; // No results found
        } catch (\Exception $e) {
            throw new \Exception("Google Maps API error: " . $e->getMessage());
        }
    }

    /**
     * Extract the street name and number from address components.
     *
     * @param array $addressComponents
     * @return string|null
     */
    private function getStreetNameFromAddressComponents($addressComponents)
    {
        $street = null;
        $number = null;

        foreach ($addressComponents as $component) {
            if (in_array('route', $component['types'])) {
                $street = $component['long_name'];
            }

            if (in_array('street_number', $component['types'])) {
                $number = $component['long_name'];
            }
        }

        return trim(($number ? $number . ' ' : '') . $street);
    }

    /**
     * Extract the city from address components.
     *
     * @param array $addressComponents
     * @return string|null
     */
    private function getCityFromAddressComponents($addressComponents)
    {
        foreach ($addressComponents as $component) {
            if (in_array('locality', $component['types'])) {
                return $component['long_name'];
            }
        }

        return null; // City not found
    }
}
