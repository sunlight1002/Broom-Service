<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\User;
use App\Models\ManageTime;
use App\Models\WorkerNotAvailableDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $id              = $request->id;
        $total_jobs      = Job::where('worker_id', $id)->count();
        $latest_jobs     = Job::query()
            ->with(['client', 'offer', 'worker', 'jobservice'])
            ->where('worker_id', $id)
            ->orderBy('id', 'desc')
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
            'worker_id'  => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $date = new WorkerNotAvailableDate;
        $date->user_id = $request->worker_id;
        $date->date    = $request->date;
        $date->status  = $request->status;
        $date->save();

        return response()->json(['message' => 'Date added']);
    }

    public function getNotAvailableDates(Request $request)
    {
        $dates = WorkerNotAvailableDate::where(['user_id' => $request->id])->get();
        return response()->json(['dates' => $dates]);
    }

    public function deleteNotAvailableDates(Request $request)
    {
        WorkerNotAvailableDate::find($request->id)->delete();
        return response()->json(['message' => 'date deleted']);
    }
}
