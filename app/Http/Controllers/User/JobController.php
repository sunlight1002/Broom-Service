<?php

namespace App\Http\Controllers\User;

use App\Enums\JobStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Admin;
use App\Models\WorkerAvailability;
use App\Models\JobHours;
use App\Models\Problems;
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
use App\Enums\WorkerAffectedAvailabilityStatusEnum;
use App\Events\JobNotificationToAdmin;
use App\Events\WorkerChangeAffectedAvailability;
use App\Models\WorkerAffectedAvailability;

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
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');

        $jobs = Job::query()
            ->with('worker', 'client', 'offer', 'jobservice', 'propertyAddress')
            ->where('worker_id', Auth::id())
            ->when($start_date, function ($q) use ($start_date) {
                return $q->whereDate('start_date', '>=', $start_date);
            })
            ->when($end_date, function ($q) use ($end_date) {
                return $q->whereDate('start_date', '<=', $end_date);
            })
            ->orderBy('start_date')
            ->paginate(20);

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

        $upcomingJobDates = Job::query()
            ->where('worker_id', $worker->id)
            ->whereDate('start_date', '>=', today()->toDateString())
            ->whereIn(
                'status',
                [
                    JobStatusEnum::PROGRESS,
                    JobStatusEnum::SCHEDULED,
                    JobStatusEnum::UNSCHEDULED,
                ]
            )
            ->pluck('start_date')
            ->toArray();

        foreach ($upcomingJobDates as $key => $date_) {
            $currentDate = Carbon::parse($date_);
            $weekDay = $currentDate->weekday();
            $dateString = $currentDate->toDateString();

            $newTimeSlotsByDate = [];
            $newTimeSlotsByWeekDay = [];

            $newTimeSlots = [];
            if (isset($data['time_slots'][$dateString])) {
                $newTimeSlots = $newTimeSlotsByDate = $data['time_slots'][$dateString];
            } elseif (
                isset($data['default']['time_slots']) &&
                isset($data['default']['time_slots'][$weekDay])
            ) {
                $newTimeSlots = $newTimeSlotsByWeekDay = $data['default']['time_slots'][$weekDay];
            }

            $oldTimeSlots = $oldTimeSlotsByDate = $worker->availabilities()
                ->whereDate('date', $dateString)
                ->selectRaw('DATE_FORMAT(start_time, "%H:%i") AS start_time')
                ->selectRaw('DATE_FORMAT(end_time, "%H:%i") AS end_time')
                ->get()
                ->toArray();

            $oldTimeSlotsByWeekDay = $worker->defaultAvailabilities()
                ->where('weekday', $weekDay)
                ->where(function ($q) use ($dateString) {
                    $q
                        ->whereNull('until_date')
                        ->orWhereDate('until_date', '>=', $dateString);
                })
                ->selectRaw('DATE_FORMAT(start_time, "%H:%i") AS start_time')
                ->selectRaw('DATE_FORMAT(end_time, "%H:%i") AS end_time')
                ->get()
                ->toArray();
            if (empty($oldTimeSlots)) {
                $oldTimeSlots = $oldTimeSlotsByWeekDay;
            }

            if ($this->timeArraysAreDifferent($oldTimeSlots, $newTimeSlots)) {
                $workerAffectAvail = WorkerAffectedAvailability::create([
                    'worker_id' => $worker->id,
                    'old_values' => [
                        'date' => $dateString,
                        'time_by_date' => $oldTimeSlotsByDate,
                        'weekday' => $weekDay,
                        'time_by_weekday' => $oldTimeSlotsByWeekDay,
                    ],
                    'new_values' => [
                        'date' => $dateString,
                        'time_by_date' => $newTimeSlotsByDate,
                        'weekday' => $weekDay,
                        'time_by_weekday' => $newTimeSlotsByWeekDay,
                    ],
                    'status' => WorkerAffectedAvailabilityStatusEnum::PENDING
                ]);

                event(new WorkerChangeAffectedAvailability($worker, $dateString, $workerAffectAvail));
            }
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

    public function JobStartTime($id)
    {
        $job = Job::find($id);

        $time = JobHours::query()
            ->where('worker_id', Auth::id())
            ->where('job_id', $id)
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
        }

        JobHours::create([
            'job_id' => $job->id,
            'worker_id' => Auth::id(),
            'start_time' => $currentDateTime,
        ]);

        return response()->json([
            'message' => 'Updated Successfully',
        ]);
    }

    public function JobEndTime($id)
    {
        $time = JobHours::query()
            ->where('worker_id', Auth::id())
            ->where('job_id', $id)
            ->whereNull('end_time')
            ->first();

        if (!$time) {
            return response()->json([
                'message' => 'Resume timer',
            ], 404);
        }

        $currentDateTime = now()->toDateTimeString();

        $time->update([
            'end_time' => $currentDateTime,
            'time_diff' => Carbon::parse($time->start_time)->diffInSeconds(),
        ]);

        $this->updateJobWorkerMinutes($time->job_id);

        return response()->json([
            'message' => 'Updated Successfully',
        ]);
    }

    public function getJobTime(Request $request)
    {
        $time = JobHours::where('job_id', $request->job_id)->where('worker_id', Auth::id());
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
            $job = Job::query()
                ->with(['worker', 'client', 'jobservice', 'propertyAddress'])
                ->where('worker_id', Auth::id())
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
            //send notification to worker
            $worker = $job['worker'];
            App::setLocale($worker['lng']);

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

    public function timeArraysAreDifferent($array1, $array2)
    {
        // Check if both arrays have the same length
        if (count($array1) !== count($array2)) {
            return true;
        }

        // Iterate through the arrays
        foreach ($array1 as $index => $timeRange1) {
            if (isset($array2[$index])) {
                $timeRange2 = $array2[$index];

                // Compare each time range
                if ($timeRange1['start_time'] !== $timeRange2['start_time'] || $timeRange1['end_time'] !== $timeRange2['end_time']) {
                    return true;
                }
            } else {
                // If the second array does not have a corresponding index
                return true;
            }
        }

        // If no differences were found
        return false;
    }

    public function todayJobs()
    {
        $today_jobs = Job::query()
            ->with(['client', 'jobservice', 'propertyAddress'])
            ->where('worker_id', Auth::id())
            ->whereDate('start_date', Carbon::today()->toDateString())
            ->where('status', '!=', JobStatusEnum::COMPLETED)
            ->take(10)
            ->get();

        $today_jobs = $today_jobs->map(function ($job, $key) {
            $jobArr = $job->toArray();

            $time = JobHours::where('job_id', $jobArr['id'])
                ->where('worker_id', Auth::id())
                ->get(['start_time', 'end_time']);

            $jobArr['time'] = $time;

            return $jobArr;
        });

        return response()->json([
            'today_jobs' => $today_jobs
        ]);
    }

    public function addProblems($id)
    {

        // Fetch the job with its related worker and client data using the $id parameter
        $job = Job::with(['client', 'worker'])->findOrFail($id);

        // Prepare necessary notification data
        $client = $job->client;
        $worker = $job->worker;

        // Fire the WhatsappNotificationEvent with the needed data
        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::TEAM_NOTIFY_CONTACT_MANAGER,
            "notificationData" => [
                'job' => $job,
                'client' => $client,
                'worker' => $worker,
            ]
        ]));

        // Return a response back to the frontend
        return response()->json(['message' => 'Notification sent successfully to Manger.'], 200);
    }

    public function NeedExtraTime(Request $request)
    {
        $job_id = $request->job_id;
        $job = Job::with(['client', 'worker', 'propertyAddress'])->where('id', $job_id)->first();
        if(!$job){
            return response()->json(['error' => 'Job not found'], 404);
        }
        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::WORKER_NEED_EXTRA_TIME,
            "notificationData" => array(
                'job'        => $job->toArray(),
            )
        ]));

        return response()->json(['message' => 'Notification sent successfully to Manger for extra time...'], 200);
    }

    public function ContactManager(Request $request)
    {
        \Log::info($request->all());
        $validated = $request->validate([
            'problem' => 'required|string|max:1000',
        ]);

        $job = Job::with(['client', 'worker', 'propertyAddress'])->where('uuid', $request->uuid)->first();

        if (!$job) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        $problem = new Problems();
        $problem->client_id = $job->client_id;
        $problem->job_id = $job->id;
        $problem->worker_id = $job->worker_id;
        $problem->problem = $validated['problem'];
        $problem->save();

        // Dispatch the WhatsApp notification event
        event(new WhatsappNotificationEvent([
            'type' => WhatsappMessageTemplateEnum::WORKER_CONTACT_TO_MANAGER,
            'notificationData' => [
                'job' => $job->toArray(),
                'client' => $job->client->toArray(),
                'worker' => $job->worker->toArray(),
            ]
        ]));

        // Return success response
        return response()->json(['message' => 'Problem saved successfully'], 201);
    }

    public function leaveForWork(Request $request)
    {
        $rData = $request->all();
        try {
            $job = Job::query()
                ->with(['worker', 'client', 'jobservice', 'propertyAddress'])
                ->whereNotIn('status', [JobStatusEnum::CANCEL, JobStatusEnum::COMPLETED])
                ->where('uuid', $rData['uuid'])
                ->first();

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
            //send notification to worker
            $worker = $job['worker'];
            App::setLocale($worker['lng']);

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

    public function getJobByUuid($uuid)
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
            ->where('uuid', $uuid)
            ->first();

        if (!$job) {
            return response()->json([
                'message' => 'Job not found',
            ], 404);
        }

        return response()->json([
            'job' => $job,
        ]);
    }



    public function JobStartTimeUuid($uuid)
    {
        $job = Job::where('uuid', $uuid)->first();

        $time = JobHours::query()
            ->where('worker_id', $job->worker_id)
            ->where('job_id', $job->id)
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
        }

        JobHours::create([
            'job_id' => $job->id,
            'worker_id' => $job->worker_id,
            'start_time' => $currentDateTime,
        ]);

        return response()->json([
            'message' => 'Updated Successfully',
        ]);
    }

    public function JobEndTimeUuid($uuid)
    {
        $job = Job::where('uuid', $uuid)->first();

        $time = JobHours::query()
            ->where('job_id', $job->id)
            ->whereNull('end_time')
            ->first();

        if (!$time) {
            return response()->json([
                'message' => 'Resume timer',
            ], 404);
        }

        $currentDateTime = now()->toDateTimeString();

        $time->update([
            'end_time' => $currentDateTime,
            'time_diff' => Carbon::parse($time->start_time)->diffInSeconds(),
        ]);

        $this->updateJobWorkerMinutes($time->job_id);

        return response()->json([
            'message' => 'Updated Successfully',
        ]);
    }

    public function getJobTimeUuid(Request $request)
    {
        $job = Job::where('uuid', $request->uuid)->first();
        $total = 0;

        $time = JobHours::where('job_id', $job->id)->where('worker_id', $job->worker_id);
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
