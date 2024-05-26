<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\User;
use App\Models\ManageTime;
use App\Models\WorkerNotAvailableDate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $workerID = Auth::id();
        $todayDate = today()->toDateString();

        $counts = Job::query()
            ->where('worker_id', $workerID)
            ->selectRaw("count(case when date(start_date) < ? then 1 end) as past_job_count", [$todayDate])
            ->selectRaw("sum(case when date(start_date) < ? then actual_time_taken_minutes else 0 end) as past_job_minutes", [$todayDate])
            ->selectRaw("count(case when date(start_date) > ? then 1 end) as upcoming_job_count", [$todayDate])
            ->selectRaw("count(case when date(start_date) = ? then 1 end) as today_job_count", [$todayDate])
            ->first();

        $approval_pending_job = Job::query()
            ->with(['client', 'jobservice'])
            ->where('worker_id', $workerID)
            ->whereDate('start_date', Carbon::tomorrow()->toDateString())
            ->whereNull('worker_approved_at')
            ->orderBy('start_date', 'asc')
            ->take(10)
            ->get(['id', 'start_date', 'shifts', 'status', 'client_id']);

        return response()->json([
            'counts' => $counts,
            'approval_pending_job' => $approval_pending_job
        ]);
    }

    public function getTime()
    {
        return response()->json([
            'data' => ManageTime::where('id', 1)->first()
        ]);
    }

    public function addNotAvailableDates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date'     => 'required',
            'start_time' => 'required_with:end_time',
            'end_time' => 'required_with:start_time',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        WorkerNotAvailableDate::create([
            'user_id' => Auth::user()->id,
            'date'    => $request->date,
            'status'  => $request->status,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time
        ]);

        return response()->json(['message' => 'Date added']);
    }

    public function notAvailableDates()
    {
        $dates = WorkerNotAvailableDate::where(['user_id' => Auth::user()->id])->get();
        return response()->json(['dates' => $dates]);
    }

    public function deleteNotAvailableDates(Request $request)
    {
        WorkerNotAvailableDate::find($request->id)->delete();
        return response()->json(['message' => 'date deleted']);
    }
}
