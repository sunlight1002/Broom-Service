<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Schedule;
use App\Models\TeamMemberAvailability;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class TeamMemberController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        /*$team = Admin::query()->where('role','admin')->orWhere('role','member');
        $team = $team->orderBy('id','desc')->paginate(10);
        return response()->json([
            'team' => $team
        ]);*/
        $q = $request->q;
        $result = Admin::query();
        /* $result->where('name',    'like','%'.$q.'%');
        $result->orWhere('phone',      'like','%'.$q.'%');
        $result->orWhere('status',     'like','%'.$q.'%');
        $result->orWhere('email',      'like','%'.$q.'%');*/
        if (isset($request->q)) {
            $q = $request->q;
            $result->orWhere(function ($qry) use ($q) {
                $qry->where('name', 'like', '%' . $q . '%')
                    ->orWhere('phone',   'like', '%' . $q . '%')
                    ->orWhere('status', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%')
                    ->where('name', '!=', 'superadmin');
            });
        }

        $result = $result->orderBy('id', 'desc')->where('name', '!=', 'superadmin')->paginate(20);

        return response()->json([
            'team' => $result,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:admins'],
            'phone' => ['required'],
            'password' => ['required', 'min:6', 'required_with:confirmation', 'same:confirmation'],
            'status' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $input = $request->input();
        $input['password'] = Hash::make($input['password']);
        Admin::create($input);

        return response()->json([
            'message' => 'Team member added successfully'
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\TeamMember  $teamMember
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $admin = Admin::find($id);
        if (!$admin) {
            return response()->json([
                'error' => [
                    'message' => 'Team member not found!'
                ]
            ], 404);
        }

        return response()->json([
            'data' => $admin
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TeamMember  $teamMember
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins,email,' . $id],
            'phone' => ['required'],
            'password' => $request->password ? ['min:6', 'required_with:confirmation', 'same:confirmation'] : [],
            'status' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $admin = Admin::find($id);
        if (!$admin) {
            return response()->json([
                'error' => [
                    'message' => 'Team member not found!'
                ]
            ], 404);
        }

        $request = $request->except(['confirmation']);
        if ($request['password'] != null) {
            $request['password'] = Hash::make($request['password']);
        } else {
            unset($request['password']);
        }

        $admin->update($request);
        return response()->json([
            'message' => 'Team member updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TeamMember  $teamMember
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $admin = Admin::find($id);

        if (!$admin) {
            return response()->json([
                'error' => [
                    'message' => 'Team member not found!'
                ]
            ], 404);
        }

        $admin->delete();
        return response()->json([
            'message' => 'Team member deleted successfully'
        ]);
    }

    public function updateAvailability(Request $request, $id)
    {
        $teamMember = Admin::find($id);

        $data = $request->all();

        $teamMember->availabilities()->delete();

        foreach ($data['time_slots'] as $key => $availabilties) {
            $date = trim($key);

            foreach ($availabilties as $key => $availabilty) {
                TeamMemberAvailability::create([
                    'team_member_id' => $id,
                    'date' => $date,
                    'start_time' => $availabilty['start_time'],
                    'end_time' => $availabilty['end_time'],
                    'status' => '1',
                ]);
            }
        }

        $teamMember->defaultAvailabilities()->delete();

        if (isset($data['default']['time_slots'])) {
            foreach ($data['default']['time_slots'] as $weekday => $availabilties) {
                foreach ($availabilties as $key => $timeSlot) {
                    $teamMember->defaultAvailabilities()->create([
                        'weekday' => $weekday,
                        'start_time' => $timeSlot['start_time'],
                        'end_time' => $timeSlot['end_time'],
                        'until_date' => $data['default']['until_date'],
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Availability updated successfully'
        ]);
    }

    public function availability($id)
    {
        $teamMember = Admin::find($id);

        $team_member_availabilities = $teamMember->availabilities()
            ->orderBy('date', 'asc')
            ->get(['date', 'start_time', 'end_time']);

        $availabilities = [];
        foreach ($team_member_availabilities->groupBy('date') as $date => $times) {
            $availabilities[$date] = $times->map(function ($item, $key) {
                return $item->only(['start_time', 'end_time']);
            });
        }

        $default_availabilities = $teamMember->defaultAvailabilities()
            ->orderBy('id', 'asc')
            ->get(['weekday', 'start_time', 'end_time', 'until_date'])
            ->groupBy('weekday');

        return response()->json([
            'data' => [
                'regular' => $availabilities,
                'default' => $default_availabilities
            ],
        ]);
    }

    public function availabilityByDate($id, $date)
    {
        $teamMember = Admin::find($id);

        $available_slots = $teamMember->availabilities()
            ->whereDate('date', $date)
            ->get(['start_time', 'end_time']);

        $bookedSlots = Schedule::query()
            ->whereDate('start_date', $date)
            ->whereNotNull('start_time')
            ->where('start_time', '!=', '')
            ->whereNotNull('end_time')
            ->where('end_time', '!=', '')
            // ->selectRaw("DATE_FORMAT(start_date, '%Y-%m-%d') as start_date")
            ->selectRaw("DATE_FORMAT(STR_TO_DATE(start_time, '%h:%i %p'), '%H:%i') as start_time")
            ->selectRaw("DATE_FORMAT(STR_TO_DATE(end_time, '%h:%i %p'), '%H:%i') as end_time")
            ->get();

        return response()->json([
            'booked_slots' => $bookedSlots,
            'available_slots' => $available_slots
        ]);
    }
}
