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
        $schedule->command('worker:default-availability')->onOneServer()->weekly();
        $schedule->command('telescope:prune --hours=336')->onOneServer()->daily();

        // Backup schedule
        $schedule->command('backup:clean')->onOneServer()->daily()->at('01:00');
        $schedule->command('backup:run')->onOneServer()->daily()->at('01:30');

        // Invoices
        // $schedule->command('regular-invoice:generate')->onOneServer()->dailyAt('12:00');
        // $schedule->command('invoice:check-once-in-month')->onOneServer()->dailyAt('17:30');

        // Worker reminder
        // $schedule->command('worker:send_invitation')->onOneServer()->dailyAt('09:00');
        $schedule->command('worker:notify-next-day-job-at-5-pm')->onOneServer()->dailyAt('17:00');
        $schedule->command('worker:notify-next-day-job-at-6-pm')->onOneServer()->dailyAt('18:00');
        $schedule->command('worker:notify-worker-confirm-on-your-way-before-1-hour')->onOneServer()->everyMinute();
        $schedule->command('worker:job-not-finished-on-time')->onOneServer()->everyMinute();

        // Team reminder
        $schedule->command('team:notify-team-if-worker-not-confirm-before-30-mins')->onOneServer()->everyMinute();
        $schedule->command('team:notify-team-if-worker-not-confirm-after-30-mins')->onOneServer()->everyMinute();
        
        $schedule->command('team:lead-status-pending-from-24-hours')->onOneServer()->dailyAt('08:00');
        $schedule->command('team:price-offer-reminder-to-team')->onOneServer()->dailyAt('08:00');
        // $schedule->command('team-and-client:contract-reminder')->onOneServer()->hourly();
        $schedule->command('client:offsite-meeting-reminder')->onOneServer()->dailyAt('08:00');

        // Admin reminder
        // $schedule->command('admin:send-worker-invitation-report')->onOneServer()->twiceDailyAt(8, 18);

        // Facebook Leads
        $schedule->command('lead:fetch-facebook-leads')->onOneServer()->everyFiveMinutes();
        $schedule->command('campaign:create')->onOneServer()->hourly();

        // $schedule->command('client:review-job-request')->onOneServer()->dailyAt('08:00');

        // $schedule->command('worker:notify-yearly-insurance-form')->onOneServer()->yearlyOn(1, 1, '09:00');
        // $schedule->command('meeting:reminder')->onOneServer()->hourly();
        // $schedule->command('client:update-lead-status')->onOneServer()->hourly();

        // $schedule->command('notifyclientforcontract')->onOneServer()->hourly();
        // $schedule->command('mondayNotify')->onOneServer()->weeklyOn(1, '08:00'); // 1 = Monday
        // $schedule->command('remind:next-week-services')->onOneServer()->weeklyOn(3, '9:00');

        $schedule->command('notifyTeamAndClientTommorowMeeting')->onOneServer()->dailyAt('19:00');

        $schedule->command('notify:team-and-worker-for-visa-renewal')->onOneServer()->weeklyOn(1, '8:00'); // Every Monday at 8:00 AM

        $schedule->command('Notify:UnansweredClients')->onOneServer()->hourly();

        $schedule->command('notify:team-reschedule-call-today')->onOneServer()->dailyAt('08:00');
        $schedule->command('making:task')->onOneServer()->dailyAt('08:00');

        $schedule->command('send:worker-lead-reminders')->onOneServer()->hourly();

        $schedule->command('send:worker-in-hiring-process')->onOneServer()->dailyAt('08:00');
        $schedule->command('send:worker-when-alex-set-unaswered')->onOneServer()->dailyAt('08:00');

        $schedule->command('terminate:worker')->onOneServer()->dailyAt('08:00');

        $schedule->command('send:reminder-with-pending-forms')->onOneServer()->dailyAt('08:00');

        // Close active client bot
        $schedule->command('client:close-active-client-bot')->onOneServer()->everyMinute();
        $schedule->command('client:close-active-worker-bot')->onOneServer()->everyMinute();

        $schedule->command('client:job-review-message')->onOneServer()->weeklyOn(Schedule::MONDAY, '11:00');
        $schedule->command('client:job-review-message')->onOneServer()->weeklyOn(Schedule::TUESDAY, '11:00');
        $schedule->command('client:job-review-message')->onOneServer()->weeklyOn(Schedule::WEDNESDAY, '11:00');
        $schedule->command('client:job-review-message')->onOneServer()->weeklyOn(Schedule::THURSDAY, '11:00');
        $schedule->command('client:job-review-message')->onOneServer()->weeklyOn(Schedule::SUNDAY, '11:00');

        $schedule->command('send:to-active-clients')->onOneServer()->weeklyOn(Schedule::MONDAY, '8:30');
        $schedule->command('send:to-active-workers')->onOneServer()
            ->mondays()
            ->between('08:30', '20:30')
            ->hourlyAt(30);

        // Monday at 1:30 PM
        $schedule->command('worker:not_respond_on_monday')->onOneServer()
            ->weeklyOn(Schedule::MONDAY, '13:30');

        // Monday at 8:00 PM
        $schedule->command('worker:not_respond_on_monday')->onOneServer()
            ->weeklyOn(Schedule::MONDAY, '20:00');

        // Tuesday at 8:30 AM
        $schedule->command('worker:not_respond_on_monday')->onOneServer()
            ->weeklyOn(Schedule::TUESDAY, '08:30');

        // $schedule->command('gmail:fetch')->everyFiveMinutes();
        $schedule->command('set:active-workers-monday-message')->weeklyOn(Schedule::SUNDAY, '20:00');

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
