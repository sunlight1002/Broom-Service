<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Models\WorkerMetas;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Enums\JobStatusEnum;
use Illuminate\Support\Facades\DB;

class WorkerNotifyNextDayJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worker:notify-next-day-job-at-5-pm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify worker about next day job at 5 PM';

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

        // Get all jobs for tomorrow where workers haven't been notified
        $jobs = Job::query()
            ->with(['worker', 'client', 'jobservice', 'propertyAddress', 'workerMetas'])
            ->whereIn('worker_id', ['209', '185', '67'])
            ->whereNotNull('worker_id')
            ->whereHas('worker')
            ->whereDoesntHave('workerMetas', function ($query) {
                $query->whereColumn('job_id', 'jobs.id')
                    ->whereColumn('worker_id', 'jobs.worker_id')
                    ->where('key', 'next_day_job_reminder_at_5_pm');
            })
            ->whereNull('worker_approved_at')
            ->whereNotIn('status', [JobStatusEnum::COMPLETED, JobStatusEnum::CANCEL])
            ->whereDate('start_date', $tomorrow)
            ->orderBy('start_time') // gets the earliest job for that day
            ->get();

        // Group jobs by worker_id and get the first job per worker
        $uniqueJobs = $jobs->groupBy('worker_id')->map->first();

        foreach ($uniqueJobs as $job) {
            $worker = $job->worker;
            $client = $job->client;

            if ($worker) {
                App::setLocale($worker->lng ?? 'en');

                $notificationData = [
                    'job'    => $job->toArray(),
                    'worker' => $worker->toArray(),
                    'client' => $client->toArray(),
                ];

                if (!empty($worker->phone)) {
                    event(new WhatsappNotificationEvent([
                        "type" => WhatsappMessageTemplateEnum::WORKER_NEXT_DAY_JOB_REMINDER_AT_5_PM,
                        "notificationData" => $notificationData
                    ]));
                }

                WorkerMetas::create([
                    'worker_id' => $worker->id,
                    'job_id' => $job->id,
                    'key' => 'next_day_job_reminder_at_5_pm',
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
