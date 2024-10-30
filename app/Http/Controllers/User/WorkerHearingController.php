<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\HearingInvitation;
use Illuminate\Http\Request;
use Validator;
use Carbon\Carbon;


class WorkerHearingController extends Controller
{
    /**
     * Fetch the hearing schedule details.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHearingDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $hearing = HearingInvitation::with(['admin', 'user'])->find($request->input('id'));

        if (!$hearing) {
            return response()->json(['message' => 'Hearing not found'], 404);
        }

        return response()->json([
            'schedule' => $hearing,
            'worker' => $hearing->user,
            'team_name' => $hearing->admin ? $hearing->admin->name : null,
        ], 200);
    }

    /**
     * Accept the hearing schedule.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function acceptHearing(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $hearing = HearingInvitation::find($request->input('id'));

        if (!$hearing) {
            return response()->json(['message' => 'Hearing not found'], 404);
        }

        if ($hearing->booking_status === 'confirmed') {
            return response()->json(['message' => 'Hearing already confirmed'], 200);
        }

        $hearing->booking_status = 'confirmed';
        $hearing->save();

        return response()->json(['message' => 'Hearing accepted successfully'], 200);
    }

    /**
     * Reject the hearing schedule.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rejectHearing(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $hearing = HearingInvitation::find($request->input('id'));

        if (!$hearing) {
            return response()->json(['message' => 'Hearing not found'], 404);
        }

        if ($hearing->booking_status === 'declined') {
            return response()->json(['message' => 'Hearing already declined'], 200);
        }

        $hearing->booking_status = 'declined';
        $hearing->save();

        return response()->json(['message' => 'Hearing rejected successfully'], 200);
    }

    public function rescheduleHearing(Request $request, $id)
    {
        $data = $request->all();

        $schedule = HearingInvitation::find($id);
        if (!$schedule) {
            return response()->json([
                'message' => 'Hearing not found'
            ], 404);
        }

        $data['end_time'] = Carbon::createFromFormat('Y-m-d h:i A', date('Y-m-d') . ' ' . $data['start_time'])
                                ->addMinutes(30)
                                ->format('h:i A');

    
        $schedule->update([
            'start_date' => $data['start_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'booking_status' => 'rescheduled'
        ]);

        return response()->json([
            'message' => 'Thanks, your hearing has been rescheduled'
        ]);
    }

}
