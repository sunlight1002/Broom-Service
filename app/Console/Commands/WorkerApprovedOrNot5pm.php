<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Models\WorkerMetas;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Enums\WorkerMetaEnum;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Events\JobNotificationToWorker; // Adjust as necessary

class WorkerApprovedOrNot5pm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job:remind-workers-to-confirm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications to workers who haven\'t confirmed their jobs by 5:00 PM.';

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
        // Define the static date to start notifications from
        $staticDate = "2024-10-19"; // Static date to start notifications from
        // Get current time
        $currentTime = now();
        \Log::info($currentTime->format('Y-m-d'));

        // Get unconfirmed jobs where the current time is 5:00 PM or later
        $unconfirmedJobs = Job::with(['client', 'worker', 'propertyAddress'])
            ->where('worker_approved_at', null)
            ->whereDate('created_at', '>=', $staticDate)
            ->whereDate('start_date', '=', $currentTime->copy()->addDay()->format('Y-m-d')) // Get jobs for tomorrow
            ->get();

            $addresses=[];
                foreach ($unconfirmedJobs as $jobs) {
                    // if (!empty($jobs['property_address'])) {
                    //     // $addresses[] = $jobs['property_address']['address_name'];
                    // }
                    $addresses = $jobs->propertyAddress->address_name;
                }


        // 5:00 PM notification
        if ($currentTime->isBetween($currentTime->copy()->setTime(17, 0), $currentTime->copy()->setTime(17, 1))) {
            foreach ($unconfirmedJobs as $job) {
                if (!$this->hasNotificationBeenSent($job->id, $job->worker->id, WorkerMetaEnum::NOTIFICATION_SENT_5_PM)) {
                    // Send the notification
                    event(new WhatsappNotificationEvent([
                        "type" => WhatsappMessageTemplateEnum::REMIND_WORKER_TO_JOB_CONFIRM,
                        "notificationData" => [
                            'job' => $job,
                            'time' => "5PM"
                        ]
                    ]));

                    // Mark the notification as sent in WorkerMetas
                    $this->markNotificationAsSent($job->id, $job->worker->id, WorkerMetaEnum::NOTIFICATION_SENT_5_PM);
                }
            }
        }

        // 5:30 PM notification
        // if ($currentTime->isBetween($currentTime->copy()->setTime(17, 30), $currentTime->copy()->setTime(17, 31))) {
        //     foreach ($unconfirmedJobs as $job) {
        //         if (!$this->hasNotificationBeenSent($job->worker->id, WorkerMetaEnum::NOTIFICATION_SENT_5_30PM)) {
        //             // Send the notification
        //             event(new WhatsappNotificationEvent([
        //                 "type" => WhatsappMessageTemplateEnum::REMIND_WORKER_TO_JOB_CONFIRM,
        //                 "notificationData" => [
        //                     'job' => $job,
        //                 ]
        //             ]));

        //             // Mark the notification as sent in WorkerMetas
        //             $this->markNotificationAsSent($job->worker->id, WorkerMetaEnum::NOTIFICATION_SENT_5_30PM);
        //         }
        //     }
        // }

        // 6:00 PM notification to the team
        if ($currentTime->isBetween($currentTime->copy()->setTime(18, 0), $currentTime->copy()->setTime(18, 1))) {
            foreach ($unconfirmedJobs as $job) {

                if (!$this->hasNotificationBeenSent($job->id, $job->worker->id, WorkerMetaEnum::NOTIFICATION_SENT_6PM)) {
                    // Send the notification
                    event(new WhatsappNotificationEvent([
                        "type" => WhatsappMessageTemplateEnum::REMIND_WORKER_TO_JOB_CONFIRM,
                        "notificationData" => [
                            'job' => $job,
                            'time' => "6PM"
                        ]
                    ]));

                    // Mark the notification as sent in WorkerMetas
                    $this->markNotificationAsSent($job->id, $job->worker->id, WorkerMetaEnum::NOTIFICATION_SENT_6PM);

                     // Send the final notification to the team
                    event(new WhatsappNotificationEvent([
                        "type" => WhatsappMessageTemplateEnum::TO_TEAM_WORKER_NOT_CONFIRM_JOB,
                        "notificationData" => [
                            'job' => $job,
                        ]
                    ]));
                }
               
            }
        }

        return 0;
    }

    /**
     * Check if the notification has already been sent to the worker.
     *
     * @param int $workerId
     * @param int $jobId
     * @param string $notificationKey
     * @return bool
     */
    private function hasNotificationBeenSent(int $jobId, int $workerId, string $notificationKey): bool
    {
        return WorkerMetas::where('job_id', $jobId)
            ->where('key', $notificationKey)
            ->exists();
    }

    /**
     * Mark the notification as sent in the WorkerMetas table.
     *
     * @param int $workerId
     * @param int $jobId
     * @param string $notificationKey
     */
    private function markNotificationAsSent(int $jobId, int $workerId, string $notificationKey): void
    {
        WorkerMetas::create([
            'worker_id' => $workerId,
            'job_id' => $jobId,
            'key' => $notificationKey,
            'value' => Carbon::now(),
        ]);
    }
}
