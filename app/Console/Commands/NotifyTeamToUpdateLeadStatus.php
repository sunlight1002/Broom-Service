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
        // Get LeadStatus records where lead_status is 'pending' and were created more than 24 hours ago
        $leadStatuses = LeadStatus::where('lead_status', 'pending')
            ->where('created_at', '<=', Carbon::now()->subHours(24))
            ->get();

        foreach ($leadStatuses as $leadStatus) {
            $client = Client::find($leadStatus->client_id);

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
        $receiverNumber = config('services.whatsapp_groups.problem_with_workers');
        $text = '*Pending Lead Status Update Required*';

        $text .= "\n\nHi, everyone\n\n";
        $text .= 'The lead status has been pending for more than 24 hours. Please update the status.' . "\n\n";

        $text .= sprintf(
            "Date/Time: %s\nClient: %s\nLead ID: %s",
            Carbon::now()->format('M d Y H:i'),
            $client->firstname, 
            $leadStatus->id
        );

        $response = Http::withToken($this->whapiApiToken)
        ->post($this->whapiApiEndpoint . 'messages/text', [
            'to' => $receiverNumber,
            'body' => $text
        ]);

        if ($response->successful()) {
            $this->info("Notification sent for Lead Status ID: {$leadStatus->id}");
        } else {
            $this->error("Failed to send notification for Lead Status ID: {$leadStatus->id}");
        }
    }
}
