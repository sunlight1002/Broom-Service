<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LeadActivity;
use Carbon\Carbon;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

class NotifyTeamRescheduleCallToday extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:team-reschedule-call-today';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify team for reschedule call date is today';

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
        // Get today's date
        $today = Carbon::today();

        // Get Lead Activities with reschedule dates today
        $activities = LeadActivity::whereDate('reschedule_date', $today)->get();

        foreach ($activities as $activity) {
            // Send notifications for today
            $this->sendNotifications($activity, $today);
        }

        return 0;
    }

    /**
     * Sends notifications to the team.
     *
     * @param \App\Models\LeadActivity $activity
     * @param \Carbon\Carbon $date
     * @return void
     */
    private function sendNotifications($activity, $date)
    {
        // Prepare data for notification
        $client = $activity->client;  // Assuming the client is a related model
        $notificationData = [
            "client" => $client->toArray(),
            "activity" => $activity->toArray(),
        ];

        // Dispatch team notification event for the selected day
        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::RESCHEDULE_CALL_FOR_TEAM_ON_DATE,
            "notificationData" => $notificationData
        ]));
    }
}
