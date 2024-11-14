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
use App\Enums\JobStatusEnum;
use Illuminate\Support\Facades\DB;

class WorkerNotifyBefore1HourOfJobStart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worker:notify-worker-confirm-on-your-way-before-1-hour';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reminder to Worker 1 Hour Before Job Start';

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
            ->with(['worker', 'client', 'jobservice', 'propertyAddress', 'workerMetas'])
            ->whereHas('worker')
            ->whereDoesntHave('workerMetas', function ($query) {
                $query->where('worker_id', DB::raw('jobs.worker_id'));
                $query->where('key', 'reminder_to_worker_1_hour_before_job_start');
            })
            ->whereNotNull('worker_approved_at')
            ->whereNotNull('start_time')
            ->whereNull('job_opening_timestamp')
            ->whereRaw("
                STR_TO_DATE(CONCAT(start_date, ' ', start_time), '%Y-%m-%d %H:%i:%s') BETWEEN ? AND ?
            ", [now(), now()->addHour()])
            ->whereNotIn('status', [JobStatusEnum::COMPLETED, JobStatusEnum::CANCEL])
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
                if (isset($notificationData['job']['worker']) && !empty($notificationData['job']['worker']['phone'])) {
                    event(new WhatsappNotificationEvent([
                        "type" => WhatsappMessageTemplateEnum::REMINDER_TO_WORKER_1_HOUR_BEFORE_JOB_START,
                        "notificationData" => $notificationData
                    ]));
                }

                WorkerMetas::create([
                    'worker_id' => $worker->id,
                    'job_id' => $job->id,
                    'key' => 'reminder_to_worker_1_hour_before_job_start',
                    'value' => '1',
                ]);
            }
        }

        return 0;
    }
}
