<?php

namespace App\Console\Commands;

use App\Enums\JobStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\JobNotificationToWorker;
use App\Events\WhatsappNotificationEvent;
use App\Models\Job;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class JobNotFinishOnTimeNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:job-not-finished-on-time';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Job reminder, if job not finished on time';

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
        $currentTime = Carbon::now()->toTimeString();

        $jobs = Job::query()
            ->with(['worker', 'client', 'jobservice', 'propertyAddress'])
            ->whereNotNull('worker_id')
            ->whereHas('worker')
            ->whereDate('start_date', $currentDate)
            ->where('end_time', '<=', $currentTime)
            ->whereNotNull('job_opening_timestamp')
            ->whereNotIn('status', [JobStatusEnum::COMPLETED, JobStatusEnum::CANCEL])
            ->get();

        foreach ($jobs as $key => $job) {
            //send notification to worker
            $jobArray = $job->toArray();
            $worker = $jobArray['worker'];
            App::setLocale($worker['lng']);

            $emailData = [
                'emailSubject'  => __('mail.job_common.worker_job_reminder_subject'),
                'emailTitle'  => __('mail.job_common.job_status'),
                'emailContent'  => __('mail.job_common.worker_job_not_started'),
            ];
            event(new JobNotificationToWorker($worker, $jobArray, $emailData));

            Notification::create([
                'user_id' => $worker['id'],
                'user_type' => get_class($job->worker),
                'type' => NotificationTypeEnum::WORKER_NOT_FINISHED_JOB_ON_TIME,
                'status' => 'not-finished',
                'job_id' => $jobArray['id']
            ]);

            // notify admin
            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::WORKER_NOT_FINISHED_JOB_ON_TIME,
                "notificationData" => array(
                    'job' => $jobArray,
                    'content' => 'Worker has not finished the job on time.'
                )
            ]));
        }

        return 0;
    }
}
