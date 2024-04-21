<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JobStatusEnum;
use App\Enums\LeadStatusEnum;
use App\Events\JobShiftChanged;
use App\Events\JobWorkerChanged;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Job;
use App\Models\User;
use App\Models\Contract;
use App\Models\Services;
use App\Models\ServiceSchedule;
use App\Models\JobHours;
use App\Models\JobService;
use App\Traits\JobSchedule;
use App\Traits\PriceOffered;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Helpers\Helper;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

class JobController extends Controller
{
    use JobSchedule, PriceOffered;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $keyword = $request->get('keyword');
        $payment_filter = $request->get('payment_filter');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');

        $jobs = Job::query()
            ->with([
                'worker',
                'client',
                'offer',
                'jobservice',
                'order',
                'invoice',
                'jobservice.service',
                'propertyAddress'
            ])
            ->when($keyword, function ($q) use ($keyword) {
                return $q
                    ->whereHas('worker', function ($sq) use ($keyword) {
                        $sq->where(function ($sq) use ($keyword) {
                            $sq->where(DB::raw('firstname'), 'like', '%' . $keyword . '%');
                            $sq->orWhere(DB::raw('lastname'), 'like', '%' . $keyword . '%');
                        });
                    })
                    ->orWhereHas('client', function ($sq) use ($keyword) {
                        $sq->where(function ($sq) use ($keyword) {
                            $sq->where(DB::raw('firstname'), 'like', '%' . $keyword . '%');
                            $sq->orWhere(DB::raw('lastname'), 'like', '%' . $keyword . '%');
                        });
                    })
                    ->orWhereHas('jobservice', function ($sq) use ($keyword) {
                        $sq->where(function ($sq) use ($keyword) {
                            $sq->where(DB::raw('name'), 'like', '%' . $keyword . '%');
                            $sq->orWhere(DB::raw('heb_name'), 'like', '%' . $keyword . '%');
                        });
                    })
                    ->orWhere('status', 'like', '%' . $keyword . '%');
            })
            ->when($start_date, function ($q) use ($start_date) {
                return $q->whereDate('start_date', '>=', $start_date);
            })
            ->when($end_date, function ($q) use ($end_date) {
                return $q->whereDate('start_date', '<=', $end_date);
            })
            ->when(isset($payment_filter), function ($q) use ($payment_filter) {
                return $q->where('is_paid', $payment_filter);
            })
            ->orderBy('start_date')
            ->orderBy('client_id')
            ->groupBy('jobs.id')
            ->paginate(20);

