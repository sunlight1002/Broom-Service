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
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

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
        
         $worker = User::find($request->input('user_id'));
         $teamName = null;
         
         if ($request->input('team_id')) {
            $team = Admin::find($request->input('team_id'));
            $teamName = $team ? $team->name : "No team specified";
        }

         if ($worker) {
             // Prepare the notification data
             $notificationData = [
                 'worker' => [
                     'phone' => $worker->phone,
                     'lng' => $worker->lng,
                     'firstname' => $worker->firstname,
                     'lastname' => $worker->lastname,
                 ], 
                 'start_date' => $request->input('start_date'),
                 'start_time' => $startTime,
                 'end_time' => $endTime,
                 'purpose' => $request->input('purpose'),
                 'team_name' => $teamName,
                 'id' => $invitation->id,
             ];
 
             // Dispatch the WhatsApp notification event
             event(new WhatsappNotificationEvent([
                'type' => WhatsappMessageTemplateEnum::WORKER_HEARING_SCHEDULE,
                'notificationData' => $notificationData
            ]));         
        }

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

        $startDateTime = Carbon::createFromFormat('Y-m-d h:i A', $request->input('start_date') . ' ' . $request->input('start_time'));
        $endDateTime = $startDateTime->copy()->addMinutes(30);

        $invitation->update([
            'user_id' => $request->input('user_id'),
            'team_id' => $request->input('team_id'),
            'start_date' => $request->input('start_date'),
            'start_time' => $startDateTime->format('h:i A'),
            'end_time' => $endDateTime->format('h:i A'),
            'meet_via' => $request->input('meet_via'),
            'meet_link' => $request->input('meet_link'),
            'purpose' => $request->input('purpose'),
            'booking_status' => $request->input('booking_status'),
            'address_id' => $request->input('address_id'),
        ]);    

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

    // public function index(Request $request)
    // {
    //     $query = HearingInvitation::query()
    //         ->leftJoin('admins', 'hearing_invitations.team_id', '=', 'admins.id')
    //         ->leftJoin('users', 'hearing_invitations.user_id', '=', 'users.id')
    //         ->select(
    //             'hearing_invitations.id',
    //             'hearing_invitations.start_date',
    //             'hearing_invitations.start_time',
    //             'hearing_invitations.end_time',
    //             'hearing_invitations.booking_status',
    //             'admins.name as attender_name',
    //             'users.firstname',
    //             'users.lastname',
    //             'users.phone',
    //             'users.address',
    //             'users.id as worker_id'
    //         );

    //     return DataTables::eloquent($query)
    //         ->filter(function ($query) use ($request) {
    //             if ($request->has('search')) {
    //                 $keyword = $request->get('search')['value'];

    //                 if (!empty($keyword)) {
    //                     $query->where(function ($sq) use ($keyword) {
    //                         $sq->whereRaw("CONCAT_WS(' ', users.firstname, users.lastname) like ?", ["%{$keyword}%"])
    //                             ->orWhere('users.address', 'like', "%" . $keyword . "%")
    //                             ->orWhere('users.phone', 'like', "%" . $keyword . "%")
    //                             ->orWhere('admins.name', 'like', "%" . $keyword . "%");
    //                     });
    //                 }
    //             }
    //         })
    //         ->editColumn('name', function ($data) {
    //             return $data->firstname . ' ' . $data->lastname; // Concatenate worker's name
    //         })
    //         ->filterColumn('name', function ($query, $keyword) {
    //             $sql = "CONCAT_WS(' ', users.firstname, users.lastname) like ?";
    //             $query->whereRaw($sql, ["%{$keyword}%"]);
    //         })
    //         ->orderColumn('name', function ($query, $order) {
    //             $query->orderBy('users.firstname', $order);
    //         })
    //         ->orderColumn('start_date', function ($query, $order) {
    //             $query->orderBy('hearing_invitations.start_date', $order)
    //                 ->orderBy('hearing_invitations.start_time', $order);
    //         })
    //         ->addColumn('action', function ($data) {
    //             return '';
    //         })
    //         ->rawColumns(['action'])
    //         ->toJson();
    // }

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

    // Filter by worker ID if provided
    if ($request->has('worker_id')) {
        $query->where('hearing_invitations.user_id', $request->input('worker_id'));
    }

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
            return $data->firstname . ' ' . $data->lastname;
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
            return '';
        })
        ->rawColumns(['action'])
        ->toJson();
}



    public function getScheduledHearings($id)
    {
        $hearing = HearingInvitation::find($id);
    
        if (!$hearing) {
            return response()->json(['message' => 'Hearing Invitation not found'], 404);
        }
    
        return response()->json($hearing);
    }
    
    public function destroy($id)
    {
        $invitation = HearingInvitation::find($id);

        if (!$invitation) {
            return response()->json(['message' => 'Hearing Invitation not found'], 404);
        }
        $invitation->delete();

        return response()->json(['message' => 'Hearing Invitation deleted successfully'], 200);
    }
}
