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
use App\Enums\LeadStatusEnum;
use Carbon\Carbon;


class updateLeadsWithCampaign extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:updateLeadsWithCampaign';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update leads with campaign';

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
        $notExist = [];
        $filePath = storage_path('app/campaign.csv');

        if (!file_exists($filePath)) {
            $this->error('File not found at: ' . $filePath);
            return 1;
        }

        $this->info('File found at: ' . $filePath);

        // Load data as a collection of arrays
        $data = Excel::toArray([], $filePath);

        if (empty($data) || count($data[0]) < 2) {
            $this->error('No data found in the CSV.');
            return 1;
        }

        // Extract header and rows
        $header = $data[0][0]; // First row is header
        $rows = array_slice($data[0], 1); // Skip header

        // Get the index for created_time and phone_number
        $createdTimeIndex = array_search('created_time', $header);
        $phoneIndex = array_search('phone_number', $header);

        if ($createdTimeIndex === false || $phoneIndex === false) {
            $this->error('Required columns not found in the CSV.');
            return 1;
        }

        foreach ($rows as $row) {
            if (!isset($row[$createdTimeIndex]) || !isset($row[$phoneIndex])) {
                continue; // Skip incomplete rows
            }

            try {
                $createdAt = Carbon::parse($row[$createdTimeIndex])->toDateTimeString();
                $phoneNumber = $this->fixedPhoneNumber($row[$phoneIndex]);
                // $this->info("Phone: $phoneNumber | Created At: $createdAt");

                $client = Client::with('lead_status')->where('phone', $phoneNumber)->first();
                if(!$client) {
                    $notExist[] = "Phone: " . $phoneNumber;
                    continue;
                }else if ($client->created_at->isSameDay(Carbon::parse($createdAt))) {
                    \Log::info($client->id . " | " . $client->lead_status->lead_status . " | " . $client->created_at);
                    continue;
                }else{
                    $client->created_at = $createdAt;
                    $client->status = 0;
                    $client->lead_status->lead_status = LeadStatusEnum::PENDING;
                    $client->lead_status->updated_at = now();
                    $client->lead_status->save();
                    $client->save();
                }

            } catch (Exception $e) {
                Log::error("Error parsing row: " . json_encode($row) . " | " . $e->getMessage());
            }
        }

        \Log::info($notExist);

        return 0;
    }


    public function fixedPhoneNumber($phone)
    {
        // Step 1: Remove 'p:' prefix if it exists
        if (strpos($phone, 'p:') === 0) {
            $phone = substr($phone, 2);
        }

        // Step 2: Remove all characters except digits and +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Step 3: Normalize the phone number
        if (strpos($phone, '+') === 0) {
            $phone = substr($phone, 1); // remove the plus
        }

        if (strpos($phone, '0') === 0) {
            $phone = '972' . substr($phone, 1);
        }

        // If phone is 9 or 10 digits and doesn't start with 972, add it
        $phoneLength = strlen($phone);
        if (($phoneLength === 9 || $phoneLength === 10) && strpos($phone, '972') !== 0) {
            $phone = '972' . $phone;
        }

        return $phone;
    }


}
