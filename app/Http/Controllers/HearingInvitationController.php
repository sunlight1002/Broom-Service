<?php

namespace App\Http\Controllers;

use App\Models\HearingInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Schedule;
use App\Models\Admin;
use App\Models\User;
use Carbon\Carbon;
use App\Traits\GoogleAPI;

class HearingInvitationController extends Controller
{
    /**
     * Display the specified hearing invitation.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $invitation = HearingInvitation::find($id);

        if (!$invitation) {
            return response()->json(['message' => 'Hearing Invitation not found'], 404);
        }

        return response()->json(['schedule' => $invitation], 200);
    }

    /**
     * Store a newly created hearing invitation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'team_id' => 'nullable|integer',
            'start_date' => 'required|date',
            'start_time' => 'required|string',
            'meet_via' => 'required|string',
            'meet_link' => 'nullable|string',
            'purpose' => 'nullable|string',
            'booking_status' => 'nullable|string',
            'address_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $startTime = Carbon::createFromFormat('Y-m-d h:i A', date('Y-m-d') . ' ' . $request->input('start_time'))->format('h:i A');
        $endTime = Carbon::createFromFormat('Y-m-d h:i A', date('Y-m-d') . ' ' . $startTime)->addMinutes(30)->format('h:i A');

        $invitationData = $request->only([
            'user_id', 
            'team_id', 
            'start_date', 
            'meet_via', 
            'meet_link', 
            'purpose', 
            'booking_status', 
            'address_id'
        ]);

        $invitationData['start_time'] = $startTime; 
        $invitationData['end_time'] = $endTime;  

        $invitation = HearingInvitation::create($invitationData);

        return response()->json(['message' => 'Hearing Invitation created successfully', 'data' => $invitation], 201);
    }

    /**
     * Update the specified hearing invitation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $invitation = HearingInvitation::find($id);

        if (!$invitation) {
            return response()->json(['message' => 'Hearing Invitation not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'team_id' => 'nullable|integer',
            'start_date' => 'required|date',
            'start_time' => 'required|string',
            'meet_via' => 'required|string',
            'meet_link' => 'nullable|string',
            'purpose' => 'nullable|string',
            'booking_status' => 'nullable|string',
            'address_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $invitation->update($request->all());

        return response()->json(['message' => 'Hearing Invitation updated successfully', 'data' => $invitation], 200);
    }

    /**
     * Create a new event for the specified hearing invitation.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function createEvent($id)
    {
        $invitation = HearingInvitation::find($id);

        if (!$invitation) {
            return response()->json(['message' => 'Hearing Invitation not found'], 404);
        }

        return response()->json(['message' => 'Event created successfully for hearing invitation', 'data' => $invitation], 201);
    }

    public function index(Request $request)
    {
        $query = HearingInvitation::query()
            ->leftJoin('admins', 'hearing_invitations.team_id', '=', 'admins.id')
            ->leftJoin('users', 'hearing_invitations.user_id', '=', 'users.id')
            ->select(
                'hearing_invitations.id',
                'hearing_invitations.start_date',
                'hearing_invitations.start_time',
                'hearing_invitations.end_time',
                'hearing_invitations.booking_status',
                'admins.name as attender_name',
                'users.firstname',
                'users.lastname',
                'users.phone',
                'users.address',
                'users.id as worker_id'
            );

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                if ($request->has('search')) {
                    $keyword = $request->get('search')['value'];

                    if (!empty($keyword)) {
                        $query->where(function ($sq) use ($keyword) {
                            $sq->whereRaw("CONCAT_WS(' ', users.firstname, users.lastname) like ?", ["%{$keyword}%"])
                                ->orWhere('users.address', 'like', "%" . $keyword . "%")
                                ->orWhere('users.phone', 'like', "%" . $keyword . "%")
                                ->orWhere('admins.name', 'like', "%" . $keyword . "%");
                        });
                    }
                }
            })
            ->editColumn('name', function ($data) {
                return $data->firstname . ' ' . $data->lastname; // Concatenate worker's name
            })
            ->filterColumn('name', function ($query, $keyword) {
                $sql = "CONCAT_WS(' ', users.firstname, users.lastname) like ?";
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->orderColumn('name', function ($query, $order) {
                $query->orderBy('users.firstname', $order);
            })
            ->orderColumn('start_date', function ($query, $order) {
                $query->orderBy('hearing_invitations.start_date', $order)
                    ->orderBy('hearing_invitations.start_time', $order);
            })
            ->addColumn('action', function ($data) {
                return ''; // Define your action buttons here if needed
            })
            ->rawColumns(['action'])
            ->toJson();
    }

    

}
