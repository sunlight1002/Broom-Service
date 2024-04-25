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
        $schedule->command('request:expired')->dailyAt('02:00');
        $schedule->command('worker:notify-next-day-job')->dailyAt('17:00');
        $schedule->command('worker:failed-to-approve-job')->dailyAt('20:00');
        // $schedule->command('order:generate')->everyMinute();
        // $schedule->command('regular-invoice:generate')->dailyAt('17:00');
        // $schedule->command('invoice:generate')->dailyAt('16:30');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
