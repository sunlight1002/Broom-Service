<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('worker:default-availability')->weekly();
        $schedule->command('worker:notify-next-day-job')->dailyAt('17:00');
        $schedule->command('client:review-job-request')->dailyAt('17:00');
        $schedule->command('worker:failed-to-approve-job')->dailyAt('20:00');
        $schedule->command('reminder:job-not-approve-or-leave')->hourlyAt(45);
        $schedule->command('reminder:job-not-started')->hourlyAt(1);
        $schedule->command('notification:job-not-finished-on-time')->hourlyAt(5);
        $schedule->command('notification:job-time-exceed')->hourlyAt(1);
        $schedule->command('invoice:check-once-in-month')->dailyAt('17:30');
        $schedule->command('regular-invoice:generate')->dailyAt('12:00');
        $schedule->command('worker:notify-yearly-insurance-form')->yearlyOn(1, 1, '09:00');
        $schedule->command('meeting:reminder')->hourly();
        $schedule->command('client:update-lead-status')->hourly();
        $schedule->command('worker:send_invitation')->dailyAt('09:00');
        $schedule->command('report')->twiceDailyAt(8, 18);
        $schedule->command('telescope:prune --hours=336')->daily();

        // Backup schedule
        $schedule->command('backup:clean')->daily()->at('01:00');
        $schedule->command('backup:run')->daily()->at('01:30');


        // $schedule->command('update24')->daily();
        // $schedule->command('StatusNotUpdated24')->daily();
        // $schedule->command('updateteam24')->daily();


    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
