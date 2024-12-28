<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WorkerLeads;
use Carbon\Carbon;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use Illuminate\Support\Facades\Log;

class SendWorkerWhenAlexSetUnaswered extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:worker-when-alex-set-unaswered';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send worker Daily Reminder to the Lead Until Alex Updates Status';

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
        $workerLeads = WorkerLeads::where('status', 'unaswered')->get();

        // Loop through each worker lead
        foreach ($workerLeads as $workerLead) {
            try {

                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::NEW_LEAD_HIRING_ALEX_REPLY_UNANSWERED,
                    "notificationData" => [
                        'worker' => $workerLead->toArray(),
                    ]
                ]));

                // Log success
                Log::info("Notification sent to WorkerLead ID: {$workerLead->id}, Phone: {$workerLead->phone_number}");
            } catch (\Exception $e) {
                // Log error for debugging
                Log::error("Failed to send notification to WorkerLead ID: {$workerLead->id}. Error: {$e->getMessage()}");
            }
        }
        return 0;
    }
}
