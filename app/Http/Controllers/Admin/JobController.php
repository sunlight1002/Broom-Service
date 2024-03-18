<?php

namespace App\Http\Controllers\Admin;

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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class JobController extends Controller
{
    use JobSchedule;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $q = $request->q;
        $w = $request->filter_week;
        $jobs = Job::query()
            ->leftJoin('job_hours', function ($join) {
                $join->on('jobs.id', '=', 'job_hours.job_id');
            })
            ->with([
                'worker',
                'client',
                'offer',
                'jobservice',
                'order',
                'invoice',
                'jobservice.service',
                'propertyAddress'
            ]);

        if ($q != '') {
            $jobs = $jobs->orWhereHas('worker', function ($qr) use ($q) {
                $qr->where(function ($qr) use ($q) {
                    $qr->where(DB::raw('firstname'), 'like', '%' . $q . '%');
                    $qr->orWhere(DB::raw('lastname'), 'like', '%' . $q . '%');
                });
            })
                ->orWhereHas('client', function ($qr) use ($q) {
                    $qr->where(function ($qr) use ($q) {
                        $qr->where(DB::raw('firstname'), 'like', '%' . $q . '%');
                        $qr->orWhere(DB::raw('lastname'), 'like', '%' . $q . '%');
                    });
                })
                ->orWhereHas('jobservice', function ($qr) use ($q) {
                    $qr->where(function ($qr) use ($q) {
                        $qr->where(DB::raw('name'), 'like', '%' . $q . '%');
                        $qr->orWhere(DB::raw('heb_name'), 'like', '%' . $q . '%');
                    });
                })
                ->orWhere('status', 'like', '%' . $q . '%');
        }

        // if($w != ''){
        if ((is_null($w) || $w == 'current') && $w != 'all') {
            $startDate = Carbon::now()->toDateString();
            $endDate = Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDays(5)->toDateString();
        }

        if ($w == 'next') {
            $startDate = Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDays(6)->toDateString();
            $endDate = Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDays(12)->toDateString();
        } else if ($w == 'nextnext') {
            $startDate = Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDays(13)->toDateString();
            $endDate = Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDays(19)->toDateString();
        } else if ($w == 'today') {
            $startDate = Carbon::today()->toDateString();
            $endDate = Carbon::today()->toDateString();
        }

        $jobs = $jobs
            ->select('jobs.*')
            ->selectRaw('(SELECT comment FROM job_comments WHERE job_comments.job_id = jobs.id AND job_comments.role = "worker" ORDER BY id DESC LIMIT 1) AS last_comment')
            ->selectRaw('SUM(TIMESTAMPDIFF(MINUTE, job_hours.start_time, job_hours.end_time)) AS total_minutes')
            ->orderBy('start_date')
            ->orderBy('client_id')
            ->groupBy('jobs.id');

        if ($w == 'all') {
            $jobs = $jobs
                ->paginate(20);
        } else if ($request->p == 1) {
            $jobs = $jobs->whereDate('start_date', '>=', $startDate)
                ->whereDate('start_date', '<=', $endDate)
                ->paginate(5);
        } else {
            $pcount = Job::count();

            $jobs = $jobs->whereDate('start_date', '>=', $startDate)
                ->whereDate('start_date', '<=', $endDate)
                ->paginate($pcount);
        }

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
        $job = Job::with(['client', 'worker', 'service', 'offer', 'jobservice', 'order', 'propertyAddress'])->find($id);

        return response()->json([
            'job' => $job,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $job = Job::with(['client', 'worker', 'service', 'offer', 'jobservice', 'propertyAddress'])->find($id);

        return response()->json([
            'job' => $job
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $job = Job::find($id);
        $job->delete();

        return response()->json([
            'message' => "Job has been deleted"
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
                $jobQuery->has('order');
            } else if ($q == 'unordered') {
                $jobQuery->whereDoesntHave('order');
            } else if ($q == 'invoiced') {
                $jobQuery->has('invoice');
            } else if ($q == 'uninvoiced') {
                $jobQuery->whereDoesntHave('invoice');
            }
        }

        $sch        = Job::where('status', 'scheduled')->where('client_id', $id)->count();
        $un_sch     = Job::where('status', 'unscheduled')->where('client_id', $id)->count();
        $cancel     = Job::where('status', 'canceled')->where('client_id', $id)->count();
        $progress   = Job::where('status', 'progress')->where('client_id', $id)->count();
        $completed  = Job::where('status', 'completed')->where('client_id', $id)->count();

        $ordered    = Job::has('order')->where('client_id', $id)->count();
        $unordered  = Job::whereDoesntHave('order')->where('client_id', $id)->count();
        $invoiced   = Job::has('invoice')->where('client_id', $id)->count();
        $unordered  = Job::whereDoesntHave('invoice')->where('client_id', $id)->count();

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
            $jobs = $jobs->where('status', 'completed');
        } else {
            $jobs = $jobs->where('status', '!=', 'completed');
        }

        $jobs = $jobs->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'jobs' => $jobs
        ]);
    }

    public function updateJob(Request $request, $id)
    {
        $job = Job::find($id);
        if ($request->date != '') {
            $job->start_date = $request->date;
        }
        if ($request->worker != '') {
            $job->worker_id = $request->worker;
        }
        if ($request->shifts != '') {
            $job->shifts = $request->shifts;
        }
        if ($request->comment != '') {
            $job->comment = $request->comment;
        }

        $job->save();
        if ($request->worker != '') {
            $this->sendWorkerEmail($id);
        }

        return response()->json([
            'message' => 'Job has been updated successfully'
        ]);
    }

    public function createJob(Request $request, $id)
    {
        $data = $request->all();

        $repeat_value = '';
        $s_name = '';
        $s_heb_name = '';
        $s_hour = '';
        $s_total = '';
        $s_id = 0;
        $contract_id = 0;
        $isClientPage = (isset($request->client_page) && $request->client_page);

        $selectedService = $data['service'];
        $serviceSchedule = ServiceSchedule::find($selectedService['frequency']);
        $service = Services::find($selectedService['service']);

        $repeat_value = $serviceSchedule->period;
        if ($selectedService['service'] == 10) {
            $s_name = $selectedService['other_title'];
            $s_heb_name = $selectedService['other_title'];
        } else {
            $s_name = $service->name;
            $s_heb_name = $service->heb_name;
        }
        $s_hour = $selectedService['jobHours'];
        $s_freq   = $selectedService['freq_name'];
        $s_cycle  = $selectedService['cycle'];
        $s_period = $selectedService['period'];
        $s_total  = $selectedService['totalamount'];
        $s_id     = $selectedService['service'];
        if ($isClientPage) {
            $contract_id = $selectedService['c_id'];
        }

        if ($isClientPage) {
            $contract = Contract::with('offer')->find($contract_id);
        } else {
            $contract = Contract::with('offer')->find($id);
        }

        $workerIDs = array_values(array_unique(data_get($data, 'workers.*.worker_id')));
        foreach ($workerIDs as $workerID) {
            $workerDates = Arr::where($data['workers'], function ($value) use ($workerID) {
                return $value['worker_id'] == $workerID;
            });

            foreach ($workerDates as $key => $workerDate) {
                $job_date = Carbon::parse($workerDate['date']);
                $preferredWeekDay = strtolower($job_date->format('l'));
                $next_job_date = $this->scheduleNextJobDate($job_date, $repeat_value, $preferredWeekDay);

                $job_date = $job_date->toDateString();

                $status = 'scheduled';

                if (
                    Job::where('start_date', $job_date)
                    ->where('worker_id', $workerDate['worker_id'])
                    ->exists()
                ) {
                    $status = 'unscheduled';
                }

                $job = Job::create([
                    'worker_id'     => $workerDate['worker_id'],
                    'client_id'     => $isClientPage ? $id : $contract->client_id,
                    'contract_id'   => $isClientPage ? $contract_id : $id,
                    'offer_id'      => $contract->offer_id,
                    'start_date'    => $job_date,
                    'shifts'        => $workerDate['shifts'],
                    'schedule'      => $repeat_value,
                    'schedule_id'   => $s_id,
                    'status'        => $status,
                    'next_start_date'    => $next_job_date,
                    'address_id'  => $selectedService['address']['id'],
                    'keep_prev_worker' => isset($data['prevWorker'])?$data['prevWorker']:false,
                ]);

                JobService::create([
                    'job_id'        => $job->id,
                    'service_id'    => $s_id,
                    'name'          => $s_name,
                    'heb_name'      => $s_heb_name,
                    'job_hour'      => $s_hour,
                    'freq_name'     => $s_freq,
                    'cycle'         => $s_cycle,
                    'period'        => $s_period,
                    'total'         => $s_total,
                    'config'        => [
                        'cycle'             => $serviceSchedule->cycle,
                        'period'            => $serviceSchedule->period,
                        'preferred_weekday' => $preferredWeekDay
                    ]
                ]);

                $shiftArr = explode(',', $workerDate['shifts']);

                $shiftFormattedArr = [];
                foreach ($shiftArr as $key => $_shift) {
                    $time = explode('-', $_shift);

                    $start_time = Carbon::createFromFormat('H', str_replace(['am', 'pm'], '', $time[1]))->toTimeString();
                    $end_time = Carbon::createFromFormat('H', str_replace(['am', 'pm'], '', $time[2]))->toTimeString();

                    $shiftFormattedArr[$key] = [
                        'starting_at' => Carbon::parse($job_date . ' ' . $start_time)->toDateTimeString(),
                        'ending_at' => Carbon::parse($job_date . ' ' . $end_time)->toDateTimeString()
                    ];
                }

                foreach ($this->mergeContinuousTimes($shiftFormattedArr) as $key => $shift) {
                    $job->workerShifts()->create($shift);
                }

                if ($key == 0) {
                    $job->load(['client', 'worker', 'jobservice', 'propertyAddress']);

                    $_timeShift = $workerDate['shifts'];
                    if ($_timeShift != '') {
                        $_timeShift1 = explode('-', $_timeShift)[1];

                        $_ts = preg_replace('/[^0-9]/', '', $_timeShift1) . ":00";
                        $ttime = strtotime($_ts) + 60 * 90;
                        $atime = date('H:i', $ttime);
                        $_timeShift = $_ts . " - " . $atime;
                    }

                    $data = array(
                        'email' => $job['worker']['email'],
                        'job'  => $job->toArray(),
                        'start_time' => $_timeShift
                    );
                    App::setLocale($job->worker->lng);

                    if (!is_null($job['worker']['email']) && $job['worker']['email'] != 'Null') {
                        Mail::send('/Mails/NewJobMail', $data, function ($messages) use ($data) {
                            $messages->to($data['email']);
                            $sub = __('mail.worker_new_job.subject') . "  " . __('mail.worker_new_job.company');
                            $messages->subject($sub);
                        });
                    }
                }
            }
        }

        return response()->json([
            'message' => 'Job has been created successfully'
        ]);
    }

    public function getShifts($shift, $lng = 'en')
    {
        $show_shift = array(
            "Full Day",
            "Morning",
            'Afternoon',
            'Evening',
            'Night',
        );
        $shifts = explode(',', $shift);
        $check = '';
        $new_shift = '';
        foreach ($show_shift as $s_s) {
            if ($s_s == 'Afternoon') {
                $check = 'noon';
            } else {
                $check = $s_s;
            }

            foreach ($shifts as $shift) {
                if (str_contains($shift, strtolower($check))) {
                    if ($new_shift == '') {
                        $new_shift = $s_s;
                    } else {
                        if (!str_contains($new_shift, $s_s)) {
                            $new_shift = $new_shift . ' | ' . $s_s;
                        }
                    }
                }
            }
        }

        if ($lng == 'heb') {
            $new_shift = str_replace("Full Day", "יום שלם", $new_shift);
            $new_shift = str_replace("Morning", "בוקר", $new_shift);
            $new_shift = str_replace("Noon", "צהריים", $new_shift);
            $new_shift = str_replace("Afternoon", "אחהצ", $new_shift);
            $new_shift = str_replace("Evening", "ערב", $new_shift);
            $new_shift = str_replace("Night", "לילה", $new_shift);
        }
        return $new_shift;
    }

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
            'start_time' => ['required'],
            'end_time'  => ['required']
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()]);
        }

        $time = new JobHours();
        $time->job_id = $request->job_id;
        $time->worker_id = $request->worker_id;
        $time->start_time = $request->start_time;
        $time->end_time = $request->end_time;
        $time->time_diff = $request->timeDiff;
        $time->save();

        return response()->json([
            'time' => $time,
        ]);
    }

    public function updateJobTime(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_time' => ['required'],
            'end_time'  => ['required']
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()]);
        }

        $time = JobHours::find($request->id);
        $time->start_time = $request->start_time;
        $time->end_time = $request->end_time;
        $time->time_diff = $request->timeDiff;
        $time->save();

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
            'status' => 'cancel',
            'cancellation_fee_percentage' => $feePercentage,
            'cancellation_fee_amount' => $feeAmount,
            'cancelled_by_role' => 'admin',
            'cancelled_at' => now()
        ]);

        $admin = Admin::find(1)->first();
        App::setLocale('en');
        $data = array(
            'by'         => 'admin',
            'email'      => $admin->email,
            'admin'      => $admin->toArray(),
            'job'        => $job->toArray(),
        );

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
            $row['worker_name']      = $job->worker->firstname . " " . $job->worker->lastname;
            $row['worker_id']        = $job->worker->worker_id;
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
            ->with(['client', 'worker', 'jobservice','propertyAddress'])
            ->where('id', $job_id)
            ->first();

        $data = array(
            'email' => $job['worker']['email'],
            'job'  => $job->toArray(),
        );

        if (
            isset($job['worker']['email']) &&
            $job['worker']['email'] != null &&
            $job['worker']['email'] != 'Null'
        ) {
            App::setLocale($job->worker->lng);
            Mail::send('/Mails/NewJobMail', $data, function ($messages) use ($data) {
                $messages->to($data['email']);
                $sub = __('mail.worker_new_job.subject') . "  " . __('mail.worker_new_job.company');
                $messages->subject($sub);
            });
        }

        return true;
    }
}
