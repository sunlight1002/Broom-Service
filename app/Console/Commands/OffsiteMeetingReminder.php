<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LeadActivity;
use App\Models\Files;
use App\Models\ClientMetas;
use App\Models\Schedule; // Include Schedule model
use Carbon\Carbon;
use App\Events\WhatsappNotificationEvent;
use App\Enums\ClientMetaEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App;
use Illuminate\Support\Facades\DB;

class OffsiteMeetingReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:offsite-meeting-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify clients with potential status for 24 hours , 3days and 7days have not submitted files';

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

        $dates = [
            Carbon::now()->subDay(1)->toDateString(),
            Carbon::now()->subDays(3)->toDateString(),
            Carbon::now()->subDays(7)->toDateString(),
        ];

        $meetings = Schedule::with('client')
            ->whereHas('client.lead_status', function ($query) {
                $query->where('status', 'potential');
            })
            ->where('meet_via', 'off-site')
            ->whereDate('created_at', '>=', '2024-10-19')
            ->whereDoesntHave('files')
            ->whereIn(DB::raw('DATE(created_at)'), $dates)
            ->get();

        foreach ($meetings as $key => $meeting) {
            // event(new WhatsappNotificationEvent([
            //     "type" => WhatsappMessageTemplateEnum::OFF_SITE_MEETING_REMINDER_TO_CLIENT,
            //     "notificationData" => $meeting->toArray()
            // ]));

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::OFF_SITE_MEETING_REMINDER_TO_TEAM,
                "notificationData" => $meeting->toArray()
            ]));
        }
}

}
