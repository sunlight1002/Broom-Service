<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Job;
use App\Models\Holiday;
use App\Models\WhatsappTemplate;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use Carbon\Carbon;

class WeeklyNotifyClientWorker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mondayNotify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Every Monday, send a notification to all clients and workers asking if they have any changes to their schedule for the following week or if they would like to keep the same schedule. Also, notify them if there is any holiday during that week.';

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
        // Get the start and end dates for the following week
        $startOfNextWeek = Carbon::now()->addWeek()->startOfWeek();
        $endOfNextWeek = Carbon::now()->addWeek()->endOfWeek();
        \Log::info($startOfNextWeek." start");
        \Log::info($endOfNextWeek." end");

        // Fetch scheduled jobs for the next week
        $scheduledJobs = Job::whereBetween('start_date', [$startOfNextWeek, $endOfNextWeek])
            ->with(['client', 'worker'])
            ->get();

        // Fetch holidays for the next week
        $holidays = Holiday::whereBetween('start_date', [$startOfNextWeek, $endOfNextWeek])
            ->orWhereBetween('end_date', [$startOfNextWeek, $endOfNextWeek])
            ->get();
        // Build holiday message
        $holidayMessage = null;
        if ($holidays->count() > 0) {
            $holidayMessage = "";
            foreach ($holidays as $holiday) {
                $holidayMessage .= "- {$holiday->holiday_name} from {$holiday->start_date} to {$holiday->end_date}\n";
            }
        }

        // Get the WhatsApp template
        $template = WhatsappTemplate::where('key','NOTIFY_MONDAY_CLIENT_AND_WORKER_FOR_SCHEDULE')->first();

        foreach ($scheduledJobs as $job) {
            if ($job->client) {
                $clientData = [
                    'type' => WhatsappMessageTemplateEnum::NOTIFY_MONDAY_CLIENT_FOR_SCHEDULE,
                    'notificationData' => [
                        // 'job' => $job,
                        'client' => $job->client,
                        // 'holidayMessage' => $holidayMessage,
                    ],
                ];
                event(new WhatsappNotificationEvent($clientData));
                $job->client->stop_last_message = 0;
                $job->client->save();
            }
            // if ($job->worker) {
            //     $workerData = [
            //         'type' => WhatsappMessageTemplateEnum::NOTIFY_MONDAY_WORKER_FOR_SCHEDULE,
            //         'notificationData' => [
            //             // 'job' => $job,
            //             'worker' => $job->worker,
            //             // 'holidayMessage' => $holidayMessage,
            //         ],
            //     ];
            //     event(new WhatsappNotificationEvent($workerData));
            //     $job->worker->stop_last_message = 0;
            //     $job->worker->save();
            // }
        }

        return 0;
    }
}
