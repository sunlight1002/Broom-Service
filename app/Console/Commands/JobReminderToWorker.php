<?php

namespace App\Console\Commands;

use App\Events\JobNotificationToWorker;
use App\Models\Job;
use Carbon\Carbon;
use Illuminate\Console\Command;

class JobReminderToWorker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worker:job-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Job reminder to worker if did not click on start job';

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
        // $currentDateTime = Carbon::now()->toDateTimeString();
        // $currentDateTimeAfter = Carbon::now()->addMinutes(15)->toDateTimeString();
        $currentDate = Carbon::now()->toDateString();

        $jobs = Job::query()
            ->whereNotNull('worker_id')
            ->whereHas('worker')
            ->whereNull('worker_approved_at')
            ->whereNotNull('start_date')
            ->whereDate('start_date', $currentDate)
            ->with(['worker', 'client', 'jobservice', 'propertyAddress'])
            ->get();

        foreach ($jobs as $key => $job) {
            //send notification to worker
            $job = $job->toArray();
            $worker = $job['worker'];
            $emailData = [
                'emailSubject'  => __('mail.job_common.worker_job_reminder_subject'),
                'emailTitle'  => __('mail.job_common.job_status'),
                'emailContent'  => __('mail.job_common.worker_job_reminder_content'),
            ];
            event(new JobNotificationToWorker($worker, $job, $emailData));
        }

        return 0;
    }
}
