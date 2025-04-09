<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CancellationActionEnum;
use App\Enums\JobStatusEnum;
use App\Enums\LeadStatusEnum;
use App\Enums\OrderPaidStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\ContractStatusEnum;
use App\Events\ClientLeadStatusChanged;
use App\Events\JobShiftChanged;
use App\Events\JobWorkerChanged;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Problems;
use App\Models\Conflict;
use App\Models\Offer;
use App\Models\Client;
use App\Models\Job;
use App\Models\ParentJobs;
use App\Models\ClientPropertyAddress;
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
use App\Models\Discount;
use App\Models\Order;
use App\Traits\PaymentAPI;
use App\Events\JobNotificationToAdmin;
use App\Events\JobNotificationToClient;
use App\Events\JobNotificationToWorker;
use App\Models\Notification;
use Yajra\DataTables\Facades\DataTables;
use App\Jobs\SendUninterestedClientEmail;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Str;


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

    public function showConflicts(Request $request)
    {
        $columns = [
            'id',
            'job_id',
            'worker_id',
            'client_id',
            'conflict_client_id',
            'conflict_job_id',
            'date',
            'shift',
            'hours',
            'created_at',
            'updated_at'
        ];
    
        // Retrieve filter values from the request
        $search = $request->get('search')['value'] ?? null;
        $start_time_filter = $request->get('start_time_filter');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $worker_id = $request->get('worker_id');
        $client_id = $request->get('client_id');
    
        // Get sorting and pagination parameters
        $start = $request->get("start", 0);
        $length = $request->get("length", 10);
        $columnIndex = $request->get('order')[0]['column'] ?? 0;
        $dir = $request->get('order')[0]['dir'] ?? 'asc';
    
        // Query the Conflict model with relationships
        $query = Conflict::with(['job', 'client', 'worker', 'conflictClient']);
    
        // Search functionality
        if ($search) {
            $query->where(function ($query) use ($search, $columns) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', "%{$search}%");
                }
                $query->orWhereHas('worker', function ($q) use ($search) {
                    $q->where('firstname', 'like', "%{$search}%")
                      ->orWhere('lastname', 'like', "%{$search}%");
                });
                $query->orWhereHas('client', function ($q) use ($search) {
                    $q->where('firstname', 'like', "%{$search}%")
                      ->orWhere('lastname', 'like', "%{$search}%");
                });
                $query->orWhereHas('conflictClient', function ($q) use ($search) {
                    $q->where('firstname', 'like', "%{$search}%")
                      ->orWhere('lastname', 'like', "%{$search}%");
                });
                $query->orWhereHas('job', function ($q) use ($search) {
                    $q->where('start_date', 'like', "%{$search}%")
                      ->orWhere('end_date', 'like', "%{$search}%")
                      ->orWhere('shift', 'like', "%{$search}%")
                      ->orWhere('id', 'like', "%{$search}%");
                });
            });
        }
    
        // Apply filters
        $query->when($worker_id, function ($q) use ($worker_id) {
            return $q->where('worker_id', $worker_id);
        })
        // ->when($start_time_filter, function ($q) use ($start_time_filter) {
        //     return $q->where('hours', '>=', $start_time_filter);
        // })
        // ->when($start_date && $end_date, function ($q) use ($start_date, $end_date) {
        //     return $q->whereBetween('date', [$start_date, $end_date]);
        // })
        ->when($client_id, function ($q) use ($client_id) {
            return $q->where('client_id', $client_id);
        });
    
        // Get total record count before pagination
        $totalRecords = $query->count();
    
        // Apply sorting and pagination
        $query->orderBy($columns[$columnIndex] ?? 'id', $dir);
        $conflicts = $query->skip($start)->take($length)->get();
    
        // Format the data
        $conflicts = $conflicts->map(function ($conflict) {
            return [
                'id' => $conflict->id,
                'job_id' => $conflict->job_id,
                'worker_id' => $conflict->worker_id,
                'worker_name' => optional($conflict->worker)->firstname . ' ' . optional($conflict->worker)->lastname,
                'client_id' => $conflict->client_id,
                'client_name' => optional($conflict->client)->firstname . ' ' . optional($conflict->client)->lastname,
                'conflict_client_id' => $conflict->conflict_client_id,
                'conflict_client_name' => optional($conflict->conflictClient)->firstname . ' ' . optional($conflict->conflictClient)->lastname,
                'conflict_job_id' => $conflict->conflict_job_id,
                'date' => $conflict->date,
                'shift' => $conflict->shift,
                'hours' => $conflict->hours,
                'created_at' => $conflict->created_at,
                'updated_at' => $conflict->updated_at,
            ];
        });
    
        // Return response in the required format
        return response()->json([
            'filter' => $request->filter,
            'draw' => intval($request->get('draw')),
            'data' => $conflicts,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
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
                'propertyAddress',
                'hours'
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

    public function approveWorkerJob($wid, $jid)
    {
        $job = Job::query()
            ->with(['worker', 'client', 'jobservice', 'propertyAddress'])
            ->where('worker_id', $wid)
            ->whereNotIn('status', [JobStatusEnum::CANCEL, JobStatusEnum::COMPLETED])
            ->find($jid);

        if (!$job) {
            return response()->json([
                'message' => 'Something went wrong!'
            ], 404);
        }

        if ($job->worker_approved_at) {
            return response()->json([
                'message' => 'Job already approved'
            ], 403);
        }

        $job->update([
            'worker_approved_at' => Carbon::now()->toDateTimeString()
        ]);

        return response()->json([
            'data' => 'Job approved successfully'
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
        $selectedService = $data['selectedService'];

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

        // Fetch the offer
        $offer = $contract->offer;
        if (!$offer) {
            return response()->json([
                'message' => 'Offer not found'
            ], 404);
        }

        // Decode services (if stored as JSON)
        $services = is_string($offer->services) ? json_decode($offer->services, true) : $offer->services;

        // // Locate the service and add is_one_time field
        // foreach ($services as &$service) {
        //     if (($service['service'] == 1) || isset($service['freq_name']) && (in_array($service['freq_name'], ['One Time', 'חד פעמי']))) {
        //         $service['is_one_time'] = true; // Add the field
        //     }
        // }

        // // Save updated services back to the offer
        // $offer->services = json_encode($services);
        // $offer->save();


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
                \Log::info([$mergedContinuousTime]);

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
                $conflictClientId = null;
                $conflictJobId = NULL;
                $conflictCheck = $this->isJobTimeConflicting($mergedContinuousTime, $job_date, $editJob->worker_id, $editJob->id);
                if ($conflictCheck['is_conflicting']) {
                    $status = JobStatusEnum::UNSCHEDULED;
                    $conflictClientId = $conflictCheck['conflict_client_id']; // Extract conflict_client_id
                    $conflictJobId = $conflictCheck['conflict_job_id']; // Extract conflict_job_id
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

                if($status == JobStatusEnum::UNSCHEDULED) {
                    Conflict::create([
                        'job_id' => $conflictJobId,
                        'worker_id' => $editJob->worker_id,
                        'client_id' => $conflictClientId,
                        'conflict_client_id' => $editJob->client_id,
                        'conflict_job_id' => $editJob->id,
                        'job_date' => $editJob->start_date,
                        'shifts' => $editJob->shifts,
                        'hours' => round($minutes / 60, 2)
                    ]);
                }

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

                $editJob->load(['client', 'worker', 'jobservice', 'propertyAddress', 'offer']);

                event(new JobShiftChanged($editJob, $mergedContinuousTime[0]['starting_at']));
            }
        }

        $offerServices = $this->formatServices($selectedService, false);
        // $filtered = Arr::where($offerServices, function ($value, $key) use ($data) {
        //     return $value['service'] == $data['service_id'];
        // });

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
                $conflictClientId = null;
                $conflictJobId = NULL;
                $conflictCheck = $this->isJobTimeConflicting($mergedContinuousTime, $job_date, $workerDate['worker_id']);
                if ($conflictCheck['is_conflicting']) {
                    $status = JobStatusEnum::UNSCHEDULED;
                    $conflictClientId = $conflictCheck['conflict_client_id']; // Extract conflict_client_id
                    $conflictJobId = $conflictCheck['conflict_job_id']; // Extract conflict_job_id
                }

                $start_time = Carbon::parse($mergedContinuousTime[0]['starting_at'])->toTimeString();
                $end_time = Carbon::parse($mergedContinuousTime[count($mergedContinuousTime) - 1]['ending_at'])->toTimeString();

                $job = Job::create([
                    'uuid'          => Str::uuid(),
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
                    'offer_service'    => $offerServices
                ]);

                // Create entry in ParentJobs
                $parentJob = ParentJobs::create([
                    'job_id' => $job->id,
                    'client_id' => $contract->client_id,
                    'worker_id' => $workerDate['worker_id'],
                    'offer_id' => $contract->offer_id,
                    'contract_id' => $contract->id,
                    'schedule'      => $repeat_value,
                    'schedule_id'   => $s_id,
                    'start_date' => $job_date,
                    'next_start_date'   => $next_job_date,
                    'keep_prev_worker'  => isset($data['prevWorker']) ? $data['prevWorker'] : false,
                    'status' => $status, // You can set this according to your needs
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

                if($status == JobStatusEnum::UNSCHEDULED) {
                    Conflict::create([
                        'job_id' => $conflictJobId,
                        'worker_id' => $job->worker_id,
                        'client_id' => $conflictClientId,
                        'conflict_client_id' => $job->client_id,
                        'conflict_job_id' => $job->id,
                        'job_date' => $job->start_date,
                        'shifts' => $job->shifts,
                        'hours' => round($minutes / 60, 2)
                    ]);
                }

                $jobGroupID = $jobGroupID ? $jobGroupID : $job->id;


                $discount = Discount::whereJsonContains('client_ids', $contract->client_id)
                        ->whereJsonContains('service_ids', $s_id)
                        ->whereDate('created_at', '=', $job->start_date)
                        ->where(function ($query) use ($contract) {
                            $query->whereNull('applied_client_ids') 
                                ->orWhereJsonDoesntContain('applied_client_ids', $contract->client_id); 
                        })
                        ->first();

                $job->update([
                    'origin_job_id' => $job->id,
                    'job_group_id' => $jobGroupID,
                    'parent_job_id' => $parentJob->id,
                    'discount_type' => $discount ? $discount->type : null,
                    'discount_value' => $discount ? $discount->value : null,
                ]);

                if ($discount) {
                    $applied = $discount->applied_client_ids ?? [];

                    // Ensure it's an array
                    if (!is_array($applied)) {
                        $applied = json_decode($applied, true);
                    }

                    $applied[] = $contract->client_id;
                    $applied = array_unique($applied);

                    $discount->applied_client_ids = $applied;
                    $discount->save();
                }

                foreach ($mergedContinuousTime as $key => $shift) {
                    $job->workerShifts()->create($shift);
                }

                $this->copyDefaultCommentsToJob($job);

                $job->load(['client', 'worker', 'jobservice', 'propertyAddress', 'offer']);

                // Send notification to client
                $jobData = $job->toArray();
                $clientData = $jobData['client'];
                $workerData = $jobData['worker'];
                $emailData = [
                    'emailSubject'  => __('mail.worker_new_job.subject') . "  " . __('mail.worker_new_job.company'),
                    'emailTitle'  => __('mail.worker_new_job.new_job_assigned'),
                    'emailContent'  => __('mail.worker_new_job.new_job_assigned')
                ];
                \Log::info($emailData);
                event(new JobNotificationToClient($workerData, $clientData, $jobData, $emailData));
                ScheduleNextJobOccurring::dispatch($job->id, null);

            }
        }

        // if ($job->status == JobStatusEnum::SCHEDULED) {
        // }

        $newLeadStatus = $this->getClientLeadStatusBasedOnJobs($client);

        if (!$client->lead_status || $client->lead_status->lead_status != $newLeadStatus) {
            $client->lead_status()->updateOrCreate(
                [],
                ['lead_status' => $newLeadStatus]
            );

            event(new ClientLeadStatusChanged($client, $newLeadStatus));

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
                'propertyAddress',
                'offer'
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
                $conflictClientId = null;
                $conflictJobId = NULL;
                $conflictCheck = $this->isJobTimeConflicting($mergedContinuousTime, $job_date, $editJob->worker_id, $editJob->id);
                if ($conflictCheck['is_conflicting']) {
                    $status = JobStatusEnum::UNSCHEDULED;
                    $conflictClientId = $conflictCheck['conflict_client_id']; // Extract conflict_client_id
                    $conflictJobId = $conflictCheck['conflict_job_id']; // Extract conflict_job_id
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

                if($status == JobStatusEnum::UNSCHEDULED) {
                    Conflict::create([
                        'job_id' => $conflictJobId,
                        'worker_id' => $editJob->worker_id,
                        'client_id' => $conflictClientId,
                        'conflict_client_id' => $editJob->client_id,
                        'conflict_job_id' => $editJob->id,
                        'job_date' => $editJob->start_date,
                        'shifts' => $editJob->shifts,
                        'hours' => round($minutes / 60, 2)
                    ]);
                }

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

                $editJob->load(['client', 'worker', 'jobservice', 'propertyAddress', 'offer']);

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
        $conflictClientId = null;
        $conflictJobId = NULL;
        $conflictCheck = $this->isJobTimeConflicting($mergedContinuousTime, $job_date, $data['worker']['worker_id']);
        if ($conflictCheck['is_conflicting']) {
            $status = JobStatusEnum::UNSCHEDULED;
            $conflictClientId = $conflictCheck['conflict_client_id']; // Extract conflict_client_id
            $conflictJobId = $conflictCheck['conflict_job_id']; // Extract conflict_job_id

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

        if($status == JobStatusEnum::UNSCHEDULED) {
            Conflict::create([
                'job_id' => $conflictJobId,
                'worker_id' => $job->worker_id,
                'client_id' => $conflictClientId,
                'conflict_client_id' => $job->client_id,
                'conflict_job_id' => $job->id,
                'job_date' => $job->start_date,
                'shifts' => $job->shifts,
                'hours' => round($minutes / 60, 2)
            ]);
        }

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

        $feePercentage = $request->fee;
        $feeAmount = ($feePercentage / 100) * $job->total_amount;

        $jobData['cancellation_fee_percentage'] = $feePercentage;
        $jobData['cancellation_fee_amount'] = $feeAmount;

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

            event(new ClientLeadStatusChanged($client, $newLeadStatus));

        }

        Notification::create([
            'user_id' => $job->client->id,
            'user_type' => get_class($job->client),
            'type' => NotificationTypeEnum::JOB_SCHEDULE_CHANGE,
            'job_id' => $job->id,
            'status' => 'changed'
        ]);

        $job->load(['client', 'worker', 'jobservice', 'propertyAddress', 'offer']);

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
                $conflictClientId = null;
                $conflictJobId = NULL;
                $conflictCheck = $this->isJobTimeConflicting($mergedContinuousTime, $job_date, $editJob->worker_id, $editJob->id);
                if ($conflictCheck['is_conflicting']) {
                    $status = JobStatusEnum::UNSCHEDULED;
                    $conflictClientId = $conflictCheck['conflict_client_id']; // Extract conflict_client_id
                    $conflictJobId = $conflictCheck['conflict_job_id']; // Extract conflict_job_id
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

                if($status == JobStatusEnum::UNSCHEDULED) {
                    Conflict::create([
                        'job_id' => $conflictJobId,
                        'worker_id' => $editJob->worker_id,
                        'client_id' => $conflictClientId,
                        'conflict_client_id' => $editJob->client_id,
                        'conflict_job_id' => $editJob->id,
                        'job_date' => $editJob->start_date,
                        'shifts' => $editJob->shifts,
                        'hours' => round($minutes / 60, 2)
                    ]);
                }

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

                $editJob->load(['client', 'worker', 'jobservice', 'propertyAddress', 'offer']);

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
        $conflictClientId = null;
        $conflictJobId = NULL;
        $conflictCheck = $this->isJobTimeConflicting($mergedContinuousTime, $job_date, $job->worker_id, $job->id);
        if ($conflictCheck['is_conflicting']) {
            $status = JobStatusEnum::UNSCHEDULED;
            $conflictClientId = $conflictCheck['conflict_client_id']; // Extract conflict_client_id
            $conflictJobId = $conflictCheck['conflict_job_id']; // Extract conflict_job_id
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

        if($status == JobStatusEnum::UNSCHEDULED) {
            Conflict::create([
                'job_id' => $conflictJobId,
                'worker_id' => $job->worker_id,
                'client_id' => $conflictClientId,
                'conflict_client_id' => $job->client_id,
                'conflict_job_id' => $job->id,
                'job_date' => $job->start_date,
                'shifts' => $job->shifts,
                'hours' => round($minutes / 60, 2)
            ]);
        }

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

            event(new ClientLeadStatusChanged($client, $newLeadStatus));

        }

        Notification::create([
            'user_id' => $job->client->id,
            'user_type' => get_class($job->client),
            'type' => NotificationTypeEnum::JOB_SCHEDULE_CHANGE,
            'job_id' => $job->id,
            'status' => 'changed'
        ]);

        $job->load(['client', 'worker', 'jobservice', 'propertyAddress', 'offer']);

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
            $job->workerShifts()->delete();

            CreateJobOrder::dispatch($job->id);
            ScheduleNextJobOccurring::dispatch($job->id,null);


            $manageTime = ManageTime::first();
            $workingWeekDays = json_decode($manageTime->days);
            $preferredWeekDay = strtolower(Carbon::parse($job->start_date)->format('l'));

            $next_job_date = $this->scheduleNextJobDate($job->start_date, $job->schedule, $preferredWeekDay, $workingWeekDays);

            // Find the last job based on the values obtained
            $lastJob = Job::where('client_id', $job->client_id)
            ->where('address_id', $job->address_id)
            ->where('worker_id', $job->worker_id)
            ->where('contract_id', $job->contract_id)
            ->where('offer_id', $job->offer_id)
            ->orderBy('start_date', 'desc')
            ->first();

            if ($lastJob && ($repeatancy == 'until_date' && $until_date >= $lastJob->start_date) && $key == $lastKey) {
                ScheduleNextJobOccurring::dispatch($lastJob->id, null);
            } elseif ($repeatancy != 'forever' && ($repeatancy == "until_date" && $until_date < $lastJob->start_date)) {
                \Log::info('creating new job');


                // Calculate next job date
                $last_job_next_job_date = $this->scheduleNextJobDate(
                    $lastJob->next_start_date, 
                    $job->schedule, 
                    $preferredWeekDay, 
                    $workingWeekDays
                );
            
                // Convert offer_service to an array safely
                $minutes = 0;
                $selectedService = $lastJob->offer_service ?? [];
                
                if (isset($selectedService['type'])) {
                    if ($selectedService['type'] == 'hourly') {
                        $hours = ($minutes / 60);
                        $total_amount = ($selectedService['rateperhour'] ?? 0) * $hours;
                    } elseif ($selectedService['type'] == 'squaremeter') {
                        $total_amount = ($selectedService['ratepersquaremeter'] ?? 0) * ($selectedService['totalsquaremeter'] ?? 0);
                    } else {
                        $total_amount = $selectedService['fixed_price'] ?? 0;
                    }
                } else {
                    $total_amount = 0;
                }
                
                $mergedContinuousTime = [
                    [
                        "starting_at" => $lastJob->start_date . ' ' . $lastJob->start_time,
                        "ending_at" => $lastJob->start_date . ' ' . $lastJob->end_time
                    ]
                ];
                
                foreach ($mergedContinuousTime as $slot) {
                    $start = Carbon::parse($slot['starting_at']);
                    $end = Carbon::parse($slot['ending_at']);
                    $interval = 15; // in minutes
                    while ($start < $end) {
                        $start->addMinutes($interval);
                        $minutes += $interval;
                    }
                }


                $status = JobStatusEnum::SCHEDULED;
                $conflictClientId = null;
                $conflictJobId = NULL;
                $conflictCheck = $this->isJobTimeConflicting($mergedContinuousTime, $lastJob->start_date, $lastJob->worker_id);
                if ($conflictCheck['is_conflicting']) {
                    $status = JobStatusEnum::UNSCHEDULED;
                    $conflictClientId = $conflictCheck['conflict_client_id']; // Extract conflict_client_id
                    $conflictJobId = $conflictCheck['conflict_job_id']; // Extract conflict_job_id
                }
            
                // Create new job
                $newjob = Job::create([
                    'uuid'              => Str::uuid(),
                    'worker_id'         => $lastJob->worker_id,
                    'client_id'         => $lastJob->client_id,
                    'contract_id'       => $lastJob->contract_id,
                    'offer_id'          => $lastJob->offer_id,
                    'parent_job_id'     => $lastJob->parent_job_id,
                    'start_date'        => Carbon::parse($lastJob->next_start_date)->format('Y-m-d'),
                    'start_time'        => $lastJob->start_time,
                    'end_time'          => $lastJob->end_time,
                    'shifts'            => $lastJob->shifts,
                    'schedule'          => $lastJob->schedule,
                    'schedule_id'       => $lastJob->schedule_id,
                    'status'            => $status,
                    'subtotal_amount'   => $total_amount,
                    'total_amount'      => $total_amount,
                    'next_start_date'   => $this->scheduleNextJobDate(
                                            $lastJob->next_start_date, 
                                            $job->schedule, 
                                            $preferredWeekDay, 
                                            $workingWeekDays
                                        ),        
                    'address_id'        => $selectedService['address']['id'] ?? null,
                    'keep_prev_worker'  => $lastJob->keep_prev_worker,
                    'original_worker_id'=> $lastJob->worker_id,
                    'original_shifts'   => $lastJob->shifts,
                    'offer_service'     => json_encode($selectedService),
                ]);

                $jobser = JobService::create([
                    'job_id'            => $newjob->id,
                    'service_id'        => $selectedService['service'],
                    'name'              => $selectedService['name'],
                    'heb_name'          => $selectedService['heb_name'],
                    'duration_minutes'  => $minutes,
                    'freq_name'         => $selectedService['freq_name'],
                    'cycle'             => $selectedService['cycle'],
                    'period'            => $selectedService['period'],
                    'total'             => $total_amount,
                    'config'            => [
                        'cycle'             => $selectedService['cycle'],
                        'period'            => $selectedService['period'],
                        'preferred_weekday' => $preferredWeekDay
                    ]
                ]);
            
                if($status == JobStatusEnum::UNSCHEDULED) {
                    Conflict::create([
                        'job_id' => $conflictJobId,
                        'worker_id' => $newjob->worker_id,
                        'client_id' => $conflictClientId,
                        'conflict_client_id' => $newjob->client_id,
                        'conflict_job_id' => $newjob->id,
                        'date' => $newjob->start_date,
                        'shift' => $newjob->shifts,
                        'hours' => round($minutes / 60, 2)
                    ]);
                }

                // Assign shifts to new job
                foreach ($mergedContinuousTime as $shift) {
                    $newjob->workerShifts()->create([
                        'start_time' => $shift['starting_at'],
                        'end_time' => $shift['ending_at']
                    ]);
                }

            }

            if ($job->offer && $job->offer->services) {
                $services = json_decode($job->offer->services, true); 
    
                // Remove `is_one_time` field if it exists in any service
                $services = array_map(function ($service) {
                    if (isset($service['is_one_time'])) {
                        unset($service['is_one_time']);
                    }
                    return $service;
                }, $services);
    
                // Save the updated services back to the offer
                $job->offer->services = json_encode($services);
                $job->offer->save();
            }

            $offer = $job->offer;
            $offerArr = $offer->toArray();
                $services = json_decode($offerArr['services']);
                
                if (isset($services)) {
                    $s_names = '';
                    $s_templates_names = '';
                    foreach ($services as $k => $service) {
                        if ($k != count($services) - 1 && $service->template != "others") {
                            $s_names .= $service->name . ", ";
                            $s_templates_names .= $service->template . ", ";
                        } else if ($service->template == "others") {
                            if ($k != count($services) - 1) {
                                $s_names .= $service->other_title . ", ";
                                $s_templates_names .= $service->template . ", ";
                            } else {
                                $s_names .= $service->other_title;
                                $s_templates_names .= $service->template;
                            }
                        } else {
                            $s_names .= $service->name;
                            $s_templates_names .= $service->template;
                        }
                    }
                }
                $offerArr['services'] = $services;
                $offerArr['service_names'] = $s_names;
                $offerArr['service_template_names'] = $s_templates_names;

                $property = null;

                $addressId = $services[0]->address;
                if (isset($addressId)) {
                    $address = ClientPropertyAddress::find($addressId);
                    if (isset($address)) {
                        $property = $address;
                    }
                }

            $data = array(
                'by'         => 'admin',
                'email'      => $admin->email??"",
                'admin'      => $admin?->toArray()??[],
                'job'        => $job?->toArray()??[],
                'offer'      => $offerArr ?? null,
                'property'   => $property ?? null
            );

            if (isset($job->client) && !empty($job->client->phone)) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::CLIENT_JOB_STATUS_NOTIFICATION,
                    "notificationData" => $data
                ]));
            }
            App::setLocale('en');

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

            //send notification to worker
            $job = $job->toArray();
            $worker = $job['worker'];
            $emailData = [
                'by'            => $data['by']
            ];
            event(new JobNotificationToWorker($worker, $job, $emailData));
        }

        return response()->json([
            'msg' => 'Job cancelled succesfully!'
        ]);
    }


    public function cancelJobByGoogleSheet(Request $request, $id)
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
        $until_date = $request->get('untilDate');
    
        $jobs = Job::query()
            ->with(['worker', 'offer', 'client', 'jobservice', 'propertyAddress'])
            ->whereIn('status', [
                JobStatusEnum::SCHEDULED,
                JobStatusEnum::UNSCHEDULED,
            ])
            ->when($repeatancy == 'until_date' && !empty($until_date), function ($q) use ($until_date) {
                return $q->whereDate('start_date', '<=', Carbon::parse($until_date)->format('Y-m-d'));
            })
            ->when($repeatancy == 'one_time', function ($q) use ($id) {
                return $q->where('id', $id);
            })
            ->where('job_group_id', $job->job_group_id)
            ->get();
    
        $admin = Admin::where('role', 'admin')->first();
    
        // Store all cancelled job IDs
        $cancelledJobIds = [];
    
        foreach ($jobs as $key => $job) {
            $feePercentage = $request->fee;
            $feeAmount = ($feePercentage / 100) * $job->total_amount;
    
            JobCancellationFee::create([
                'job_id' => $job->id,
                'job_group_id' => $job->job_group_id,
                'cancellation_fee_percentage' => $feePercentage,
                'cancellation_fee_amount' => $feeAmount,
                'cancelled_user_role' => 'admin',
                'cancelled_by' => $admin->id,
                'action' => CancellationActionEnum::CANCELLATION,
                'duration' => $repeatancy,
                'until_date' => $until_date,
            ]);
    
            $job->update([
                'status' => JobStatusEnum::CANCEL,
                'cancellation_fee_percentage' => $feePercentage,
                'cancellation_fee_amount' => $feeAmount,
                'cancelled_by_role' => 'admin',
                'cancelled_by' => $admin->id,
                'cancelled_at' => now(),
                'cancelled_for' => $repeatancy,
                'cancel_until_date' => $until_date,
            ]);
    
            // Add the cancelled job ID to the array
            $cancelledJobIds[] = $job->id;
    
            CreateJobOrder::dispatch($job->id);
            ScheduleNextJobOccurring::dispatch($job->id, null);
    
            if ($job->offer && $job->offer->services) {
                $services = json_decode($job->offer->services, true); 
    
                // Remove `is_one_time` field if it exists in any service
                $services = array_map(function ($service) {
                    if (isset($service['is_one_time'])) {
                        unset($service['is_one_time']);
                    }
                    return $service;
                }, $services);
    
                // Save the updated services back to the offer
                $job->offer->services = json_encode($services);
                $job->offer->save();
            }
    
            $offer = $job->offer;
            $offerArr = $offer->toArray();
            $services = json_decode($offerArr['services']);
    
            if (isset($services)) {
                $s_names = '';
                $s_templates_names = '';
                foreach ($services as $k => $service) {
                    if ($k != count($services) - 1 && $service->template != "others") {
                        $s_names .= $service->name . ", ";
                        $s_templates_names .= $service->template . ", ";
                    } else if ($service->template == "others") {
                        if ($k != count($services) - 1) {
                            $s_names .= $service->other_title . ", ";
                            $s_templates_names .= $service->template . ", ";
                        } else {
                            $s_names .= $service->other_title;
                            $s_templates_names .= $service->template;
                        }
                    } else {
                        $s_names .= $service->name;
                        $s_templates_names .= $service->template;
                    }
                }
            }
    
            $offerArr['services'] = $services;
            $offerArr['service_names'] = $s_names;
            $offerArr['service_template_names'] = $s_templates_names;
    
            $property = null;
    
            $addressId = $services[0]->address ?? null;
            if (isset($addressId)) {
                $address = ClientPropertyAddress::find($addressId);
                if (isset($address)) {
                    $property = $address;
                }
            }
    
            $data = array(
                'by'         => 'admin',
                'email'      => $admin->email ?? "",
                'admin'      => $admin?->toArray() ?? [],
                'job'        => $job?->toArray() ?? [],
                'offer'      => $offerArr ?? null,
                'property'   => $property ?? null
            );
    
            if (isset($job->client) && !empty($job->client->phone)) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::CLIENT_JOB_STATUS_NOTIFICATION,
                    "notificationData" => $data
                ]));
            }
    
            App::setLocale('en');
    
            $worker = $job['worker'] ?? null;
            $emailData = [
                'by' => $data['by']
            ];
    
            if ($worker) {
                event(new JobNotificationToWorker($worker, $job, $emailData));
            }
        }
    
        return response()->json([
            'msg' => 'Job cancelled successfully!',
            'cancelled_job_ids' => $cancelledJobIds
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

    public function workersToSwitch($id)
    {
        // Find the job with jobservice relationship
        $job = Job::with(['jobservice'])->find($id);
    
        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }
    
        $jobService = $job->jobservice;
    
        if (!$jobService) {
            return response()->json(['message' => 'Job service not found'], 404);
        }
    
        // Get job details
        $startDate = $job->start_date;
        $startStime = $job->start_time;
        $endTime = $job->end_time;
    
        $jobWorkerId = $job->worker_id;
        $serviceId = $jobService->service_id;
    
        // Check address conditions
        $address = ClientPropertyAddress::find($job->address_id);
     
        $workers = User::where('id', '!=', $jobWorkerId)
            ->whereJsonContains('skill', $serviceId) 
            ->whereHas('availabilities', function ($query) use ($startDate) {
                $query->where('date', $startDate); 
            })
            ->whereHas('jobs', function ($query) use ($startDate, $startStime, $endTime) {
                $query->where('start_date', $startDate) 
                ->where('start_time', '=', $startStime)
                ->where('end_time', '=', $endTime);
                    // ->where(function ($timeQuery) use ($startStime, $endTime) {
                    //     $timeQuery->whereBetween('start_time', [$startStime, $endTime]) 
                    //         ->orWhereBetween('end_time', [$startStime, $endTime]) 
                    //         ->orWhere(function ($innerQuery) use ($startStime, $endTime) {
                    //             $innerQuery->where('start_time', '<=', $startStime) 
                    //                 ->where('end_time', '>=', $endTime);
                    //         });
                    // });
            })
            ->when($address, function ($query) use ($address) {
                // Add condition to exclude users afraid of cats/dogs if the address has cats/dogs
                if ($address->is_cat_avail) {
                    $query->where('is_afraid_by_cat', false);
                }
                if ($address->is_dog_avail) {
                    $query->where('is_afraid_by_dog', false);
                }
            })
            ->get();
    
        // Return filtered workers
        return response()->json([
            'workers' => $workers,
        ]);
    }
    

    // public function workersToSwitch(Request $request, $id)
    // {
    //     $job = Job::find($id);
    //     $prefer_type = $request->get('prefer_type');

    //     if (!$job) {
    //         return response()->json([
    //             'message' => 'Job not found',
    //         ], 404);
    //     }

    //     if (
    //         $job->status == JobStatusEnum::COMPLETED ||
    //         $job->is_job_done
    //     ) {
    //         return response()->json([
    //             'message' => 'Job already completed',
    //         ], 403);
    //     }

    //     if ($job->status == JobStatusEnum::CANCEL) {
    //         return response()->json([
    //             'message' => 'Job already cancelled',
    //         ], 403);
    //     }

    //     $workers = User::query()
    //         ->whereIn('id', function ($q) use ($job) {
    //             $q->from('jobs')
    //                 ->whereNotIn('status', [
    //                     JobStatusEnum::COMPLETED,
    //                     JobStatusEnum::CANCEL
    //                 ])
    //                 ->where('worker_id', '!=', $job->worker_id)
    //                 ->where('start_date', $job->start_date)
    //                 ->where('shifts', $job->shifts)
    //                 ->select('worker_id');
    //         })
    //         ->when(in_array($prefer_type, ['male', 'female']), function ($q) use ($prefer_type) {
    //             return $q->where('gender', $prefer_type);
    //         })
    //         ->get(['id', 'firstname', 'lastname']);

    //     return response()->json([
    //         'data' => $workers,
    //     ]);
    // }
    

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

        $job->load(['client', 'worker', 'jobservice', 'propertyAddress', 'offer']);
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
            sendJobWANotification($emailData);
            // Mail::send('/Mails/NewJobMail', $emailData, function ($messages) use ($emailData) {
            //     $messages->to($emailData['email']);
            //     $sub = __('mail.worker_new_job.subject') . "  " . __('mail.worker_new_job.company');
            //     $messages->subject($sub);
            // });
        }

        $otherWorkerJob->load(['client', 'worker', 'jobservice', 'propertyAddress', 'offer']);
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
            sendJobWANotification($emailData);
        }

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


    public function switchWorkerInGoogleSheet(Request $request, $id)
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

        $worker = User::where('status', 1)
        ->whereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ['%' . trim($request->worker) . '%'])
        ->first();

        $hasWorkerJob = Job::where('worker_id', $worker->id)
            ->where('start_date', $job->start_date)
            ->where('shifts', $job->shifts)
            ->first();

        if ($hasWorkerJob) {
            return response()->json([
                'message' => "Worker already has job for this date and shift",
            ], 403);
        }


        if ($data['repeatancy'] == 'one_time') {

            $job->worker_id = $worker->id;
            $job->previous_worker_after = null;
            $job->save();

        } else if ($data['repeatancy'] == 'until_date') {

           $jobs = Job::where('worker_id', $job->worker_id)
                ->where('start_date', '<=', $data['untilDate'])
                ->where('client_id', $job->client_id)
                ->get();

            foreach ($jobs as $job) {   
                $job->worker_id = $worker->id;
                $job->previous_worker_id = $job->worker_id;
                $job->previous_worker_after = $data['untilDate'];
                $job->save();
            }
            // $job->previous_worker_after = $data['untilDate'];

        } else if ($data['repeatancy'] == 'forever') {

            $jobs = Job::where('worker_id', $job->worker_id)
            ->where('start_date', '>=', $job->start_date)
            ->where('client_id', $job->client_id)
            ->get();

            foreach ($jobs as $job) {
                $job->worker_id = $worker->id;
                $job->previous_worker_id = $job->worker_id;
                $job->previous_worker_after = null;
                $job->save();

            }            
        }

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
            $job->status = JobStatusEnum::COMPLETED;
            $this->updateJobAmount($job->id);
            $todayNextJob = Job::where('worker_id', $job->worker_id)
                ->with(['worker', 'client', 'jobservice', 'propertyAddress'])
                ->whereDate('start_date', now())
                ->whereNotIn('status', [JobStatusEnum::COMPLETED, JobStatusEnum::CANCEL])
                ->whereRaw("STR_TO_DATE(start_time, '%H:%i:%s') > ?", [now()->format('H:i:s')])
                ->first();

            if($todayNextJob) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::WORKER_NOTIFY_FOR_NEXT_JOB_ON_COMPLETE_JOB,
                    "notificationData" => [
                        'job' => $todayNextJob->toArray(),
                        'client' => $todayNextJob->client->toArray(),
                        'worker' => $todayNextJob->worker->toArray(),
                    ]
                ]));
            } else {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::WORKER_NOTIFY_FINAL_NOTIFICATION_OF_DAY,
                    "notificationData" => [
                        'job' => $job->toArray(),
                        'client' => $job->client->toArray(),
                        'worker' => $job->worker->toArray(),
                    ]
                ]));
            }
            $job->save();
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


    public function updateJobDoneByGoogleSheet(Request $request, $id)
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
            $job->status = JobStatusEnum::COMPLETED;
            $this->updateJobAmount($job->id);
            // $todayNextJob = Job::where('worker_id', $job->worker_id)
            //     ->with(['worker', 'client', 'jobservice', 'propertyAddress'])
            //     ->whereDate('start_date', now())
            //     ->whereNotIn('status', [JobStatusEnum::COMPLETED, JobStatusEnum::CANCEL])
            //     ->whereRaw("STR_TO_DATE(start_time, '%H:%i:%s') > ?", [now()->format('H:i:s')])
            //     ->first();

            // if($todayNextJob) {
            //     event(new WhatsappNotificationEvent([
            //         "type" => WhatsappMessageTemplateEnum::WORKER_NOTIFY_FOR_NEXT_JOB_ON_COMPLETE_JOB,
            //         "notificationData" => [
            //             'job' => $todayNextJob->toArray(),
            //             'client' => $todayNextJob->client->toArray(),
            //             'worker' => $todayNextJob->worker->toArray(),
            //         ]
            //     ]));
            // } else {
            //     event(new WhatsappNotificationEvent([
            //         "type" => WhatsappMessageTemplateEnum::WORKER_NOTIFY_FINAL_NOTIFICATION_OF_DAY,
            //         "notificationData" => [
            //             'job' => $job->toArray(),
            //             'client' => $job->client->toArray(),
            //             'worker' => $job->worker->toArray(),
            //         ]
            //     ]));
            // }
            $job->save();
            CreateJobOrder::dispatch($job->id)->onConnection('sync');
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
        $job->refresh();
        return response()->json([
            'message' => 'Job has been updated',
            'order' => $job->order ?? null
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

    public function updateWorkerActualTimeInGoogleSheet(Request $request, $id)
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
            'actual_time_taken_minutes' => $request->actualTimeInMinutes
        ]);

        return response()->json([
            'message' => 'Job actual time has been updated',
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
            'extra_amount_type' => $data['extra_amount_type'],
            'extra_amount_value' => $data['extra_amount_value'],
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

    public function setJobOpeningTimestamp(Request $request)
    {
        $rData = $request->all();
        try {
            $job = Job::query()
                ->with(['worker', 'client', 'jobservice', 'propertyAddress'])
                ->where('worker_id', $rData['worker_id'])
                ->whereNotIn('status', [JobStatusEnum::CANCEL, JobStatusEnum::COMPLETED])
                ->find($rData['job_id']);

            if (!$job) {
                return response()->json([
                    'message' => 'Something went wrong!'
                ], 404);
            }

            if ($job->job_opening_timestamp) {
                return response()->json([
                    'message' => 'Worker already leave for work'
                ], 403);
            }

            $job->update([
                'job_opening_timestamp' => Carbon::now()->toDateTimeString()
            ]);

            Notification::create([
                'user_id' => $job->client->id,
                'user_type' => get_class($job->client),
                'type' => NotificationTypeEnum::OPENING_JOB,
                'job_id' => $job->id,
                'status' => 'going to start'
            ]);

            App::setLocale('en');
            $job->load(['client', 'worker', 'jobservice', 'propertyAddress'])->toArray();

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::WORKER_NOTIFY_AFTER_CONFIRMING_ON_MY_WAY,
                "notificationData" => array(
                    'worker'     => $job->worker->toArray(),
                    'client'     => $job->client->toArray(),
                    'job'        => $job->toArray(),
                )
            ]));
            return response()->json([
                'message' => 'Job opening time has been updated!'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Something went wrong!'
            ]);
        }
    }

    public function JobStartTime(Request $request)
    {
        $data = $request->all();
        $job = Job::find($data['job_id']);

        $time = JobHours::query()
            ->where('worker_id', $data['worker_id'])
            ->where('job_id', $data['job_id'])
            ->whereNull('end_time')
            ->first();

        if ($time) {
            return response()->json([
                'message' => 'End timer',
            ], 404);
        }

        $currentDateTime = now()->toDateTimeString();

        if ($job->status != JobStatusEnum::PROGRESS) {
            $job->status = JobStatusEnum::PROGRESS;
            $job->save();
            //send notification to worker
            $job->load(['client', 'worker', 'jobservice', 'propertyAddress', 'offer']);
            $jobData = $job->toArray();
            $worker = $jobData['worker'];

            $emailData = [
                'by' => 'admin',
            ];

            event(new JobNotificationToWorker($worker, $jobData, $emailData));
        }

        JobHours::create([
            'job_id' => $job->id,
            'worker_id' => $data['worker_id'],
            'start_time' => $currentDateTime,
        ]);

        return response()->json([
            'message' => 'Updated Successfully',
        ]);
    }

    public function getPendingJobsAndPayment($id){
        try {
            $jobs = Job::where("client_id", $id)
                    ->whereNotIn("status", [JobStatusEnum::CANCEL, JobStatusEnum::COMPLETED, JobStatusEnum::PROGRESS])
                    ->get();
                    

            $orders = Order::where("client_id", $id)
                    ->where("paid_status", '!=' ,OrderPaidStatusEnum::PAID)
                    ->where("status", 'Open')
                    ->get();

            return response()->json([
                'jobs' => $jobs ?? [],
                'orders' => $orders ?? []
            ]);

        } catch (\Throwable $th) {
            throw $th;
            // return response()->json([
            //     'message' => 'Something went wrong!'
            // ]);
        }
    }

    public function CancelPendingJobsAndPayment(Request $request, $id){
        try {
            $jobs = Job::where("client_id", $id)
                ->whereNotIn("status", [JobStatusEnum::CANCEL, JobStatusEnum::COMPLETED, JobStatusEnum::PROGRESS])
                ->get();
            
            if (!$jobs) {
                return response()->json([
                    'message' => 'No pending jobs found!'
                ]);
            }
            
            $orders = Order::where("client_id", $id)
                    ->where("paid_status", '!=' ,OrderPaidStatusEnum::PAID)
                    ->where("status", 'Open')
                    ->get();

            foreach ($orders as $key => $order) {
                $order->update(['status' => 'Closed']);
            }

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
                    'duration' => 'forever',
                ]);
    
                $job->update([
                    'status' => JobStatusEnum::CANCEL,
                    'cancellation_fee_percentage' => $feePercentage,
                    'cancellation_fee_amount' => $feeAmount,
                    'cancelled_by_role' => 'admin',
                    'cancelled_by' => Auth::user()->id,
                    'cancelled_at' => now(),
                    'cancelled_for' => 'forever',
                ]);
    
                CreateJobOrder::dispatch($job->id);
                ScheduleNextJobOccurring::dispatch($job->id,null);
            }

            return response()->json([
                'message' => 'Cancelled Successfully'
            ]);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function extendWorkerJobTime(Request $request){
        $data = $request->all();
        $job = Job::find($data['job_id']);
    }

    public function makeJobInGoogleSheet(Request $request)
    {
        $data = null;
        $date = null;
        $currentDateObj = null;
        $weekDays = []; // Initialize array
        $jobArray = [];

        $row = $request->all();

        if (isset($row['frequency'])) {
            $data = $row;

            // Decode JSON 'values' string
            $row = json_decode($data['values'], true);

            // Fix parsing issue with date
            $cleanDate = substr($data["date"], 4, 20); // Extracts "Feb 20 2025 00:00:00"
            $givenDate = Carbon::parse($cleanDate);
            \Log::info($givenDate);
            
            $givenDay = $givenDate->format('l'); // Get day name (e.g., "Monday")

            // Add the given date itself to weekDays
            $weekDays[$givenDay] = $givenDate->format('Y-m-d');

            // Get selected weekdays array
            $selectedWeekDays = $data['selectedWeekDays'] ?? [];

            foreach ($selectedWeekDays as $day) {
                // Convert day name to Carbon day number
                $dayNumber = Carbon::parse($day)->dayOfWeek;
                $currentDayNumber = $givenDate->dayOfWeek;

                // If the selected day is today or later in the same week, return that date
                if ($currentDayNumber <= $dayNumber) {
                    $weekDays[$day] = $givenDate->copy()->next($day)->format('Y-m-d');
                } else {
                    // Otherwise, return the next occurrence in the following week
                    $weekDays[$day] = $givenDate->copy()->next($day)->format('Y-m-d');
                }
            }
        } else {
            $date = $this->convertDate($row["date"]);
            $currentDateObj = Carbon::parse($date); // Current date
            $day = $currentDateObj->format('l');
            $weekDays[$day] = $date;
        }

        try {
            foreach($weekDays as $day => $currentDate){
                $clientId = null;
                if (strpos(trim($row[2]), '#') === 0) {
                    $clientId = substr(trim($row[2]), 1);
                }
                $offerId = $row[3] ?? null;
                $selectedWorker = $row[10] ?? null;
                $shift = "";
                $buisnessHour = $row[11] ?? null;
                $ServiceName = $row[13] ?? null;
                $properHours = $row[14] ?? null;
                $frequencyName = $row[17] ?? null;
                $type = $row[24] ? (trim($row[24]) == "f" ? "fixed" : "hourly") : "";
    
                $startTime = null;
                $endTime = null;

                $currentDateObj = Carbon::parse($currentDate); // Current date
                $day = $currentDateObj->format('l');
    
                $offer = Offer::with('service')->where('id', $offerId)->first();
                $client = Client::where('id', $clientId)->first();

                $contract = Contract::where('client_id', $clientId)
                    ->where('offer_id', $offerId)
                    ->where('status', ContractStatusEnum::VERIFIED)
                    ->first();

                $worker = User::where('status', 1)
                ->whereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ['%' . trim($selectedWorker) . '%'])
                ->first();

                $service = Services::where('heb_name', $ServiceName)
                ->orWhere('name', $ServiceName)                
                ->first();

                $serviceId = $service->id;

                $selectedFrequency = ServiceSchedule::where('name_heb', $frequencyName)
                ->orWhere('name', $frequencyName)
                ->first();

                $services = json_decode($offer->services, true); // Convert JSON to PHP array

                $selectedService = collect($services)->first(function ($service) use ($serviceId, $selectedFrequency, $type) {
                    return $service['service'] == $serviceId && $service['frequency'] == $selectedFrequency->id && $service['type'] == $type;
                });
    
                if ($offer) {
    
                    $jobData = Job::where('offer_id', $offer->id)
                            ->where('start_date', $currentDate)
                            ->where('client_id', $client->id)
                            ->whereHas('contract', function ($q) {
                                $q->where('status', 'verified');                  
                            })
                            ->whereHas('offer', function ($q) use ($selectedFrequency, $serviceId) {
                                $q->whereRaw("
                                    EXISTS (
                                        SELECT 1 
                                        FROM JSON_TABLE(offers.services, '$[*]' 
                                            COLUMNS (
                                                service INT PATH '$.service',
                                                frequency INT PATH '$.frequency'
                                            )
                                        ) AS services_table
                                        WHERE services_table.service = ? 
                                        AND services_table.frequency = ?
                                    )
                                ", [$serviceId, $selectedFrequency->id]);
                            })
                            ->first();
                
                        if ($jobData) {
                            return response()->json([
                                $jobData->id => $jobData,
                                'message' => 'Job already exists.',
                            ]);
                        }
    
                        if($client->lng == 'en') {
                            switch (trim($buisnessHour)) {
                                case 'יום':
                                case 'בוקר':
                                case '7 בבוקר':
                                case 'בוקר 11':
                                case 'בוקר מוקדם':
                                case 'בוקר 6':
                                    $shift = "Morning";
                                    break;
    
                                case 'צהריים':
                                case 'צהריים 14':
                                    $shift = "Noon";
                                    break;
    
                                case 'אחהצ':
                                case 'אחה״צ':
                                case 'ערב':
                                case 'אחר״צ':
                                    $shift = "After noon";
                                    break;
    
                                default:
                                    $shift = $row[9];
                                    break;
                            }
                        } else {
                            switch (trim($buisnessHour)) {
                                case 'יום':
                                case 'בוקר':
                                case '7 בבוקר':
                                case 'בוקר 11':
                                case 'בוקר מוקדם':
                                case 'בוקר 6':
                                    $shift = "בוקר";
                                    break;
    
                                case 'צהריים':
                                case 'צהריים 14':
                                    $shift = 'צהריים';
                                    break;
    
                                case 'אחהצ':
                                case 'אחה״צ':
                                case 'ערב':
                                case 'אחר״צ':
                                    $shift = "אחה״צ";
                                    break;
    
    
                                default:
                                    $shift = $buisnessHour;
                                    break;
                            }
                            switch ($day) {
                                case 'Sunday':
                                    $day = "ראשון";
                                    break;
                                case 'Monday':
                                    $day = "שני";
                                    break;
                                case 'Tuesday':
                                    $day = "שלישי";
                                    break;
                                case 'Wednesday':
                                    $day = "רביעי";
                                    break;
                                case 'Thursday':
                                    $day = "חמישי";
                                    break;
                                case 'Friday':
                                    $day = "שישי";
                                    break;
                                case 'Saturday':
                                    $day = "שבת";
                                    break;
                            }
                        }
    
                        if ($worker) {
                            // Check if the worker has a job for the given date
                            $hasJob = $worker->jobs()->where('start_date', $currentDate)->get();
    
                            if (count($hasJob) > 0) {
                                foreach ($hasJob as $job) {
                                    if ($job->end_time) {
                                        $startTime = $job->end_time;
                                    }
                                }
                                // \Log::info("Worker found and has a job on $currentDate.");
                            }else{
                                // Default start time based on shift
                                switch ($shift) {
                                    case "Morning":
                                    case "בוקר":
                                        $startTime = "08:00:00";
                                        break;
                            
                                    case "Noon":
                                    case "צהריים":
                                        $startTime = "12:00:00";
                                        break;
                            
                                    case "After noon":
                                    case "Afternoon":
                                    case "אחה״צ":
                                        $startTime = "16:00:00";
                                        break;
                            
                                    default:
                                        $startTime = "08:00:00";
                                        break;
                                }
                            }
    
                            $value = str_replace(',', '.', $properHours);
                            $value = floatval($value); // Convert to float
                            
                            $wholePart = floor($value); // Extract whole number part
                            $decimalPart = $value - $wholePart; // Extract decimal part
                            
                            // Convert decimal part to minutes
                            if ($decimalPart == 0) {
                                $minutes = 0;
                            } elseif ($decimalPart > 0 && $decimalPart <= 0.2) {
                                $minutes = 15;
                            } elseif ($decimalPart > 0.2 && $decimalPart <= 0.5) {
                                $minutes = 30;
                            } elseif ($decimalPart > 0.5 && $decimalPart <= 0.8) {
                                $minutes = 45;
                            } else {
                                // Round up to the next hour
                                $wholePart += 1;
                                $minutes = 0;
                            }
                            
                            // Calculate end time using Carbon
                            $startDateTime = Carbon::createFromFormat('H:i:s', $startTime);
                            $endDateTime = $startDateTime->copy()->addHours($wholePart)->addMinutes($minutes);
                            
                            $endTime = $endDateTime->format('H:i');
    
                            \Log::info("Worker found end time: $endTime.");
                            
    
                            if (!$client) {
                                return response()->json([
                                    'message' => 'Client not found'
                                ], 404);
                            }
    
                            // Decode services (if stored as JSON)
                            $services = is_string($offer->services) ? json_decode($offer->services, true) : $offer->services;
    
                            // Locate the service and add is_one_time field
                            foreach ($services as &$service) {
                                if (($service['service'] == 1) || isset($service['freq_name']) && (in_array($service['freq_name'], ['One Time', 'חד פעמי']))) {
                                    $service['is_one_time'] = true; // Add the field
                                }
                            }
    
                            // Save updated services back to the offer
                            $offer->services = json_encode($services);
                            $offer->save();
    
    
                            $manageTime = ManageTime::first();
                            $workingWeekDays = json_decode($manageTime->days);
    
    
                            // $offerServices = $this->formatServices($offer, false);
                            // $filtered = Arr::where($offerServices, function ($value, $key) use ($serviceId) {
                            //     return $value['service'] == $serviceId;
                            // });
    
                            // $selectedService = head($filtered);
                            // \Log::info($selectedService);
    
                            $service = Services::find($serviceId);
                            $serviceSchedule = ServiceSchedule::find($selectedFrequency->id);
    
                            $repeat_value = $serviceSchedule->period;
                            if ($selectedService['template'] == 'others') {
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
    
                            
                            $job_date = Carbon::parse($currentDate);
                            $preferredWeekDay = strtolower($job_date->format('l'));
                            $next_job_date = $this->scheduleNextJobDate($job_date, $repeat_value, $preferredWeekDay, $workingWeekDays);
    
                            $job_date = $job_date->toDateString();
    
                            $shiftFormattedArr = [];
    
                            $shiftFormattedArr[0] = [
                                'starting_at' => $startTime,
                                'ending_at' => $endTime
                            ];
    
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
    
                            if ($this->isJobTimeConflicting($mergedContinuousTime, $job_date, $worker->id)) {
                                \Log::info("Job time is conflicting with another job. Job will be unscheduled.");
                                $status = JobStatusEnum::UNSCHEDULED;
                            }
    
                            $start_time = Carbon::parse($mergedContinuousTime[0]['starting_at'])->toTimeString();
                            $end_time = Carbon::parse($mergedContinuousTime[count($mergedContinuousTime) - 1]['ending_at'])->toTimeString();

                            Job::$skipObserver = true;

                            $job = Job::create([
                                'uuid'          => Str::uuid(),
                                'worker_id'     => $worker->id,
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
                                'address_id'        => $selectedService['address'],
                                'original_worker_id'     => $worker->id,
                                'original_shifts'        => $slotsInString,
                                'keep_prev_worker'      => true,
                                'offer_service'     => $selectedService
                            ]);
    
                            // Create entry in ParentJobs
                            $parentJob = ParentJobs::create([
                                'job_id' => $job->id,
                                'client_id' => $contract->client_id,
                                'worker_id' => $worker->id,
                                'offer_id' => $contract->offer_id,
                                'contract_id' => $contract->id,
                                'schedule'      => $repeat_value,
                                'schedule_id'   => $s_id,
                                'start_date' => $job_date,
                                'next_start_date'   => $next_job_date,
                                'status' => $status, // You can set this according to your needs
                                'keep_prev_worker'      => true
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
                                'job_group_id' => $jobGroupID,
                                'parent_job_id' => $parentJob->id
                            ]);
    
                            foreach ($mergedContinuousTime as $key => $shift) {
                                $job->workerShifts()->create($shift);
                            }
    
                            // $job->load(['client', 'worker', 'jobservice', 'propertyAddress', 'offer']);
    
                            // // Send notification to client
                            // $jobData = $job->toArray();
    
                            Job::$skipObserver = false;

                            ScheduleNextJobOccurring::dispatch($job->id, null);

    
                            $newLeadStatus = $this->getClientLeadStatusBasedOnJobs($client);
    
                            if (!$client->lead_status || $client->lead_status->lead_status != $newLeadStatus) {
                                $client->lead_status()->updateOrCreate(
                                    [],
                                    ['lead_status' => $newLeadStatus]
                                );
    
                            }
                            $jobArray[$job->id] = $job;
                        } else {
                            \Log::info("No worker found matching: " . $selectedWorker);
                        }
                }
            }
            return $jobArray;
        } catch (\Throwable $th) {
            throw $th;
        }

    }


    public function makeJobWithDaysInGoogleSheet(Request $request)
    {
        $row = $request->all();
        \Log::info(['row' => $row]);
        // \Log::info($row['date']);
        // \Log::info($row['selectedWeekDays']);
        // \Log::info($row['currentDay']);
        // \Log::info($row['values']);
        dd($row);
        try {
            $currentDate = $this->convertDate($row["date"]);
            $clientId = null;
            if (strpos(trim($row[2]), '#') === 0) {
                $clientId = substr(trim($row[2]), 1);
            }
            $offerId = $row[3] ?? null;
            $selectedWorker = $row[10] ?? null;
            $shift = "";
            $buisnessHour = $row[11] ?? null;
            $ServiceName = $row[13] ?? null;
            $properHours = $row[14] ?? null;
            $frequencyName = $row[17] ?? null;

            $currentDateObj = Carbon::parse($currentDate); // Current date
            $startTime = null;
            $endTime = null;
            $day = $currentDateObj->format('l');

            $offer = Offer::with('service')->where('id', $offerId)->first();
            $client = Client::where('id', $clientId)->first();
            $contract = Contract::where('client_id', $clientId)
                ->where('offer_id', $offerId)
                ->where('status', ContractStatusEnum::VERIFIED)
                ->first();
            $worker = User::where('status', 1)
            ->whereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ['%' . trim($selectedWorker) . '%'])
            ->first();
            $selectedService = Services::where('heb_name', $ServiceName)
            ->orWhere('name', $ServiceName)                
            ->first();
            $serviceId = $selectedService->id;
            $selectedFrequency = ServiceSchedule::where('name_heb', $frequencyName)
            ->orWhere('name', $frequencyName)
            ->first();

            if ($offer) {

                $jobData = Job::where('offer_id', $offer->id)
                        ->where('start_date', $currentDate)
                        ->where('client_id', $client->id)
                        ->whereHas('contract', function ($q) {
                            $q->where('status', 'verified');                  
                        })
                        ->whereHas('offer', function ($q) use ($selectedFrequency, $serviceId) {
                            $q->whereRaw("
                                EXISTS (
                                    SELECT 1 
                                    FROM JSON_TABLE(offers.services, '$[*]' 
                                        COLUMNS (
                                            service INT PATH '$.service',
                                            frequency INT PATH '$.frequency'
                                        )
                                    ) AS services_table
                                    WHERE services_table.service = ? 
                                    AND services_table.frequency = ?
                                )
                            ", [$serviceId, $selectedFrequency->id]);
                        })
                        ->first();
            
                    if ($jobData) {
                        return response()->json([
                            'message' => 'Job already exists.'
                        ]);
                    }

                    if($client->lng == 'en') {
                        switch (trim($buisnessHour)) {
                            case 'יום':
                            case 'בוקר':
                            case '7 בבוקר':
                            case 'בוקר 11':
                            case 'בוקר מוקדם':
                            case 'בוקר 6':
                                $shift = "Morning";
                                break;

                            case 'צהריים':
                            case 'צהריים 14':
                                $shift = "Noon";
                                break;

                            case 'אחהצ':
                            case 'אחה״צ':
                            case 'ערב':
                            case 'אחר״צ':
                                $shift = "After noon";
                                break;

                            default:
                                $shift = $row[9];
                                break;
                        }
                    } else {
                        switch (trim($buisnessHour)) {
                            case 'יום':
                            case 'בוקר':
                            case '7 בבוקר':
                            case 'בוקר 11':
                            case 'בוקר מוקדם':
                            case 'בוקר 6':
                                $shift = "בוקר";
                                break;

                            case 'צהריים':
                            case 'צהריים 14':
                                $shift = 'צהריים';
                                break;

                            case 'אחהצ':
                            case 'אחה״צ':
                            case 'ערב':
                            case 'אחר״צ':
                                $shift = "אחה״צ";
                                break;


                            default:
                                $shift = $buisnessHour;
                                break;
                        }
                        switch ($day) {
                            case 'Sunday':
                                $day = "ראשון";
                                break;
                            case 'Monday':
                                $day = "שני";
                                break;
                            case 'Tuesday':
                                $day = "שלישי";
                                break;
                            case 'Wednesday':
                                $day = "רביעי";
                                break;
                            case 'Thursday':
                                $day = "חמישי";
                                break;
                            case 'Friday':
                                $day = "שישי";
                                break;
                            case 'Saturday':
                                $day = "שבת";
                                break;
                        }
                    }

                    if ($worker) {
                        // Check if the worker has a job for the given date
                        $hasJob = $worker->jobs()->where('start_date', $currentDate)->get();

                        if (count($hasJob) > 0) {
                            foreach ($hasJob as $job) {
                                if ($job->end_time) {
                                    $startTime = $job->end_time;
                                }
                            }
                            // \Log::info("Worker found and has a job on $currentDate.");
                        }else{
                            // Default start time based on shift
                            switch ($shift) {
                                case "Morning":
                                case "בוקר":
                                    $startTime = "08:00:00";
                                    break;
                        
                                case "Noon":
                                case "צהריים":
                                    $startTime = "12:00:00";
                                    break;
                        
                                case "After noon":
                                case "Afternoon":
                                case "אחה״צ":
                                    $startTime = "16:00:00";
                                    break;
                        
                                default:
                                    $startTime = "08:00:00";
                                    break;
                            }
                        }

                        $value = str_replace(',', '.', $properHours);
                        $value = floatval($value); // Convert to float
                        
                        $wholePart = floor($value); // Extract whole number part
                        $decimalPart = $value - $wholePart; // Extract decimal part
                        
                        // Convert decimal part to minutes
                        if ($decimalPart == 0) {
                            $minutes = 0;
                        } elseif ($decimalPart > 0 && $decimalPart <= 0.2) {
                            $minutes = 15;
                        } elseif ($decimalPart > 0.2 && $decimalPart <= 0.5) {
                            $minutes = 30;
                        } elseif ($decimalPart > 0.5 && $decimalPart <= 0.8) {
                            $minutes = 45;
                        } else {
                            // Round up to the next hour
                            $wholePart += 1;
                            $minutes = 0;
                        }
                        
                        // Calculate end time using Carbon
                        $startDateTime = Carbon::createFromFormat('H:i:s', $startTime);
                        $endDateTime = $startDateTime->copy()->addHours($wholePart)->addMinutes($minutes);
                        
                        $endTime = $endDateTime->format('H:i');

                        \Log::info("Worker found end time: $endTime.");
                        

                        if (!$client) {
                            return response()->json([
                                'message' => 'Client not found'
                            ], 404);
                        }

                        // Decode services (if stored as JSON)
                        $services = is_string($offer->services) ? json_decode($offer->services, true) : $offer->services;

                        // Locate the service and add is_one_time field
                        foreach ($services as &$service) {
                            if (($service['service'] == 1) || isset($service['freq_name']) && (in_array($service['freq_name'], ['One Time', 'חד פעמי']))) {
                                $service['is_one_time'] = true; // Add the field
                            }
                        }

                        // Save updated services back to the offer
                        $offer->services = json_encode($services);
                        $offer->save();


                        $manageTime = ManageTime::first();
                        $workingWeekDays = json_decode($manageTime->days);


                        $offerServices = $this->formatServices($offer, false);
                        $filtered = Arr::where($offerServices, function ($value, $key) use ($selectedService) {
                            return $value['service'] == $selectedService->id;
                        });

                        $selectedService = head($filtered);
                        // \Log::info($selectedService);

                        $service = Services::find($serviceId);
                        $serviceSchedule = ServiceSchedule::find($selectedFrequency->id);

                        $repeat_value = $serviceSchedule->period;
                        if ($selectedService['template'] == 'others') {
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

                        
                        $job_date = Carbon::parse($currentDate);
                        $preferredWeekDay = strtolower($job_date->format('l'));
                        $next_job_date = $this->scheduleNextJobDate($job_date, $repeat_value, $preferredWeekDay, $workingWeekDays);

                        $job_date = $job_date->toDateString();

                        $shiftFormattedArr = [];

                        $shiftFormattedArr[0] = [
                            'starting_at' => $startTime,
                            'ending_at' => $endTime
                        ];

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

                        if ($this->isJobTimeConflicting($mergedContinuousTime, $job_date, $worker->id)) {
                            \Log::info("Job time is conflicting with another job. Job will be unscheduled.");
                            $status = JobStatusEnum::UNSCHEDULED;
                        }

                        $start_time = Carbon::parse($mergedContinuousTime[0]['starting_at'])->toTimeString();
                        $end_time = Carbon::parse($mergedContinuousTime[count($mergedContinuousTime) - 1]['ending_at'])->toTimeString();

                        $job = Job::create([
                            'worker_id'     => $worker->id,
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
                            'original_worker_id'     => $worker->id,
                            'original_shifts'        => $slotsInString,
                            'keep_prev_worker'      => true
                        ]);

                        // Create entry in ParentJobs
                        $parentJob = ParentJobs::create([
                            'job_id' => $job->id,
                            'client_id' => $contract->client_id,
                            'worker_id' => $worker->id,
                            'offer_id' => $contract->offer_id,
                            'contract_id' => $contract->id,
                            'schedule'      => $repeat_value,
                            'schedule_id'   => $s_id,
                            'start_date' => $job_date,
                            'next_start_date'   => $next_job_date,
                            'status' => $status, // You can set this according to your needs
                            'keep_prev_worker'      => true
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
                            'job_group_id' => $jobGroupID,
                            'parent_job_id' => $parentJob->id
                        ]);

                        foreach ($mergedContinuousTime as $key => $shift) {
                            $job->workerShifts()->create($shift);
                        }

                        // $job->load(['client', 'worker', 'jobservice', 'propertyAddress', 'offer']);

                        // // Send notification to client
                        // $jobData = $job->toArray();

                        ScheduleNextJobOccurring::dispatch($job->id, null);


                        $newLeadStatus = $this->getClientLeadStatusBasedOnJobs($client);

                        if (!$client->lead_status || $client->lead_status->lead_status != $newLeadStatus) {
                            $client->lead_status()->updateOrCreate(
                                [],
                                ['lead_status' => $newLeadStatus]
                            );

                        }
                        $jobArray[$job->id] = $job;
                    } else {
                        \Log::info("No worker found matching: " . $selectedWorker);
                    }
            }
            return $jobArray;
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    public function convertDate($dateString, $sheet=null)
    {
        // // Extract year from the sheet (assumes format: "Month Year" e.g., "ינואר 2025" or "דצמבר 2024")
        // preg_match('/\d{4}/', $sheet, $yearMatch);
        // $year = $yearMatch[0] ?? date('Y'); // Default to current year if no match
        $year = date('Y');

        // Normalize different formats (convert ',' to '.')
        $dateString = str_replace(',', '.', $dateString);

        // Extract day and month
        if (preg_match('/(\d{1,2})\.(\d{1,2})/', $dateString, $matches)) {
            // Format: 12.01 → day = 12, month = 01
            $day = sprintf('%02d', $matches[1]);
            $month = sprintf('%02d', $matches[2]);
        } elseif (preg_match('/(\d{2})(\d{2})/', $dateString, $matches)) {
            // Format: 0401 → day = 04, month = 01
            $day = sprintf('%02d', $matches[1]);
            $month = sprintf('%02d', $matches[2]);
        } elseif (preg_match('/(\d{1,2})\s*,\s*(\d{1,2})/', $dateString, $matches)) {
            // Format: 3,1 → day = 3, month = 1
            $day = sprintf('%02d', $matches[1]);
            $month = sprintf('%02d', $matches[2]);
        } else {
            return false;
        }

        // Return formatted date
        return "$year-$month-$day";
    }
}
