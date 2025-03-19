<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use App\Enums\SettingKeyEnum;
use Illuminate\Support\Facades\Http;

class DuplicateIcountCustomer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'icount:find-duplicate-customer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Icount find duplicate customer';

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
        $url = 'https://api.icount.co.il/api/v3.php/client/get_list';

        // Request data, including client phone and email
        $requestData = [
            'cid' => $iCountCompanyID,
            'user' => $iCountUsername,
            'pass' => $iCountPassword,
            'detail_level' => 10
        ];

        // Send POST request to iCount API
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $requestData);

        if ($response->successful()) {
            $data = $response->json();
            $clients = $data['clients'] ?? [];

            // Lookup arrays to track duplicate phone and mobile numbers (ignoring 972)
            $phoneMap = [];
            $duplicates = [];

            foreach ($clients as $client) {
                $email = trim($client['email'] ?? '');
                $clientId = $client['id']; // Unique client ID
                $fullPhone = $this->normalizePhone(trim($client['phone'] ?? ''));
                $fullMobile = $this->normalizePhone(trim($client['mobile'] ?? ''));

                // Remove '972' for duplicate checking, but store full number for output
                $localPhone = $this->removeCountryCode($fullPhone);
                $localMobile = $this->removeCountryCode($fullMobile);

                // Skip if both phone and mobile are empty or '0'
                if ((empty($localPhone) || $localPhone == '0') && (empty($localMobile) || $localMobile == '0')) {
                    continue;
                }

                // Store client data for tracking duplicates
                $clientData = [
                    'id' => $clientId,
                    'name' => $client['client_name'],
                    'email' => $email,
                    'phone' => $localPhone,
                    'mobile' => $localMobile
                ];

                // Store by local phone (without 972)
                if (!empty($localPhone)) {
                    if (!isset($phoneMap[$localPhone])) {
                        $phoneMap[$localPhone] = [];
                    }
                    // Ensure unique clients only
                    if (!array_key_exists($clientId, array_column($phoneMap[$localPhone], 'id', 'id'))) {
                        $phoneMap[$localPhone][$clientId] = $clientData;
                    }
                }

                // Store by local mobile (without 972)
                if (!empty($localMobile)) {
                    if (!isset($phoneMap[$localMobile])) {
                        $phoneMap[$localMobile] = [];
                    }
                    // Ensure unique clients only
                    if (!array_key_exists($clientId, array_column($phoneMap[$localMobile], 'id', 'id'))) {
                        $phoneMap[$localMobile][$clientId] = $clientData;
                    }
                }
            }

            // Extract only duplicate groups
            $duplicateGroups = [];
            foreach ($phoneMap as $number => $clients) {
                if (count($clients) > 1) {
                    $duplicateGroups[] = array_values($clients);
                }
            }

            // Output all duplicate client groups
            if (!empty($duplicateGroups)) {
                $this->info("Duplicate client groups found (Ignoring 972 prefix):");

                foreach ($duplicateGroups as $group) {
                    $this->line("\n----------------------");
                    $this->line("Duplicates for Phone/Mobile: " . $group[0]['phone'] . " / " . $group[0]['mobile']);
                    foreach ($group as $dup) {
                        $this->line("ID: {$dup['id']}, Name: {$dup['name']}, Email: {$dup['email']}, Phone: {$dup['phone']}, Mobile: {$dup['mobile']}");
                    }
                    $this->line("----------------------");
                }

                // Store result in a structured array for merge API
                return $duplicateGroups;
            } else {
                $this->info("No duplicate clients found.");
            }
        } else {
            $this->error("Failed to retrieve client data from iCount.");
        }

        return 0;
    }

    /**
     * Normalize phone numbers to Israeli format.
     *
     * @param string $phone
     * @return string
     */
    private function normalizePhone($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/\D/', '', $phone);

        if (strpos($phone, '0') === 0) {
            // Remove leading '0' and prepend '972'
            $phone = '972' . substr($phone, 1);
        } elseif (strpos($phone, '972') === 0) {
            // Ensure no leading '+'
            $phone = ltrim($phone, '+');
        } elseif (strpos($phone, '+') === 0) {
            // Remove the '+'
            $phone = substr($phone, 1);
        } else {
            // If no country code, prepend '972'
            $phone = '972' . $phone;
        }

        return $phone;
    }

    /**
     * Remove country code 972 for duplicate checking.
     *
     * @param string $phone
     * @return string
     */
    private function removeCountryCode($phone)
    {
        if (strpos($phone, '972') === 0) {
            return substr($phone, 3); // Remove '972' prefix
        }
        return $phone;
    }
}
