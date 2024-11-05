<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LeadActivity;
use App\Models\Files;
use App\Models\Offer;
use App\Models\Schedule;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use Carbon\Carbon;
use App;

class NotifyTeamPriceOffer12 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifyteamoffer12';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify team if status is potential, meeting is off-site, files are submitted, but no offer is generated';

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
        $staticDate = "2024-10-19"; // Static date to start notifications from
        $currentDateTime = Carbon::now();
        $yesterdayDateTime = $currentDateTime->subHours(12); // 12 hours ago

        // Fetch LeadActivities with the new conditions
        $leadActivities = LeadActivity::with('client')
            ->where('changes_status', 'potential')
            ->whereHas('client', function ($query) use ($staticDate) {
                // Limit to clients created on or after the static date
                $query->whereDate('created_at', '>=', $staticDate);
            })
            ->where('status_changed_date', '<=', $yesterdayDateTime) // Older than 12 hours
            ->get();

        \Log::info("Command execution started for NotifyTeamPriceOffer12.");

        foreach ($leadActivities as $leadActivity) {
            $client = $leadActivity->client;

            if ($client) {
                // Check if the meeting was off-site
                $schedule = Schedule::where('client_id', $client->id)
                    ->where('meet_via', 'off-site')
                    ->first();

                if (!$schedule) {
                    $this->info("No off-site meeting found for client: " . $client->firstname);
                    continue;
                }

                // Check if files were submitted by the client
                $fileSubmitted = Files::where('user_id', $client->id)->exists();

                if (!$fileSubmitted) {
                    $this->info("Files not submitted by client: " . $client->firstname);
                    continue;
                }

                // Check if an offer was generated for the client
                $offerGenerated = Offer::where('client_id', $client->id)->exists();

                if ($offerGenerated) {
                    $this->info("Offer already generated for client: " . $client->firstname);
                    continue;
                }

                // If all conditions are met, send the notification to the team
                App::setLocale($client->lng);

                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::PRICE_OFFER_REMINDER_12_HOURS,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));

                // Log the success
                $this->info("Notification sent to team: " . $client->firstname . " (No offer generated)");
            } else {
                // Log if the client is not found
                $this->error("Client not found for Lead Activity ID: {$leadActivity->id}");
            }
        }

        return 0;
    }
}
