<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Enums\WorkerMetaEnum;
use App\Models\Job;
use App\Models\WorkerMetas;
use Carbon\Carbon;

class NotifyWorkerBeforeJob extends Command
{
    protected $signature = 'notifyBeforeJob';
    protected $description = 'Notify worker 1 hour and 30 minutes before job starts on the same day.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $currentTime = Carbon::now();
        $oneHourLater = $currentTime->copy()->addHour();
        $thirtyMinutesLater = $currentTime->copy()->addMinutes(30);

        $jobsToNotify = Job::with(['client', 'worker'])
            ->whereNotNull('worker_approved_at')
            ->whereDate('start_date', $currentTime->toDateString())
            ->whereTime('start_time', '=', $oneHourLater->format('H:i'))
            ->orWhereTime('start_time', '=', $thirtyMinutesLater->format('H:i'))
            ->get();


        foreach ($jobsToNotify as $job) {
            $jobStartTime = Carbon::parse($job->start_time);
            $minutesDifference = $currentTime->diffInMinutes($jobStartTime, false);

            if ($minutesDifference === 59) {
                $this->sendNotification($job, WorkerMetaEnum::NOTIFICATION_SENT_1HOUR_BEFORE_JOB_STARTS, 'worker');
            } elseif ($minutesDifference === 29) {
                $this->sendNotification($job, WorkerMetaEnum::NOTIFICATION_SENT_30MIN_BEFORE_JOB_STARTS, 'worker');
                $this->sendNotification($job, WorkerMetaEnum::NOTIFICATION_SENT_30MIN_BEFORE_JOB_STARTS, 'team');
            }
        }

        return 0;
    }

    protected function sendNotification($job, string $notificationType, string $recipient)
    {
        $currentDate = Carbon::now()->toDateString();

        // Check if notification has already been sent for this job
        $existingNotification = WorkerMetas::where([
            'job_id' => $job->id,
            'key' => $notificationType . '_' . $recipient, 
        ])->first();

        if ($existingNotification) {
            return; 
        }

        // Determine the event type based on the recipient and notification type
        $eventType = match ($recipient) {
            'worker' => WhatsappMessageTemplateEnum::WORKER_NOTIFY_BEFORE_ON_MY_WAY,
            'team' => WhatsappMessageTemplateEnum::TEAM_NOTIFY_WORKER_BEFORE_ON_MY_WAY,
            default => null,
        };

        // Only send the notification if the event type is defined
        if ($eventType) {
            event(new WhatsappNotificationEvent([
                "type" => $eventType,
                "notificationData" => [
                    'job' => $job,
                ]
            ]));
        }

        // Record that the notification was sent
        WorkerMetas::create([
            'worker_id' => $job->worker_id,
            'job_id' => $job->id,
            'key' => $notificationType . '_' . $recipient,
            'value' => Carbon::now(),
        ]);
    }
}
