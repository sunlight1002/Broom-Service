<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\LeadStatus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

class NotifyTeamToUpdateLeadStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'team:lead-status-pending-from-24-hours';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a notification to the team if lead status is still pending after 24 hours';

    /**
     * Execute the console command.
     *
     * @return int
     */

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $clients = Client::where('created_at', '<=', Carbon::now()->subHours(24))->whereHas('lead_status', function ($q) {
            $q->whereIn('lead_status', ['pending']);
        })->whereDate('created_at', '>=', Carbon::now()->subDays(30))->count();

        \Log::info($clients);

        if ($clients == 0) {
            return 0;
        }

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::FOLLOW_UP_REQUIRED,
            "notificationData" => [
                'pending_lead_count' => $clients,
            ]
        ]));

        return 0;
    }
}