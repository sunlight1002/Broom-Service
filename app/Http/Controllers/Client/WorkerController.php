<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WorkerController extends Controller
{
    public function index(Request $request)
    {
        $service = '';
        $ignoreWorkerIDArr = $request->ignore_worker_ids ? explode(',', $request->ignore_worker_ids) : [];
        if ($request->service_id) {
            // $contract=Contract::with('offer','client')->find($request->contract_id);
            // if($contract->offer){
            //     $services=json_decode($contract->offer['services']);
            //     $service=$services[0]->service;
            // }
            $service = $request->service_id;
        }
        if ($request->job_id) {
            $job = Job::with('offer')->find($request->job_id);
            if ($job->offer) {
                $services = json_decode($job->offer['services']);
                $service = $services[0]->service;
            }
        }
        $available_date = $request->get('available_date');
        $has_cat = $request->get('has_cat');
        $has_dog = $request->get('has_dog');
        $prefer_type = $request->get('prefer_type');

        $workers = User::query()
            ->with([
                'availabilities:user_id,day,date,start_time,end_time',
                'jobs:worker_id,start_date,shifts,client_id',
                'jobs.client:id,firstname,lastname',
                'notAvailableDates:user_id,date,start_time,end_time'
            ])
            ->with(['availabilities', 'jobs:worker_id,start_date,shifts', 'notAvailableDates:user_id,date'])
            ->when(count($ignoreWorkerIDArr), function ($q) use ($ignoreWorkerIDArr) {
                return $q->whereNotIn('id', $ignoreWorkerIDArr);
            })
            ->when($service != '', function ($q) use ($service) {
                return $q
                    ->whereHas('availabilities', function ($query) {
                        $query->where('date', '>=', Carbon::now()->toDateString());
                    })
                    // ->whereRelation('jobs', function ($query) {
                    //     $query->where('start_date', '>=', Carbon::now()->toDateString());
                    // })
                    ->where('skill',  'like', '%' . $service . '%');
            })
            ->when($has_dog == '1', function ($q) {
                return $q->where('is_afraid_by_dog', false);
            })
            ->when($has_cat == '1', function ($q) {
                return $q->where('is_afraid_by_cat', false);
            })
            ->when(in_array($prefer_type, ['male', 'female']), function ($q) use ($prefer_type) {
                return $q->where('gender', $prefer_type);
            })
            ->whereDoesntHave('notAvailableDates', function ($q) use ($available_date) {
                $q->where('date', $available_date);
            })
            ->where('status', 1)
            ->get();

        if (isset($request->filter)) {
            $workers = $workers->map(function ($worker, $key) {
                $workerArr = $worker->toArray();

                $availabilities = [];
                foreach ($worker->availabilities->groupBy('date') as $date => $times) {
                    $availabilities[$date] = $times->map(function ($item, $key) {
                        return $item->only(['start_time', 'end_time']);
                    });
                }

                $workerArr['availabilities'] = $availabilities;
                $workerArr['aval'] = $this->workerAvl($worker->availabilities);
                $workerArr['wjobs'] = $this->workerJobs($worker->jobs);

                $dates = array();
                foreach ($worker->jobs as $job) {
                    $slotInfo = [
                        'client_name' => $job->client->firstname . ' ' . $job->client->lastname,
                        'slot' => $job->shifts
                    ];

                    $dates[$job->start_date][] = $slotInfo;
                }

                $workerArr['booked_slots'] = $dates;
                $workerArr['not_available_on'] = $worker
                    ->notAvailableDates
                    ->map(function ($item) {
                        return $item->only([
                            'date',
                            'start_time',
                            'end_time'
                        ]);
                    })
                    ->toArray();
                $workerArr['not_available_dates'] = $worker->notAvailableDates->pluck('date')->toArray();

                return $workerArr;
            });

            return response()->json([
                'workers' => $workers,
            ]);
        }

        return response()->json([
            'workers' => $workers,
        ]);
    }

    public function workerJobs($jobs)
    {
        $data = array();
        foreach ($jobs as $job) {
            if (array_key_exists($job->start_date, $data)) {
                $data[$job->start_date] = $data[$job->start_date] . ',' . $job->shifts;
            } else {
                $data[$job->start_date] = $job->shifts;
            }
        }
        return $data;
    }

    public function workerAvl($availabilities)
    {
        $worker_availabilities = [];
        foreach ($availabilities->groupBy('date') as $date => $times) {
            $worker_availabilities[$date] = $times->map(function ($item, $key) {
                return $item->only(['start_time', 'end_time']);
            });
        }

        return $worker_availabilities;
    }
}
