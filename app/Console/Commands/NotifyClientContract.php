<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contract;
use App\Models\ClientMetas;
use App\Enums\ClientMetaEnum;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use Carbon\Carbon;
use App;

class NotifyClientContract extends Command
{
    protected $signature = 'notifyclientforcontract';
    protected $description = 'Notify clients with "not-signed" contracts after specific durations';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
{
    // Capture the current date and time once
    $currentDateTime = Carbon::now();

    // Define the static date to start notifications from
    $staticDate = "2024-09-20"; // Static date to start notifications from

    // Define time limits for 24-hour and 3-day notifications
    $timeLimit24Hours = $currentDateTime->subHours(24)->toDateTimeString(); // Contracts older than 24 hours
    $timeLimit3Days = $currentDateTime->subDays(3)->toDateTimeString(); // Contracts older than 3 days

    // Fetch contracts "not-signed" that were created more than 24 hours ago, but within the last 3 days
    $contracts24Hours = Contract::with('client')
        ->where('status', 'not-signed')
        ->where('created_at', '>=', $staticDate)
        ->where('created_at', '<=', $timeLimit24Hours)
        ->where('created_at', '>', $timeLimit3Days) // Older than 24 hours but not older than 3 days
        ->get();

    // Notify for contracts "not-signed" in the last 24 hours
    foreach ($contracts24Hours as $contract) {
        \Log::info($contract);
        $client = $contract->client;
        
        // Check if 24-hour notification has already been sent
        $metaExists = ClientMetas::where('client_id', $client->id)
            ->where('key', ClientMetaEnum::NOTIFICATION_SENT_CONTRACT24HOUR)
            ->exists();

        if ($client && !$metaExists) {
            App::setLocale($client->lng); // Set locale for notifications

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::CONTRACT_REMINDER_TO_CLIENT_AFTER_24HOUR,
                "notificationData" => [
                    'client' => $client->toArray(),
                    'contract' => $contract->toArray(),
                ]
            ]));

            // Store that the 24-hour notification has been sent
            ClientMetas::create([
                'client_id' => $client->id,
                'key' => ClientMetaEnum::NOTIFICATION_SENT_CONTRACT24HOUR,
                'value' => Carbon::now(),
            ]);

            $this->info("24-hour notification sent for client: " . $client->firstname);
        }
    }

    // Fetch contracts "not-signed" that were created more than 3 days ago
    $contracts3Days = Contract::with('client')
        ->where('status', 'not-signed')
        ->where('created_at', '>=', $staticDate) // Start from static date
        ->where('created_at', '<=', $timeLimit3Days) // Older than 3 days
        ->get();

    // Notify for contracts "not-signed" older than 3 days
    foreach ($contracts3Days as $contract) {
        $client = $contract->client;
        
        // Check if 3-day notification has already been sent
        $metaExists = ClientMetas::where('client_id', $client->id)
            ->where('key', ClientMetaEnum::NOTIFICATION_SENT_CONTRACT3DAY)
            ->exists();

        if ($client && !$metaExists) {
            App::setLocale($client->lng); // Set locale for notifications

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::CONTRACT_REMINDER_TO_CLIENT_AFTER_3DAY,
                "notificationData" => [
                    'client' => $client->toArray(),
                    'contract' => $contract->toArray(),
                ]
            ]));

            // Store that the 3-day notification has been sent
            ClientMetas::create([
                'client_id' => $client->id,
                'key' => ClientMetaEnum::NOTIFICATION_SENT_CONTRACT3DAY,
                'value' => Carbon::now(),
            ]);

            $this->info("3-day notification sent for client: " . $client->firstname);
        }
    }

    return 0;
}

}

