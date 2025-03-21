<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\Setting;
use App\Enums\SettingKeyEnum;
use App\Models\ClientPropertyAddress;
use Illuminate\Support\Facades\Http;
class updateSomeClientAddress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updateSomeClientAddress';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $clients = [
            1 => [
                'client_id' => 1,
                'address_name' => "12 O'Connell Street",
                'map_link' => "https://maps.app.goo.gl/FZMKv8XXqocvNets5",
                'latlng' => 32.0918167,34.7936914,
                'apt_no' => 'Asael 4, Tel Aviv-Jaffa',
                'floor' => "",
                'entrence_code' => ""

            ],
            40 => [
                'client_id' => 40,
                'address_name' => "כפר שמריהו, ישראל",
                'map_link' => "https://maps.app.goo.gl/oED9r5CiAREg3PKk8",
                'latlng' => 32.185896,34.8210973,
                'apt_no' => "סמ הזורע 2, Kfar Shmaryahu",
                'floor' => "",
                'entrence_code' => ""

            ],
            88 => [
                'client_id' => 40,
                'address_name' => "שמואל שניצר 4, תל אביב-יפו, ישראל",
                'map_link' => "https://maps.app.goo.gl/SCbJDRxk71MrQU9J6",
                'latlng' => 32.1194905,34.8358298,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
                
            ],
            238 => [
                'client_id' => 238, 
                'address_name' => "רמת השרון, ישראל",
                'map_link' => "https://maps.app.goo.gl/QHGC26Y7ekHy72Bv5",
                'latlng' =>32.0812383,34.8015651,
                'apt_no' => "",
                'floor' => "floor 3 ",
                'entrence_code' => ""
                
            ],
            240 => [
                'client_id' => 240, 
                'address_name' => "10 יכין",
                'map_link' => "https://maps.app.goo.gl/pw426oezBxREo1TN7",
                'latlng' =>32.0805358,34.8068623,
                'apt_no' => "apt 3",
                'floor' => "Floor 0",
                'entrence_code' => "#5964"
                
            ],
            246 => [
                'client_id' => 246, 
                'address_name' => "יצחק טבנקין 44, תל אביב-יפו, ישראל",
                'map_link' => "https://maps.app.goo.gl/8vRnKQFj7qkVyQPHA",
                'latlng' =>32.1378043,34.7937698,
                'apt_no' => "Building 8 Apt 41 FL 11",
                'floor' => "11",
                'entrence_code' => ""
                
            ],
            339 => [
                'client_id' => 339, 
                'address_name' => "קרן קימת לישראל 21, גבעתיים, 5323728, ישראל",
                'map_link' => "https://maps.app.goo.gl/1YJvrjRc9DeRHLa3A",
                'latlng' =>32.0772051,34.8065154,
                'apt_no' => "apt 7",
                'floor' => "floor 2",
                'entrence_code' => ""
                
            ],
            396 => [
                'client_id' => 396, 
                'address_name' => "שדרות לוי אשכול 2, תל אביב-יפו, ישראל",
                'map_link' => "https://maps.app.goo.gl/zgMqoL38VSWpcKXE8",
                'latlng' =>32.0684456,34.785742,
                'apt_no' => "apt 1343",
                'floor' => "floor 38 ",
                'entrence_code' => ""
                
            ],
            407 => [
                'client_id' => 407, 
                'address_name' => "הגולן 32, תל אביב-יפו, ישראל",
                'map_link' => "https://maps.app.goo.gl/gzJzHdgnF86ioLBNA",
                'latlng' =>32.1190676,34.8257402,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
                
            ],
            34 => [
                'client_id' => 34, 
                'address_name' => "שבטי ישראל 24, רמת השרון, ישראל",
                'map_link' => "https://maps.app.goo.gl/Lhhr17s42Wr5t6Ay6",
                'latlng' =>32.109871,34.787571,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
                
            ],
            668 => [
                'client_id' => 668, 
                'address_name' => "יפו, תל אביב-יפו, ישראל",
                'map_link' => "https://maps.app.goo.gl/JpfUHrXnv9R2DgE26",
                'latlng' =>32.0629772,34.7953195,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
                
            ],
            723 => [
                'client_id' => 723, 
                'address_name' => "Jaffa, Tel Aviv-Yafo, Israel",
                'map_link' => "https://maps.app.goo.gl/4muXCcj67qTFxZeg6",
                'latlng' =>32.0633587,34.773402,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
                
            ],
            726 => [
                'client_id' => 726, 
                'address_name' => "העצמאות 29, הרצליה, ישראל",
                'map_link' => "https://maps.app.goo.gl/GZcpUWWmQHqb7JGA8",
                'latlng' =>32.1184381,34.8013807,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
                
            ],
            642 => [
                'client_id' => 642, 
                'address_name' => "פנקס 64",
                'map_link' => "https://maps.app.goo.gl/yFhYzEmvthCGGpzT6",
                'latlng' =>32.0912625,34.7931305,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
            ],
            730 => [
                'client_id' => 730, 
                'address_name' => "Jaffa, Tel Aviv-Yafo, Israel",
                'map_link' => "https://maps.app.goo.gl/youz5gfKw7wFeskH7",
                'latlng' =>32.0718016,34.7801917,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
                
            ],
            833 => [
                'client_id' => 833, 
                'address_name' => "הרצליה, ישראל",
                'map_link' => "https://maps.app.goo.gl/QT1Pxm2AFzdQfeM77",
                'latlng' =>32.0968016,34.7952238,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
                
            ],
            884 => [
                'client_id' => 884, 
                'address_name' => "תל אביב-יפו, ישראל",
                'map_link' => "https://maps.app.goo.gl/gU8GdKrAxocsU8UM7",
                'latlng' =>32.0690999,34.7954755,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
                
            ],
            906 => [
                'client_id' => 906, 
                'address_name' => "יפו, תל אביב-יפו, ישראל",
                'map_link' => "https://maps.app.goo.gl/GyqKMSCGyoF86a9cA",
                'latlng' =>32.0999062,34.7886077,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
                
            ],
            949 => [
                'client_id' => 949, 
                'address_name' => "יפו, תל אביב-יפו, ישראל",
                'map_link' => "https://maps.app.goo.gl/PsaNyo8w71RaedhH7",
                'latlng' =>32.0575712,34.7662843,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
                
            ],
            987 => [
                'client_id' => 987, 
                'address_name' => "יפו, תל אביב-יפו, ישראל",
                'map_link' => "https://maps.app.goo.gl/7MVfL2zt8oHUsREC8",
                'latlng' =>32.0903501,34.7756473,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
                
            ],
            1268 => [
                'client_id' => 1268, 
                'address_name' => "רמת גן, ישראל",
                'map_link' => "https://maps.app.goo.gl/LNH7KchuZDispVSs8",
                'latlng' =>32.0553478,34.8456851,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
                
            ],
            1327 => [
                'client_id' => 1327, 
                'address_name' => "12 O'Connell Street",
                'map_link' => "https://maps.app.goo.gl/utZkBNePAyWud8H8A",
                'latlng' =>32.0857839,34.7922588,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
                
            ],
            1426 => [
                'client_id' => 1426, 
                'address_name' => "רמת גן, ישראל",
                'map_link' => "https://maps.app.goo.gl/1XbA4EDua9wr6mKj9",
                'latlng' =>32.1588991,34.817533,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
                
            ],
            1428 => [
                'client_id' => 1428, 
                'address_name' => "12 O'Connell Street",
                'map_link' => "https://maps.app.goo.gl/8P4sFZyC9QiMWpBd9",
                'latlng' =>32.0896935,34.775883,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
                
            ],
            1515 => [
                'client_id' => 1515, 
                'address_name' => "12 O'Connell Street",
                'map_link' => "https://maps.app.goo.gl/sap8r7RorFx9DU5h9",
                'latlng' =>32.1654188,34.8406211,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
                
            ],
            1581 => [
                'client_id' => 1581, 
                'address_name' => "12 O'Connell Street",
                'map_link' => "https://maps.app.goo.gl/rCvaPevFZPESgr87A",
                'latlng' =>32.0871432,34.8048112,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
                
            ],
            1610 => [
                'client_id' => 1610, 
                'address_name' => "Jaffa, Tel Aviv-Yafo, Israel",
                'map_link' => "https://maps.app.goo.gl/pvikdbKWiBMUBV3Z8",
                'latlng' =>32.0476879,34.7557673,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
                
            ],
            1615 => [
                'client_id' => 1615, 
                'address_name' => "רמת השרון, ישראל",
                'map_link' => "https://maps.app.goo.gl/uXWQt5AVYKDz9RVF9",
                'latlng' =>32.1435697,34.8297792,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
                
            ],
            1655 => [
                'client_id' => 1655, 
                'address_name' => "12 O'Connell Street",
                'map_link' => "https://maps.app.goo.gl/NQc99HHAUhFRtNSa9",
                'latlng' =>32.0806811,34.7833394,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
                
            ],
            1687 => [
                'client_id' => 1687, 
                'address_name' => "12 O'Connell Street",
                'map_link' => "https://maps.app.goo.gl/e1ggLi6tENQskMjWA",
                'latlng' =>32.0746093,34.8123841,
                'apt_no' => "",
                'floor' => "",
                'entrence_code' => ""
                
            ],
        ];

            foreach ($clients as $clientData) {
                $clientId = $clientData['client_id'];
                $client = Client::find($clientId);

                $clientAddressName = $clientData['address_name'];
                $mapLink = $clientData['map_link'];
                $apt_no = !empty($clientData['apt_no']) ? $clientData['apt_no'] : "";
                $floor = !empty($clientData['floor']) ? $clientData['floor'] : "";
                $entrence_code = !empty($clientData['entrence_code']) ? $clientData['entrence_code'] : "";

    
                // Extract latitude & longitude from map link
                $latLng = $this->extractLatLongFromMap($mapLink);
                if (!$latLng) {
                    Log::error("Failed to extract lat/long for client ID: $clientId");
                    continue;
                }
    
                [$latitude, $longitude] = $latLng;
    
                // Fetch client's property addresses
                $clientPropertyAddresses = ClientPropertyAddress::where('client_id', $clientId)->get();
    
                foreach ($clientPropertyAddresses as $clientPropertyAddress) {
                    // Match address_name
                    if ($clientPropertyAddress->address_name == $clientAddressName) {
                        $addressData = $this->fetchAddressFromGoogleMaps($client, $latitude, $longitude);

                        \Log::info("Updating address ID: {$clientPropertyAddress->id} with new data: " . json_encode($addressData, JSON_UNESCAPED_UNICODE));
        
                        if ($addressData) {
                            $clientPropertyAddress->update([
                                'address_name' => $addressData['address_name'], 
                                'geo_address' => $addressData['geo_address'],
                                'latitude' => $addressData['latitude'],
                                'longitude' => $addressData['longitude'],
                                'city' => $addressData['city'],
                                'apt_no' => $apt_no,
                                'floor' => $floor,
                                'entrence_code' => $entrence_code
                            ]);
                        }
                    }

                }
            }
    
        return 0;
    }

        /**
     * Extract latitude & longitude from Google Maps link.
     */
    private function extractLatLongFromMap($mapLink)
    {
        // Fetch the actual location from the map link
        $response = Http::get($mapLink);

        if ($response->successful()) {
            preg_match('/@([0-9.-]+),([0-9.-]+)/', $response->body(), $matches);
            return isset($matches[1], $matches[2]) ? [(float)$matches[1], (float)$matches[2]] : null;
        }

        return null;
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

