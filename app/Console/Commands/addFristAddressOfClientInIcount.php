<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\Setting;
use App\Enums\SettingKeyEnum;
use App\Models\ClientPropertyAddress;
use Illuminate\Support\Facades\Http;
use SplTempFileObject;
use League\Csv\Writer;
use Illuminate\Support\Facades\Storage;


class addFristAddressOfClientInIcount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add-frist-address-of-client-in-icount';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'add frist address of client in icount';

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
        $clients = Client::with('lead_status')
                ->whereHas('lead_status', function ($q) {
                    $q->where('lead_status', 'active client');
                })
                ->where('status', 2)
                ->get();

        $headers = [
            'Client ID', 'Full Name', 'Invoice Name', 'Phone', 'Address Name', 'Geo Address', 'City', 'Zip Code',
        ];
        // Create CSV writer instance
        $csv = Writer::createFromFileObject(new SplTempFileObject());

        // Insert the headers
        $csv->insertOne($headers);

        foreach ($clients as $client) {
            $clientPropertyAddress = ClientPropertyAddress::where('client_id', $client->id)->first();

            $data = [
                $client->id,
                ($client->firstname ?? null) . ' ' . ($client->lastname ?? null),
                $client->invoicename ?? ($client->firstname ?? null) . ' ' . ($client->lastname ?? null),
                $client->phone ?? null,
                $clientPropertyAddress->address_name ?? null,
                $clientPropertyAddress->geo_address ?? null,
                $clientPropertyAddress->city ?? null,
                $clientPropertyAddress->zipcode ?? null
            ];

            $csv->insertOne($data);

            // if ($clientPropertyAddress && $client->icount_client_id) {
            //     $data = [
            //         'client_id' => $client->icount_client_id,
            //         'bus_street' => $clientPropertyAddress->geo_address,
            //         'bus_city' => $clientPropertyAddress->city ?? null,
            //         'bus_zip' => $clientPropertyAddress->zipcode ?? null,
            //     ];

            //     $this->updateClientIcount($data);
            // }
        }
        
        // Output the CSV file to storage
        $fileName = 'clients_first_address.csv';
        Storage::put($fileName, $csv->toString());

        $this->info("Report has been exported to storage/{$fileName}");

        return 0;
    }


    public function updateClientIcount($data)
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
            'client_id' => $data['client_id'] ?? 0,
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
        // return $data;
    }
}
