<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Schedule;
use App\Models\TeamMemberAvailability;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
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
