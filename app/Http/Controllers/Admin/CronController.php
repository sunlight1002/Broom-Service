<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\User;
use App\Models\JobService;
use App\Models\WorkerAvailability;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class CronController extends Controller
{
    public function WeeklyJob()
    {
        $startDate = Carbon::now()->startOfWeek(Carbon::SUNDAY)->subDays(1);
        $endDate = Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDays(5);

        $jobs = Job::query()
            ->with(['offer', 'contract', 'jobservice'])
            ->whereBetween('start_date', [$startDate, $endDate])
            ->whereHas('contract', function ($query) {
                $query->where('job_status', '=', 1);
            })
            ->get();

        foreach ($jobs as $job) {
            if ($job->schedule == 'w') {
                $date = Carbon::parse($job->start_date);
                $newDate = $date->addDays(7);
            }
            if ($job->schedule == '2w') {
                $date = Carbon::parse($job->start_date);
                $newDate = $date->addDays(14);
            }
            if ($job->schedule == '3w') {
                $date = Carbon::parse($job->start_date);
                $newDate = $date->addDays(21);
            }
            if ($job->schedule == 'm') {
                $date = Carbon::parse($job->start_date);
                $newDate = $date->addMonths(1);
            }
            if ($job->schedule == '2m') {
                $date = Carbon::parse($job->start_date);
                $newDate = $date->addMonths(2);
            }
            if ($job->schedule == '3m') {
                $date = Carbon::parse($job->start_date);
                $newDate = $date->addMonths(3);
            }

            if (isset($newDate)) {
                $new = new Job();
                $new->client_id     = $job->client_id;
                $new->worker_id     = $job->worker_id;
                $new->offer_id      = $job->offer_id;
                $new->contract_id   = $job->contract_id;
                $new->start_date    = $newDate;
                $new->start_time    = $job->start_time;
                $new->end_time      = $job->end_time;
                $new->schedule      = $job->schedule;
                $new->schedule_id   = $job->schedule_id;
                $new->shifts        = $job->shifts;
                if ($this->checkWorker($job)) {
                    $new->status = 'scheduled';
                } else {
                    $new->status = 'unscheduled';
                }

                $new->save();

                $jserv = ($job->jobservice->toArray());
                unset($jserv['id']);
                unset($jserv['created_at']);
                unset($jserv['updated_at']);
                $jserv['job_id'] = $new->id;
                $jobSer = $jserv;

                JobService::create($jobSer);
            }
        }
        echo "Job Updated Successfully.";
    }

    public function checkWorker($job)
    {
        $allslot =  [
            '8am-16pm' => array('08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00'),
            '8am-10am' => array('08:00', '08:30', '09:00', '09:30', '10:00'),
            '10am-12pm' => array('10:00', '10:30', '11:00', '11:30', '12:00'),
            '12pm-14pm' => array('12:00', '12:30', '13:00', '13:30', '14:00'),
            '14pm-16pm' => array('14:00', '14:30', '15:00', '15:30', '16:00'),
            '12pm-16pm' => array('12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00'),
            '16pm-18pm' => array('16:00', '16:30', '17:00', '17:30', '18:00'),
            '18pm-20pm' => array('18:00', '18:30', '19:00', '19:30', '20:00'),
            '16pm-20pm' => array('16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30', '20:00'),
            '20pm-22pm' => array('20:00', '20:30', '21:00', '21:30', '22:00'),
            '22pm-24am' => array('22:00', '22:30', '23:00', '23:30', '00:00'),
            '20pm-24am' => array('20:00', '20:30', '21:00', '21:30', '22:00', '22:30', '23:00', '23:30', '00:00'),
        ];

        $availabiltities = false;
        $w_a = WorkerAvailability::query()
            ->where('user_id', $job->worker_id)
            ->where('date', $job->start_date)
            ->first();

        if ($w_a) {
            $data = $allslot[$w_a->working[0]];
            if (in_array($job->start_time, $data) && in_array($job->end_time, $data)) {
                $availabiltities = true;
            }
        }
        return  $availabiltities;
    }
}
