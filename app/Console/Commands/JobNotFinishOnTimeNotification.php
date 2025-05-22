<?php

namespace App\Console\Commands;

use App\Enums\JobStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\WhatsappNotificationEvent;
use App\Models\Job;
use App\Models\Notification;
use App\Models\WorkerMetas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class JobNotFinishOnTimeNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worker:job-not-finished-on-time';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notification to Worker (sent 1 minute after scheduled job completion time)';

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
        $jobs = Job::query()
            ->with(['worker', 'client', 'jobservice', 'propertyAddress'])
            ->whereIn('worker_id', ['209','185', '67'])
            ->whereNotNull('worker_id')
            ->whereHas('worker')
            ->whereDoesntHave('workerMetas', function ($query) {
                $query->whereColumn('job_id', 'jobs.id') // Match by job_id
                      ->whereColumn('worker_id', 'jobs.worker_id') 
                      ->where('key', 'worker_notify_on_job_time_over');
            })
            ->whereDate('start_date', now())
            ->whereRaw("STR_TO_DATE(end_time, '%H:%i:%s') > ?", [now()->format('H:i:s')])
            ->whereNotNull('job_opening_timestamp')
            ->whereNotIn('status', [JobStatusEnum::COMPLETED, JobStatusEnum::CANCEL])
            ->get();

        foreach ($jobs as $key => $job) {
            //send notification to worker
            $jobArray = $job->toArray();
            $worker = $jobArray['worker'];
            App::setLocale($worker['lng']);

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::WORKER_NOTIFY_ON_JOB_TIME_OVER,
                "notificationData" => array(
                    'job' => $jobArray,
                    'client' => $job->client->toArray(),
                    'worker' => $job->worker->toArray(),
                )
            ]));

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
                    'client' => $job->client->toArray(),
                    'worker' => $job->worker->toArray(),
                )
            ]));

            WorkerMetas::create([
                'worker_id' => $worker['id'],
                'job_id' => $job->id,
                'key' => 'worker_notify_on_job_time_over',
                'value' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        }

        return 0;
    }
}
