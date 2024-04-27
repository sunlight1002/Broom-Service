<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ChangeWorkerRequestStatusEnum;
use App\Enums\JobStatusEnum;
use App\Events\JobWorkerChanged;
use App\Http\Controllers\Controller;
use App\Models\ChangeJobWorkerRequest;
use App\Models\Job;
use App\Models\ManageTime;
use App\Traits\JobSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChangeWorkerController extends Controller
{
    use JobSchedule;

    public function index()
    {
        $data = ChangeJobWorkerRequest::query()
            ->leftJoin('jobs', 'jobs.id', '=', 'change_job_worker_requests.job_id')
            ->leftJoin('job_services', 'job_services.job_id', '=', 'change_job_worker_requests.job_id')
            ->leftJoin('clients', 'clients.id', '=', 'change_job_worker_requests.client_id')
            ->leftJoin('users', 'users.id', '=', 'jobs.worker_id')
            ->leftJoin('users as w2', 'w2.id', '=', 'change_job_worker_requests.worker_id')
            ->select('change_job_worker_requests.*')
            ->addSelect('job_services.name as service_name')
            ->addSelect('job_services.heb_name as service_heb_name')
            ->addSelect('jobs.start_date as current_start_date')
            ->addSelect('jobs.shifts as current_shifts')
            ->selectRaw("CONCAT(users.firstname, ' ', users.lastname) as worker_name")
            ->selectRaw("CONCAT(clients.firstname, ' ', clients.lastname) as client_name")
            ->selectRaw("CONCAT(w2.firstname, ' ', w2.lastname) as new_worker_name")
            ->latest('change_job_worker_requests.created_at')
            ->paginate(20);

        return response()->json([
            'data' => $data,
        ]);
    }

    public function accept($id)
    {
        $changeWorkerRequest = ChangeJobWorkerRequest::query()
            ->where('status', ChangeWorkerRequestStatusEnum::PENDING)
            ->find($id);

        if (!$changeWorkerRequest) {
            return response()->json([
                'message' => "Request not found"
            ], 404);
        }

        if (Carbon::parse($changeWorkerRequest->date)->isPast()) {
            return response()->json([
                'message' => "Requested date is passed. Can't accept"
            ], 403);
        }

        $job = Job::query()
            ->with([
                'client',
                'worker',
                'jobservice',
            ])
            ->find($changeWorkerRequest->job_id);

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

        $oldWorker = $job->worker;

        $old_job_data = [
            'start_date' => $job->start_date,
            'shifts' => $job->shifts,
        ];

        $manageTime = ManageTime::first();
        $workingWeekDays = json_decode($manageTime->days);

        $repeat_value = $job->jobservice->period;

        $job_date = Carbon::parse($changeWorkerRequest->date);
        $preferredWeekDay = strtolower($job_date->format('l'));
        $next_job_date = $this->scheduleNextJobDate($job_date, $repeat_value, $preferredWeekDay, $workingWeekDays);

        $job_date = $job_date->toDateString();

        $slots = explode(',', $changeWorkerRequest->shifts);
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
            ->where('worker_id', $changeWorkerRequest->worker_id)
            ->exists()
        ) {
            $status = JobStatusEnum::UNSCHEDULED;
        }

        $jobData = [
            'worker_id'     => $changeWorkerRequest->worker_id,
            'start_date'    => $job_date,
            'shifts'        => $slotsInString,
            'status'        => $status,
            'next_start_date'   => $next_job_date,
        ];

        if ($changeWorkerRequest->repeatancy == 'one_time') {
            $jobData['previous_worker_id'] = $job->worker_id;
            $jobData['previous_worker_after'] = NULL;
            $jobData['previous_shifts'] = $job->shifts;
            $jobData['previous_shifts_after'] = NULL;
        } else if ($changeWorkerRequest->repeatancy == 'until_date') {
            $jobData['previous_worker_id'] = $job->worker_id;
            $jobData['previous_worker_after'] = $changeWorkerRequest->repeat_until_date;
            $jobData['previous_shifts'] = $job->shifts;
            $jobData['previous_shifts_after'] = $changeWorkerRequest->repeat_until_date;
        } else if ($changeWorkerRequest->repeatancy == 'forever') {
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

        $changeWorkerRequest->update([
            'status' => ChangeWorkerRequestStatusEnum::ACCEPTED,
            'action_by_type' => Auth::user()->role,
            'action_by_id' => Auth::user()->id,
        ]);

        $job->load(['client', 'worker', 'jobservice', 'propertyAddress']);

        event(new JobWorkerChanged($job, $mergedContinuousTime[0]['starting_at'], $old_job_data, $oldWorker));

        return response()->json([
            'message' => 'Job has been updated successfully'
        ]);
    }

    public function reject($id)
    {
        $changeWorkerRequest = ChangeJobWorkerRequest::query()
            ->where('status', ChangeWorkerRequestStatusEnum::PENDING)
            ->find($id);

        if (!$changeWorkerRequest) {
            return response()->json([
                'message' => "Request not found"
            ], 404);
        }

        $changeWorkerRequest->update([
            'status' => ChangeWorkerRequestStatusEnum::REJECTED,
            'action_by_type' => Auth::user()->role,
            'action_by_id' => Auth::user()->id,
        ]);

        return response()->json([
            'message' => 'Job has been updated successfully'
        ]);
    }
}
