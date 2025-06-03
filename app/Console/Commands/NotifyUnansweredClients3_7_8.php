<?php

namespace App\Console\Commands;

use App\Models\Client;
use Carbon\Carbon;
use App\Models\ClientMetas;
use App\Models\LeadStatus;
use Illuminate\Console\Command;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Enums\ClientMetaEnum;
use App\Enums\LeadStatusEnum;

class NotifyUnansweredClients3_7_8 extends Command
{
    protected $signature = 'Notify:UnansweredClients';
    protected $description = 'Notify Unanswered Clients after 3, 7, and 8 days';

    public function handle()
    {
        $today = Carbon::today();

        $clients = Client::whereHas('lead_status', function ($query) {
            $query->where('lead_status', LeadStatusEnum::UNANSWERED);
        })->get();

        foreach ($clients as $client) {
            $updatedAt = $client->lead_status->updated_at->startOfDay();
            $daysSinceUpdate = $updatedAt->diffInDays($today);

            $notifications = [
                1 => [
                    'meta' => ClientMetaEnum::NOTIFICATION_SENT_UNANSWERED_1DAY,
                    'template' => WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_1_DAY,
                    'message' => "Hello {$client->firstname}, just checking in since we haven’t heard from you. Let us know if you have any questions.",
                ],
                3 => [
                    'meta' => ClientMetaEnum::NOTIFICATION_SENT_UNANSWERED_3DAYS,
                    'template' => WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_3_DAYS,
                    'message' => "Hi {$client->firstname}, we noticed you haven’t responded for 3 days. Please get back to us when you can.",
                ],
                4 => [
                    'meta' => ClientMetaEnum::NOTIFICATION_SENT_UNANSWERED_4DAYS,
                    'template' => WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_4_DAYS,
                    'message' => "Dear {$client->firstname}, it's been 4 days since your inquiry. We're here to assist—please respond.",
                    'finalize' => true,
                ],
            ];

            if (!isset($notifications[$daysSinceUpdate])) {
                continue;
            }

            $metaEnum = $notifications[$daysSinceUpdate]['meta'];
            if (ClientMetas::where('client_id', $client->id)->where('key', $metaEnum)->exists()) {
                $this->info("Notification already sent to client: {$client->firstname} for {$daysSinceUpdate} days.");
                continue;
            }

            // Send WhatsApp
            $this->sendWhatsAppMessage($client, $notifications[$daysSinceUpdate]['template']);

            // Save meta
            ClientMetas::create([
                'client_id' => $client->id,
                'key' => $metaEnum,
                'value' => Carbon::now(),
            ]);

            $this->info("Notification sent to client: {$client->firstname} ({$client->phone}) with message: {$notifications[$daysSinceUpdate]['message']}");

            // Update status to UNANSWERED_FINAL on day 4
            if (!empty($notifications[$daysSinceUpdate]['finalize'])) {
                $client->lead_status->update(['lead_status' => LeadStatusEnum::UNANSWERED_FINAL]);
                $this->info("Client status updated to UNANSWERED_FINAL for {$client->firstname}.");
            }
        }

        return 0;
    }


    protected function sendWhatsAppMessage($client, $enum)
    {
        event(new WhatsappNotificationEvent([
            "type" => $enum,
            "notificationData" => [
                "client" => $client->toArray()
            ]
        ]));
    }
}
