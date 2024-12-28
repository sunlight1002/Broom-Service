<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WorkerLeads;
use Carbon\Carbon;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use Illuminate\Support\Facades\Log;

class SendWorkerInHiringProcess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:worker-in-hiring-process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily notification to Team if a Worker is in the hiring process';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Fetch worker leads with 'hiring' status
        $workerLeads = WorkerLeads::where('status', 'hiring')->get();

        // Loop through each worker lead
        foreach ($workerLeads as $workerLead) {
            try {

                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::NEW_LEAD_IN_HIRING_DAILY_REMINDER_TO_TEAM,
                    "notificationData" => [
                        'worker' => $workerLead->toArray(),
                    ]
                ]));

                Log::info("Notification sent to Team WorkerLead ID: {$workerLead->id}, Phone: {$workerLead->phone_number}");

            } catch (\Exception $e) {
                // Log error for debugging
                Log::error("Failed to send notification to Team WorkerLead ID: {$workerLead->id}. Error: {$e->getMessage()}");
            }
        }

        return 0;
    }
}
