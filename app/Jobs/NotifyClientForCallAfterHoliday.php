<?php

namespace App\Jobs;

use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Models\Client;
use App\Models\LeadActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyClientForCallAfterHoliday implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $client;
    public $activity;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\Client $client
     * @param \App\Models\LeadActivity $activity
     */
    public function __construct(Client $client, LeadActivity $activity)
    {
        $this->client = $client;
        $this->activity = $activity;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Trigger the event for sending a WhatsApp notification to the client
        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::RESCHEDULE_CALL_FOR_CLIENT,
            "notificationData" => [
                "client" => $this->client->toArray(),
                "activity" => $this->activity->toArray(),
            ]
        ]));
    }
}
