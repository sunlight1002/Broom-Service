<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Models\WorkerMetas;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\JobStatusEnum;
use Illuminate\Support\Facades\DB;

class TeamNotifyAfter30MinsIfWorkerNotConfirm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'team:notify-team-if-worker-not-confirm-after-30-mins';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notification to Team if Worker Hasn’t Started Job Within 30 Minutes';

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
        $today = now()->format('Y-m-d');

        $jobs = Job::query()
            ->with(['worker', 'client', 'jobservice', 'propertyAddress', 'workerMetas'])
            ->whereHas('worker')
            // ->whereDoesntHave('workerMetas', function ($query) {
            //     $query->where('worker_id', DB::raw('jobs.worker_id'));
            //     $query->where('key', 'team_job_not_confirm_after_30_mins');
            // })
            ->whereDoesntHave('workerMetas', function ($query) {
                $query->whereColumn('job_id', 'jobs.id') // Match by job_id
                      ->whereColumn('worker_id', 'jobs.worker_id') // Match by worker_id
                      ->where('key', 'team_job_not_confirm_after_30_mins');
            })            
            ->whereNotNull('worker_approved_at')
            ->whereNotNull('start_time')
            ->whereDoesntHave('hours')
            ->whereDate('start_date', $today)
            ->whereNotIn('status', [JobStatusEnum::COMPLETED, JobStatusEnum::CANCEL])
            ->whereRaw("
                STR_TO_DATE(CONCAT(start_date, ' ', start_time), '%Y-%m-%d %H:%i:%s') <= ?
            ", [now()->subMinutes(30)])
            ->get();
        foreach ($jobs as $key => $job) {
            $worker = $job->worker;
            $client = $job->client;
            if ($worker) {
                App::setLocale($worker['lng'] ?? 'en');
                $notificationData = array(
                    'job'  => $job->toArray(),
                    'worker'  => $worker->toArray(),
                    'client'  => $client->toArray(),
                );

                Notification::create([
                    'user_id' => $worker->id,
                    'user_type' => get_class($worker),
                    'type' => NotificationTypeEnum::WORKER_NOT_LEFT_FOR_JOB,
                    'status' => 'not-left',
                    'job_id' => $job->id
                ]);

                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::TEAM_JOB_NOT_CONFIRM_AFTER_30_MINS,
                    "notificationData" => $notificationData
                ]));


                WorkerMetas::create([
                    'worker_id' => $worker->id,
                    'job_id' => $job->id,
                    'key' => 'team_job_not_confirm_after_30_mins',
                    'value' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
            }
        }

        return 0;
    }
}
