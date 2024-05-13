<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Schedule;
use App\Models\TeamMemberAvailability;
use App\Traits\TeamTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class TeamMemberController extends Controller
{
    use TeamTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $keyword = $request->q;

        $data = Admin::query()
            ->when($keyword, function ($query, $keyword) {
                $query
                    ->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('phone',   'like', '%' . $keyword . '%')
                    ->orWhere('status', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%')
                    ->where('name', '!=', 'superadmin');
            })
            ->where('id', '!=', Auth::id())
            ->where('name', '!=', 'superadmin')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return response()->json([
            'team' => $data,
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

    public function updateMyAvailability(Request $request)
    {
        $teamMember = Auth::user();

        $this->saveTeamAvailabilities($teamMember, $request->all());

        return response()->json([
            'message' => 'Availability updated successfully'
        ]);
    }

    public function myAvailability()
    {
        $teamMember = Auth::user();

        [$availabilities, $default_availabilities] = $this->getAvailabilities($teamMember);

        return response()->json([
            'data' => [
                'regular' => $availabilities,
                'default' => $default_availabilities
            ],
        ]);
    }

    public function updateAvailability(Request $request, $id)
    {
        $teamMember = Admin::find($id);

        $this->saveTeamAvailabilities($teamMember, $request->all());

        return response()->json([
            'message' => 'Availability updated successfully'
        ]);
    }

    public function availability($id)
    {
        $teamMember = Admin::find($id);

        [$availabilities, $default_availabilities] = $this->getAvailabilities($teamMember);

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

        if ($available_slots->count() == 0) {
            $weekDay = Carbon::parse($date)->weekday();
            $available_slots = $teamMember->defaultAvailabilities()
                ->where('weekday', $weekDay)
                ->whereDate('until_date', '>=', date('Y-m-d'))
                ->get(['start_time', 'end_time']);
        }

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
