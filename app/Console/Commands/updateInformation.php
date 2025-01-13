<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Exception;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Client;
use App\Models\Setting;
use App\Enums\SettingKeyEnum;


class updateInformation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updateInformation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update information';

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
        $filePath = storage_path('clients חדש.xlsx');

        if (!file_exists($filePath)) {
            $this->error('File not found at: ' . $filePath);
            return;
        }

        $data = Excel::toArray([], $filePath);
            $firstSheet = $data[0] ?? [];
    
            if (empty($firstSheet)) {
                $this->info("The first sheet is empty.");
                return;
            }
    
            $limitedRows = array_slice($firstSheet, 0, 1050);

            foreach ($limitedRows as $rowIndex => $row) {
                $row = array_slice($row, 0, 45);
    
                if (empty(array_filter($row))) {
                    continue;
                }

                $email = $row[18] ?? null;
                \Log::info("Email: $email");
                $client = Client::where('email', $email)->first();
                if($client){
                    $data = $this->getIcountClientInfo($client, $row);
                    // \Log::info("Data: " . json_encode($data));
                }


                // Log::info('Google Sheet Row $rowIn', $row);
            }
    }

    public function getIcountClientInfo($client, $row)
    {
        $firstname = $row[1] ?? null;
        $lastname = $row[2] ?? null;
        $invoiceName = $row[3] ?? null;
        $phone = $row[17] ?? null;

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


        if ($http_code == 200 && $data['status'] === true) {
            $clientInfo = $data['client_info']; // Get client info from the response
            \Log::info('iCount Client Info', $clientInfo);
            $propertyAddress = $client->property_addresses()->first();

            if ($clientInfo) {
                $data = [
                    'id' => $clientInfo['id'],
                    'email' => $clientInfo['email'],
                    'company_name' => empty($clientInfo['company_name']) ? $invoiceName : $clientInfo['company_name'],
                ];

                $needToUpdate = false;
                if(empty($clientInfo['fname'])) {
                    $needToUpdate = true;
                    $data['fname'] = empty($clientInfo['fname']) ? $firstname : $clientInfo['fname'];
                }

                if(empty($clientInfo['lname'])) {
                    $needToUpdate = true;
                    $data['lname'] = $lastname ?? $client->lastname;
                }

                if(empty($clientInfo['mobile'])) {
                    $needToUpdate = true;
                    $data['mobile'] = $phone ? $this->fixedPhoneNumber($phone) : $this->fixedPhoneNumber($client->phone);
                }

               if($needToUpdate){
                $res= $this->updateClientIcount($data);
               }
            }

            $client->update([
                'firstname' => $clientInfo['fname'] ? $clientInfo['fname'] : $client['firstname'],
                'lastname' => $clientInfo['lname'] ? $clientInfo['lname'] : $client['lastname'],
                'invoicename' => $clientInfo['company_name'] ? $clientInfo['company_name'] : $client['invoicename'],
                'vat_number' => $clientInfo['vat_id'] ? $clientInfo['vat_id'] : $client['vat_number'],
            ]);

            return $data;
        } else {
            echo $client->email . PHP_EOL;
        }
    }

    private function updateClientIcount($data)
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
            'client_id' => $data['id'] ?? 0,
            'email' => $data['email'] ?? null,
            'fname' => $data['fname'] ?? null,
            'lname' => $data['lname'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'phone' => '',
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

        return $data;
    }

    public function fixedPhoneNumber($phone){
        // $phone = $client->phone;

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
            if (strpos($phone, '+') === 0) {
                $phone = substr($phone, 1);
            }
        }

        $phoneLength = strlen($phone);
        if (($phoneLength === 9 || $phoneLength === 10) && strpos($phone, '972') !== 0) {
            $phone = '972' . $phone;
        }

        return $phone;
    }
}
