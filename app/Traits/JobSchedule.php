<?php

namespace App\Traits;

use App\Enums\JobStatusEnum;
use App\Enums\LeadStatusEnum;
use App\Models\Admin;
use App\Models\Client;
use App\Models\ClientPropertyAddress;
use App\Models\Comment;
use App\Models\Job;
use App\Models\JobComments;
use App\Models\JobHours;
use App\Models\JobWorkerShift;
use App\Models\Services;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait JobSchedule
{
    private function periodType($period)
    {
        $type = NULL;

        $total_sequence = NULL;

        if (Str::endsWith($period, 'd')) {
            $type = 'day';
            $total_sequence = (int)Str::replaceLast('d', '', $period);
        } else if (Str::endsWith($period, 'w')) {
            $type = 'week';
            $total_sequence = (int)Str::replaceLast('w', '', $period);
        } else if (Str::endsWith($period, 'm')) {
            $type = 'month';
            $total_sequence = (int)Str::replaceLast('m', '', $period);
        } else if (Str::endsWith($period, 'y')) {
            $type = 'year';
            $total_sequence = (int)Str::replaceLast('y', '', $period);
        } else if ($period == 'na') {
            $type = 'day';
            $total_sequence = 0;
        }

        if (!$type) {
            throw new Exception('Invalid Type');
        }

        if (strlen($period) == 2) {
            return [$type, $total_sequence];
        } else {
            return [$type, 1];
        }
    }

    private function scheduleJob($service)
    {
        $period = $service['period'];
        $cycle = $service['cycle'];
        $startDate = $service['start_date'];
        $weekdayOccurrence = $service['weekday_occurrence'];
        $weekday = $service['weekday'];
        $weekdays = $service['weekdays'];
        $monthOccurrence = $service['month_occurrence'];
        $monthdaySelectionType = $service['monthday_selection_type'];
        $monthDate = $service['month_date'];

        $jobsArr = [];
        $jobIdx = 0;

        $start_date = Carbon::parse($startDate)->startOfDay();

        $configuration = [
            'cycle' => $cycle,
            'period' => $period,
            'start_date' => $startDate,
        ];

        if ($period == "na") {
            // one time
            $jobsArr[$jobIdx] = [
                'job_date' => $start_date->toDateString(),
                'next_job_date' => NULL,
                'configuration' => $configuration
            ];
            $jobIdx++;
        } else {
            // recurring
            [$period_type, $period_sequence_length] = $this->periodType($period);
            if ($period_type == "day") {
                $next_start_date = $start_date
                    ->clone()
                    ->addDays($period_sequence_length);

                $jobsArr[$jobIdx] = [
                    'job_date' => $start_date->toDateString(),
                    'next_job_date' => $next_start_date->toDateString(),
                    'configuration' => $configuration
                ];
                $jobIdx++;
            } else if ($period_type == 'week') {
                if ($period_sequence_length > 1) {
                    $configuration = array_merge($configuration, [
                        'weekday_occurrence' => $weekdayOccurrence,
                        'weekday' => $weekday,
                    ]);

                    $job_date = NULL;
                    if ($weekdayOccurrence == 'last') {
                        $job_date = $start_date
                            ->clone()
                            ->addWeeks($period_sequence_length)
                            ->previous($weekday);
                    } else if (ctype_digit($weekdayOccurrence)) {
                        $_weekday_occurrence = (int)$weekdayOccurrence;

                        $job_date = $start_date
                            ->clone()
                            ->next($weekday)
                            ->addWeeks($_weekday_occurrence - 1);
                    }

                    if ($job_date) {
                        $jobsArr[$jobIdx] = [
                            'job_date' => $job_date->toDateString(),
                            'next_job_date' => $job_date->addWeeks($period_sequence_length)->toDateString(),
                            'configuration' => $configuration
                        ];
                        $jobIdx++;
                    }
                } else {
                    if ($cycle == '1') {
                        $configuration = array_merge($configuration, [
                            'weekday' => $weekday,
                        ]);

                        $job_date = $start_date
                            ->clone()
                            ->next($weekday);

                        $jobsArr[$jobIdx] = [
                            'job_date' => $job_date->toDateString(),
                            'next_job_date' => $job_date->addWeeks($period_sequence_length)->toDateString(),
                            'configuration' => $configuration
                        ];
                        $jobIdx++;
                    } else {
                        $configuration = array_merge($configuration, [
                            'weekdays' => $weekdays,
                        ]);

                        foreach ($weekdays as $d) {
                            $job_date = $start_date
                                ->clone()
                                ->next($d);

                            $jobsArr[$jobIdx] = [
                                'job_date' => $job_date->toDateString(),
                                'next_job_date' => $job_date->addWeeks($period_sequence_length)->toDateString(),
                                'configuration' => $configuration
                            ];
                            $jobIdx++;
                        }
                    }
                }
            } elseif ($period_type == 'month') {
                $monthOccurrence = $period_sequence_length > 1 ? $monthOccurrence : 1;

                [$job_date, $next_job_date] = $this->scheduleJobByMonth(
                    $period_sequence_length,
                    $start_date,
                    $monthOccurrence,
                    $monthdaySelectionType,
                    $monthDate,
                    $weekdayOccurrence,
                    $weekday
                );

                $configuration = array_merge($configuration, [
                    'month_occurrence' => $monthOccurrence,
                    'monthday_selection_type' => $monthdaySelectionType,
                    'month_date' => $monthDate,
                    'weekday_occurrence' => $weekdayOccurrence,
                    'weekday' => $weekday,
                ]);

                $jobsArr[$jobIdx] = [
                    'job_date' => $job_date,
                    'next_job_date' => $next_job_date,
                    'configuration' => $configuration
                ];
                $jobIdx++;
            } elseif ($period_type == 'year') {
                [$job_date, $next_job_date] = $this->scheduleJobByYear(
                    $period_sequence_length,
                    $start_date,
                    $monthOccurrence,
                    $monthdaySelectionType,
                    $monthDate,
                    $weekdayOccurrence,
                    $weekday
                );

                $configuration = array_merge($configuration, [
                    'month_occurrence' => $monthOccurrence,
                    'monthday_selection_type' => $monthdaySelectionType,
                    'month_date' => $monthDate,
                    'weekday_occurrence' => $weekdayOccurrence,
                    'weekday' => $weekday,
                ]);

                $jobsArr[$jobIdx] = [
                    'job_date' => $job_date,
                    'next_job_date' => $next_job_date,
                    'configuration' => $configuration
                ];
                $jobIdx++;
            }
        }

        return $jobsArr;
    }

    private function scheduleJobByMonth(
        $periodSequenceLength,
        $startDate,
        $monthOccurrence,
        $monthdaySelectionType,
        $monthDate,
        $weekdayOccurrence,
        $weekday
    ) {
        $_month_occurrence = (int)$monthOccurrence;

        $job_date = $startDate->clone()->addMonths($_month_occurrence - 1);

        [$job_date, $next_job_date] = $this->getDateAndNextDate(
            $periodSequenceLength,
            $job_date,
            $monthdaySelectionType,
            $monthDate,
            $weekdayOccurrence,
            $weekday,
            'months'
        );

        return [
            $job_date,
            $next_job_date,
        ];
    }

    private function scheduleJobByYear(
        $periodSequenceLength,
        $startDate,
        $monthOccurrence,
        $monthdaySelectionType,
        $monthDate,
        $weekdayOccurrence,
        $weekday
    ) {
        $_month_occurrence = (int)$monthOccurrence;

        $job_date = $startDate->clone()->setMonth($_month_occurrence);

        [$job_date, $next_job_date] = $this->getDateAndNextDate(
            $periodSequenceLength,
            $job_date,
            $monthdaySelectionType,
            $monthDate,
            $weekdayOccurrence,
            $weekday,
            'years'
        );

        return [
            $job_date,
            $next_job_date,
        ];
    }

    private function getDateAndNextDate(
        $periodSequenceLength,
        $date,
        $monthdaySelectionType,
        $monthDate,
        $weekdayOccurrence,
        $weekday,
        $unit
    ) {
        $job_date = $date;

        if ($monthdaySelectionType == 'date') {
            $job_date->setDay($monthDate);
            $next_job_date = $job_date->add($periodSequenceLength, $unit);
        } else {
            if ($weekdayOccurrence == 'last') {
                $job_date = $this->getLastWeekDayOfMonth($job_date, $weekday);
                $next_job_date = $this->getLastWeekDayOfMonth(
                    $job_date->clone()->add($periodSequenceLength, $unit),
                    $weekday
                );
            } else if (ctype_digit($weekdayOccurrence)) {
                $_weekday_occurrence = (int)$weekdayOccurrence;

                $job_date = $this->getMentionedWeekDayOfMonth($job_date, $weekday, $_weekday_occurrence);
                $next_job_date = $this->getMentionedWeekDayOfMonth(
                    $job_date->clone()->add($periodSequenceLength, $unit),
                    $weekday,
                    $_weekday_occurrence
                );
            }
        }

        return [
            $job_date->toDateString(),
            $next_job_date->toDateString(),
        ];
    }

    private function getMentionedWeekDayOfMonth($date, $weekday, $weekdayOccurrence)
    {
        $date->startOfMonth()->addWeeks($weekdayOccurrence - 1);

        if (!$date->is($weekday)) {
            $date->next($weekday);
        }

        return $date;
    }

    private function getLastWeekDayOfMonth($date, $weekday)
    {
        $date->endOfMonth();

        if (!$date->is($weekday)) {
            $date->previous($weekday);
        }

        return $date;
    }

    private function scheduleNextJobDate($jobDate, $period, $preferredWeekDay, $workingWeekDays)
    {
        if ($period == 'na') {
            return NULL;
        }

        $next_job_date = NULL;

        // recurring
        [$period_type, $period_sequence_length] = $this->periodType($period);
        if ($period_type == "day") {
            $next_job_date = $jobDate
                ->clone()
                ->addDays($period_sequence_length);

            if ($this->isHoliday($next_job_date, $workingWeekDays)) {
                $next_job_date->modify('next sunday');
            }
        } else if ($period_type == 'week') {
            $next_job_date = $jobDate
                ->clone()
                ->addWeeks($period_sequence_length);
        } elseif ($period_type == 'month') {
            $next_job_date = $jobDate
                ->clone()
                ->addMonths($period_sequence_length);

            if (
                !$next_job_date->is($preferredWeekDay) ||
                $this->isHoliday($next_job_date, $workingWeekDays)
            ) {
                $next_job_date->modify('next ' . $preferredWeekDay);
            }
        } elseif ($period_type == 'year') {
            $next_job_date = $jobDate
                ->clone()
                ->addYears($period_sequence_length);

            if (
                !$next_job_date->is($preferredWeekDay) ||
                $this->isHoliday($next_job_date, $workingWeekDays)
            ) {
                $next_job_date->modify('next ' . $preferredWeekDay);
            }
        }

        return $next_job_date ? $next_job_date->toDateString() : NULL;
    }

    private function isHoliday($date, $workingWeekDays)
    {
        return !in_array($date->dayOfWeek, $workingWeekDays);
    }

    private function mergeContinuousTimes($times)
    {
        $mergedTimes = [];
        $currentSlot = null;

        foreach ($times as $time) {
            $startingAt = strtotime($time['starting_at']);
            $endingAt = strtotime($time['ending_at']);

            if ($currentSlot === null) {
                $currentSlot = ['starting_at' => $time['starting_at'], 'ending_at' => $time['ending_at']];
            } elseif ($startingAt == strtotime($currentSlot['ending_at'])) {
                // Merge the current slot with the next slot
                $currentSlot['ending_at'] = $time['ending_at'];
            } else {
                // Push the current merged slot and start a new one
                $mergedTimes[] = $currentSlot;
                $currentSlot = ['starting_at' => $time['starting_at'], 'ending_at' => $time['ending_at']];
            }
        }

        // Add the last merged slot
        if ($currentSlot !== null) {
            $mergedTimes[] = $currentSlot;
        }

        return $mergedTimes;
    }

    private function calcTimeDiffInMins($time1, $time2)
    {
        // Parse time strings into Carbon objects
        $time1Obj = Carbon::createFromFormat('G', $time1);
        $time2Obj = Carbon::createFromFormat('G', $time2);

        // Calculate the difference
        return $time2Obj->diffInMinutes($time1Obj);
    }

    private function updateJobWorkerMinutes($jobID)
    {
        $job = Job::find($jobID);

        if ($job) {
            if (!JobHours::query()->where('job_id', $job->id)->exists()) {
                $actual_minutes = $job->jobservice->duration_minutes;
            } else {
                $hours = JobHours::query()
                    ->where('job_id', $job->id)
                    ->selectRaw('SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time)) / 60) as minutes')
                    ->first();

                $actual_minutes = (int)$hours->minutes;
            }

            $job->update([
                'actual_time_taken_minutes' => $actual_minutes
            ]);
        }
    }

    private function updateJobAmount($jobID)
    {
        $job = Job::with(['offer', 'jobservice'])->find($jobID);

        if ($job) {
            $offerServices = $this->formatServices($job->offer, false);
            $filtered = Arr::where($offerServices, function ($value, $key) use ($job) {
                return $value['service'] == $job->schedule_id;
            });

            $selectedService = head($filtered);

            if ($selectedService['type'] == 'hourly') {
                if ($job->actual_time_taken_minutes > 0) {
                    $minutes = $job->actual_time_taken_minutes;
                } else {
                    $minutes = $job->jobservice->duration_minutes;
                }

                $hours = ($minutes / 60);
                $subtotal_amount = $selectedService['rateperhour'] * $hours;
            } else if ($selectedService['type'] == 'squaremeter') {
                $subtotal_amount = $selectedService['ratepersquaremeter'] * $selectedService['totalsquaremeter'];
            } else {
                $subtotal_amount = $selectedService['fixed_price'];
            }

            if ($job->extra_amount) {
                $subtotal_amount = $subtotal_amount + $job->extra_amount;
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

            $job->update([
                'subtotal_amount' => $subtotal_amount,
                'discount_amount' => $discount_amount,
                'total_amount' => $total_amount
            ]);

            $job->jobservice()->update([
                'total'  => $total_amount,
            ]);
        }
    }

    private function copyDefaultCommentsToJob($job)
    {
        $client_id = $job->client_id;
        $service_id = $job->schedule_id;
        $address_id = $job->address_id;

        $defaultComments = Comment::query()
            ->with('commenter', 'attachments')
            ->where(function ($q) use ($client_id, $service_id, $address_id) {
                $q->where(function ($sq) use ($client_id) {
                    $sq->where('relation_type', Client::class)
                        ->where('relation_id', $client_id);
                })->orWhere(function ($sq) use ($service_id) {
                    $sq->where('relation_type', Services::class)
                        ->where('relation_id', $service_id);
                })->orWhere(function ($sq) use ($address_id) {
                    $sq->where('relation_type', ClientPropertyAddress::class)
                        ->where('relation_id', $address_id);
                });
            })
            ->where(function ($q) {
                $q->whereNull('valid_till')
                    ->orWhereDate('valid_till', '>=', date('Y-m-d'));
            })
            ->get();

        if (!Storage::disk('public')->exists('uploads/attachments')) {
            Storage::disk('public')->makeDirectory('uploads/attachments');
        }

        foreach ($defaultComments as $key => $dComment) {
            $commenter_name = NULL;
            if (get_class($dComment->commenter) == Admin::class) {
                $commenter_name = $dComment->commenter->name;
            } else if (get_class($dComment->commenter) == User::class) {
                $commenter_name = $dComment->commenter->firstname . ' ' . $dComment->commenter->lastname;
            } else if (get_class($dComment->commenter) == Client::class) {
                $commenter_name = $dComment->commenter->firstname . ' ' . $dComment->commenter->lastname;
            }

            if ($commenter_name) {
                $comment = JobComments::create([
                    'name' => $commenter_name,
                    'comment_for' => 'worker',
                    'job_id' => $job->id,
                    'comment' => $dComment->comment,
                ]);

                $resultArr = [];
                foreach ($dComment->attachments()->get() as $key => $attachment_) {

                    $original_name = $attachment_->original_name;
                    $file_name = $job->id . "_" . date('s') . "_" . $original_name;

                    if (Storage::disk('public')->exists('uploads/attachments/' . $attachment_->file_name)) {
                        if (Storage::disk('public')->copy(
                            'uploads/attachments/' . $attachment_->file_name,
                            'uploads/attachments/' . $file_name
                        )) {
                            array_push($resultArr, [
                                'file_name' => $file_name,
                                'original_name' => $original_name
                            ]);
                        }
                    }
                }

                $comment->attachments()->createMany($resultArr);
            }
        }
    }

    private function isJobTimeConflicting($newSlots, $job_date, $workerID, $ignoreJobID = NULL)
    {
        $bookedSlots = JobWorkerShift::query()
            ->leftJoin('jobs', 'job_worker_shifts.job_id', '=', 'jobs.id')
            ->whereDate('jobs.start_date', $job_date)
            ->where('jobs.worker_id', $workerID)
            ->when($ignoreJobID, function ($q) use ($ignoreJobID) {
                return $q->where('jobs.id', '!=', $ignoreJobID);
            })
            ->select('job_worker_shifts.starting_at', 'job_worker_shifts.ending_at')
            ->get()
            ->toArray();

        $isConflicting = false;
        foreach ($newSlots as $slot) {
            $ns = Carbon::parse($slot['starting_at']);
            $ne = Carbon::parse($slot['ending_at'])->subSecond();

            foreach ($bookedSlots as $key => $bookedSlot) {
                $bss = Carbon::parse($bookedSlot['starting_at']);
                $bse = Carbon::parse($bookedSlot['ending_at'])->subSecond();

                if ($ns->between($bss, $bse) || $ne->between($bss, $bse)) {
                    $isConflicting = true;
                    break;
                }
            }

            if ($isConflicting) {
                break;
            }
        }

        return $isConflicting;
    }

    private function getClientLeadStatusBasedOnJobs($client)
    {
        $hasFutureJob = Job::query()
            ->where('client_id', $client->id)
            ->where(function ($q1) {
                $q1->whereDate('start_date', '>=', date('Y-m-d'))
                    ->orWhereDate('next_start_date', '>=', date('Y-m-d'));
            })
            ->where(function ($q2) {
                $q2
                    ->whereIn('status', [
                        JobStatusEnum::PROGRESS,
                        JobStatusEnum::SCHEDULED,
                        JobStatusEnum::UNSCHEDULED,
                        JobStatusEnum::COMPLETED,
                    ])
                    ->orWhere(function ($sq1) {
                        $sq1->where('status', JobStatusEnum::CANCEL)
                            ->where('cancelled_for', '!=', 'forever');
                    });
            })
            ->exists();

        if (!$hasFutureJob) {
            $status = LeadStatusEnum::PAST;
        } else {
            $today = Carbon::today()->toDateString();
            $weekEndDate = Carbon::today()->endOfWeek()->toDateString();

            $hasJobCurrentWeek = Job::query()
                ->where('client_id', $client->id)
                ->whereDate('start_date', '>=', $today)
                ->whereDate('start_date', '<=', $weekEndDate)
                ->where(function ($q2) {
                    $q2->whereNull('cancelled_for')
                        ->orWhere('cancelled_for', '!=', 'forever');
                })
                ->exists();

            if ($hasJobCurrentWeek) {
                $status = LeadStatusEnum::ACTIVE_CLIENT;
            } else {
                $status = LeadStatusEnum::FREEZE_CLIENT;
            }
        }

        return $status;
    }
}
