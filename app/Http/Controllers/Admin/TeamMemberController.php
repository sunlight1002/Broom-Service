<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Schedule;
use App\Traits\TeamTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;
use App\Rules\ValidPhoneNumber;
use App\Jobs\AddGoogleContactForTeamJob;
use Laravel\Passport\Token;

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
        $query = Admin::query()
            ->where('id', '!=', Auth::id())
            ->where('name', '!=', 'superadmin');

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                if (request()->has('search')) {
                    $keyword = request()->get('search')['value'];

                    if (!empty($keyword)) {
                        $query->where(function ($sq) use ($keyword) {
                            $sq->where('admins.email', 'like', "%" . $keyword . "%")
                                ->orWhere('admins.phone', 'like', "%" . $keyword . "%")
                                ->orWhere('admins.name', 'like', "%" . $keyword . "%");
                        });
                    }
                }
            })
            ->addColumn('action', function ($data) {
                return '';
            })
            ->rawColumns(['action'])
            ->toJson();
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
            'phone'     => ['required', 'string', 'max:20', new ValidPhoneNumber()],
            'password' => ['required', 'min:6', 'required_with:confirmation', 'same:confirmation'],
            'status' => ['required'],
            'payment_type' => ['required', 'string'],
            'full_name' => ['required_if:payment_type,money_transfer'],
            'bank_name' => ['required_if:payment_type,money_transfer'],
            'bank_number' => ['required_if:payment_type,money_transfer'],
            'branch_number' => ['required_if:payment_type,money_transfer'],
            'account_number' => ['required_if:payment_type,money_transfer'],
        ], [
            'payment_type.required' => 'The payment type is required.',
            'full_name.required_if' => 'The full name is required.',
            'bank_name.required_if' => 'The bank name is required.',
            'bank_number.required_if' => 'The bank number is required.',
            'branch_number.required_if' => 'The branch number is required.',
            'account_number.required_if' => 'The account number is required.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $input = $request->input();

        $input['lng'] = $input ['lng'] ?? 'en';
        $input['password'] = Hash::make($input['password']);
        $admin = Admin::create($input);

        AddGoogleContactForTeamJob::dispatch($admin);

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
            'payment_type' => ['required', 'string'],
            'full_name' => ['required_if:payment_type,money_transfer'],
            'bank_name' => ['required_if:payment_type,money_transfer'],
            'bank_number' => ['required_if:payment_type,money_transfer'],
            'branch_number' => ['required_if:payment_type,money_transfer'],
            'account_number' => ['required_if:payment_type,money_transfer'],
        ], [
            'payment_type.required' => 'The payment type is required.',
            'full_name.required_if' => 'The full name is required.',
            'bank_name.required_if' => 'The bank name is required .',
            'bank_number.required_if' => 'The bank number is required .',
            'branch_number.required_if' => 'The branch number is required.',
            'account_number.required_if' => 'The account number is required.',
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
            Token::where('user_id', $admin->id)
                ->update(['revoked' => true]);
        } else {
            unset($request['password']);
        }

        $admin->update($request);

        AddGoogleContactForTeamJob::dispatch($admin);

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
                ->where(function ($q) {
                    $q
                        ->whereNull('until_date')
                        ->orWhereDate('until_date', '>=', date('Y-m-d'));
                })
                ->get(['start_time', 'end_time']);
        }

        $bookedSlots = Schedule::query()
            ->whereDate('start_date', $date)
            ->where('team_id', $teamMember->id)
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

    public function getAll()
    {
        $teams = Admin::query()
            ->where('name', '!=', 'superadmin')
            ->select('id', 'name', 'status')
            ->get();

        return response()->json([
            'data' => $teams
        ]);
    }
}
