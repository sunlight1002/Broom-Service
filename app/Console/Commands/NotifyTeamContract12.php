<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contract;
use App\Models\Client;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use Carbon\Carbon;
use App;

class NotifyTeamContract12 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifyteamcontract12';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify the team if a contract is not signed after 12 hours from when it was sent to the client';

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
        $staticDate = "2024-09-20"; // Static date to start notifications from
        $timeLimit12Hours = Carbon::now()->subHours(12); // Define the 12-hour time limit

        // Fetch contracts that are "not-signed" and were created (sent) more than 12 hours ago
        $contracts = Contract::with('client')
            ->where('status', 'not-signed')
            ->where('created_at', '>=', $staticDate) // Filter contracts created after the static date
            ->where('created_at', '<=', $timeLimit12Hours) // Check if they were created more than 12 hours ago
            ->get();

        // Notify the team for each contract that is "not-signed"
        foreach ($contracts as $contract) {
            $client = $contract->client;

            if ($client) {
                // Set locale based on the client's language preference

                // Trigger the team notification event
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::CONTRACT_NOT_SIGNED_12_HOURS,
                    "notificationData" => [
                        'client' => $client->toArray(),
                        'contract' => $contract->toArray(),
                        'contract_sent_date' => $contract->created_at
                    ]
                ]));

                // Log the notification for tracking
                $this->info("12-hour notification sent for client: " . $client->firstname . " to the team.");
            }
        }

        return 0;
    }
}
