<?php

namespace App\Console\Commands;

use App\Events\JobNotificationToWorker; // Adjust as necessary
use App\Models\Job;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use Illuminate\Console\Command;

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
        // Get current time
        $currentTime = now();
        \Log::info($currentTime);

        // Get unconfirmed jobs where the current time is 5:00 PM or later
        $unconfirmedJobs = Job::with(['client','worker'])->where('worker_approved_at', null)
            ->whereTime('start_date', '<=', $currentTime) // Ensure the job has started
            ->get();

        // Check the current time and send appropriate notifications
        if ($currentTime->isBetween($currentTime->copy()->setTime(17, 0), $currentTime->copy()->setTime(17, 1))) {
            // 5:00 PM notification
            foreach ($unconfirmedJobs as $job) {
                $emailData = [
                    'emailSubject' => 'Reminder: Confirm your job by 5 PM!',
                    'emailTitle' => '5 PM Job Confirmation Reminder',
                    'emailContentWa' => 'This is your friendly reminder to confirm your job before 5 PM.',
                ];

                // Send notification
                event(new JobNotificationToWorker($job->worker, $job, $emailData));
                \Log::info("sending");
            }
        } elseif ($currentTime->isBetween($currentTime->copy()->setTime(17, 30), $currentTime->copy()->setTime(17, 31))) {
            // 5:30 PM notification
            foreach ($unconfirmedJobs as $job) {
                $emailData = [
                    'emailSubject' => 'Last Call: Confirm your job by 6 PM!',
                    'emailTitle' => '5:30 PM Job Confirmation Reminder',
                    'emailContentWa' => 'You have 30 minutes left to confirm your job! Please confirm before 6 PM.',
                ];

                // Send notification
                event(new JobNotificationToWorker($job->worker, $job, $emailData));
            }
        } elseif ($currentTime->isBetween($currentTime->copy()->setTime(18, 0), $currentTime->copy()->setTime(18, 1))) {
            // 6:00 PM notification
            foreach ($unconfirmedJobs as $job) {
                $client = $job->client;
                $worker = $job->worker;

                $emailData = [
                    'emailSubject' => 'Final Reminder: Confirm your job now!',
                    'emailTitle' => '6 PM Job Confirmation Reminder',
                    'emailContentWa' => 'This is your final reminder to confirm your job. Please confirm immediately.',
                ];

                // Send notification
                // event(new JobNotificationToWorker($job->worker, $job, $emailData));

                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::JOB_APPROVED_NOTIFICATION_TO_TEAM,
                    "notificationData" => [
                        'job' => $job,
                        'client' => $client,
                        'worker' => $worker,
                    ]
                ]));
            }
        }

        return 0;
    }
}
