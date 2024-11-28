<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ClientPropertyAddress;
use App\Models\Client;
use Illuminate\Support\Facades\Http;

class UpdateEnglishAddressToHebrew extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'address:update-english-address';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update English Address to Hebrew';

    /**
     * Google Maps API Key
     *
     * @var string
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
        $clientIds = Client::where('lng', 'heb')->get()->pluck('id');
        $addresses = ClientPropertyAddress::whereIn('client_id', $clientIds)->get();

        $englishAddresses = $addresses->filter(function ($address) {
            return !preg_match('/[\p{Hebrew}]/u', $address->geo_address);
        });
        foreach ($englishAddresses as $key => $address) {
            $this->info("Processing Address ID: {$address->id}");
            try {
                // Fetch updated address data from Google Maps API
                $updatedData = $this->fetchAddressFromGoogleMaps(
                    $address->latitude,
                    $address->longitude
                );

                if ($updatedData) {
                    // Update address in the database
                    $address->update($updatedData);
                    $this->info("Updated Address ID: {$address->id} successfully.");
                } else {
                    $this->warn("No data found for Address ID: {$address->id}");
                }
            } catch (\Exception $e) {
                $this->error("Error updating Address ID: {$address->id} - {$e->getMessage()}");
            }
        }
        return 0;
    }

    /**
     * Fetch updated address data from Google Maps API.
     *
     * @param string $latitude
     * @param string $longitude
     * @return array|null
     */
    private function fetchAddressFromGoogleMaps($latitude, $longitude)  
    {
        $url = "https://maps.googleapis.com/maps/api/geocode/json";

        try {
            $response = Http::get($url, [
                'latlng' => "{$latitude},{$longitude}",
                'language' => 'he', // Get address in Hebrew
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
