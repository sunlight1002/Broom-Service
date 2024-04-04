<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\TeamMemberAvailability;
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

    public function updateAvailability(Request $request)
    {
        $data = $request->all();
        $time_slots = $data['time_slots'];
        $team_id = $data['teamId'];
        try {
            TeamMemberAvailability::updateOrCreate([
                'team_member_id' => $team_id,
            ], [
                'time_slots' => $time_slots
            ]);

            return response()->json([
                'message' => 'Availability updated successfully'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error'
            ], 500);
        }
    }

    public function availability($id)
    {
        $availArr = TeamMemberAvailability::select('time_slots')->where('team_member_id', $id)->first();
        return response()->json([
            'data' => $availArr
        ]);
    }
}
