<?php

namespace Database\Seeders;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class UpdateScheduleStartDateFormat extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $schedules = Schedule::query()
            ->whereNotNull('start_date')
            ->get();

        foreach ($schedules as $schedule) {
            $schedule->update([
                'start_date' => Carbon::parse($schedule->start_date)->toDateString(),
                'old_start_date_format' => $schedule->start_date,
                'start_time_standard_format' => Carbon::createFromFormat('h:i A', $schedule->start_time)->toTimeString()
            ]);
        }
    }
}
