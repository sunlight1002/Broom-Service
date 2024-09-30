<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Job;
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
    protected $description = 'Every Monday, send a notification to all clients and workers asking if they have any changes to their schedule for the following week or if they would like to keep the same schedule.';

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


        $scheduledJobs = Job::whereBetween('start_date', [$startOfNextWeek, $endOfNextWeek])
            ->with(['client', 'worker']) 
            ->get();
            \Log::info($scheduledJobs."scheduled");

        // Get the WhatsApp template
        $template = WhatsappTemplate::where('key','NOTIFY_MONDAY_CLIENT_AND_WORKER_FOR_SCHEDULE')->first();

        foreach ($scheduledJobs as $job) {
            if ($job->client) {
                $clientData = [
                    'type' => WhatsappMessageTemplateEnum::NOTIFY_MONDAY_CLIENT_AND_WORKER_FOR_SCHEDULE,
                    'notificationData' => [
                        'template' => $template,
                        'job' => $job, 
                        'recipientType' => 'client',
                    ],
                ];
                event(new WhatsappNotificationEvent($clientData));
            }
        
            if ($job->worker) {
                $workerData = [
                    'type' => WhatsappMessageTemplateEnum::NOTIFY_MONDAY_CLIENT_AND_WORKER_FOR_SCHEDULE,
                    'notificationData' => [
                        'template' => $template,
                        'job' => $job, 
                        'recipientType' => 'worker',
                    ],
                ];
                event(new WhatsappNotificationEvent($workerData));
            }
        }
        

        return 0;
    }
}
