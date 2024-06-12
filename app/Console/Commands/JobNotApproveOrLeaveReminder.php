<?php

namespace App\Console\Commands;

use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\JobNotificationToWorker;
use App\Events\WhatsappNotificationEvent;
use App\Models\Job;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class JobNotApproveOrLeaveReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:job-not-approve-or-leave';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Job reminder, if not approved or leave for job';

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
        $currentDate = Carbon::now()->toDateString();
        $currentTime = Carbon::now()->addMinutes(15)->toTimeString();

        $jobs = Job::query()
            ->with(['worker', 'client', 'jobservice', 'propertyAddress'])
            ->whereNotNull('worker_id')
            ->whereHas('worker')
            ->whereDate('start_date', $currentDate)
            ->where('start_time', '<=', $currentTime)
            ->where(function ($q) {
                $q->whereNull('worker_approved_at')
                    ->orWhereNull('job_opening_timestamp');
            })
            ->get();

        foreach ($jobs as $key => $job) {
            if (!$job->worker_approved_at) {
                //send notification to worker
                $jobArray = $job->toArray();
                $worker = $jobArray['worker'];
                App::setLocale($worker['lng']);

                $emailData = [
                    'emailSubject'  => __('mail.job_common.worker_job_reminder_subject'),
                    'emailTitle'  => __('mail.job_common.job_status'),
                    'emailContent'  => __('mail.job_common.worker_job_reminder_content'),
                ];
                event(new JobNotificationToWorker($worker, $jobArray, $emailData));

                Notification::create([
                    'user_id' => $worker['id'],
                    'user_type' => get_class($job->worker),
                    'type' => NotificationTypeEnum::WORKER_NOT_APPROVED_JOB,
                    'status' => 'not-approved',
                    'job_id' => $jobArray['id']
                ]);

                // notify admin
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::WORKER_NOT_APPROVED_JOB,
                    "notificationData" => array(
                        'job' => $jobArray,
                        'content' => 'Worker has not approved the job yet.'
                    )
                ]));
            } else if (!$job->job_opening_timestamp) {
                //send notification to worker
                $jobArray = $job->toArray();
                $worker = $jobArray['worker'];
                App::setLocale($worker['lng']);

                $emailData = [
                    'emailSubject'  => __('mail.job_common.worker_job_reminder_subject'),
                    'emailTitle'  => __('mail.job_common.job_status'),
                    'emailContent'  => __('mail.job_common.worker_job_reminder_content'),
                ];
                event(new JobNotificationToWorker($worker, $jobArray, $emailData));

                Notification::create([
                    'user_id' => $worker['id'],
                    'user_type' => get_class($job->worker),
                    'type' => NotificationTypeEnum::WORKER_NOT_LEFT_FOR_JOB,
                    'status' => 'not-left',
                    'job_id' => $jobArray['id']
                ]);

                // notify admin
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::WORKER_NOT_LEFT_FOR_JOB,
                    "notificationData" => array(
                        'job' => $jobArray,
                        'content' => 'Worker has not left for job yet.'
                    )
                ]));
            }
        }

        return 0;
    }
}
