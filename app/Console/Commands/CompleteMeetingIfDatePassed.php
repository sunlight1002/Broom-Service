<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Schedule;
use Carbon\Carbon;

class CompleteMeetingIfDatePassed extends Command
{
    protected $signature = 'meetings:complete-if-passed';

    protected $description = 'Marks schedules as completed if the end time has passed.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $now = Carbon::now();

        $schedules = Schedule::where('booking_status', '!=', 'declined')->get();

        foreach ($schedules as $schedule) {
            try {
                // Parse the start_date (ISO format)
                $startDate = Carbon::parse($schedule->start_date);

                // Parse the end_time (like "04:30 PM")
                $endTime = Carbon::createFromFormat('h:i A', $schedule->end_time);

                // Merge the end_time into the start_date
                $endDateTime = $startDate->copy()
                    ->setTime($endTime->hour, $endTime->minute, 0);

                // Compare with current time
                if ($endDateTime->isPast()) {
                    $schedule->booking_status = 'completed';
                    $schedule->save();

                    $this->info("Schedule ID {$schedule->id} marked as completed.");
                }
            } catch (\Exception $e) {
                $this->error("Error with schedule ID {$schedule->id}: " . $e->getMessage());
            }
        }

        return 0;
    }
}
