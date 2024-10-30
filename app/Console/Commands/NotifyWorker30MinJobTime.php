<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\JobNotificationToWorker;
use App\Events\AdminNotificationEvent;
use App\Enums\WorkerMetaEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\WhatsappNotificationEvent;
use App\Models\Job;
use App\Models\JobHours;
use App\Models\WorkerMetas;
use Carbon\Carbon;

class NotifyWorker30MinJobTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifyWorkerBeforeJobTime';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify worker before 30min to finish the job';

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
        $staticDate = "2024-10-29"; // Static date to start notifications from
        $currentTime = Carbon::now();
        \Log::info('Current Time: ' . $currentTime->format('H:i'));
        $currentDate = Carbon::now()->toDateString();

        // Calculate 30 minutes after the current time
        $timeBefore30Min = $currentTime->copy()->addMinutes(30);
        \Log::info('Time 30 Minutes Later: ' . $timeBefore30Min->format('H:i'));

        // Fetch jobs that are ending in 30 minutes
        $jobs = Job::with(["hours", 'worker', 'client', 'propertyAddress'])
                    ->where('end_time', '>=', $currentTime->format('H:i'))
                    ->where('end_time', '<=', $timeBefore30Min)
                    ->where('status', 'progress')
                    ->whereDate('start_date', $currentDate)
                    ->whereHas('hours', function ($query) use ($staticDate) {
                        // Limit to JobHours records created on or after the static date
                        $query->whereDate('created_at', '>=', $staticDate);
                    })
                    ->get();
        foreach ($jobs as $job) {
            // Check if notification has already been sent
            $notificationSent = WorkerMetas::where('job_id', $job->id)
                                            ->where('key', WorkerMetaEnum::NOTIFICATION_SENT_30MIN_BEFORE_JOB_ENDTIME)
                                            ->exists();

            if (!$notificationSent) {
                // Send notification here
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::NOTIFY_WORKER_BEFORE_30MIN_JOB_END_TIME,
                    "notificationData" => array(
                        'job'        => $job->toArray(),
                    )
                ]));
    
                // Log info for tracking (optional)
                \Log::info("WhatsApp notification sent to worker ID: {$job->worker_id} for Job ID: {$job->id}.");

                // Log that the notification has been sent
                WorkerMetas::updateOrCreate(
                    [
                        'worker_id' => $job->worker_id,
                        'job_id' => $job->id,
                        'key' => WorkerMetaEnum::NOTIFICATION_SENT_30MIN_BEFORE_JOB_ENDTIME,
                    ],
                    [
                        'value' => Carbon::now(),
                    ]
                );
            }
        }

        return 0;
    }


}
