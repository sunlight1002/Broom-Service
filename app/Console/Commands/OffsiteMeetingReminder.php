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
use Illuminate\Support\Facades\DB;

class OffsiteMeetingReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'team:offsite-meeting-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify clients with potential status for 24 hours , 3days and 7days have not submitted files';

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

        $dates = [
            Carbon::now()->subDay(1)->toDateString(),
            Carbon::now()->subDays(3)->toDateString(),
            Carbon::now()->subDays(7)->toDateString(),
        ];

        $meetings = Schedule::with('client')
            ->where('meet_via', 'off-site')
            ->whereDate('created_at', '>=', '2024-10-19')
            ->whereDoesntHave('files')
            ->whereIn(DB::raw('DATE(created_at)'), $dates)
            ->get();

        foreach ($meetings as $key => $meeting) {
            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::OFF_SITE_MEETING_REMINDER_TO_CLIENT,
                "notificationData" => [
                    'client' => $meeting->toArray(),
                ]
            ]));
        }


        $staticDate = "2024-10-19"; // Static date to start notifications from
        $currentDateTime = Carbon::now();

        // Define time thresholds
        $yesterdayDateTime = $currentDateTime->copy()->subHours(24); // 24 hours ago
        $threeDaysAgoDateTime = $currentDateTime->copy()->subDays(3); // 3 days ago
        $sevenDaysAgoDateTime = $currentDateTime->copy()->subDays(7); // 7 days ago

        // Fetch LeadActivities where changes_status is 'potential' and it's older than 24 hours
        $leadActivities = LeadActivity::with('client')
            ->where('changes_status', 'potential')
            ->where('status_changed_date', '<=', $yesterdayDateTime) // Include all activities older than 7 days
            ->whereHas('client', function ($q) use ($staticDate) {
                $q->whereDate('created_at', '>=', $staticDate);
            })
            ->get();



        \Log::info("Command execution started.");
        \Log::info($leadActivities);

        foreach ($leadActivities as $leadActivity) {
            $client = $leadActivity->client;

            if ($client) {
                $schedule = Schedule::where('client_id', $client->id)
                    ->where('meet_via', 'off-site')
                    ->first();

                if (!$schedule) {
                    $this->info("No off-site meeting found for client: " . $client->firstname);
                    continue;
                }

                // Check if the client has submitted any files
                $fileSubmitted = Files::where('user_id', $client->id)->exists();

                if (!$fileSubmitted) {
                    App::setLocale($client->lng);

                    // Notify if no file has been submitted based on different time thresholds
                    if ($leadActivity->status_changed_date <= $sevenDaysAgoDateTime) {
                        // 7-Day Notification
                        $this->sendNotification(
                            $client,
                            ClientMetaEnum::NOTIFICATION_SENT_OFFSITE_7DAYS,
                            WhatsappMessageTemplateEnum::FILE_SUBMISSION_REQUEST,
                            "7-day notification sent for client: "
                        );
                    } elseif ($leadActivity->status_changed_date <= $threeDaysAgoDateTime) {
                        // 3-Day Notification
                        $this->sendNotification(
                            $client,
                            ClientMetaEnum::NOTIFICATION_SENT_OFFSITE_3DAYS,
                            WhatsappMessageTemplateEnum::FILE_SUBMISSION_REQUEST,
                            "3-day notification sent for client: "
                        );
                    } elseif ($leadActivity->status_changed_date <= $yesterdayDateTime) {
                        // 24-Hour Notification
                        $this->sendNotification(
                            $client,
                            ClientMetaEnum::NOTIFICATION_SENT_OFFSITE_24HOURS,
                            WhatsappMessageTemplateEnum::FILE_SUBMISSION_REQUEST,
                            "24-hour notification sent for client: "
                        );
                    }
                } else {
                    $this->info("Client: " . $client->firstname . " has already submitted files.");
                }
            } else {
                $this->error("Client not found for Lead Activity ID: {$leadActivity->id}");
            }
        }

        return 0;
    }

/**
 * Helper function to send notifications and store meta
 */
private function sendNotification($client, $metaKey, $templateEnum, $logMessage)
{
    // Check if notification has already been sent
    $notificationSent = ClientMetas::where('client_id', $client->id)
        ->where('key', $metaKey)
        ->exists();

    if (!$notificationSent) {
        event(new WhatsappNotificationEvent([
            "type" => $templateEnum,
            "notificationData" => [
                'client' => $client->toArray(),
            ]
        ]));

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::FILE_SUBMISSION_REQUEST_TEAM,
            "notificationData" => [
                'client' => $client->toArray(),
            ]
        ]));

        $this->info($logMessage . $client->firstname);

        // Store the notification status in the client_metas table
        ClientMetas::updateOrCreate(
            [
                'client_id' => $client->id,
                'key' => $metaKey,
            ],
            [
                'value' => Carbon::now()->toDateTimeString(),
            ]
        );
    } else {
        $this->info("Notification already sent for client: " . $client->firstname);
    }
}

}
