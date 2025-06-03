<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Events\MeetingReminderEvent;

class MeetingReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meeting:reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remind about meeting';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $targetHour = Carbon::now()->subHours(4);
        $start = $targetHour->copy()->startOfHour();
        $end = $targetHour->copy()->endOfHour();

        $schedules = Schedule::query()
            ->where('booking_status', 'pending')
            ->whereNotNull('meeting_mail_sent_at')
            ->whereBetween('meeting_mail_sent_at', [$start, $end])
            ->with(['team', 'client', 'propertyAddress'])
            ->get();

        foreach ($schedules as $schedule) {
            event(new MeetingReminderEvent($schedule));
        }

        return 0;
    }
}
