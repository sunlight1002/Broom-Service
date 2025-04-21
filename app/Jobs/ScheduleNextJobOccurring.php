<?php

namespace App\Jobs;

use App\Enums\JobStatusEnum;
use App\Enums\SettingKeyEnum;
use App\Models\Job;
use App\Models\ManageTime;
use App\Models\Client;
use App\Models\User;
use App\Models\Contract;
use App\Models\ClientPropertyAddress;
use App\Models\Offer;
use App\Models\Services;
use App\Models\ServiceSchedule;
use App\Models\JobHours;
use App\Models\JobService;
use App\Models\Conflict;
use App\Models\Setting;
use App\Models\Notification;
use App\Traits\JobSchedule;
use App\Traits\GoogleAPI;
use App\Traits\PriceOffered;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\JobNotificationToAdmin;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Enums\NotificationTypeEnum;
use App\Jobs\SendUninterestedClientEmail;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Http;
use App\Jobs\SyncGoogleSheetAddJobOccurring;
use Illuminate\Support\Str;



class ScheduleNextJobOccurring implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, JobSchedule, PriceOffered, GoogleAPI;

    protected $jobID;
    protected $startDate;
    protected $spreadsheetId;
    protected $googleAccessToken;
    protected $googleRefreshToken;
    protected $googleSheetEndpoint = 'https://sheets.googleapis.com/v4/spreadsheets/';
    protected $sheetName = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($jobID, $startDate, $sheetName = null)
    {
        $this->jobID = $jobID;
        $this->startDate = $startDate;
        $this->sheetName = $sheetName;

    }

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $i = 0;

        $job = Job::query()
            ->with('client')
            ->where('schedule', '!=', 'na')
            // ->where('is_next_job_created', false)
            ->where(function ($q) {
                $q->whereNull('cancelled_for')
                    ->orWhere('cancelled_for', '!=', 'forever');
            })
            ->find($this->jobID);

        $manageTime = ManageTime::first();
        $workingWeekDays = json_decode($manageTime->days);

        try {
            if ($job) {
                $client = $job->client;
                $offer_service = $job->offer_service;

                $offerServices = $this->formatServices($offer_service, false);

                // $filtered = Arr::where($offerServices, function ($value, $key) use ($job) {
                //     return $value['service'] == $job->schedule_id;
                // });

                // $selectedService = head($filtered);

                $selectedService = $offerServices;


                $preferredWeekDay = $job->jobservice->config['preferred_weekday'];

                if ($job->cancelled_for == 'until_date') {
                    $sixMonthsFromNow = Carbon::parse($job->cancel_until_date)->addMonths(2);
                }else{
                    $sixMonthsFromNow = Carbon::now()->addMonths(2);  // Calculate date 6 months from now
                }


                // Check if startDate is provided
                if ($this->startDate) {
                    $job_start_date = Carbon::parse($this->startDate);
                    \Log::info("Job Start Date: " . $job_start_date);

                    // Run scheduleNextJob only once with startDate
                    $nextJobDate =  $this->scheduleNextJob($job_start_date, $selectedService, $client, $job, $preferredWeekDay, $workingWeekDays, $i);

                } else {
                    if ($job->cancelled_for == 'until_date') {
                        $job_start_date = Carbon::parse($job->cancel_until_date)->format('Y-m-d');
                    }else{
                        $job_start_date = Carbon::parse($job->start_date);
                    }

                    do {
                        $nextJobDate = $this->scheduleNextJob($job_start_date, $selectedService, $client, $job, $preferredWeekDay, $workingWeekDays, $i);

                        // If the next job date is after 2 months, stop the scheduling
                        if (Carbon::parse($nextJobDate)->gt($sixMonthsFromNow)) {
                            break;
                        }

                        // Update job start date for the next iteration
                        $job_start_date = Carbon::parse($nextJobDate);
                    } while (true);
                }//Keep iterating until the condition is met
            }else {
                \Log::warning("Job not found for ID: {$this->jobID}");
            }
        }  catch (Exception $e) {
            \Log::error("Error occurred in ScheduleNextJobOccurring: " . $e);
        }
    }


    protected function scheduleNextJob($job_date, $selectedService,  $client, $job, $preferredWeekDay, $workingWeekDays, &$i){
            $next_job_date = $this->scheduleNextJobDate($job_date, $job->schedule, $preferredWeekDay, $workingWeekDays);

            // if ($job->cancelled_for == 'until_date') {
            //     $carbon_next_job_date = Carbon::parse($next_job_date);
            //     while (Carbon::parse($job->cancel_until_date)->gte($carbon_next_job_date)) {
            //         $next_job_date = $this->scheduleNextJobDate($carbon_next_job_date, $job->schedule, $preferredWeekDay, $workingWeekDays);

            //         $carbon_next_job_date = Carbon::parse($next_job_date);
            //     }
            // }
            $next_to_next_job_date = $this->scheduleNextJobDate(Carbon::parse($next_job_date), $job->schedule, $preferredWeekDay, $workingWeekDays);
            // $job->update([
            //     'next_start_date' => $next_job_date,
            // ]);

            $job_date = Carbon::parse($job_date)->toDateString();

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

            $shiftFormattedArr = [];
            foreach ($slots as $key => $shift) {
                $timing = explode('-', $shift);

                $start_time = Carbon::createFromFormat('H:i', $timing[0])->toTimeString();
                $end_time = Carbon::createFromFormat('H:i', $timing[1])->toTimeString();

                $shiftFormattedArr[$key] = [
                    'starting_at' => Carbon::parse($next_job_date . ' ' . $start_time)->toDateTimeString(),
                    'ending_at' => Carbon::parse($next_job_date . ' ' . $end_time)->toDateTimeString()
                ];
            }
            $mergedContinuousTime = $this->mergeContinuousTimes($shiftFormattedArr);

            $conflictClientId = NULL;
            $conflictJobId = NULL;
            if ($workerId) {
                $status = JobStatusEnum::SCHEDULED;
                $conflictCheck = $this->isJobTimeConflicting($mergedContinuousTime, $job_date, $workerId);

                if ($conflictCheck['is_conflicting']) {
                    $status = JobStatusEnum::UNSCHEDULED;
                    $conflictClientId = $conflictCheck['conflict_client_id']; // Extract conflict_client_id
                    $conflictJobId = $conflictCheck['conflict_job_id']; // Extract conflict_job_id
                }
            } else {
                $status = JobStatusEnum::UNSCHEDULED;
            }

            \Log::info("------------------------------------");

            $minutes = 0;
            $slotsInString = '';
            foreach ($mergedContinuousTime as $key => $slot) {
                if (!empty($slotsInString)) {
                    $slotsInString .= ',';
                }

                $slotsInString .= Carbon::parse($slot['starting_at'])->format('H:i') . '-' . Carbon::parse($slot['ending_at'])->format('H:i');

                $minutes += Carbon::parse($slot['ending_at'])->diffInMinutes(Carbon::parse($slot['starting_at']));
            }

            if ($selectedService['type'] == 'hourly') {
                $hours = ($minutes / 60);
                $subtotal_amount = $selectedService['rateperhour'] * $hours;
            } else if($selectedService['type'] == 'squaremeter') {
                $subtotal_amoun = $selectedService['ratepersquaremeter'] * $selectedService['totalsquaremeter'];
            } else {
                $subtotal_amount = $selectedService['fixed_price'];
            }

            $discount_amount = NULL;
            if ($job->discount_type == 'percentage') {
                $discount_amount = (($job->discount_value / 100) * $subtotal_amount);
            } else if ($job->discount_type == 'fixed') {
                $discount_amount = $job->discount_value;
            } else {
                $discount_amount = 0;
            }

            $total_amount = $subtotal_amount - $discount_amount;

            $start_time = Carbon::parse($mergedContinuousTime[0]['starting_at'])->toTimeString();
            $end_time = Carbon::parse($mergedContinuousTime[count($mergedContinuousTime) - 1]['ending_at'])->toTimeString();

            $nextJob = Job::create([
                'uuid'          => Str::uuid(),
                'worker_id'     => $workerId,
                'client_id'     => $job->client_id,
                'contract_id'   => $job->contract_id,
                'offer_id'      => $job->offer_id,
                'start_date'    => $next_job_date,
                'start_time'    => $start_time,
                'end_time'      => $end_time,
                'shifts'        => $slotsInString,
                'schedule'      => $job->schedule,
                'schedule_id'   => $job->schedule_id,
                'parent_job_id' => $job->parent_job_id,
                'status'        => $status,
                'subtotal_amount'   => $subtotal_amount,
                'discount_type'     => $job->discount_type,
                'discount_value'    => $job->discount_value,
                'discount_amount'   => $discount_amount,
                'total_amount'      => $total_amount,
                'next_start_date'   => $next_to_next_job_date,
                'address_id'        => $job->address_id,
                'keep_prev_worker'  => $job->keep_prev_worker,
                'origin_job_id'         => $job->origin_job_id,
                'job_group_id'          => $job->job_group_id,
                'original_worker_id'    => $job->original_worker_id,
                'original_shifts'       => $job->original_shifts,
                'previous_worker_id'    => $previous_worker_id,
                'previous_worker_after' => $previous_worker_after,
                'previous_shifts'       => $previous_shifts,
                'previous_shifts_after' => $previous_shifts_after,
                'offer_service' => $job->offer_service,
            ]);

            $nextJobService = $job->jobservice->replicate()->fill([
                'job_id' => $nextJob->id,
                'duration_minutes'  => $minutes,
                'total'             => $total_amount,
            ]);
            
            $nextJobService->save();

            if($status == JobStatusEnum::UNSCHEDULED) {
                Conflict::create([
                    'job_id' => $conflictJobId,
                    'worker_id' => $nextJob->worker_id,
                    'client_id' => $conflictClientId,
                    'conflict_client_id' => $nextJob->client_id,
                    'conflict_job_id' => $nextJob->id,
                    'date' => $nextJob->start_date,
                    'shift' => $nextJob->shifts,
                    'hours' => round($minutes / 60, 2)
                ]);
            }


            foreach ($mergedContinuousTime as $key => $shift) {
                $nextJob->workerShifts()->create([
                    'starting_at' => Carbon::parse($shift['starting_at'])->toDateTimeString(),
                    'ending_at'   => Carbon::parse($shift['ending_at'])->toDateTimeString(),
                ]);
               
            }

            $job->update([
                'is_next_job_created' => true
            ]);

            $this->copyDefaultCommentsToJob($nextJob);

            // SyncGoogleSheetAddJobOccurring::dispatch($nextJob);

            return $next_job_date;
    }


}
