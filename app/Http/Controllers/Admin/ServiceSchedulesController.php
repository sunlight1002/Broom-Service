<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceSchedulesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $schedule = ServiceSchedule::query();
        $schedule   = $schedule->orderBy('id', 'desc')->paginate(20);

        return response()->json([
            'schedules'       => $schedule,
        ], 200);
    }

    public function allSchedules()
    {
        $schedule = ServiceSchedule::where('status', 1)->get();
        return response()->json([
            'schedules'       => $schedule,
        ], 200);
    }

    public function allSchedulesByLng(Request $request)
    {
        $schedules = ServiceSchedule::where('status', 1)->get();
        $result = [];
        if (isset($schedules)) {
            foreach ($schedules as $schedule) {

                $res['name'] = ($request->lng == 'en') ? $schedule->name : $schedule->name_heb;
                $res['id']   = $schedule->id;
                $res['cycle'] = $schedule->cycle;
                $res['period'] = $schedule->period;
                array_push($result, $res);
            }
        }
        return response()->json([
            'schedules'       => $result,
        ], 200);
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
            'message' => 'Schedule has been create successfully'
        ], 200);
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
        return response()->json([
            'schedule' => $schedule
        ], 200);
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

        ServiceSchedule::where('id', $id)->update($request->input());
        return response()->json([
            'message' => 'Schedule has been updated successfully'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ServiceSchedule  $serviceSchedules
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        ServiceSchedule::find($id)->delete();
        return response()->json([
            'message'     => "Schedule has been deleted"
        ], 200);
    }
}
