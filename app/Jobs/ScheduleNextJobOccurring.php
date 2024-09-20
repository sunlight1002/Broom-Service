<?php

namespace App\Jobs;

use App\Enums\JobStatusEnum;
use App\Events\ClientLeadStatusChanged;
use App\Models\Job;
use App\Models\ManageTime;
use App\Models\Notification;
use App\Traits\JobSchedule;
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


class ScheduleNextJobOccurring implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, JobSchedule, PriceOffered;

    protected $jobID;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($jobID)
    {
        $this->jobID = $jobID;
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
        $job = Job::query()
            ->with('client')
            ->where('schedule', '!=', 'na')
            ->where('is_next_job_created', false)
            ->where(function ($q) {
                $q->whereNull('cancelled_for')
                    ->orWhere('cancelled_for', '!=', 'forever');
            })
            ->find($this->jobID);

        $manageTime = ManageTime::first();
        $workingWeekDays = json_decode($manageTime->days);

        if ($job) {
            $client = $job->client;

            $offerServices = $this->formatServices($job->offer, false);
            $filtered = Arr::where($offerServices, function ($value, $key) use ($job) {
                return $value['service'] == $job->schedule_id;
            });

            $selectedService = head($filtered);

            $job_date = Carbon::parse($job->start_date);
            $preferredWeekDay = $job->jobservice->config['preferred_weekday'];
            $next_job_date = $this->scheduleNextJobDate($job_date, $job->schedule, $preferredWeekDay, $workingWeekDays);

            if ($job->cancelled_for == 'until_date') {
                $carbon_next_job_date = Carbon::parse($next_job_date);
                while (Carbon::parse($job->cancel_until_date)->gte($carbon_next_job_date)) {
                    $next_job_date = $this->scheduleNextJobDate($carbon_next_job_date, $job->schedule, $preferredWeekDay, $workingWeekDays);

                    $carbon_next_job_date = Carbon::parse($next_job_date);
                }
            }

            $next_to_next_job_date = $this->scheduleNextJobDate(Carbon::parse($next_job_date), $job->schedule, $preferredWeekDay, $workingWeekDays);

            $job->update([
                'next_start_date' => $next_job_date,
            ]);

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
                    'starting_at' => Carbon::parse($job_date . ' ' . $start_time)->toDateTimeString(),
                    'ending_at' => Carbon::parse($job_date . ' ' . $end_time)->toDateTimeString()
                ];
            }

            $mergedContinuousTime = $this->mergeContinuousTimes($shiftFormattedArr);

            if ($workerId) {
                $status = JobStatusEnum::SCHEDULED;
                if ($this->isJobTimeConflicting($mergedContinuousTime, $job_date, $workerId)) {
                    $status = JobStatusEnum::UNSCHEDULED;
                }
            } else {
                $status = JobStatusEnum::UNSCHEDULED;
            }

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
            ]);

            $nextJobService = $job->jobservice->replicate()->fill([
                'job_id' => $nextJob->id,
                'duration_minutes'  => $minutes,
                'total'             => $total_amount,
            ]);
            $nextJobService->save();

            foreach ($mergedContinuousTime as $key => $shift) {
                $nextJob->workerShifts()->create($shift);
            }

            $job->update([
                'is_next_job_created' => true
            ]);

            $this->copyDefaultCommentsToJob($nextJob);

            $newLeadStatus = $this->getClientLeadStatusBasedOnJobs($client);

            if ($client->lead_status->lead_status != $newLeadStatus) {
                $client->lead_status()->updateOrCreate(
                    [],
                    ['lead_status' => $newLeadStatus]
                );
    
                event(new ClientLeadStatusChanged($client, $newLeadStatus));

                $emailData = [
                    'client' => $client->toArray(),
                    'status' => $newLeadStatus,
                ];

                if($newLeadStatus === 'freeze client'){
                    // Trigger WhatsApp Notification
                    event(new WhatsappNotificationEvent([
                       "type" => WhatsappMessageTemplateEnum::CLIENT_IN_FREEZE_STATUS,
                       "notificationData" => [
                           'client' => $client->toArray(),
                       ]
                   ]));
               }
                
                if ($client->notification_type === "both") {

                    if ($newLeadStatus === 'uninterested') {

                        event(new WhatsappNotificationEvent([
                            "type" => WhatsappMessageTemplateEnum::FOLLOW_UP_ON_OUR_CONVERSATION,
                            "notificationData" => [
                                'client' => $client->toArray(),
                            ]
                        ]));
                
                        SendUninterestedClientEmail::dispatch($client, $emailData);
                    }

                    if ($newLeadStatus === 'unanswered') {

                        // Trigger WhatsApp Notification
                        event(new WhatsappNotificationEvent([
                            "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
                            "notificationData" => [
                                'client' => $client->toArray(),
                            ]
                        ]));
                        // App::setLocale($client['lng']);
                        // // Send Email Notification
                        // Mail::send('Mails.UnansweredLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                        //     $messages->to($emailData['client']['email']);
                        //     $sub = __('mail.unanswered_lead.header');
                        //     $messages->subject($sub);
                        // });
                    }
                    
                    if ($newLeadStatus === 'irrelevant') {

                        // Trigger WhatsApp Notification
                        event(new WhatsappNotificationEvent([
                            "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
                            "notificationData" => [
                                'client' => $client->toArray(),
                            ]
                        ]));
                        // App::setLocale($client['lng']);
                        // // Send Email Notification
                        // Mail::send('Mails.IrrelevantLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                        //     // $messages->to($emailData['client']['email']);
                        //     $messages->to($emailData['client']['email']);
                        //     $sub = __('mail.irrelevant_lead.header');
                        //     $messages->subject($sub);
                        // });
                    } 

                        // Trigger WhatsApp Notification
                        // event(new WhatsappNotificationEvent([
                        //     "type" => WhatsappMessageTemplateEnum::USER_STATUS_CHANGED,
                        //     "notificationData" => [
                        //         'client' => $client->toArray(),
                        //         'status' => $newLeadStatus,
                        //     ]
                        // ]));
                    
                } elseif ($client->notification_type === "email") {

                    if ($newLeadStatus === 'uninterested') {

                        SendUninterestedClientEmail::dispatch($client, $emailData);
                    }

                    if ($newLeadStatus === 'unanswered') {
                        // App::setLocale($client['lng']);
                        // // Send Email Notification
                        // Mail::send('Mails.UnansweredLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                        //     $messages->to($emailData['client']['email']);
                        //     $sub = __('mail.unanswered_lead.header');
                        //     $messages->subject($sub);
                        // });
                    }
                    if ($newLeadStatus === 'irrelevant') {
                        // App::setLocale($client['lng']);
                        // // Send Email Notification
                        // Mail::send('Mails.IrrelevantLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                        //     // $messages->to($emailData['client']['email']);
                        //     $messages->to($emailData['client']['email']);
                        //     $sub = __('mail.irrelevant_lead.header');
                        //     $messages->subject($sub);
                        // });
                    }
                    // event(new WhatsappNotificationEvent([
                    //     "type" => WhatsappMessageTemplateEnum::USER_STATUS_CHANGED,
                    //     "notificationData" => [
                    //         'client' => $client->toArray(),
                    //         'status' => $newLeadStatus,
                    //     ]
                    // ]));
                    
                } else {

                    if ($newLeadStatus === 'uninterested') {

                        event(new WhatsappNotificationEvent([
                            "type" => WhatsappMessageTemplateEnum::FOLLOW_UP_ON_OUR_CONVERSATION,
                            "notificationData" => [
                                'client' => $client->toArray(),
                            ]
                        ]));
                    }

                    if ($newLeadStatus === 'unanswered') {

                        // Trigger WhatsApp Notification Only
                        event(new WhatsappNotificationEvent([
                            "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
                            "notificationData" => [
                                'client' => $client->toArray(),
                            ]
                        ]));
                    }
                    if ($newLeadStatus === 'irrelevant') {

                        // Trigger WhatsApp Notification Only
                        event(new WhatsappNotificationEvent([
                            "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
                            "notificationData" => [
                                'client' => $client->toArray(),
                            ]
                        ]));
                    }
                        // Trigger WhatsApp Notification Only
                        // event(new WhatsappNotificationEvent([
                        //     "type" => WhatsappMessageTemplateEnum::USER_STATUS_CHANGED,
                        //     "notificationData" => [
                        //         'client' => $client->toArray(),
                        //         'status' => $newLeadStatus,
                        //     ]
                        // ]));
                }
            }
        }
    }
}
