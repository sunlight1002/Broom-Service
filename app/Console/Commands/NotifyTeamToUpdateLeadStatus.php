<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\LeadStatus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

class NotifyTeamToUpdateLeadStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update24';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a notification to the team if lead status is still pending after 24 hours';

    /**
     * Execute the console command.
     *
     * @return int
     */

    public function __construct()
    {
        parent::__construct();
        $this->whapiApiEndpoint = config('services.whapi.url');
        $this->whapiApiToken = config('services.whapi.token');
    }

    public function handle()
    {
        $todayDate = Carbon::now()->format('Y-m-d');
        // Get LeadStatus records where lead_status is 'pending' and were created more than 24 hours ago
        $leadStatuses = LeadStatus::with('client')
        ->where('lead_status', 'pending')
        ->whereHas('client', function ($q) use ($todayDate) {
            $q->whereDate('created_at', '>=', "2024-10-15"); // Limit to leads created on or after 2024-09-19
        })
        ->where('created_at', '<=', Carbon::now()->subHours(24))  // Created more than 24 hours ago
        ->get();

        foreach ($leadStatuses as $leadStatus) {
            \Log::info($leadStatus);
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
     * @param  \App\Models\Client
     * @param  \App\Models\LeadStatus
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
