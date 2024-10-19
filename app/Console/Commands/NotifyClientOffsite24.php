<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LeadActivity;
use App\Models\Files;
use App\Models\ClientMetas;
use App\Models\Schedule; // Include Schedule model
use Carbon\Carbon;
use App\Events\WhatsappNotificationEvent;
use App\Enums\ClientMetaEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App;

class NotifyClientOffsite24 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifyoffsite24';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify clients with potential status for 24 hours and have not submitted files';

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
        $yesterdayDateTime = $currentDateTime->subHours(24); // 24 hours ago from now

        // Fetch LeadActivities where changes_status is 'potential' and it's older than 24 hours
        $leadActivities = LeadActivity::with('client')
            ->where('changes_status', 'potential')
            ->where('status_changed_date', '<=', $yesterdayDateTime) // Status changed more than 24 hours ago
            ->whereHas('client', function ($q) use ($staticDate) {
                // Only include clients created on or after the static date
                $q->whereDate('created_at', '>=', $staticDate);
            })
            ->get();

        \Log::info("Command execution started.");
        \Log::info($leadActivities);

        foreach ($leadActivities as $leadActivity) {
            $client = $leadActivity->client;

            if ($client) {
                // Find the associated schedule
                $schedule = Schedule::where('client_id', $client->id)
                    ->where('meet_via', 'off-site') // Ensure meet_via is 'off-site'
                    ->first();

                // Skip if no schedule or meet_via is not 'off-site'
                if (!$schedule) {
                    $this->info("No off-site meeting found for client: " . $client->firstname);
                    continue;
                }

                // Check if the notification has already been sent
                $notificationSent = ClientMetas::where('client_id', $client->id)
                    ->where('key', ClientMetaEnum::NOTIFICATION_SENT_OFFSITE)
                    ->exists();

                if ($notificationSent) {
                    // Skip sending the notification if it was already sent
                    $this->info("Notification already sent for client: " . $client->firstname);
                    continue;
                }

                // Check if the client has submitted any files
                $fileSubmitted = Files::where('user_id', $client->id)->exists();

                if (!$fileSubmitted) {
                    // Set locale based on client's language
                    App::setLocale($client->lng);

                    // Trigger WhatsApp notification for missing file submission
                    event(new WhatsappNotificationEvent([
                        "type" => WhatsappMessageTemplateEnum::FILE_SUBMISSION_REQUEST,
                        "notificationData" => [
                            'client' => $client->toArray(),
                        ]
                    ]));

                    // Log the success
                    $this->info("Notification sent for client: " . $client->firstname . " (File submission missing)");

                    // Store the notification status in the client_metas table to ensure it's only sent once
                    ClientMetas::updateOrCreate(
                        [
                            'client_id' => $client->id,
                            'key' => ClientMetaEnum::NOTIFICATION_SENT_OFFSITE,
                        ],
                        [
                            'value' => Carbon::now()->toDateTimeString(), // Value indicating the notification has been sent
                        ]
                    );
                } else {
                    $this->info("Client: " . $client->firstname . " has already submitted files.");
                }
            } else {
                // Log if the client is not found
                $this->error("Client not found for Lead Activity ID: {$leadActivity->id}");
            }
        }

        return 0;
    }
}
