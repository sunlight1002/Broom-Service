<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\User;
use App\Models\ManageTime;
use App\Models\WorkerNotAvailableDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $total_jobs      = Job::where('worker_id', Auth::id())->count();
        $latest_jobs     = Job::query()
            ->with(['client', 'offer', 'worker', 'jobservice'])
            ->where('worker_id', Auth::id())
            ->whereDate('start_date', '>=', today()->toDateString())
            ->orderBy('start_date', 'asc')
            ->take(10)
            ->get();

        return response()->json([
            'total_jobs'  => $total_jobs,
            'latest_jobs' => $latest_jobs
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
