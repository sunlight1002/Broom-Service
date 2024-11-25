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
        $schedule->command('telescope:prune --hours=336')->daily();

        // Backup schedule
        $schedule->command('backup:clean')->daily()->at('01:00');
        $schedule->command('backup:run')->daily()->at('01:30');

        // Invoices
        $schedule->command('regular-invoice:generate')->dailyAt('12:00');
        $schedule->command('invoice:check-once-in-month')->dailyAt('17:30');

        // Worker reminder
        // $schedule->command('worker:send_invitation')->dailyAt('09:00');
        $schedule->command('worker:notify-next-day-job-at-5-pm')->dailyAt('17:00');
        $schedule->command('worker:notify-next-day-job-at-6-pm')->dailyAt('18:00');
        $schedule->command('worker:notify-worker-confirm-on-your-way-before-1-hour')->everyMinute();
        $schedule->command('worker:job-not-finished-on-time')->everyMinute();

        // Team reminder
        $schedule->command('team:notify-team-if-worker-not-confirm-before-30-mins')->everyMinute();
        $schedule->command('team:notify-team-if-worker-not-confirm-after-30-mins')->everyMinute();
        $schedule->command('team:lead-status-pending-from-24-hours')->dailyAt('08:00');
        $schedule->command('team:price-offer-reminder-to-team')->dailyAt('08:00');
        $schedule->command('team:contract-reminder')->hourly();
        $schedule->command('client:offsite-meeting-reminder')->dailyAt('08:00');

        // Admin reminder
        // $schedule->command('admin:send-worker-invitation-report')->twiceDailyAt(8, 18);

        // Facebook Leads
        $schedule->command('lead:fetch-facebook-leads')->everyFiveMinutes();

        $schedule->command('client:review-job-request')->dailyAt('08:00');

        // $schedule->command('worker:notify-yearly-insurance-form')->yearlyOn(1, 1, '09:00');
        // $schedule->command('meeting:reminder')->hourly();
        $schedule->command('client:update-lead-status')->hourly();

        // $schedule->command('notifyclientforcontract')->hourly();
        $schedule->command('mondayNotify')->weeklyOn(1, '08:00'); // 1 = Monday
        $schedule->command('remind:next-week-services')->weeklyOn(3, '9:00');

        $schedule->command('notifyTeamAndClientTommorowMeeting')->dailyAt('19:00');

        $schedule->command('notify:team-and-worker-for-visa-renewal')->weeklyOn(1, '8:00'); // Every Monday at 8:00 AM

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
