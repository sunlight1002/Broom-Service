<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class ServiceSchedulesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = ServiceSchedule::query();

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                if (request()->has('search')) {
                    $keyword = request()->get('search')['value'];

                    if (!empty($keyword)) {
                        $query->where(function ($sq) use ($keyword) {
                            $sq->where('service_schedules.name', 'like', "%" . $keyword . "%");
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

    public function allSchedules()
    {
        $schedule = ServiceSchedule::where('status', 1)->get();

        return response()->json([
            'schedules' => $schedule,
        ]);
    }

    public function allSchedulesByLng(Request $request)
    {
        $schedules = ServiceSchedule::where('status', 1)->get();

        $result = [];
        foreach ($schedules as $schedule) {
            $res['name'] = ($request->lng == 'en') ? $schedule->name : $schedule->name_heb;
            $res['id']   = $schedule->id;
            $res['cycle'] = $schedule->cycle;
            $res['period'] = $schedule->period;
            $res['icon'] = $schedule->icon;
            array_push($result, $res);
        }

        return response()->json([
            'schedules' => $result,
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
        $validator = Validator::make($request->input(), [
            'name' => 'required',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        ServiceSchedule::create($request->input());

        return response()->json([
            'message' => 'Schedule has been created successfully'
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ServiceSchedule  $serviceSchedules
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $schedule = ServiceSchedule::find($id);

        if (!$schedule) {
            return response()->json([
                'error' => [
                    'message' => 'Schedule not found!',
                    'code' => 404
                ]
            ], 404);
        }

        return response()->json([
            'schedule' => $schedule
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ServiceSchedule  $serviceSchedules
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->input(), [
            'name' => 'required',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $schedule = ServiceSchedule::find($id);

        if (!$schedule) {
            return response()->json([
                'error' => [
                    'message' => 'Schedule not found!',
                    'code' => 404
                ]
            ], 404);
        }

        $schedule->update($request->input());

        return response()->json([
            'message' => 'Schedule has been updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ServiceSchedule  $serviceSchedules
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $schedule = ServiceSchedule::find($id);

        if (!$schedule) {
            return response()->json([
                'error' => [
                    'message' => 'Schedule not found!',
                    'code' => 404
                ]
            ], 404);
        }

        $schedule->delete();

        return response()->json([
            'message' => "Schedule has been deleted successfully"
        ]);
    }
}
