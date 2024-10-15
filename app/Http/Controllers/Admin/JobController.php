<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CancellationActionEnum;
use App\Enums\JobStatusEnum;
use App\Enums\LeadStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Events\JobShiftChanged;
use App\Events\JobWorkerChanged;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Problems;
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
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\ClientOrderCancelled;
use App\Jobs\CreateJobOrder;
use App\Jobs\ScheduleNextJobOccurring;
use App\Models\JobCancellationFee;
use App\Models\ManageTime;
use App\Traits\PaymentAPI;
use App\Events\JobNotificationToAdmin;
use App\Events\JobNotificationToClient;
use App\Events\JobNotificationToWorker;
use App\Models\Notification;
use Yajra\DataTables\Facades\DataTables;
use App\Jobs\SendUninterestedClientEmail;
use Illuminate\Mail\Mailable;

class JobController extends Controller
{
    use JobSchedule, PriceOffered, PaymentAPI;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Retrieve filter values from the request
        $done_filter = $request->get('done_filter');
        $start_time_filter = $request->get('start_time_filter');
        $actual_time_exceed_filter = $request->get('actual_time_exceed_filter');
        $has_no_worker = $request->get('has_no_worker');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
    
        $query = Job::query()
            ->leftJoin('clients', 'jobs.client_id', '=', 'clients.id')
            ->leftJoin('users', 'jobs.worker_id', '=', 'users.id')
            ->leftJoin('job_services', 'job_services.job_id', '=', 'jobs.id')
            ->leftJoin('services', 'job_services.service_id', '=', 'services.id')
            ->leftJoin('order', 'order.id', '=', 'jobs.order_id')
            ->when($start_date, function ($q) use ($start_date) {
                return $q->whereDate('jobs.start_date', '>=', $start_date);
            })
            ->when($end_date, function ($q) use ($end_date) {
                return $q->whereDate('jobs.start_date', '<=', $end_date);
            })
            ->when($done_filter == 'done', function ($q) {
                return $q->where('jobs.is_job_done', 1);
            })
            ->when($done_filter == 'undone', function ($q) {
                return $q->where('jobs.is_job_done', 0);
            })
            ->when($start_time_filter == 'morning', function ($q) {
                return $q->where('jobs.start_time', '<=', '12:00:00');
            })
            ->when($start_time_filter == 'noon', function ($q) {
                return $q->where('jobs.start_time', '>', '12:00:00')
                    ->where('jobs.start_time', '<=', '16:00:00');
            })
            ->when($start_time_filter == 'afternoon', function ($q) {
                return $q->where('jobs.start_time', '>', '16:00:00');
            })
            ->when($actual_time_exceed_filter == 1, function ($q) {
                return $q->whereRaw('jobs.actual_time_taken_minutes > job_services.duration_minutes');
            })
            ->when($has_no_worker == 1, function ($q) {
                return $q->whereNull('jobs.worker_id');
            })
            ->select(
                'jobs.id', 
                'jobs.start_date', 
                'clients.id as client_id', 
                'clients.color as client_color', 
                'users.id as worker_id',
                'jobs.shifts', 
                'jobs.is_job_done', 
                'jobs.status', 
                'job_services.duration_minutes', 
                'job_services.freq_name', // Include freq_name
                'jobs.actual_time_taken_minutes', 
                'jobs.comment', 
                'jobs.review', 
                'jobs.rating', 
                'jobs.total_amount', 
                'jobs.is_order_generated', 
                'jobs.job_group_id',
                DB::raw("CONCAT_WS(' ', clients.firstname, clients.lastname) as client_name"),
                DB::raw("CONCAT_WS(' ', users.firstname, users.lastname) as worker_name"),
                DB::raw('IF(order.status = "Closed", 1, 0) AS is_order_closed'),
                DB::raw('IF(clients.lng = "en", job_services.name, job_services.heb_name) AS service_name'),
                DB::raw('
                CASE 
                    WHEN job_services.name = "AirBnb" THEN "#00FF00" -- This condition should come first
                    WHEN job_services.freq_name = "Once Time week" AND job_services.name LIKE "%Star%" THEN "#FFFFFF"
                    WHEN job_services.freq_name = "Once in every two weeks" AND job_services.name LIKE "%Star%" THEN "#00FF"
                    WHEN job_services.freq_name = "One Time" OR job_services.name = "Cleaning After Renovation" OR job_services.name = "Window cleaning" OR job_services.name LIKE "%Basic%" OR job_services.name LIKE "%Standard%" OR job_services.name LIKE "%Premium%" THEN "#D3D3D3"
                    WHEN job_services.name LIKE "%Star%" THEN "#FFFFFF" 
                    WHEN job_services.name = "Office Cleaning" THEN "#FFA07A"
                    ELSE services.color_code
                END AS service_color
            ')            
            
            )
            ->groupBy('jobs.id');
    
        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                if (request()->has('search')) {
                    $keyword = request()->get('search')['value'];
    
                    if (!empty($keyword)) {
                        $query->where(function ($sq) use ($keyword) {
                            $sq->whereRaw("CONCAT_WS(' ', clients.firstname, clients.lastname) like ?", ["%{$keyword}%"])
                                ->orWhereRaw("CONCAT_WS(' ', users.firstname, users.lastname) like ?", ["%{$keyword}%"])
                                ->orWhere('job_services.name', 'like', "%" . $keyword . "%")
                                ->orWhere('job_services.heb_name', 'like', "%" . $keyword . "%")
                                ->orWhere('jobs.shifts', 'like', "%" . $keyword . "%");
                        });
                    }
                }
            })
            ->editColumn('start_date', function ($data) {
                return $data->start_date ? Carbon::parse($data->start_date)->format('d/m/y') : '-';
            })
            ->editColumn('comment', function ($data) {
                return $data->comment ? $data->comment : '-';
            })
            ->editColumn('worker_name', function ($data) {
                return $data->worker_name ? $data->worker_name : 'NA';
            })
            ->addColumn('action', function ($data) {
                return '';
            })
            ->rawColumns(['action'])
            ->toJson();
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

    public function getJobByClient(Request $request, $id)
    {
        $status = $request->get('status');
        $qType = $request->get('q');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');

        $jobs = Job::query()
            ->with(['offer', 'worker', 'jobservice', 'order', 'invoice'])
            ->where('client_id', $id)
            ->when($status, function ($q) use ($status) {
                return $q->where('status', $status);
            })
            ->when($qType == 'ordered', function ($q) {
                return $q->where('is_order_generated', true);
            })
            ->when($qType == 'unordered', function ($q) {
                return $q->where('is_order_generated', false);
            })
            ->when($qType == 'invoiced', function ($q) {
                return $q->where('is_invoice_generated', true);
            })
            ->when($qType == 'uninvoiced', function ($q) {
                return $q->where('is_invoice_generated', false);
            })
            ->when($start_date, function ($q) use ($start_date) {
                return $q->whereDate('start_date', '>=', $start_date);
            })
            ->when($end_date, function ($q) use ($end_date) {
                return $q->whereDate('start_date', '<=', $end_date);
            })
            ->orderBy('start_date', 'desc')
            ->paginate(20);


        $sch        = Job::where('status', JobStatusEnum::SCHEDULED)->where('client_id', $id)->count();
        $un_sch     = Job::where('status', JobStatusEnum::UNSCHEDULED)->where('client_id', $id)->count();
        $cancel     = Job::where('status', JobStatusEnum::CANCEL)->where('client_id', $id)->count();
        $progress   = Job::where('status', JobStatusEnum::PROGRESS)->where('client_id', $id)->count();
        $completed  = Job::where('status', JobStatusEnum::COMPLETED)->where('client_id', $id)->count();

        $ordered    = Job::where('client_id', $id)->where('is_order_generated', true)->count();
        $unordered  = Job::where('client_id', $id)->where('is_order_generated', false)->count();
        $invoiced   = Job::where('client_id', $id)->where('is_invoice_generated', true)->count();
        $unordered  = Job::where('client_id', $id)->where('is_invoice_generated', false)->count();

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

        $manageTime = ManageTime::first();
        $workingWeekDays = json_decode($manageTime->days);

        if (isset($data['updatedJobs'])) {
            foreach ($data['updatedJobs'] as $updateJob) {
                $editJob = Job::find($updateJob['job_id']);

                $repeat_value = $editJob->jobservice->period;

                $job_date = Carbon::parse($updateJob['date']);
                $preferredWeekDay = strtolower($job_date->format('l'));
                $next_job_date = $this->scheduleNextJobDate($job_date, $repeat_value, $preferredWeekDay, $workingWeekDays);

                $job_date = $job_date->toDateString();

                $slots = explode(',', $updateJob['shifts']);
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

                $minutes = 0;
                $slotsInString = '';
                foreach ($mergedContinuousTime as $key => $slot) {
                    if (!empty($slotsInString)) {
                        $slotsInString .= ',';
                    }

                    $slotsInString .= Carbon::parse($slot['starting_at'])->format('H:i') . '-' . Carbon::parse($slot['ending_at'])->format('H:i');

                    $minutes += Carbon::parse($slot['ending_at'])->diffInMinutes(Carbon::parse($slot['starting_at']));
                }

                $status = JobStatusEnum::SCHEDULED;

                if ($this->isJobTimeConflicting($mergedContinuousTime, $job_date, $editJob->worker_id, $editJob->id)) {
                    $status = JobStatusEnum::UNSCHEDULED;
                }

                $start_time = Carbon::parse($mergedContinuousTime[0]['starting_at'])->toTimeString();
                $end_time = Carbon::parse($mergedContinuousTime[count($mergedContinuousTime) - 1]['ending_at'])->toTimeString();

                $jobData = [
                    'start_date'    => $job_date,
                    'start_time'    => $start_time,
                    'end_time'      => $end_time,
                    'shifts'        => $slotsInString,
                    'status'        => $status,
                    'next_start_date'   => $next_job_date,
                ];

                $jobData['previous_shifts'] = $editJob->shifts;
                $jobData['previous_shifts_after'] = NULL;

                $editJob->update($jobData);

                $editJob->jobservice()->update([
                    'duration_minutes'  => $minutes,
                    'config'            => [
                        'cycle'             => $editJob->jobservice->cycle,
                        'period'            => $editJob->jobservice->period,
                        'preferred_weekday' => $preferredWeekDay
                    ]
                ]);

                $editJob->workerShifts()->delete();
                foreach ($mergedContinuousTime as $key => $shift) {
                    $editJob->workerShifts()->create($shift);
                }

                $editJob->load(['client', 'worker', 'jobservice', 'propertyAddress']);

                event(new JobShiftChanged($editJob, $mergedContinuousTime[0]['starting_at']));
            }
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
        $s_id     = $selectedService['service'];

        $jobGroupID = NULL;

        $workerIDs = array_values(array_unique(data_get($data, 'workers.*.worker_id')));
        foreach ($workerIDs as $workerID) {
            $workerDates = Arr::where($data['workers'], function ($value) use ($workerID) {
                return $value['worker_id'] == $workerID;
            });
    
            $workerDates = array_values($workerDates);
            foreach ($workerDates as $workerIndex => $workerDate) {
                $job_date = Carbon::parse($workerDate['date']);
                $preferredWeekDay = strtolower($job_date->format('l'));
                $next_job_date = $this->scheduleNextJobDate($job_date, $repeat_value, $preferredWeekDay, $workingWeekDays);
    
                $job_date = $job_date->toDateString();
    
                $slots = explode(',', $workerDate['shifts']);
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
    
                $minutes = 0;
                $slotsInString = '';
                foreach ($mergedContinuousTime as $key => $slot) {
                    if (!empty($slotsInString)) {
                        $slotsInString .= ',';
                    }
    
                    $slotsInString .= Carbon::parse($slot['starting_at'])->format('H:i') . '-' . Carbon::parse($slot['ending_at'])->format('H:i');
    
                    // Calculate duration in 15-minute slots
                    $start = Carbon::parse($slot['starting_at']);
                    $end = Carbon::parse($slot['ending_at']);
                    $interval = 15; // in minutes
                    while ($start < $end) {
                        $start->addMinutes($interval);
                        $minutes += $interval;
                    }
                }
    
                if ($selectedService['type'] == 'hourly') {
                    $hours = ($minutes / 60);
                    $total_amount = ($selectedService['rateperhour'] * $hours);
                } else if($selectedService['type'] == 'squaremeter') {
                    $total_amount = ($selectedService['ratepersquaremeter'] * $selectedService['totalsquaremeter']);
                } else {
                    $total_amount = ($selectedService['fixed_price']);
                }
    
                $status = JobStatusEnum::SCHEDULED;
    
                if ($this->isJobTimeConflicting($mergedContinuousTime, $job_date, $workerDate['worker_id'])) {
                    $status = JobStatusEnum::UNSCHEDULED;
                }
    
                $start_time = Carbon::parse($mergedContinuousTime[0]['starting_at'])->toTimeString();
                $end_time = Carbon::parse($mergedContinuousTime[count($mergedContinuousTime) - 1]['ending_at'])->toTimeString();
    
                $job = Job::create([
                    'worker_id'     => $workerDate['worker_id'],
                    'client_id'     => $contract->client_id,
                    'contract_id'   => $contract->id,
                    'offer_id'      => $contract->offer_id,
                    'start_date'    => $job_date,
                    'start_time'    => $start_time,
                    'end_time'      => $end_time,
                    'shifts'        => $slotsInString,
                    'schedule'      => $repeat_value,
                    'schedule_id'   => $s_id,
                    'status'        => $status,
                    'subtotal_amount'  => $total_amount,
                    'total_amount'  => $total_amount,
                    'next_start_date'   => $next_job_date,
                    'address_id'        => $selectedService['address']['id'],
                    'keep_prev_worker'  => isset($data['prevWorker']) ? $data['prevWorker'] : false,
                    'original_worker_id'     => $workerDate['worker_id'],
                    'original_shifts'        => $slotsInString,
                ]);
    
                $jobser = JobService::create([
                    'job_id'            => $job->id,
                    'service_id'        => $s_id,
                    'name'              => $s_name,
                    'heb_name'          => $s_heb_name,
                    'duration_minutes'  => $minutes,
                    'freq_name'         => $s_freq,
                    'cycle'             => $s_cycle,
                    'period'            => $s_period,
                    'total'             => $total_amount,
                    'config'            => [
                        'cycle'             => $serviceSchedule->cycle,
                        'period'            => $serviceSchedule->period,
                        'preferred_weekday' => $preferredWeekDay
                    ]
                ]);
    
                $jobGroupID = $jobGroupID ? $jobGroupID : $job->id;
    
                $job->update([
                    'origin_job_id' => $job->id,
                    'job_group_id' => $jobGroupID
                ]);
    
                foreach ($mergedContinuousTime as $key => $shift) {
                    $job->workerShifts()->create($shift);
                }
    
                $this->copyDefaultCommentsToJob($job);
    
                $job->load(['client', 'worker', 'jobservice', 'propertyAddress']);
                if ($workerIndex == 0) {
                    $adminEmailData = [
                        'emailData'   => [
                            'job'   =>  $job->toArray(),
                        ],
                        'emailSubject'  => __('mail.worker_new_job.subject') . "  " . __('mail.worker_new_job.company'),
                        'emailTitle'  => 'New Job',
                        'emailContent'  => __('mail.worker_new_job.new_job_assigned') . " " . __('mail.worker_new_job.please_check')
                    ];
                    event(new JobNotificationToAdmin($adminEmailData));
                }
    
                // Send notification to client
                $jobData = $job->toArray();
                $clientData = $jobData['client'];
                $workerData = $jobData['worker'];
                $emailData = [
                    'emailSubject'  => __('mail.worker_new_job.subject') . "  " . __('mail.worker_new_job.company'),
                    'emailTitle'  => __('mail.worker_new_job.new_job_assigned'),
                    'emailContent'  => __('mail.worker_new_job.new_job_assigned')
                ];
                event(new JobNotificationToClient($workerData, $clientData, $jobData, $emailData));
            }
        }

        $newLeadStatus = $this->getClientLeadStatusBasedOnJobs($client);

        if (!$client->lead_status || $client->lead_status->lead_status != $newLeadStatus) {
            $client->lead_status()->updateOrCreate(
                [],
                ['lead_status' => $newLeadStatus]
            );

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
      
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));
            }
            
            if ($newLeadStatus === 'irrelevant') {
      
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));
            }; 

            
          } elseif ($client->notification_type === "email") {

            if ($newLeadStatus === 'uninterested') {

                SendUninterestedClientEmail::dispatch($client, $emailData);
            }
            
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
      
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));
            }
            if ($newLeadStatus === 'irrelevant') {
      
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));
            }
            }
        }

        return response()->json([
            'message' => 'Job has been created successfully',
            'data' => $data,
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

        if (
            $job->status == JobStatusEnum::COMPLETED ||
            $job->is_job_done
        ) {
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

        $manageTime = ManageTime::first();
        $workingWeekDays = json_decode($manageTime->days);

        if (isset($data['updatedJobs'])) {
            foreach ($data['updatedJobs'] as $updateJob) {
                $editJob = Job::find($updateJob['job_id']);

                $repeat_value = $editJob->jobservice->period;

                $job_date = Carbon::parse($updateJob['date']);
                $preferredWeekDay = strtolower($job_date->format('l'));
                $next_job_date = $this->scheduleNextJobDate($job_date, $repeat_value, $preferredWeekDay, $workingWeekDays);

                $job_date = $job_date->toDateString();

                $slots = explode(',', $updateJob['shifts']);
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

                $minutes = 0;
                $slotsInString = '';
                foreach ($mergedContinuousTime as $key => $slot) {
                    if (!empty($slotsInString)) {
                        $slotsInString .= ',';
                    }

                    $slotsInString .= Carbon::parse($slot['starting_at'])->format('H:i') . '-' . Carbon::parse($slot['ending_at'])->format('H:i');

                    $minutes += Carbon::parse($slot['ending_at'])->diffInMinutes(Carbon::parse($slot['starting_at']));
                }

                $status = JobStatusEnum::SCHEDULED;

                if ($this->isJobTimeConflicting($mergedContinuousTime, $job_date, $editJob->worker_id, $editJob->id)) {
                    $status = JobStatusEnum::UNSCHEDULED;
                }

                $start_time = Carbon::parse($mergedContinuousTime[0]['starting_at'])->toTimeString();
                $end_time = Carbon::parse($mergedContinuousTime[count($mergedContinuousTime) - 1]['ending_at'])->toTimeString();

                $jobData = [
                    'start_date'    => $job_date,
                    'start_time'    => $start_time,
                    'end_time'      => $end_time,
                    'shifts'        => $slotsInString,
                    'status'        => $status,
                    'next_start_date'   => $next_job_date,
                ];

                $jobData['previous_shifts'] = $editJob->shifts;
                $jobData['previous_shifts_after'] = NULL;

                $editJob->update($jobData);

                $editJob->jobservice()->update([
                    'duration_minutes'  => $minutes,
                    'config'            => [
                        'cycle'             => $editJob->jobservice->cycle,
                        'period'            => $editJob->jobservice->period,
                        'preferred_weekday' => $preferredWeekDay
                    ]
                ]);

                $editJob->workerShifts()->delete();
                foreach ($mergedContinuousTime as $key => $shift) {
                    $editJob->workerShifts()->create($shift);
                }

                $editJob->load(['client', 'worker', 'jobservice', 'propertyAddress']);

                event(new JobShiftChanged($editJob, $mergedContinuousTime[0]['starting_at']));
            }
        }

        $oldWorker = $job->worker;

        $old_job_data = [
            'start_date' => $job->start_date,
            'start_time' => $job->start_time,
            'shifts' => $job->shifts,
        ];

        $repeat_value = $job->jobservice->period;

        $job_date = Carbon::parse($data['worker']['date']);
        $preferredWeekDay = strtolower($job_date->format('l'));
        $next_job_date = $this->scheduleNextJobDate($job_date, $repeat_value, $preferredWeekDay, $workingWeekDays);

        $job_date = $job_date->toDateString();

        $slots = explode(',', $data['worker']['shifts']);
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

        $minutes = 0;
        $slotsInString = '';
        foreach ($mergedContinuousTime as $key => $slot) {
            if (!empty($slotsInString)) {
                $slotsInString .= ',';
            }

            $slotsInString .= Carbon::parse($slot['starting_at'])->format('H:i') . '-' . Carbon::parse($slot['ending_at'])->format('H:i');

            $minutes += Carbon::parse($slot['ending_at'])->diffInMinutes(Carbon::parse($slot['starting_at']));
        }

        $status = JobStatusEnum::SCHEDULED;

        if ($this->isJobTimeConflicting($mergedContinuousTime, $job_date, $data['worker']['worker_id'])) {
            $status = JobStatusEnum::UNSCHEDULED;
        }

        $start_time = Carbon::parse($mergedContinuousTime[0]['starting_at'])->toTimeString();
        $end_time = Carbon::parse($mergedContinuousTime[count($mergedContinuousTime) - 1]['ending_at'])->toTimeString();

        $jobData = [
            'worker_id'     => $data['worker']['worker_id'],
            'start_date'    => $job_date,
            'start_time'    => $start_time,
            'end_time'      => $end_time,
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

        $feePercentage = $request->fee;
        $feeAmount = ($feePercentage / 100) * $job->total_amount;

        JobCancellationFee::create([
            'job_id' => $job->id,
            'job_group_id' => $job->job_group_id,
            'cancellation_fee_percentage' => $feePercentage,
            'cancellation_fee_amount' => $feeAmount,
            'cancelled_user_role' => 'admin',
            'cancelled_by' => Auth::user()->id,
            'action' => CancellationActionEnum::CHANGE_WORKER,
            'duration' => $request->repeatancy,
            'until_date' => $request->until_date,
        ]);

        $newLeadStatus = $this->getClientLeadStatusBasedOnJobs($client);

        if (!$client->lead_status || $client->lead_status->lead_status != $newLeadStatus) {
            $client->lead_status()->updateOrCreate(
                [],
                ['lead_status' => $newLeadStatus]
            );


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
      
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));
            }
            
            if ($newLeadStatus === 'irrelevant') {
      
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));
            }; 

            
          } elseif ($client->notification_type === "email") {

            if ($newLeadStatus === 'uninterested') {

                SendUninterestedClientEmail::dispatch($client, $emailData);
            }
            
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
      
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));
            }
            if ($newLeadStatus === 'irrelevant') {

                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));
            }
            }
        }

        Notification::create([
            'user_id' => $job->client->id,
            'user_type' => get_class($job->client),
            'type' => NotificationTypeEnum::JOB_SCHEDULE_CHANGE,
            'job_id' => $job->id,
            'status' => 'changed'
        ]);

        $job->load(['client', 'worker', 'jobservice', 'propertyAddress']);

        event(new JobWorkerChanged(
            $job,
            $mergedContinuousTime[0]['starting_at'],
            $old_job_data,
            $oldWorker,
            false
        ));

        $this->checkAndDeleteProblems($job->id);

        return response()->json([
            'message' => 'Job has been updated successfully'
        ]);
    }

    private function checkAndDeleteProblems($jobId)
    {
        // Find problems associated with the job_id
        $problems = Problems::where('job_id', $jobId)->get();

        // If there are problems associated with the job_id, delete them
        if ($problems->count() > 0) {
            foreach ($problems as $problem) {
                $problem->delete();
            }

            \Log::info("Problems related to job_id $jobId have been deleted.");
        }
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

        if (
            $job->status == JobStatusEnum::COMPLETED ||
            $job->is_job_done
        ) {
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

        $manageTime = ManageTime::first();
        $workingWeekDays = json_decode($manageTime->days);

        if (isset($data['updatedJobs'])) {
            foreach ($data['updatedJobs'] as $updateJob) {
                $editJob = Job::find($updateJob['job_id']);

                $repeat_value = $editJob->jobservice->period;

                $job_date = Carbon::parse($updateJob['date']);
                $preferredWeekDay = strtolower($job_date->format('l'));
                $next_job_date = $this->scheduleNextJobDate($job_date, $repeat_value, $preferredWeekDay, $workingWeekDays);

                $job_date = $job_date->toDateString();

                $slots = explode(',', $updateJob['shifts']);
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

                $minutes = 0;
                $slotsInString = '';
                foreach ($mergedContinuousTime as $key => $slot) {
                    if (!empty($slotsInString)) {
                        $slotsInString .= ',';
                    }

                    $slotsInString .= Carbon::parse($slot['starting_at'])->format('H:i') . '-' . Carbon::parse($slot['ending_at'])->format('H:i');

                    $minutes += Carbon::parse($slot['ending_at'])->diffInMinutes(Carbon::parse($slot['starting_at']));
                }

                $status = JobStatusEnum::SCHEDULED;

                if ($this->isJobTimeConflicting($mergedContinuousTime, $job_date, $editJob->worker_id, $editJob->id)) {
                    $status = JobStatusEnum::UNSCHEDULED;
                }

                $start_time = Carbon::parse($mergedContinuousTime[0]['starting_at'])->toTimeString();
                $end_time = Carbon::parse($mergedContinuousTime[count($mergedContinuousTime) - 1]['ending_at'])->toTimeString();

                $jobData = [
                    'start_date'    => $job_date,
                    'start_time'    => $start_time,
                    'end_time'      => $end_time,
                    'shifts'        => $slotsInString,
                    'status'        => $status,
                    'next_start_date'   => $next_job_date,
                ];

                $jobData['previous_shifts'] = $editJob->shifts;
                $jobData['previous_shifts_after'] = NULL;

                $editJob->update($jobData);

                $editJob->jobservice()->update([
                    'duration_minutes'  => $minutes,
                    'config'            => [
                        'cycle'             => $editJob->jobservice->cycle,
                        'period'            => $editJob->jobservice->period,
                        'preferred_weekday' => $preferredWeekDay
                    ]
                ]);

                $editJob->workerShifts()->delete();
                foreach ($mergedContinuousTime as $key => $shift) {
                    $editJob->workerShifts()->create($shift);
                }

                $editJob->load(['client', 'worker', 'jobservice', 'propertyAddress']);

                event(new JobShiftChanged($editJob, $mergedContinuousTime[0]['starting_at']));
            }
        }

        $repeat_value = $job->jobservice->period;

        $job_date = Carbon::parse($data['worker']['date']);
        $preferredWeekDay = strtolower($job_date->format('l'));
        $next_job_date = $this->scheduleNextJobDate($job_date, $repeat_value, $preferredWeekDay, $workingWeekDays);

        $job_date = $job_date->toDateString();

        $slots = explode(',', $data['worker']['shifts']);
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

        $minutes = 0;
        $slotsInString = '';
        foreach ($mergedContinuousTime as $key => $slot) {
            if (!empty($slotsInString)) {
                $slotsInString .= ',';
            }

            $slotsInString .= Carbon::parse($slot['starting_at'])->format('H:i') . '-' . Carbon::parse($slot['ending_at'])->format('H:i');

            $minutes += Carbon::parse($slot['ending_at'])->diffInMinutes(Carbon::parse($slot['starting_at']));
        }

        $status = JobStatusEnum::SCHEDULED;

        if ($this->isJobTimeConflicting($mergedContinuousTime, $job_date, $job->worker_id, $job->id)) {
            $status = JobStatusEnum::UNSCHEDULED;
        }

        $start_time = Carbon::parse($mergedContinuousTime[0]['starting_at'])->toTimeString();
        $end_time = Carbon::parse($mergedContinuousTime[count($mergedContinuousTime) - 1]['ending_at'])->toTimeString();

        $jobData = [
            'start_date'    => $job_date,
            'start_time'    => $start_time,
            'end_time'      => $end_time,
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

        $feePercentage = $request->fee;
        $feeAmount = ($feePercentage / 100) * $job->total_amount;

        JobCancellationFee::create([
            'job_id' => $job->id,
            'job_group_id' => $job->job_group_id,
            'cancellation_fee_percentage' => $feePercentage,
            'cancellation_fee_amount' => $feeAmount,
            'cancelled_user_role' => 'admin',
            'cancelled_by' => Auth::user()->id,
            'action' => CancellationActionEnum::CHANGE_SHIFT,
            'duration' => $request->repeatancy,
            'until_date' => $request->until_date,
        ]);

        $newLeadStatus = $this->getClientLeadStatusBasedOnJobs($client);

        if (!$client->lead_status || $client->lead_status->lead_status != $newLeadStatus) {
            $client->lead_status()->updateOrCreate(
                [],
                ['lead_status' => $newLeadStatus]
            );


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
      
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));
            }
            
            if ($newLeadStatus === 'irrelevant') {
      
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));
            }
            
          } elseif ($client->notification_type === "email") {

            if ($newLeadStatus === 'uninterested') {
                SendUninterestedClientEmail::dispatch($client, $emailData);
            }
            
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
      
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));
            }
            if ($newLeadStatus === 'irrelevant') {
      
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));
            }
            }
        }

        Notification::create([
            'user_id' => $job->client->id,
            'user_type' => get_class($job->client),
            'type' => NotificationTypeEnum::JOB_SCHEDULE_CHANGE,
            'job_id' => $job->id,
            'status' => 'changed'
        ]);

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
            'end_time'  => ['required', 'date_format:Y-m-d H:i:s', 'after:start_time']
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $time = JobHours::create([
            'job_id' => $request->job_id,
            'worker_id' => $request->worker_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'time_diff' => $request->timeDiff,
        ]);

        $this->updateJobWorkerMinutes($time->job_id);

        return response()->json([
            'time' => $time,
        ]);
    }

    public function updateJobTime(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_time' => ['required', 'date_format:Y-m-d H:i:s'],
            'end_time'  => ['required', 'date_format:Y-m-d H:i:s', 'after:start_time']
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $time = JobHours::find($request->id);

        $time->update([
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'time_diff' => $request->timeDiff,
        ]);

        $this->updateJobWorkerMinutes($time->job_id);

        return response()->json([
            'time' => $time,
        ]);
    }

    public function cancelJob(Request $request, $id)
    {
        $job = Job::query()->with('client')->find($id);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found'
            ], 404);
        }

        $client = $job->client;
        if (!$client) {
            return response()->json([
                'message' => 'Client not found'
            ], 404);
        }

        if (
            $job->status == JobStatusEnum::COMPLETED ||
            $job->is_job_done
        ) {
            return response()->json([
                'message' => 'Job already completed',
            ], 403);
        }

        if ($job->status == JobStatusEnum::PROGRESS) {
            return response()->json([
                'message' => 'Job is in progress',
            ], 403);
        }

        if ($job->status == JobStatusEnum::CANCEL) {
            return response()->json([
                'message' => 'Job already cancelled'
            ], 403);
        }

        $repeatancy = $request->get('repeatancy');
        $until_date = $request->get('until_date');

        $jobs = Job::query()
            ->with(['worker', 'offer', 'client', 'jobservice', 'propertyAddress'])
            ->whereIn('status', [
                JobStatusEnum::SCHEDULED,
                JobStatusEnum::UNSCHEDULED,
            ])
            ->when($repeatancy == 'until_date', function ($q) use ($until_date) {
                return $q->whereDate('start_date', '<=', $until_date);
            })
            ->when($repeatancy == 'one_time', function ($q) use ($id) {
                return $q->where('id', $id);
            })
            ->where('job_group_id', $job->job_group_id)
            ->get();

        $admin = Admin::where('role', 'admin')->first();

        foreach ($jobs as $key => $job) {
            $feePercentage = $request->fee;
            $feeAmount = ($feePercentage / 100) * $job->total_amount;

            JobCancellationFee::create([
                'job_id' => $job->id,
                'job_group_id' => $job->job_group_id,
                'cancellation_fee_percentage' => $feePercentage,
                'cancellation_fee_amount' => $feeAmount,
                'cancelled_user_role' => 'admin',
                'cancelled_by' => Auth::user()->id,
                'action' => CancellationActionEnum::CANCELLATION,
                'duration' => $repeatancy,
                'until_date' => $until_date,
            ]);

            $job->update([
                'status' => JobStatusEnum::CANCEL,
                'cancellation_fee_percentage' => $feePercentage,
                'cancellation_fee_amount' => $feeAmount,
                'cancelled_by_role' => 'admin',
                'cancelled_by' => Auth::user()->id,
                'cancelled_at' => now(),
                'cancelled_for' => $repeatancy,
                'cancel_until_date' => $until_date,
            ]);

            CreateJobOrder::dispatch($job->id);
            ScheduleNextJobOccurring::dispatch($job->id);

            App::setLocale('en');
            $data = array(
                'by'         => 'admin',
                'email'      => $admin->email??"",
                'admin'      => $admin?->toArray()??[],
                'job'        => $job?->toArray()??[],
            );

            if (isset($job->client) && !empty($job->client->phone)) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::CLIENT_JOB_STATUS_NOTIFICATION,
                    "notificationData" => $data
                ]));
            }

            $ln = $job->client->lng;
            // Mail::send('/ClientPanelMail/JobStatusNotification', $data, function ($messages) use ($data) {
            //     $messages->to($data['job']['client']['email']);

            //     ($data['by'] == 'admin') ?
            //         $sub = ($ln == 'en') ? ('Job has been cancelled') . " #" . $data['job']['id'] :
            //         $data['job']['id'] . "# " . ('העבודה בוטלה')
            //         :
            //         $sub = __('mail.client_job_status.subject') . " #" . $data['job']['id'];

            //     $messages->subject($sub);
            // });

            //send notification to admin
            $emailContent = '';
            if ($data['by'] == 'client') {
                $emailContent .=  __('mail.client_job_status.content') . ' ' . ucfirst($job->status) . '.';
                if ($job->cancellation_fee_amount) {
                    $emailContent .= __('mail.client_job_status.cancellation_fee') . ' ' . $job->cancellation_fee_amount . 'ILS.';
                }
            } else {
                $emailContent .= 'Job is marked as ' . ucfirst($job->status) . 'by admin/team.';
            }
            $emailSubject = ($data['by'] == 'admin') ?
                (($ln == 'en') ? ('Job has been cancelled') . " #" . $job->id :
                    $job->id . "# " . ('העבודה בוטלה'))
                : __('mail.client_job_status.subject') . " #" . $job->id;

            $adminEmailData = [
                'emailData'   => [
                    'job'   =>  $job->toArray(),
                ],
                'emailSubject'  => $emailSubject,
                'emailTitle'  => 'Job Status',
                'emailContent'  => $emailContent
            ];
            event(new JobNotificationToAdmin($adminEmailData));

            //send notification to worker
            $job = $job->toArray();
            $worker = $job['worker'];
            $emailData = [
                'emailSubject'  => $emailSubject,
                'emailTitle'  => __('mail.job_common.job_status'),
                'emailContent'  => $emailContent
            ];
            event(new JobNotificationToWorker($worker, $job, $emailData));
        }

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

    public function exportTimeReport(Request $request)
    {
        $jobs = Job::query()
            ->leftJoin('users', 'jobs.worker_id', '=', 'users.id')
            ->leftJoin('services', 'jobs.schedule_id', '=', 'services.id')
            ->whereNotNull('jobs.worker_id')
            ->whereDate('jobs.created_at', '>=', $request->from)
            ->whereDate('jobs.created_at', '<=', $request->to)
            ->select('users.worker_id', 'jobs.actual_time_taken_minutes')
            ->selectRaw('CONCAT(users.firstname, " ", COALESCE(users.lastname, "")) as worker_name')
            ->selectRaw('CONCAT(jobs.start_date, " | ", jobs.shifts, " | ", services.name) as job')
            ->get();

        if ($jobs->isEmpty()) {
            return response()->json([
                'message' => 'No work log is found!'
            ], 404);
        }

        $jobs = $jobs->map(function ($item, $key) {
            $item->hours = (float) number_format((float)($item->actual_time_taken_minutes / 60), 2, '.', '');

            return $item;
        });

        return response()->json([
            'jobs' => $jobs
        ]);
    }

    public function exportJobTrackedReport($id)
    {
        $jobHours = JobHours::query()
            ->leftJoin('users', 'job_hours.worker_id', '=', 'users.id')
            ->leftJoin('jobs', 'jobs.id', '=', 'job_hours.job_id')
            ->where('jobs.id', $id)
            ->select('users.worker_id', 'job_hours.start_time', 'job_hours.end_time', 'job_hours.time_diff')
            ->selectRaw('CONCAT(users.firstname, " ", COALESCE(users.lastname, "")) as worker_name')
            ->get();

        if ($jobHours->isEmpty()) {
            return response()->json([
                'message' => 'No work log is found!'
            ], 404);
        }

        $jobHours = $jobHours->map(function ($item, $key) {
            $item->hours = (float) number_format((float)($item->time_diff / 3600), 2, '.', '');

            return $item;
        });

        return response()->json([
            'job_hours' => $jobHours
        ]);
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

        if (
            $job->status == JobStatusEnum::COMPLETED ||
            $job->is_job_done
        ) {
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

        if (
            $job->status == JobStatusEnum::COMPLETED ||
            $job->is_job_done
        ) {
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

        if (
            $otherWorkerJob->status == JobStatusEnum::COMPLETED ||
            $job->is_job_done
        ) {
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
                'content_data'  => __('mail.worker_new_job.change_in_job'),
            );
            // sendJobWANotification($emailData);
            // Mail::send('/Mails/NewJobMail', $emailData, function ($messages) use ($emailData) {
            //     $messages->to($emailData['email']);
            //     $sub = __('mail.worker_new_job.subject') . "  " . __('mail.worker_new_job.company');
            //     $messages->subject($sub);
            // });
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
                'content_data'  => __('mail.worker_new_job.change_in_job'),
            );
            // sendJobWANotification($emailData);
            // Mail::send('/Mails/NewJobMail', $emailData, function ($messages) use ($emailData) {
            //     $messages->to($emailData['email']);
            //     $sub = __('mail.worker_new_job.subject') . "  " . __('mail.worker_new_job.company');
            //     $messages->subject($sub);
            // });
        }

        //send notification to admin
        $adminEmailData = [
            'emailData'   => [
                'job'   =>  $jobArray,
            ],
            'emailSubject'  => 'Request to switch Worker | Broom Service',
            'emailTitle'  => 'Worker switch by admin',
            'emailContent'  => 'Admin has been switch worker to ' . $jobArray['worker']['firstname'] . ' ' . $jobArray['worker']['lastname'] . ' from ' . $otherJobArray['worker']['firstname'] . ' ' . $otherJobArray['worker']['lastname'] . '.'
        ];
        event(new JobNotificationToAdmin($adminEmailData));

        //send notification to client
        $client = $jobArray['client'];
        $worker = $jobArray['worker'];
        $emailData = [
            'emailSubject'  => __('mail.job_common.admin_switch_worker_subject'),
            'emailTitle'  => __('mail.job_common.admin_switch_worker_title'),
            'emailContent'  => __('mail.job_common.admin_switch_worker_content', ['w1' => $jobArray['worker']['firstname'] . ' ' . $jobArray['worker']['lastname'], 'w2' => $otherJobArray['worker']['firstname'] . ' ' . $otherJobArray['worker']['lastname']])
        ];
        event(new JobNotificationToClient($worker, $client, $jobArray, $emailData));

        Notification::create([
            'user_id' => $job->client->id,
            'user_type' => get_class($job->client),
            'type' => NotificationTypeEnum::JOB_SCHEDULE_CHANGE,
            'job_id' => $job->id,
            'status' => 'changed'
        ]);

        return response()->json([
            'message' => "Worker switched successfully",
        ]);
    }

    public function updateJobDone(Request $request, $id)
    {
        $job = Job::with(['order'])->find($id);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found',
            ], 404);
        }

        if ($job->status == JobStatusEnum::CANCEL) {
            return response()->json([
                'message' => 'Job already cancelled'
            ], 403);
        }

        $job->update([
            'is_job_done' => $request->checked
        ]);

        if ($job->is_job_done) {
            $this->updateJobAmount($job->id);

            CreateJobOrder::dispatch($job->id);
        } else {
            if ($job->is_order_generated) {
                $order = $job->order;

                if ($order->status == 'Closed') {
                    return response()->json([
                        'message' => 'Job order is already closed',
                    ], 403);
                }

                $closeDocResponse = $this->cancelICountDocument(
                    $order->order_id,
                    'order',
                    'Creating another order'
                );

                if ($closeDocResponse['status'] != true) {
                    return response()->json([
                        'message' => $closeDocResponse['reason']
                    ], 500);
                }

                $order->update(['status' => 'Cancelled']);

                $order->jobs()->update([
                    'isOrdered' => 'c',
                    'order_id' => NULL,
                    'is_order_generated' => false
                ]);

                event(new ClientOrderCancelled($order->client, $order));
            }
        }

        return response()->json([
            'message' => 'Job has been updated',
        ]);
    }

    public function updateWorkerActualTime(Request $request, $id)
    {
        $job = Job::with('order')->find($id);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found',
            ], 404);
        }

        if ($job->status == JobStatusEnum::CANCEL) {
            return response()->json([
                'message' => 'Job already cancelled'
            ], 403);
        }

        if (
            $job->order &&
            $job->order->status == 'Closed'
        ) {
            return response()->json([
                'message' => 'Job order is already closed',
            ], 403);
        }

        $job->update([
            'actual_time_taken_minutes' => $request->value
        ]);

        return response()->json([
            'message' => 'Job has been updated',
        ]);
    }

    public function saveDiscount(Request $request, $id)
    {
        $job = Job::find($id);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found',
            ], 404);
        }

        if ($job->status == JobStatusEnum::CANCEL) {
            return response()->json([
                'message' => 'Job already cancelled'
            ], 403);
        }

        if ($job->is_paid) {
            return response()->json([
                'message' => 'Job is already paid'
            ], 403);
        }

        $data = $request->all();

        $job->update([
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
        ]);

        $this->updateJobAmount($job->id);

        return response()->json([
            'message' => 'Discount saved successfully'
        ]);
    }

    public function saveExtraAmount(Request $request, $id)
    {
        $job = Job::find($id);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found',
            ], 404);
        }

        if ($job->status == JobStatusEnum::CANCEL) {
            return response()->json([
                'message' => 'Job already cancelled'
            ], 403);
        }

        if ($job->is_paid) {
            return response()->json([
                'message' => 'Job is already paid'
            ], 403);
        }

        $data = $request->all();

        $job->update([
            'extra_amount' => $data['extra_amount'],
        ]);

        $this->updateJobAmount($job->id);

        return response()->json([
            'message' => 'Extra amount saved successfully'
        ]);
    }

    public function getOpenJobAmountByGroup(Request $request, $id)
    {
        $groupID = $request->get('group_id');
        $repeatancy = $request->get('repeatancy');
        $until_date = $request->get('until_date');

        $jobs = Job::query()
            ->whereIn('status', [
                JobStatusEnum::SCHEDULED,
                JobStatusEnum::UNSCHEDULED,
            ])
            ->when($repeatancy == 'until_date' && $until_date, function ($q) use ($until_date) {
                return $q->whereDate('start_date', '<=', $until_date);
            })
            ->when($repeatancy == 'one_time', function ($q) use ($id) {
                return $q->where('id', $id);
            })
            ->where('job_group_id', $groupID)
            ->selectRaw("SUM(total_amount) as total_amount")
            ->first();

        return response()->json([
            'total_amount' => $jobs->total_amount
        ]);
    }
}
