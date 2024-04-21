<?php

namespace App\Console\Commands;

use App\Enums\JobStatusEnum;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Job;
use App\Models\JobWorkerShift;
use App\Traits\JobSchedule;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Helpers\Helper;

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
        $startDate = Carbon::today()->addDays(2)->format('Y-m-d');
        $endDate = Carbon::today()->addDays(3)->format('Y-m-d');

        $jobs = Job::query()
            ->whereDate('next_start_date', '>=', $startDate)
            ->whereDate('next_start_date', '<=', $endDate)
            ->where('is_one_time_job', false)
            ->where('is_next_job_created', false)
            ->where(function ($q) {
                $q->whereNull('cancelled_for')
                    ->orWhere('cancelled_for', '!=', 'forever');
            })
            ->orderBy('start_date', 'asc')
            ->get();

        try {
            foreach ($jobs as $key => $job) {
                $job_date = Carbon::parse($job->next_start_date);
                $preferredWeekDay = $job->jobservice->config['preferred_weekday'];
                $next_job_date = $this->scheduleNextJobDate($job_date, $job->schedule, $preferredWeekDay);

                if (
                    $job->cancelled_for == 'until_date' &&
                    (Carbon::parse($job->cancel_until_date)->isToday() ||
                        Carbon::parse($job->cancel_until_date)->isFuture())
                ) {
                    $job->update([
                        'next_start_date'   => $next_job_date,
                    ]);

                    continue;
                }

                $job_date = $job_date->toDateString();

                $previous_worker_id = $job->previous_worker_id;
                $previous_worker_after = $job->previous_worker_after;
                if ($job->previous_worker_id) {

                    if ($job->previous_worker_after) {

                        if (Carbon::parse($job->previous_worker_after)->isFuture()) {
                            $workerId = $job->worker_id;
                        } else {
                            $workerId = $job->previous_worker_id;
                            $previous_worker_id = NULL;
                            $previous_worker_after = NULL;
                        }
                    } else {
                        $workerId = $job->worker_id;
                        $previous_worker_id = NULL;
                        $previous_worker_after = NULL;
                    }
                } else {
                    $workerId = $job->worker_id;
                }

                $workerId = $job->keep_prev_worker ? $workerId : NULL;

                if ($workerId) {
                    $status = JobStatusEnum::SCHEDULED;
                    if (
                        Job::where('start_date', $job_date)
                        ->where('worker_id', $workerId)
                        ->exists()
                    ) {
                        $status = JobStatusEnum::UNSCHEDULED;
                    }
                } else {
                    $status = JobStatusEnum::UNSCHEDULED;
                }

                $previous_shifts = $job->previous_shifts;
                $previous_shifts_after = $job->previous_shifts_after;
                if ($job->previous_shifts) {

                    if ($job->previous_shifts_after) {

                        if (Carbon::parse($job->previous_shifts_after)->isFuture()) {
                            $job_shifts = $job->shifts;
                        } else {
                            $job_shifts = $job->previous_shifts;
                            $previous_shifts = NULL;
                            $previous_shifts_after = NULL;
                        }
                    } else {
                        $job_shifts = $job->shifts;
                        $previous_shifts = NULL;
                        $previous_shifts_after = NULL;
                    }
                } else {
                    $job_shifts = $job->shifts;
                }

                $slots = explode(',', $job_shifts);
                // sort slots in ascending order of time before merging for continuous time
                sort($slots);

                foreach ($slots as $key => $shift) {
                    $timing = explode('-', $shift);

                    $start_time = Carbon::createFromFormat('H:i', $timing[0])->toTimeString();
                    $end_time = Carbon::createFromFormat('H:i', $timing[1])->toTimeString();

                    $shiftFormattedArr[$key] = [
                        'starting_at' => Carbon::parse($job_date . ' ' . $start_time)->toDateTimeString(),
                        'ending_at' => Carbon::parse($job_date . ' ' . $end_time)->toDateTimeString()
                    ];
                }

                $mergedContinuousTime = $this->mergeContinuousTimes($shiftFormattedArr);

                $slotsInString = '';
                foreach ($mergedContinuousTime as $key => $slot) {
                    if (!empty($slotsInString)) {
                        $slotsInString .= ',';
                    }
                    $slotsInString .= Carbon::parse($slot['starting_at'])->format('H:i') . '-' . Carbon::parse($slot['ending_at'])->format('H:i');
                }

                $minutes = 0;
                foreach ($mergedContinuousTime as $key => $value) {
                    $minutes += Carbon::parse($value['ending_at'])->diffInMinutes(Carbon::parse($value['starting_at']));
                }

                $nextJob = Job::create([
                    'worker_id'     => $workerId,
                    'client_id'     => $job->client_id,
                    'contract_id'   => $job->contract_id,
                    'offer_id'      => $job->offer_id,
                    'start_date'    => $job_date,
                    'shifts'        => $slotsInString,
                    'schedule'      => $job->schedule,
                    'schedule_id'   => $job->schedule_id,
                    'status'        => $status,
                    'total_amount'  => $job->total_amount,
                    'next_start_date'   => $next_job_date,
                    'address_id'        => $job->address_id,
                    'keep_prev_worker'  => $job->keep_prev_worker,
                    'is_one_time_job'   => $job->is_one_time_job,
                    'original_worker_id'    => $job->original_worker_id,
                    'original_shifts'       => $job->original_shifts,
                    'previous_worker_id'    => $previous_worker_id,
                    'previous_worker_after' => $previous_worker_after,
                    'previous_shifts'       => $previous_shifts,
                    'previous_shifts_after' => $previous_shifts_after,
                ]);

                $nextJobService = $job->jobservice->replicate()->fill([
                    'job_id' => $nextJob->id,
                    'duration_minutes'  => $minutes,
                ]);
                $nextJobService->save();

                foreach ($mergedContinuousTime as $key => $shift) {
                    $nextJob->workerShifts()->create($shift);
                }

                $job->update([
                    'is_next_job_created' => true
                ]);

                $nextJob->load(['client', 'worker', 'jobservice', 'propertyAddress']);

                if ($nextJob->worker_id && !empty($nextJob['worker']['email'])) {
                    App::setLocale($nextJob->worker->lng);

                    $emailData = array(
                        'email' => $nextJob['worker']['email'],
                        'job' => $nextJob->toArray(),
                        'start_time' => $mergedContinuousTime[0]['starting_at'],
                        'content'  => __('mail.worker_new_job.new_job_assigned') . " " . __('mail.worker_new_job.please_check'),
                        'content_data'  => __('mail.worker_new_job.new_job_assigned'),
                    );
                    Helper::sendJobWANotification($emailData);
                    Mail::send('/Mails/NewJobMail', $emailData, function ($messages) use ($emailData) {
                        $messages->to($emailData['email']);
                        $sub = __('mail.worker_new_job.subject') . "  " . __('mail.worker_new_job.company');
                        $messages->subject($sub);
                    });
                }
            }
        } catch (Exception $e) {
            Log::error($e);
        }
    }
}
