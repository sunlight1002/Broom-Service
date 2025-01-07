<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Http;

use Illuminate\Console\Command;
use App\Models\Setting;
use App\Enums\SettingKeyEnum;
use App\Models\Client;


class IcountSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'icount:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'icount sync';

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
        $clients = Client::all();

        foreach ($clients as $client) {
            $data = $this->syncClient($client);
        }

        return 0;
    }

    public function syncClient($client)
    {
        // Retrieve iCount credentials from settings
        $iCountCompanyID = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_COMPANY_ID)
            ->value('value');
    
        $iCountUsername = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_USERNAME)
            ->value('value');
    
        $iCountPassword = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_PASSWORD)
            ->value('value');
    
        // iCount API URL
        $url = 'https://api.icount.co.il/api/v3.php/client/info';
    
        // Request data, including client phone and email
        $requestData = [
            'cid' => $iCountCompanyID,
            'user' => $iCountUsername,
            'pass' => $iCountPassword,
            'phone' => $client['phone'] ?? null, 
            'email' => $client['email'] ?? null,
            'get_custom_info' => true,
            'get_contacts' => true
        ];
    
        // Send POST request to iCount API
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $requestData);
    
        $data = $response->json();
        $http_code = $response->status();
    
    
        // Check if the request was successful and status is true
        if ($http_code == 200 && $data['status'] === true) {
            $clientInfo = $data['client_info']; // Get client info from the response
    
            // \Log::info("Client Info: " . json_encode($clientInfo));
            // \Log::info($client['invoicename'] . " - " . $client['icount_client_id'] . " - " . $clientInfo['client_name'] . " - " . $clientInfo['id']);

            $client->update([
                'invoicename' => $clientInfo['client_name'], // Update invoicename with client_name
                'icount_client_id' => $clientInfo['id'], // Update icount_client_id with client id
            ]);
    
            return $data;
        }
    
    }
    
    
}
