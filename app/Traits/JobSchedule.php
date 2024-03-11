<?php

namespace App\Traits;

use Carbon\Carbon;
use Exception;
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
            $startDate,
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
            $startDate,
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
}
