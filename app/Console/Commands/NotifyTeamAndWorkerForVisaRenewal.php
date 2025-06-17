<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

class NotifyTeamAndWorkerForVisaRenewal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:team-and-worker-for-visa-renewal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify Team and Worker for Visa Renewal for all users whose renewal date falls within the upcoming week.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Define the upcoming Monday (start of next week) and the following Sunday (end of the week)
        $nextMonday = Carbon::now()->addWeek()->startOfWeek(); // Next Monday
        $nextSunday = $nextMonday->copy()->endOfWeek();        // Following Sunday

        // Find users with a renewal date within the next Monday - Sunday range
        $users = User::whereBetween('renewal_visa', [$nextMonday, $nextSunday])->where('status', '!=' , 0)->get();

        if ($users->isEmpty()) {
            $this->info("No users have a visa renewal date in the upcoming week.");
            return 0;
        }

        // Notify the team for each user with a visa renewal date in the target range
        foreach ($users as $user) {
            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::NOTIFY_TEAM_ONE_WEEK_BEFORE_WORKER_VISA_RENEWAL,
                "notificationData" => [
                    'worker' => $user->toArray(),
                ]
            ]));

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::NOTIFY_WORKER_ONE_WEEK_BEFORE_HIS_VISA_RENEWAL,
                "notificationData" => [
                    'worker' => $user->toArray(),
                ]
            ]));
        }

        return 0;
    }
}
