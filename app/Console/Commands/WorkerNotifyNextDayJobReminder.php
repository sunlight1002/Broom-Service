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

class WorkerNotifyNextDayJobReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worker:notify-next-day-job-at-6-pm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify worker about next day job at 6 PM';

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
        $tomorrow = Carbon::tomorrow()->toDateString();

        $jobs = Job::query()
            ->with(['worker', 'client', 'jobservice', 'propertyAddress', 'workerMetas'])
            ->whereIn('worker_id', ['209','185', '67'])
            ->whereNotNull('worker_id')
            ->whereHas('worker')
            // ->whereDoesntHave('workerMetas', function ($query) {
            //     $query->where('worker_id', DB::raw('jobs.worker_id'));
            //     $query->where('key', 'next_day_job_reminder_at_6_pm');
            // })
            ->whereDoesntHave('workerMetas', function ($query) {
                $query->whereColumn('job_id', 'jobs.id') // Match by job_id
                    ->whereColumn('worker_id', 'jobs.worker_id') 
                    ->where('key', 'next_day_job_reminder_at_6_pm');
            })
            ->whereNull('worker_approved_at')
            ->whereNotIn('status', [JobStatusEnum::COMPLETED, JobStatusEnum::CANCEL])
            ->whereDate('start_date', $tomorrow)
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
                        "type" => WhatsappMessageTemplateEnum::WORKER_NEXT_DAY_JOB_REMINDER_AT_6_PM,
                        "notificationData" => $notificationData
                    ]));
                }

                Notification::create([
                    'user_id' => $worker->id,
                    'user_type' => get_class($worker),
                    'type' => NotificationTypeEnum::WORKER_NOT_APPROVED_JOB,
                    'status' => 'not-approved',
                    'job_id' => $job->id
                ]);

                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::TEAM_JOB_NOT_APPROVE_REMINDER_AT_6_PM,
                    "notificationData" => $notificationData
                ]));


                WorkerMetas::create([
                    'worker_id' => $worker->id,
                    'job_id' => $job->id,
                    'key' => 'next_day_job_reminder_at_6_pm',
                    'value' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);

                $job->update([
                    'is_worker_reminded' => true
                ]);
            }
        }

        return 0;
    }
}