        return response()->json([
            'jobs' => $jobs,
        ]);
    }

    public function shiftChangeWorker($sid, $date)
    {
        $ava_workers = User::query()
            ->with(['availabilities', 'jobs'])
            ->where('skill',  'like', '%' . $sid . '%')
            ->whereHas('availabilities', function ($query) use ($date) {
                $query->where('date', '=', $date);
            })
            ->where('status', 1)
            ->get();

        return response()->json([
            'data' => $ava_workers,
        ]);
    }

    public function AvlWorker($id)
    {
        $job = Job::find($id);
        $js = $job->jobservice;
        $ava_worker = array();

        $ava_workers = User::query()
            ->with(['availabilities', 'jobs'])
            ->where('skill',  'like', '%' . $js->service_id . '%')
            ->whereHas('availabilities', function ($query) use ($job) {
                $query->where('date', '=', $job->start_date);
            })
            ->where('status', 1)
            ->get()
            ->toArray();

        foreach ($ava_workers as $w) {
            $check_worker_job = Job::query()
                ->where('worker_id', $w['id'])
                ->where('start_date', $job->start_date)
                ->get()
                ->toArray();

            if (!$check_worker_job) {
                $ava_worker[] = $w;
            }
        }

        return response()->json([
            'aworker' => $ava_worker,
        ]);
    }

    public function getAllJob()
    {
        return response()->json([
            'jobs' => Job::get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => ['required'],
            'worker_id' => ['required'],
            'start_date' => ['required']
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()]);
        }

        Job::create($request->input());

        return response()->json([
            'message' => 'Job has been created successfully'
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $job = Job::query()
            ->with([
                'client',
                'worker',
                'service',
                'offer',
                'jobservice',
                'order',
                'invoice',
                'propertyAddress'
            ])
            ->find($id);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found'
            ], 404);
        }

        return response()->json([
            'job' => $job,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'workers' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()]);
        }

        $worker = $request->workers[0];
        $job = Job::find($id);

        $job->upcate([
            'worker_id'  => $worker['worker_id'],
            'start_date' => $worker['date'],
            'start_time' => $worker['start'],
            'end_time'   => $worker['end'],
            'status'     => 'scheduled',
        ]);

        $this->sendWorkerEmail($id);

        return response()->json([
            'message' => 'Job has been updated successfully'
        ]);
    }

    public function getJobByClient(Request $request, $id)
    {
        $jobQuery = Job::query()
            ->with(['offer', 'worker', 'jobservice', 'order', 'invoice'])
            ->where('client_id', $id);

        if (isset($request->status)) {
            $jobQuery->where('status', $request->status);
        }

        if (isset($request->q)) {
            $q = $request->q;
            if ($q == 'ordered') {
                $jobQuery->where('is_order_generated', true);
            } else if ($q == 'unordered') {
                $jobQuery->where('is_order_generated', false);
            } else if ($q == 'invoiced') {
                $jobQuery->where('is_invoice_generated', true);
            } else if ($q == 'uninvoiced') {
                $jobQuery->where('is_invoice_generated', false);
            }
        }

        $sch        = Job::where('status', JobStatusEnum::SCHEDULED)->where('client_id', $id)->count();
        $un_sch     = Job::where('status', JobStatusEnum::UNSCHEDULED)->where('client_id', $id)->count();
        $cancel     = Job::where('status', JobStatusEnum::CANCEL)->where('client_id', $id)->count();
        $progress   = Job::where('status', JobStatusEnum::PROGRESS)->where('client_id', $id)->count();
        $completed  = Job::where('status', JobStatusEnum::COMPLETED)->where('client_id', $id)->count();

        $ordered    = Job::where('client_id', $id)->where('is_order_generated', true)->count();
        $unordered  = Job::where('client_id', $id)->where('is_order_generated', false)->count();
        $invoiced   = Job::where('client_id', $id)->where('is_invoice_generated', true)->count();
        $unordered  = Job::where('client_id', $id)->where('is_invoice_generated', false)->count();

        $jobs = $jobQuery
            ->orderBy('start_date', 'desc')
            ->paginate(20);

        $all = Job::where('client_id', $id)->count();

        return response()->json([
            'all'         => $all,
            'jobs'        => $jobs,
            'scheduled'   => $sch,
            'unscheduled' => $un_sch,
            'canceled'    => $cancel,
            'progress'    => $progress,
            'completed'   => $completed,
            'ordered'     => $ordered,
            'unordered'   => $unordered,
            'invoiced'    => $invoiced,
            'uninvoiced'  => $unordered
        ]);
    }

    public function getJobWorker(Request $request)
    {
        $filter = [];
        $filter['status'] = $request->status;
        $jobs = Job::query()
            ->with(['client', 'worker', 'service', 'jobservice'])
            ->where('worker_id', $request->wid);

        if (isset($filter['status']) && $filter['status']) {
            $jobs = $jobs->where('status', JobStatusEnum::COMPLETED);
        } else {
            $jobs = $jobs->where('status', '!=', JobStatusEnum::COMPLETED);
        }

        $jobs = $jobs->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'jobs' => $jobs
        ]);
    }

    public function createJob(Request $request)
    {
        $data = $request->all();

        $contract = Contract::with('offer')->find($data['contract_id']);
        if (!$contract) {
            return response()->json([
                'message' => 'Contract not found'
            ], 404);
        }

        $client = $contract->client;
        if (!$client) {
            return response()->json([
                'message' => 'Client not found'
            ], 404);
        }

        $offerServices = $this->formatServices($contract->offer, false);
        $filtered = Arr::where($offerServices, function ($value, $key) use ($data) {
            return $value['service'] == $data['service_id'];
        });

        $selectedService = head($filtered);

        $service = Services::find($data['service_id']);
        $serviceSchedule = ServiceSchedule::find($selectedService['frequency']);

        $repeat_value = $serviceSchedule->period;
        if ($selectedService['service'] == 10) {
            $s_name = $selectedService['other_title'];
            $s_heb_name = $selectedService['other_title'];
        } else {
            $s_name = $service->name;
            $s_heb_name = $service->heb_name;
        }
        $s_freq   = $selectedService['freq_name'];
        $s_cycle  = $selectedService['cycle'];
        $s_period = $selectedService['period'];
        $s_total  = $selectedService['totalamount'];
        $s_id     = $selectedService['service'];

        $workerIDs = array_values(array_unique(data_get($data, 'workers.*.worker_id')));
        foreach ($workerIDs as $workerID) {
            $workerDates = Arr::where($data['workers'], function ($value) use ($workerID) {
                return $value['worker_id'] == $workerID;
            });

            $workerDates = array_values($workerDates);
            foreach ($workerDates as $workerIndex => $workerDate) {
                // if ($selectedService['type'] == 'hourly') {
                //     $total_amount = $selectedService['rateperhour'];
                // } else {
                //     $total_amount = $selectedService['fixed_price'];
                // }

                $job_date = Carbon::parse($workerDate['date']);
                $preferredWeekDay = strtolower($job_date->format('l'));
                $next_job_date = $this->scheduleNextJobDate($job_date, $repeat_value, $preferredWeekDay);

                $job_date = $job_date->toDateString();

                $slots = explode(',', $workerDate['shifts']);
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

                $status = JobStatusEnum::SCHEDULED;

                if (
                    Job::where('start_date', $job_date)
                    ->where('worker_id', $workerDate['worker_id'])
                    ->exists()
                ) {
                    $status = JobStatusEnum::UNSCHEDULED;
                }

                $job = Job::create([
                    'worker_id'     => $workerDate['worker_id'],
                    'client_id'     => $contract->client_id,
                    'contract_id'   => $contract->id,
                    'offer_id'      => $contract->offer_id,
                    'start_date'    => $job_date,
                    'shifts'        => $slotsInString,
                    'schedule'      => $repeat_value,
                    'is_one_time_job'   => $repeat_value == 'na',
                    'schedule_id'   => $s_id,
                    'status'        => $status,
                    'total_amount'  => $s_total,
                    'next_start_date'   => $next_job_date,
                    'address_id'        => $selectedService['address']['id'],
                    'keep_prev_worker'  => isset($data['prevWorker']) ? $data['prevWorker'] : false,
                    'original_worker_id'     => $workerDate['worker_id'],
                    'original_shifts'        => $slotsInString,
                ]);

                JobService::create([
                    'job_id'            => $job->id,
                    'service_id'        => $s_id,
                    'name'              => $s_name,
                    'heb_name'          => $s_heb_name,
                    'duration_minutes'  => $minutes,
                    'freq_name'         => $s_freq,
                    'cycle'             => $s_cycle,
                    'period'            => $s_period,
                    'total'             => $s_total,
                    'config'            => [
                        'cycle'             => $serviceSchedule->cycle,
                        'period'            => $serviceSchedule->period,
                        'preferred_weekday' => $preferredWeekDay
                    ]
                ]);

                foreach ($mergedContinuousTime as $key => $shift) {
                    $job->workerShifts()->create($shift);
                }

                if ($workerIndex == 0) {
                    $job->load(['client', 'worker', 'jobservice', 'propertyAddress']);

                    if (!is_null($job['worker']['email']) && $job['worker']['email'] != 'Null') {
                        App::setLocale($job->worker->lng);

                        $emailData = array(
                            'email' => $job['worker']['email'],
                            'job' => $job->toArray(),
                            'start_time' => $mergedContinuousTime[0]['starting_at'],
                            'content'  => __('mail.worker_new_job.new_job_assigned') . " " . __('mail.worker_new_job.please_check'),
                        );
                        Helper::sendJobWANotification($emailData);
                        Mail::send('/Mails/NewJobMail', $emailData, function ($messages) use ($emailData) {
                            $messages->to($emailData['email']);
                            $sub = __('mail.worker_new_job.subject') . "  " . __('mail.worker_new_job.company');
                            $messages->subject($sub);
                        });
                    }
                }
            }
        }

        $client->lead_status()->updateOrCreate(
            [],
            ['lead_status' => LeadStatusEnum::ACTIVE_CLIENT]
        );

        return response()->json([
            'message' => 'Job has been created successfully'
        ]);
    }

    public function changeJobWorker(Request $request, $id)
    {
        $data = $request->all();

        if (!in_array($data['repeatancy'], ['one_time', 'until_date', 'forever'])) {
            return response()->json([
                'message' => "Repeatancy is invalid",
            ], 422);
        }

        $job = Job::query()
            ->with([
                'client',
                'worker',
                'jobservice',
            ])
            ->find($id);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found'
            ], 404);
        }

        if ($job->status == JobStatusEnum::COMPLETED) {
            return response()->json([
                'message' => 'Job already completed',
            ], 403);
        }

        if ($job->status == JobStatusEnum::CANCEL) {
            return response()->json([
                'message' => 'Job already cancelled',
            ], 403);
        }

        if ($job->status == JobStatusEnum::PROGRESS) {
            return response()->json([
                'message' => 'Job is in progress',
            ], 403);
        }

        $client = $job->client;
        if (!$client) {
            return response()->json([
                'message' => 'Client not found'
            ], 404);
        }

        $oldWorker = $job->worker;

        $old_job_data = [
            'start_date' => $job->start_date,
            'shifts' => $job->shifts,
        ];

        $repeat_value = $job->jobservice->period;

        $job_date = Carbon::parse($data['worker']['date']);
        $preferredWeekDay = strtolower($job_date->format('l'));
        $next_job_date = $this->scheduleNextJobDate($job_date, $repeat_value, $preferredWeekDay);

        $job_date = $job_date->toDateString();

        $slots = explode(',', $data['worker']['shifts']);
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

        $status = JobStatusEnum::SCHEDULED;

        if (
            Job::where('start_date', $job_date)
            ->where('worker_id', $data['worker']['worker_id'])
            ->exists()
        ) {
            $status = JobStatusEnum::UNSCHEDULED;
        }

        $jobData = [
            'worker_id'     => $data['worker']['worker_id'],
            'start_date'    => $job_date,
            'shifts'        => $slotsInString,
            'status'        => $status,
            'next_start_date'   => $next_job_date,
        ];

        if ($data['repeatancy'] == 'one_time') {
            $jobData['previous_worker_id'] = $job->worker_id;
            $jobData['previous_worker_after'] = NULL;
            $jobData['previous_shifts'] = $job->shifts;
            $jobData['previous_shifts_after'] = NULL;
        } else if ($data['repeatancy'] == 'until_date') {
            $jobData['previous_worker_id'] = $job->worker_id;
            $jobData['previous_worker_after'] = $data['until_date'];
            $jobData['previous_shifts'] = $job->shifts;
            $jobData['previous_shifts_after'] = $data['until_date'];
        } else if ($data['repeatancy'] == 'forever') {
            $jobData['previous_worker_id'] = NULL;
            $jobData['previous_worker_after'] = NULL;
            $jobData['previous_shifts'] = NULL;
            $jobData['previous_shifts_after'] = NULL;
        }

        $job->update($jobData);

        $job->jobservice()->update([
            'duration_minutes'  => $minutes,
            'config'            => [
                'cycle'             => $job->jobservice->cycle,
                'period'            => $job->jobservice->period,
                'preferred_weekday' => $preferredWeekDay
            ]
        ]);

        $job->workerShifts()->delete();
        foreach ($mergedContinuousTime as $key => $shift) {
            $job->workerShifts()->create($shift);
        }

        $job->load(['client', 'worker', 'jobservice', 'propertyAddress']);

        event(new JobWorkerChanged($job, $mergedContinuousTime[0]['starting_at'], $old_job_data, $oldWorker));

        return response()->json([
            'message' => 'Job has been updated successfully'
        ]);
    }

    public function changeJobShift(Request $request, $id)
    {
        $data = $request->all();

        if (!in_array($data['repeatancy'], ['one_time', 'until_date', 'forever'])) {
            return response()->json([
                'message' => "Repeatancy is invalid",
            ], 422);
        }

        $job = Job::query()
            ->with([
                'client',
                'worker',
                'jobservice',
            ])
            ->find($id);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found'
            ], 404);
        }

        if ($job->status == JobStatusEnum::COMPLETED) {
            return response()->json([
                'message' => 'Job already completed',
            ], 403);
        }

        if ($job->status == JobStatusEnum::CANCEL) {
            return response()->json([
                'message' => 'Job already cancelled',
            ], 403);
        }

        if ($job->status == JobStatusEnum::PROGRESS) {
            return response()->json([
                'message' => 'Job is in progress',
            ], 403);
        }

        $client = $job->client;
        if (!$client) {
            return response()->json([
                'message' => 'Client not found'
            ], 404);
        }

        $repeat_value = $job->jobservice->period;

        $job_date = Carbon::parse($data['worker']['date']);
        $preferredWeekDay = strtolower($job_date->format('l'));
        $next_job_date = $this->scheduleNextJobDate($job_date, $repeat_value, $preferredWeekDay);

        $job_date = $job_date->toDateString();

        $slots = explode(',', $data['worker']['shifts']);
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

        $status = JobStatusEnum::SCHEDULED;

        if (
            Job::where('start_date', $job_date)
            ->where('id', '!=', $job->id)
            ->where('worker_id', $job->worker_id)
            ->exists()
        ) {
            $status = JobStatusEnum::UNSCHEDULED;
        }

        $jobData = [
            'start_date'    => $job_date,
            'shifts'        => $slotsInString,
            'status'        => $status,
            'next_start_date'   => $next_job_date,
        ];

        if ($data['repeatancy'] == 'one_time') {
            $jobData['previous_shifts'] = $job->shifts;
            $jobData['previous_shifts_after'] = NULL;
        } else if ($data['repeatancy'] == 'until_date') {
            $jobData['previous_shifts'] = $job->shifts;
            $jobData['previous_shifts_after'] = $data['until_date'];
        } else if ($data['repeatancy'] == 'forever') {
            $jobData['previous_shifts'] = NULL;
            $jobData['previous_shifts_after'] = NULL;
        }

        $job->update($jobData);

        $job->jobservice()->update([
            'duration_minutes'  => $minutes,
            'config'            => [
                'cycle'             => $job->jobservice->cycle,
                'period'            => $job->jobservice->period,
                'preferred_weekday' => $preferredWeekDay
            ]
        ]);

        $job->workerShifts()->delete();
        foreach ($mergedContinuousTime as $key => $shift) {
            $job->workerShifts()->create($shift);
        }

        $job->load(['client', 'worker', 'jobservice', 'propertyAddress']);

        event(new JobShiftChanged($job, $mergedContinuousTime[0]['starting_at']));

        return response()->json([
            'message' => 'Job has been updated successfully'
        ]);
    }

    // public function getShifts($shift, $lng = 'en')
    // {
    //     $show_shift = array(
    //         "Full Day",
    //         "Morning",
    //         'Afternoon',
    //         'Evening',
    //         'Night',
    //     );
    //     $shifts = explode(',', $shift);
    //     $check = '';
    //     $new_shift = '';
    //     foreach ($show_shift as $s_s) {
    //         if ($s_s == 'Afternoon') {
    //             $check = 'noon';
    //         } else {
    //             $check = $s_s;
    //         }

    //         foreach ($shifts as $shift) {
    //             if (str_contains($shift, strtolower($check))) {
    //                 if ($new_shift == '') {
    //                     $new_shift = $s_s;
    //                 } else {
    //                     if (!str_contains($new_shift, $s_s)) {
    //                         $new_shift = $new_shift . ' | ' . $s_s;
    //                     }
    //                 }
    //             }
    //         }
    //     }

    //     if ($lng == 'heb') {
    //         $new_shift = str_replace("Full Day", "יום שלם", $new_shift);
    //         $new_shift = str_replace("Morning", "בוקר", $new_shift);
    //         $new_shift = str_replace("Noon", "צהריים", $new_shift);
    //         $new_shift = str_replace("Afternoon", "אחהצ", $new_shift);
    //         $new_shift = str_replace("Evening", "ערב", $new_shift);
    //         $new_shift = str_replace("Night", "לילה", $new_shift);
    //     }
    //     return $new_shift;
    // }

    public function getJobTime(Request $request)
    {
        $time = JobHours::where('job_id', $request->job_id)->get();
        $total = 0;
        foreach ($time as $t) {
            if ($t->time_diff) {
                $total = $total + (int)$t->time_diff;
            }
        }

        return response()->json([
            'time' => $time,
            'total' => $total
        ]);
    }

    public function addJobTime(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_time' => ['required', 'date_format:Y-m-d H:i:s'],
            'end_time'  => ['required', 'date_format:Y-m-d H:i:s']
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()]);
        }

        $time = JobHours::create([
            'job_id' => $request->job_id,
            'worker_id' => $request->worker_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'time_diff' => $request->timeDiff,
        ]);

        $this->updateJobWorkerMinutes($request->job_id);

        return response()->json([
            'time' => $time,
        ]);
    }

    public function updateJobTime(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_time' => ['required', 'date_format:Y-m-d H:i:s'],
            'end_time'  => ['required', 'date_format:Y-m-d H:i:s']
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()]);
        }

        $time = JobHours::find($request->id);

        $time->update([
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'time_diff' => $request->timeDiff,
        ]);

        $this->updateJobWorkerMinutes($request->job_id);

        return response()->json([
            'time' => $time,
        ]);
    }

    public function cancelJob(Request $request, $id)
    {
        $job = Job::query()
            ->with(['worker', 'offer', 'client', 'jobservice'])
            ->find($id);

        $feePercentage = $request->fee;
        $feeAmount = ($feePercentage / 100) * $job->offer->total;

        $job->update([
            'status' => JobStatusEnum::CANCEL,
            'cancellation_fee_percentage' => $feePercentage,
            'cancellation_fee_amount' => $feeAmount,
            'cancelled_by_role' => 'admin',
            'cancelled_by' => Auth::user()->id,
            'cancelled_at' => now(),
            'cancelled_for' => $request->repeatancy,
            'cancel_until_date' => $request->until_date,
        ]);

        $admin = Admin::find(1)->first();
        App::setLocale('en');
        $data = array(
            'by'         => 'admin',
            'email'      => $admin->email,
            'admin'      => $admin->toArray(),
            'job'        => $job->toArray(),
        );
        if (isset($data['job']['client']) && !empty($data['job']['client']['phone'])) {
            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::CLIENT_JOB_STATUS_NOTIFICATION,
                "notificationData" => $data
            ]));
        }
        Mail::send('/ClientPanelMail/JobStatusNotification', $data, function ($messages) use ($data) {
            $messages->to($data['job']['client']['email']);
            $ln = $data['job']['client']['lng'];

            ($data['by'] == 'admin') ?
                $sub = ($ln == 'en') ? ('Job has been cancelled') . " #" . $data['job']['id'] :
                $data['job']['id'] . "# " . ('העבודה בוטלה')
                :
                $sub = __('mail.client_job_status.subject') . " #" . $data['job']['id'];

            $messages->subject($sub);
        });

        return response()->json([
            'msg' => 'Job cancelled succesfully!'
        ]);
    }

    public function deleteJobTime($id)
    {
        $jobHour = JobHours::find($id);
        $jobHour->delete();

        return response()->json([
            'message' => 'Job Time deleted successfully',
        ]);
    }

    public function exportReport(Request $request)
    {
        if ($request->type == 'single') {
            $jobs = JobHours::query()
                ->with('worker')
                ->where('job_id', $request->id)
                ->get();

            $fileName = 'job_report_' . $request->id . '.csv';
        } else {
            $jobs = JobHours::query()
                ->whereDate('created_at', '>=', $request->from)
                ->whereDate('created_at', '<=', $request->to)
                ->get();

            $fileName = 'AllJob_report.csv';
        }

        if ($jobs->isEmpty()) {
            return response()->json([
                'status_code' => 404,
                'msg' => 'No work log is found!'
            ]);
        }

        $report = [];
        foreach ($jobs as $job) {
            $row['worker_name']      = $job->worker ? $job->worker->firstname . " " . $job->worker->lastname : 'NA';
            $row['worker_id']        = $job->worker ? $job->worker->worker_id : 'NA';
            $row['start_time']       = $job->start_time;
            $row['end_time']         = $job->end_time;
            $row['time_diffrence']   = $job->time_diff;
            $row['job_id']           = $job->job_id;
            $row['time_total']       = (int)$job->time_diff;

            array_push($report, $row);
        }

        return response()->json([
            'status_code' => 200,
            'filename' => $fileName,
            'report' => $report
        ]);
    }

    public function sendWorkerEmail($job_id)
    {
        $job = Job::query()
            ->with(['client', 'worker', 'jobservice', 'propertyAddress'])
            ->find($job_id);

        if (
            isset($job['worker']['email']) &&
            $job['worker']['email'] != null &&
            $job['worker']['email'] != 'Null'
        ) {
            App::setLocale($job->worker->lng);

            $data = array(
                'email' => $job['worker']['email'],
                'job'  => $job->toArray(),
                'content'  => __('mail.worker_new_job.new_job_assigned') . " " . __('mail.worker_new_job.please_check'),
            );
            Helper::sendJobWANotification($data);
            Mail::send('/Mails/NewJobMail', $data, function ($messages) use ($data) {
                $messages->to($data['email']);
                $sub = __('mail.worker_new_job.subject') . "  " . __('mail.worker_new_job.company');
                $messages->subject($sub);
            });
        }

        return true;
    }

    public function workersToSwitch(Request $request, $id)
    {
        $job = Job::find($id);
        $prefer_type = $request->get('prefer_type');

        if (!$job) {
            return response()->json([
                'message' => 'Job not found',
            ], 404);
        }

        if ($job->status == JobStatusEnum::COMPLETED) {
            return response()->json([
                'message' => 'Job already completed',
            ], 403);
        }

        if ($job->status == JobStatusEnum::CANCEL) {
            return response()->json([
                'message' => 'Job already cancelled',
            ], 403);
        }

        $workers = User::query()
            ->whereIn('id', function ($q) use ($job) {
                $q->from('jobs')
                    ->whereNotIn('status', [
                        JobStatusEnum::COMPLETED,
                        JobStatusEnum::CANCEL
                    ])
                    ->where('worker_id', '!=', $job->worker_id)
                    ->where('start_date', $job->start_date)
                    ->where('shifts', $job->shifts)
                    ->select('worker_id');
            })
            ->when(in_array($prefer_type, ['male', 'female']), function ($q) use ($prefer_type) {
                return $q->where('gender', $prefer_type);
            })
            ->get(['id', 'firstname', 'lastname']);

        return response()->json([
            'data' => $workers,
        ]);
    }

    public function switchWorker(Request $request, $id)
    {
        $data = $request->all();
        if (!in_array($data['repeatancy'], ['one_time', 'until_date', 'forever'])) {
            return response()->json([
                'message' => "Repeatancy is invalid",
            ], 422);
        }

        $job = Job::find($id);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found',
            ], 404);
        }

        if ($job->status == JobStatusEnum::COMPLETED) {
            return response()->json([
                'message' => 'Job already completed',
            ], 403);
        }

        if ($job->status == JobStatusEnum::CANCEL) {
            return response()->json([
                'message' => 'Job already cancelled',
            ], 403);
        }

        if ($job->status == JobStatusEnum::PROGRESS) {
            return response()->json([
                'message' => 'Job is in progress',
            ], 403);
        }

        $otherWorkerJob =
            Job::query()
            ->whereNotIn('status', [
                JobStatusEnum::COMPLETED,
                JobStatusEnum::CANCEL
            ])
            ->where('worker_id', '!=', $job->worker_id)
            ->where('start_date', $job->start_date)
            ->where('shifts', $job->shifts)
            ->first();

        if (!$otherWorkerJob) {
            return response()->json([
                'message' => "Other worker's job not found",
            ], 404);
        }

        if ($otherWorkerJob->status == JobStatusEnum::COMPLETED) {
            return response()->json([
                'message' => "Other worker's job already completed",
            ], 403);
        }

        if ($otherWorkerJob->status == JobStatusEnum::CANCEL) {
            return response()->json([
                'message' => "Other worker's job already cancelled",
            ], 403);
        }

        $jobData = $otherJobData = [];

        $jobData['worker_id'] = $otherWorkerJob->worker_id;
        $otherJobData['worker_id'] = $job->worker_id;

        if ($data['repeatancy'] == 'one_time') {
            $jobData['previous_worker_id'] = $job->worker_id;
            $jobData['previous_worker_after'] = NULL;
            $otherJobData['previous_worker_id'] = $otherWorkerJob->worker_id;
            $otherJobData['previous_worker_after'] = NULL;
        } else if ($data['repeatancy'] == 'until_date') {
            $jobData['previous_worker_id'] = $job->worker_id;
            $jobData['previous_worker_after'] = $data['until_date'];
            $otherJobData['previous_worker_id'] = $otherWorkerJob->worker_id;
            $otherJobData['previous_worker_after'] = $data['until_date'];
        } else if ($data['repeatancy'] == 'forever') {
            $jobData['previous_worker_id'] = NULL;
            $jobData['previous_worker_after'] = NULL;
            $otherJobData['previous_worker_id'] = NULL;
            $otherJobData['previous_worker_after'] = NULL;
        }

        $job->update($jobData);
        $otherWorkerJob->update($otherJobData);

        $job->load(['client', 'worker', 'jobservice', 'propertyAddress']);
        $jobArray = $job->toArray();

        if (
            isset($jobArray['worker']['email']) &&
            $jobArray['worker']['email']
        ) {
            App::setLocale($jobArray['worker']['lng']);

            $emailData = array(
                'email' => $jobArray['worker']['email'],
                'job'  => $jobArray,
                'content'  => __('mail.worker_new_job.change_in_job') . " " . __('mail.worker_new_job.please_check'),
            );
            Helper::sendJobWANotification($emailData);
            Mail::send('/Mails/NewJobMail', $emailData, function ($messages) use ($emailData) {
                $messages->to($emailData['email']);
                $sub = __('mail.worker_new_job.subject') . "  " . __('mail.worker_new_job.company');
                $messages->subject($sub);
            });
        }

        $otherWorkerJob->load(['client', 'worker', 'jobservice', 'propertyAddress']);
        $otherJobArray = $otherWorkerJob->toArray();

        if (
            isset($otherJobArray['worker']['email']) &&
            $otherJobArray['worker']['email']
        ) {
            App::setLocale($otherJobArray['worker']['lng']);

            $emailData = array(
                'email' => $otherJobArray['worker']['email'],
                'job'  => $otherJobArray,
                'content'  => __('mail.worker_new_job.change_in_job') . " " . __('mail.worker_new_job.please_check'),
            );
            Helper::sendJobWANotification($emailData);
            Mail::send('/Mails/NewJobMail', $emailData, function ($messages) use ($emailData) {
                $messages->to($emailData['email']);
                $sub = __('mail.worker_new_job.subject') . "  " . __('mail.worker_new_job.company');
                $messages->subject($sub);
            });
        }

        return response()->json([
            'message' => "Worker switched successfully",
        ]);
    }

    public function updateJobDone(Request $request, $id)
    {
        $job = Job::find($id);

        if ($job) {
            $job->update([
                'is_job_done' => $request->checked
            ]);
        }

        return response()->json([
            'message' => 'Job has been updated',
        ]);
    }

    public function updateWorkerActualTime(Request $request, $id)
    {
        $job = Job::find($id);

        if ($job) {
            $job->update([
                'actual_time_taken_minutes' => $request->value
            ]);
        }

        return response()->json([
            'message' => 'Job has been updated',
        ]);
    }
}
