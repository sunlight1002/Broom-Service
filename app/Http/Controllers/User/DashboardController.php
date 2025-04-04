<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\User;
use Yajra\DataTables\Facades\DataTables;
use App\Models\ManageTime;
use App\Models\WorkerNotAvailableDate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\HearingInvitation;
use Illuminate\Support\Facades\Storage;


class DashboardController extends Controller
{
    public function dashboard()
    {
        $workerID = Auth::id();
        $todayDate = today()->toDateString();
        $monthStartDate = today()->startOfMonth()->toDateString();

        $counts = Job::query()
            ->where('worker_id', $workerID)
            ->selectRaw("count(case when date(start_date) < ? then 1 end) as past_job_count", [$todayDate])
            ->selectRaw("sum(case when date(start_date) >= ? and date(start_date) < ? then actual_time_taken_minutes else 0 end) as past_job_minutes", [$monthStartDate, $todayDate])
            ->selectRaw("count(case when date(start_date) > ? then 1 end) as upcoming_job_count", [$todayDate])
            ->selectRaw("count(case when date(start_date) = ? then 1 end) as today_job_count", [$todayDate])
            ->first();

        $approval_pending_job = Job::query()
            ->with(['client', 'worker', 'jobservice', 'propertyAddress'])
            ->where('worker_id', $workerID)
            ->whereDate('start_date', Carbon::tomorrow()->toDateString())
            ->whereNull('worker_approved_at')
            ->orderBy('start_date', 'asc')
            ->take(10)
            ->get();

        return response()->json([
            'counts' => $counts,
            'approval_pending_job' => $approval_pending_job
        ]);
    }

    public function index(Request $request)
    {
        $query = HearingInvitation::query()
            ->leftJoin('admins', 'hearing_invitations.team_id', '=', 'admins.id') // Assuming admin details are linked via `admin_id`
            ->leftJoin('users', 'hearing_invitations.user_id', '=', 'users.id') // Join the User table for worker details
            ->where('hearing_invitations.user_id', Auth::user()->id) // Use worker's ID from the `User` model
            ->select(
                'hearing_invitations.id', 
                'hearing_invitations.booking_status', 
                'admins.name as attender_name', 
                'hearing_invitations.start_date', 
                'hearing_invitations.start_time', 
                'hearing_invitations.end_time', 
                'hearing_invitations.purpose', 
                'hearing_invitations.file',
                'users.firstname as worker_name',
                'users.address as address_name'
            );

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                if ($request->has('search')) {
                    $keyword = $request->get('search')['value'];
    
                    if (!empty($keyword)) {
                        $query->where(function ($sq) use ($keyword) {
                            $sq->where('admins.name', 'like', "%" . $keyword . "%")
                               ->orWhere('users.firstname', 'like', "%" . $keyword . "%"); // Allow search by worker name
                        });
                    }
                }
            })
            ->orderColumn('start_date', function ($query, $order) {
                $query->orderBy('start_date', $order)
                    ->orderBy('start_time', $order); // Assuming start_time is already formatted
            })
            ->addColumn('action', function ($data) {
                // Define actions (buttons or links) as needed, currently left empty
                return '';
            })
            ->addColumn('document', function ($data) {
                return $data->file ? '<a href="' . Storage::disk('public')->url($data->file) . '" target="_blank">Download Document</a>' : 'No Document';
            })
            ->rawColumns(['action', 'document'])
            ->toJson();
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
