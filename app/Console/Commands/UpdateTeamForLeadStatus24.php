<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LeadStatus;
use Carbon\Carbon;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App;

class UpdateTeamForLeadStatus24 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leadupdate24team';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send notification to team every 24 hour whoose lead status is pending';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->whapiApiEndpoint = config('services.whapi.url');
        $this->whapiApiToken = config('services.whapi.token');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $staticDate = "2024-10-15"; // Static date to start notifications from
        $currentDateTime = Carbon::now();
        $yesterdayDateTime = $currentDateTime->subHours(24); // 24 hours ago from now

        // Get LeadStatus records where lead_status is 'pending' and were created more than 24 hours ago
        $leadStatuses = LeadStatus::with('client')
            ->where('lead_status', 'pending')
            ->whereHas('client', function ($q) use ($staticDate) {
                // Limit to leads created on or after the static date
                $q->whereDate('created_at', '>=', $staticDate);
            })
            ->where('created_at', '<=', $yesterdayDateTime)  // Created more than 24 hours ago
            ->get();

        foreach ($leadStatuses as $leadStatus) {
            $client = $leadStatus->client;

            if ($client) {
                $this->info("Sending notification to team for client: " . $client->firstname);
                $this->sendNotification($client, $leadStatus);
            } else {
                $this->info("Client not found for Lead Status ID: {$leadStatus->id}");
            }
        }

        return 0;
    }

    /**
     * Send a notification to the team
     *
     * @param  \App\Models\Client $client
     * @param  \App\Models\LeadStatus $leadStatus
     * @return void
     */
    protected function sendNotification($client, $leadStatus)
    {
        // Trigger WhatsApp Notification Only
        $response = event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::FOLLOW_UP_REQUIRED,
            "notificationData" => [
                'client' => $client->toArray(),
            ]
        ]));

        if ($response) {
            $this->info("Notification sent for Lead Status ID: {$leadStatus->id}");
        } else {
            $this->error("Failed to send notification for Lead Status ID: {$leadStatus->id}");
        }
    }
}

