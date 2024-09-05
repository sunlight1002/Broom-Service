<?php

namespace App\Console\Commands;

use App\Models\Offer;
use App\Models\Client;
use Illuminate\Console\Command;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;


class StatusNotUpdated24hours extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'StatusNotUpdated24';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'notify team which status is not updated for over 24 hours';

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
        // Get LeadStatus records where lead_status is 'pending' and were created more than 24 hours ago
        $offerStatuses = Offer::where('status', 'sent')
            ->where('created_at', '<=', Carbon::now()->subHours(24))
            ->get();

        foreach ($offerStatuses as $offerStatus) {
            $client = Client::find($offerStatus->client_id);

            if ($client) {
                $this->info("Sending notification to team for client: " . $client->firstname);
                $this->sendNotification($client, $offerStatus);
            } else {
                $this->info("Client not found for Offer Status ID: {$offerStatus->id}");
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
    protected function sendNotification($client, $offerStatus)
    {
         // Trigger WhatsApp Notification Only
         $response = event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::STATUS_NOT_UPDATED,
            "notificationData" => [
                'client' => $client->toArray(),
            ]
        ]));

        if ($response) {
            $this->info("Notification sent for Lead Status ID: {$offerStatus->id}");
        } else {
            $this->error("Failed to send notification for Lead Status ID: {$offerStatus->id}");
        }
    }
}
