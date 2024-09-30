<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\JobNotificationToWorker;
use App\Events\AdminNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\WhatsappNotificationEvent;
use App\Models\Job;
use Carbon\Carbon;

class NotifyStartOfTheJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifyStartOfJob';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify workers and admins if job has not been started after scheduled time.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $currentTime = Carbon::now();
        $admin_notified = false;
        \Log::info($currentTime);

        // Get jobs that were scheduled for today and not completed or started
        $jobsToNotify = Job::with(['client', 'worker', 'hours'])
            ->whereNotNull('worker_approved_at') // Only jobs where the worker has approved
            ->whereNotNull('job_opening_timestamp') // Exclude completed jobs
            ->whereDoesntHave('hours') // Jobs that don't have hours recorded (e.g., not started)
            ->whereDate('start_date', $currentTime->toDateString()) // Only jobs for today
            ->get();

        foreach ($jobsToNotify as $job) {
            $startTime = Carbon::parse($job->start_time);
            \Log::info($startTime."start");

            // If 30 minutes have passed since the scheduled start time and job hasn't been marked as started
            if ($currentTime->diffInMinutes($startTime) >= 30 && !$job->is_job_done && !$job->worker_notified) {
                $this->notifyWorkerToStart($job);
                $job->worker_notified = true;
                $job->save();
            }
            // If 1 hour has passed since the scheduled start time and job hasn't been marked as started
            if ($currentTime->diffInMinutes($startTime) >= 60 && !$job->is_job_done && !$admin_notified) {
                $this->notifyAdmin($job);
                $admin_notified = true;
            }
        }

        return 0;
    }

    /**
     * Send a notification to the worker to start the job or contact the manager.
     *
     * @param Job $job
     */
    protected function notifyWorkerToStart($job)
    {
        $worker = $job->worker;

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::WORKER_NOTIFY_AFTER_ON_MY_WAY,
            "notificationData" => [
                'job' => $job,
                'worker' => $worker,
            ]
        ]));
    }

    /**
     * Send a notification to the admin with action options.
     *
     * @param Job $job
     */
    protected function notifyAdmin($job)
    {
        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::TEAM_NOTIFY_WORKER_AFTER_ON_MY_WAY,
            "notificationData" => [
                'job' => $job,
            ]
        ]));
    }
}