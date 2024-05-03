<?php

namespace App\Http\Controllers\User;

use App\Enums\JobStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Events\WorkerApprovedJob;
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
use App\Models\Notification;
use App\Traits\JobSchedule;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

class JobController extends Controller
{
    use PaymentAPI, JobSchedule;

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

    public function getAvailability()
    {
        $worker = Auth::user();

        $worker_availabilities = $worker->availabilities()
            ->orderBy('date', 'asc')
            ->get(['date', 'start_time', 'end_time']);

        $availabilities = [];
        foreach ($worker_availabilities->groupBy('date') as $date => $times) {
            $availabilities[$date] = $times->map(function ($item, $key) {
                return $item->only(['start_time', 'end_time']);
            });
        }

        $default_availabilities = $worker->defaultAvailabilities()
            ->orderBy('id', 'asc')
            ->get(['weekday', 'start_time', 'end_time', 'until_date'])
            ->groupBy('weekday');

        return response()->json([
            'availability' => [
                'regular' => $availabilities,
                'default' => $default_availabilities
            ],
        ]);
    }

    public function updateAvailability(Request $request)
    {
        $worker = Auth::user();

        $isMondayPassed = Carbon::today()->weekday() > Carbon::MONDAY;

        $data = $request->all();

        if ($isMondayPassed) {
            $firstEditDate = Carbon::today()->addWeeks(2)->startOfWeek(Carbon::SUNDAY);
        } else {
            $firstEditDate = Carbon::today()->addWeek()->startOfWeek(Carbon::SUNDAY);
        }

        $worker->availabilities()
            ->whereDate('date', '>=', $firstEditDate->toDateString())
            ->delete();

        foreach ($data['time_slots'] as $key => $availabilties) {
            $date = trim($key);

            if ($firstEditDate->lte(Carbon::parse($date))) {
                foreach ($availabilties as $key => $availabilty) {
                    WorkerAvailability::create([
                        'user_id' => Auth::user()->id,
                        'date' => $date,
                        'start_time' => $availabilty['start_time'],
                        'end_time' => $availabilty['end_time'],
                        'status' => '1',
                    ]);
                }
            }
        }

        $worker->defaultAvailabilities()->delete();

        if (isset($data['default']['time_slots'])) {
            foreach ($data['default']['time_slots'] as $weekday => $availabilties) {
                foreach ($availabilties as $key => $timeSlot) {
                    $worker->defaultAvailabilities()->create([
                        'weekday' => $weekday,
                        'start_time' => $timeSlot['start_time'],
                        'end_time' => $timeSlot['end_time'],
                        'until_date' => $data['default']['until_date'],
                    ]);
                }
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

        $this->updateJobWorkerMinutes($time->job_id);

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

    public function setJobOpeningTimestamp(Request $request)
    {
        $rData = $request->all();
        try {
            $job = Job::updateOrCreate([
                'id' => $rData['job_id'],
            ], [
                'job_opening_timestamp' => now()
            ]);
            Notification::create([
                'user_id' => $job->client->id,
                'type' => NotificationTypeEnum::OPENING_JOB,
                'job_id' => $job->id,
                'status' => 'going to start'
            ]);

            $admin = Admin::where('role', 'admin')->first();
            App::setLocale('en');
            $data = array(
                'email'      => $admin->email,
                'admin'      => $admin->toArray(),
                'worker'     => $job->worker,
                'job'        => $job->toArray(),
            );
            if (isset($data['admin']) && !empty($data['admin']['phone'])) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::WORKER_JOB_OPENING_NOTIFICATION,
                    "notificationData" => $data
                ]));
            }
            Mail::send('/WorkerPanelMail/JobOpeningNotification', $data, function ($messages) use ($data) {
                $messages->to($data['email']);
                $sub = __('mail.job_status.subject');
                $messages->subject($sub);
            });
            return response()->json([
                'message' => 'Job opening time has been updated!'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Something went wrong!'
            ]);
        }
    }

    public function workerJob(Request $request, $wid, $jid)
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

        return response()->json([
            'data' => $job
        ]);
    }

    public function approveWorkerJob(Request $request, $wid, $jid)
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

        event(new WorkerApprovedJob($job));

        return response()->json([
            'data' => 'Job approved successfully'
        ]);
    }
}
