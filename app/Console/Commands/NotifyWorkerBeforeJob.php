<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Models\Job;
use Carbon\Carbon;

class NotifyWorkerBeforeJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifyBeforeJob';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify worker 1 hour and 30 minutes before job starts on the same day.';

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
        // Get the current date and time
        $currentTime = Carbon::now();

        \Log::info($currentTime);

        // Calculate time 1 hour and 30 minutes from now
        $oneHourLater = $currentTime->copy()->addHour();
        $thirtyMinutesLater = $currentTime->copy()->addMinutes(30);

        // Fetch jobs where worker has approved and hasn't tapped the "I am leaving" button,
        // and the job start time is exactly 1 hour or 30 minutes from now.
        $jobsToNotify = Job::with(['client', 'worker'])
            ->whereNotNull('worker_approved_at') // Only jobs where the worker has approved
            ->whereDate('start_date', $currentTime->toDateString()) // Only jobs for today
            ->whereTime('start_time', '=', $oneHourLater->toTimeString()) // Jobs starting in exactly 1 hour
            ->orWhereTime('start_time', '=', $thirtyMinutesLater->toTimeString()) // Jobs starting in 30 minutes
            ->get();

        \Log::info($jobsToNotify);

        foreach ($jobsToNotify as $job) {
            // Calculate the difference in minutes between now and the job's start time
            $jobStartTime = Carbon::parse($job->start_time);
            $minutesDifference = $currentTime->diffInMinutes($jobStartTime, false);

            // Check if it's 1 hour or 30 minutes before start_time
            if ($minutesDifference === 60) {
                \Log::info("Sending 1-hour notification to worker for Job ID: " . $job->id);
                $this->sendNotification($job, '1-hour');
            } elseif ($minutesDifference === 30) {
                \Log::info("Sending 30-minute notification to worker for Job ID: " . $job->id);
                $this->sendNotification($job, '30-min');
            }
        }

        return 0;
    }


    /**
     * Send notification to the worker.
     *
     * @param Job $job
     * @param string $notificationType
     * @return void
     */
    protected function sendNotification($job, $notificationType)
    {
        // Customize the message based on the notification type
        if ($notificationType === '1-hour') {
            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::WORKER_NOTIFY_BEFORE_ON_MY_WAY,
                "notificationData" => [
                    'job' => $job,
                ]
            ]));

        } elseif ($notificationType === '30-min') {

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::WORKER_NOTIFY_BEFORE_ON_MY_WAY,
                "notificationData" => [
                    'job' => $job,
                ]
            ]));

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::TEAM_NOTIFY_WORKER_BEFORE_ON_MY_WAY,
                "notificationData" => [
                    'job' => $job,
                ]
            ]));
        }

    }
}
