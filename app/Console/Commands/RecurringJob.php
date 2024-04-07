<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Job;
use App\Models\JobWorkerShift;
use App\Traits\JobSchedule;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class RecurringJob extends Command
{
    use JobSchedule;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job:recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Recurring Job';

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
        $dateTomorrow = Carbon::now()->addDay()->format('Y-m-d');
        $dateAfterTomorrow = Carbon::now()->addDays(2)->format('Y-m-d');
        $jobs = Job::query()
            ->whereDate('next_start_date', '>=', $dateTomorrow)
            ->whereDate('next_start_date', '<=', $dateAfterTomorrow)
            ->orderBy('start_date', 'asc')
            ->get();

        try {
            foreach ($jobs as $key => $job) {

                $jobAddedThroughCrons = Job::where(['client_id' => $job->client_id, 'contract_id' => $job->contract_id, 'start_date' => $job->next_start_date, 'shifts' => $job->shifts, 'status' => 'status'])->get()->count();

                if($jobAddedThroughCrons > 0)
                    continue;

                $job_date = Carbon::parse($job->next_start_date);
                $preferredWeekDay = strtolower($job_date->format('l'));
                $next_job_date = $this->scheduleNextJobDate($job_date, $job->schedule, $preferredWeekDay);

                $nextJob = $job->replicate()->fill([
                    'start_date' => $job_date,
                    'next_start_date' => $next_job_date,
                    'worker_id'  => $job->keep_prev_worker ? $job->worker_id : Null,
                ]);
                $nextJob->save();

                $nextJobService = $job->jobservice->replicate()->fill([
                    'job_id' => $nextJob->id,
                ]);
                $nextJobService->save();

                if($job->workerShifts()->exists())
                {
                    foreach ($job->workerShifts as $key => $workerShift) {

                        $timeStart = explode(" ", $workerShift->starting_at)[1];
                        $timeEnd = explode(" ", $workerShift->ending_at)[1];

                        if($key == 0) {
                            $shiftStart = Carbon::parse($workerShift->starting_at)->format('H:i');
                        }

                        $jobWorkerShift = JobWorkerShift::create([
                            'job_id' => $nextJob->id,
                            'starting_at' => $job_date->format('Y-m-d') . " " . $timeStart,
                            'ending_at' => $job_date->format('Y-m-d') . " " . $timeEnd
                        ]);
                    }

                    $nextJob->load(['client', 'worker', 'jobservice', 'propertyAddress']);

                    if ($nextJob->worker_id && !empty($nextJob['worker']['email'])) {
                        App::setLocale($nextJob->worker->lng);

                        $emailData = array(
                            'email' => $nextJob['worker']['email'],
                            'job' => $nextJob->toArray(),
                            'start_time' => $shiftStart ?? "",
                            'content'  => __('mail.worker_new_job.new_job_assigned') . " " . __('mail.worker_new_job.please_check'),
                        );

                        Mail::send('/Mails/NewJobMail', $emailData, function ($messages) use ($emailData) {
                            $messages->to($emailData['email']);
                            $sub = __('mail.worker_new_job.subject') . "  " . __('mail.worker_new_job.company');
                            $messages->subject($sub);
                        });
                    }
                }
            }
        } catch (Exception $e) {
            \Log::error($e);
        }
    }
}
