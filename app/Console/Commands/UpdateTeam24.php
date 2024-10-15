<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Offer;
use App\Models\Client;
use Carbon\Carbon;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App;

class UpdateTeam24 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updateteam24';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the team for offers older than 24 hours';

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
        // Static date from which notifications should start
        $staticDate = "2024-10-15"; // Static date to start notifications from

        // Define the 24-hour time limit
        $timeLimit24Hours = Carbon::now()->subHours(24);

        // Get all offers with status 'sent' that are older than 24 hours and created after the static date
        $offerStatuses = Offer::where('status', 'sent')
            ->where('created_at', '>=', $staticDate) // Offers created after the static date
            ->where('created_at', '<=', $timeLimit24Hours) // Older than 24 hours
            ->get();

        foreach ($offerStatuses as $offerStatus) {
            $client = Client::find($offerStatus->client_id);

            if ($client) {
                // Set the locale based on the client's language
                App::setLocale($client->lng);

                // Trigger WhatsApp Notification
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::STATUS_NOT_UPDATED,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));

                // Log success
                $this->info("Notification sent for client: " . $client->firstname);
            } else {
                // Log failure if client not found
                $this->error("Client not found for Offer ID: {$offerStatus->id}");
            }
        }

        return 0;
    }
}
