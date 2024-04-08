<?php

namespace App\Http\Controllers\User;

use App\Enums\JobStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Admin;
use App\Models\WorkerAvailability;
use App\Models\JobHours;
use App\Traits\PaymentAPI;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class JobController extends Controller
{
    use PaymentAPI;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $w = $request->filter_week;

        $jobs = Job::query()
            ->with('worker', 'client', 'offer', 'jobservice', 'propertyAddress')
            ->where('worker_id', $request->id);

        if ((is_null($w) || $w == 'current') && $w != 'all') {
            $startDate = Carbon::now()->toDateString();
            $endDate = Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDays(5)->toDateString();
        }
        if ($w == 'next') {
            $startDate = Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDays(6)->toDateString();
            $endDate = Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDays(12)->toDateString();
        }
        if ($w == 'nextnext') {
            $startDate = Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDays(13)->toDateString();
            $endDate = Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDays(19)->toDateString();
        }
        if ($w == 'today') {
            $startDate = Carbon::today()->toDateString();
            $endDate = Carbon::today()->toDateString();
        }

        if ($w == 'all') {
            $jobs = $jobs->orderBy('created_at', 'desc')->paginate(20);
        } else {
            $jobs = $jobs->whereDate('start_date', '>=', $startDate);
            $jobs = $jobs->whereDate('start_date', '<=', $endDate);
            $pcount = Job::count();
            $jobs = $jobs->orderBy('created_at', 'desc')->paginate($pcount);
        }

        return response()->json([
            'jobs' => $jobs,
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
                'propertyAddress'
            ])
            ->where('worker_id', Auth::user()->id)
            ->find($id);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found',
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
        $job = Job::with('client', 'worker', 'jobservice')->find($id);
        //$this->invoice($id);
        $job->status = JobStatusEnum::COMPLETED;
        $job->save();

        $admin = Admin::find(1)->first();
        App::setLocale('en');
        $data = array(
            'email'      => $admin->email,
            'admin'      => $admin->toArray(),
            'job'        => $job->toArray(),
        );

        Mail::send('/WorkerPanelMail/JobStatusNotification', $data, function ($messages) use ($data) {
            $messages->to($data['email']);
            $sub = __('mail.job_status.subject');
            $messages->subject($sub);
        });

        return response()->json([
            'message' => 'Job completed',
        ]);
    }

    public function getAvailability()
    {
        $worker_availabilities = WorkerAvailability::where('user_id', Auth::user()->id)
            ->orderBy('id', 'asc')
            ->get();

        $new_array = array();
        foreach ($worker_availabilities as $w_a) {
            $new_array[$w_a->date] = $w_a->working;
        }

        return response()->json([
            'availability' => $new_array,
        ]);
    }
    public function updateAvailability(Request $request)
    {
        $isMondayPassed = Carbon::today()->weekday() > Carbon::MONDAY;

        $data = $request->all();

        if ($isMondayPassed) {
            $firstEditDate = Carbon::today()->addWeeks(2)->startOfWeek(Carbon::SUNDAY);
        } else {
            $firstEditDate = Carbon::today()->addWeek()->startOfWeek(Carbon::SUNDAY);
        }

        WorkerAvailability::query()
            ->where('user_id', Auth::user()->id)
            ->whereDate('date', '>=', $firstEditDate->toDateString())
            ->delete();

        foreach ($data as $key => $availabilty) {
            $date = trim($key);

            if ($firstEditDate->lte(Carbon::parse($date))) {
                WorkerAvailability::create([
                    'user_id' => Auth::user()->id,
                    'date' => $date,
                    'working' => $availabilty,
                    'status' => '1',
                ]);
            }
        }

        return response()->json([
            'message' => 'Updated Successfully',
        ]);
    }

    public function JobStartTime(Request $request)
    {
        $job = Job::find($request->job_id);
        if ($job->status != JobStatusEnum::PROGRESS) {
            $job->status = JobStatusEnum::PROGRESS;
            $job->save();
        }

        JobHours::create([
            'job_id' => $request->job_id,
            'worker_id' => $request->worker_id,
            'start_time' => $request->start_time,
        ]);

        return response()->json([
            'message' => 'Updated Successfully',
        ]);
    }
    public function JobEndTime(Request $request)
    {
        $time = JobHours::find($request->id);

        $time->update([
            'end_time' => $request->end_time,
            'time_diff' => $request->time_diff,
        ]);

        return response()->json([
            'message' => 'Updated Successfully',
        ]);
    }

    public function getJobTime(Request $request)
    {
        $time = JobHours::where('job_id', $request->job_id)->where('worker_id', $request->worker_id);
        $total = 0;
        if ($request->filter_end_time) {
            $time = $time->where('end_time', NULL)->first();
        } else {
            $time = $time->get();
            foreach ($time as $t) {
                if ($t->time_diff) {
                    $total = $total + (int)$t->time_diff;
                }
            }
        }

        return response()->json([
            'time' => $time,
            'total' => $total
        ]);
    }
}
