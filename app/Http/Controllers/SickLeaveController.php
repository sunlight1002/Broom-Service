<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SickLeave;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class SickLeaveController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $columns = ['id', 'start_date', 'end_date', 'status', 'rejection_comment'];

        $length = $request->get('length', 10); // Number of records per page
        $start = $request->get('start', 0); // Pagination start

        $order = $request->get('order', []);
        $columnIndex = $order[0]['column'] ?? 0;
        $direction = $order[0]['dir'] ?? 'asc';

        $search = $request->get('search', []);
        $searchValue = $search['value'] ?? '';

        $query = SickLeave::where('worker_id', $user->id)
                    ->with('user');

        // Search filter
        if ($searchValue) {
            $query->where(function ($query) use ($searchValue) {
                $query->where('start_date', 'like', "%{$searchValue}%")
                    ->orWhere('end_date', 'like', "%{$searchValue}%")
                    ->orWhere('status', 'like', "%{$searchValue}%")
                    ->orWhere('rejection_comment', 'like', "%{$searchValue}%");
            });
        }

        // Sorting
        $query->orderBy($columns[$columnIndex] ?? 'id', $direction);

        $totalRecords = $query->count();
        $sickLeaves = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => intval($request->get('draw')),
            'data' => $sickLeaves,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
        ]);
    }



    public function store(Request $request)
    {
        $validated = $request->validate([

            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'doctor_report' => 'required|file|mimes:pdf,jpg,png',
            'reason_for_leave' => 'nullable|string'
        ]);

        $workerId = Auth::id();

        $reportPath = null;
        if ($request->hasFile('doctor_report')) {
            $reportPath = $request->file('doctor_report')->store('doctor_reports', 'public');
        }

        $sickLeave = SickLeave::create([
            'worker_id' =>  $workerId,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'doctor_report_path' => $reportPath,
            'status' => 'pending', 
            'reason_for_leave'=>$validated['reason_for_leave'],
        ]);

        return response()->json($sickLeave, 201);

    }

    public function show($id)
    {
        $sickLeave = SickLeave::select('id', 'worker_id', 'start_date', 'end_date', 'doctor_report_path','reason_for_leave')   
            ->find($id);

        if (!$sickLeave) {
            return response()->json(['message' => 'Sick leave not found'], 404);
        }   
        $sickLeave->doctor_report_url = $sickLeave->doctor_report_path ? asset('storage/' . $sickLeave->doctor_report_path) : null;
        return response()->json($sickLeave);
    }

    public function update(Request $request, $id)
    {
        $sickLeave = SickLeave::findOrFail($id);

        // Validate the request data with required fields
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'doctor_report' => 'nullable|file|mimes:pdf,jpg,png',
            'reason_for_leave' => 'nullable|string'
        ]);

        $workerId = Auth::id();

        $sickLeave->worker_id = $workerId;
        $sickLeave->start_date = $validated['start_date'];
        $sickLeave->end_date = $validated['end_date'];
        $sickLeave->reason_for_leave = $validated['reason_for_leave'] ?? $sickLeave->reason_for_leave;

        if ($request->hasFile('doctor_report')) {
            if ($sickLeave->doctor_report_path) {
                Storage::disk('public')->delete($sickLeave->doctor_report_path);
            }
            $reportPath = $request->file('doctor_report')->store('doctor_reports', 'public');
            $sickLeave->doctor_report_path = $reportPath;
        }

        $sickLeave->save();

        return response()->json($sickLeave);
    }

    public function destroy($id)
    {
        $sickLeave = SickLeave::findOrFail($id);

        if ($sickLeave->doctor_report_path) {
            Storage::disk('public')->delete($sickLeave->doctor_report_path);
        }
        $sickLeave->delete();

        return response()->json(null, 204);
    }

    public function allLeaves(Request $request)
    {
        $columns = ['id', 'worker_name', 'start_date', 'end_date', 'status'];

        $length = $request->get('length', 10);
        $start = $request->get('start', 0);
        $column = $request->get('column', 0);
        $dir = $request->get('dir', 'asc');
        $search = $request->get('search', '');
        $status = $request->get('status', 'all');

        $query = SickLeave::with('user')
            ->select('sick_leaves.*') // Ensure to select from sick_leaves
            ->orderBy('created_at', 'desc');

        // Search filter
        if ($search) {
            $query->whereHas('user', function ($query) use ($search) {
                $query->where('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%");
            })
            ->orWhere('start_date', 'like', "%{$search}%")
            ->orWhere('end_date', 'like', "%{$search}%");
        }

        // Status filter
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Sorting
        $query->orderBy($columns[$column], $dir);

        // Pagination
        $totalRecords = $query->count();
        $leaveRequests = $query->skip($start)->take($length)->get()
            ->map(function ($leave) {
                $leave->start_date = Carbon::parse($leave->start_date)->format('Y-m-d');
                $leave->end_date = Carbon::parse($leave->end_date)->format('Y-m-d');

                $leave->doctor_report_path = $leave->doctor_report_path
                    ? url('storage/doctor_reports/' . basename($leave->doctor_report_path))
                    : null;

                $leave->worker_name = $leave->user ? $leave->user->firstname . ' ' . $leave->user->lastname : 'Unknown';

                return $leave;
            });

        return response()->json([
            'draw' => intval($request->get('draw')),
            'data' => $leaveRequests,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
        ]);
    }


    public function approve(Request $request,SickLeave $sickLeave)
    {
       $status = $request->input('status');  
       $rejectionReason = $request->input('rejection_comment'); 

       $validStatuses = ['approved', 'rejected', 'pending'];

        if (!in_array($status, $validStatuses)) {
            return response()->json(['error' => 'Invalid status'], 400);
        }
       $sickLeave->status = $status;

        if ($status === 'rejected') {
            $sickLeave->rejection_comment = $rejectionReason;
        }

        $sickLeave->save();

        return response()->json($sickLeave);
    }
}
