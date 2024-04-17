<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\TeamMemberAvailability;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function availabilityByDate($id, $date)
    {
        $availArr = TeamMemberAvailability::select('time_slots')->where('team_member_id', $id)->first();

        $timeSlot = json_decode($availArr->time_slots, true);
        $availableSlots = isset($timeSlot[$date]) ? $timeSlot[$date] : [];

        $availableSlots24Hrs = [];
        foreach ($availableSlots as $key => $value) {
            $availableSlots24Hrs[] = [
                'start' => Carbon::createFromFormat('Y-m-d H:i A', date('Y-m-d') . ' ' . $value[0])->format('H:i'),
                'end' => Carbon::createFromFormat('Y-m-d H:i A', date('Y-m-d') . ' ' . $value[1])->format('H:i'),
            ];
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
            'available_slots' => $availableSlots24Hrs
        ]);
    }
}
