<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WorkerAffectedAvailabilityStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\WorkerAffectedAvailability;
use App\Models\WorkerAvailability;
use Illuminate\Support\Facades\Auth;

class WorkerAffectedAvailabilitiesController extends Controller
{
    public function show($id)
    {
        $data = WorkerAffectedAvailability::with('worker')->find($id);
        if (!$data) {
            return response()->json([
                'message' => 'Record not found'
            ], 404);
        }

        return response()->json([
            'data' => $data
        ]);
    }

    public function approve($id)
    {
        $affected = WorkerAffectedAvailability::find($id);
        if (!$affected) {
            return response()->json([
                'message' => 'Record not found'
            ], 404);
        }

        if ($affected->status != WorkerAffectedAvailabilityStatusEnum::PENDING) {
            return response()->json([
                'message' => 'Record already processed'
            ], 403);
        }

        $affected->update([
            'status' => WorkerAffectedAvailabilityStatusEnum::APPROVED
        ]);

        return response()->json([
            'message' => 'Approved',
        ]);
    }

    public function reject($id)
    {
        $affected = WorkerAffectedAvailability::with('worker')->find($id);
        if (!$affected) {
            return response()->json([
                'message' => 'Record not found'
            ], 404);
        }

        if ($affected->status != WorkerAffectedAvailabilityStatusEnum::PENDING) {
            return response()->json([
                'message' => 'Record already processed'
            ], 403);
        }

        $worker = $affected->worker;

        $defaultAny = $worker->defaultAvailabilities()->first();

        $until_date = $defaultAny->until_date;

        $worker->availabilities()
            ->whereDate('date', $affected->old_values['date'])
            ->delete();

        $worker->defaultAvailabilities()
            ->where('weekday', $affected->old_values['weekday'])
            ->delete();


        foreach ($affected->new_values['time_by_date'] as $key => $availabilty) {
            WorkerAvailability::create([
                'user_id' => Auth::user()->id,
                'date' => $affected->new_values['date'],
                'start_time' => $availabilty['start_time'],
                'end_time' => $availabilty['end_time'],
                'status' => '1',
            ]);
        }

        foreach ($affected->new_values['time_by_weekday'] as $key => $timeSlot) {
            $worker->defaultAvailabilities()->create([
                'weekday' => $affected->new_values['weekday'],
                'start_time' => $timeSlot['start_time'],
                'end_time' => $timeSlot['end_time'],
                'until_date' => $until_date,
            ]);
        }

        $affected->update([
            'status' => WorkerAffectedAvailabilityStatusEnum::REJECTED,
            'responder_id' => Auth::id(),
            'responder_type' => get_class(Auth::user())
        ]);

        return response()->json([
            'message' => 'Rejected',
        ]);
    }
}
