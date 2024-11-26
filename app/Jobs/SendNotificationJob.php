<?php

namespace App\Jobs;

use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Mail\SendUninterestedClientEmail;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $client;
    protected $newLeadStatus;
    protected $emailData;
    protected $contract;

    /**
     * Create a new job instance.
     */
    public function __construct(Client $client, $newLeadStatus, $emailData, $contract)
    {
        $this->client = $client;
        $this->newLeadStatus = $newLeadStatus;
        $this->emailData = $emailData;
        $this->contract = $contract;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Trigger contract verification notifications
        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::NOTIFY_CONTRACT_VERIFY_TO_CLIENT,
            "notificationData" => ['client' => $this->client->toArray()],
        ]));

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::NOTIFY_CONTRACT_VERIFY_TO_TEAM,
            "notificationData" => [
                'client' => $this->client->toArray(),
                'contract' => $this->contract->toArray(),
            ],
        ]));
    }
}
