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
        $currentDate = Carbon::now();

        // Get clients with unanswered status
        $clients = Client::whereHas('lead_status', function ($query) {
                $query->where('lead_status', LeadStatusEnum::UNANSWERED);
            })
            ->get();

        foreach ($clients as $client) {
                $daysSinceUpdate = $client->lead_status->updated_at->startOfDay()->diffInDays($currentDate->startOfDay());
                \Log::info('Days old: ' . $daysSinceUpdate);

                $metaEnum = null;
                $enum = null;
                $message = null;

                switch ($daysSinceUpdate) {
                    case 1:
                        $metaEnum = ClientMetaEnum::NOTIFICATION_SENT_UNANSWERED_1DAY;
                        $enum = WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_1_DAY;
                        $message = "Hello {$client->firstname}, just checking in since we haven’t heard from you. Let us know if you have any questions.";
                        break;
                    case 3:
                        $metaEnum = ClientMetaEnum::NOTIFICATION_SENT_UNANSWERED_3DAYS;
                        $enum = WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_3_DAYS;
                        $message = "Hi {$client->firstname}, we noticed you haven’t responded for 3 days. Please get back to us when you can.";
                        break;
                    case 4:
                        $metaEnum = ClientMetaEnum::NOTIFICATION_SENT_UNANSWERED_4DAYS;
                        $enum = WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_4_DAYS;
                        $message = "Dear {$client->firstname}, it's been 4 days since your inquiry. We're here to assist—please respond.";

                        // Update status to UNANSWERED_FINAL after 4 days
                        $client->lead_status->update(['lead_status' => LeadStatusEnum::UNANSWERED_FINAL]);
                        $this->info("Client status updated to UNANSWERED_FINAL for {$client->firstname}.");
                        break;
                    default:
                        continue 2;
                }

                if (ClientMetas::where('client_id', $client->id)->where('key', $metaEnum)->exists()) {
                    $this->info("Notification already sent to client: {$client->firstname} for {$daysSinceUpdate} days.");
                    continue;
                }

                $this->sendWhatsAppMessage($client, $enum);

                ClientMetas::create([
                    'client_id' => $client->id,
                    'key' => $metaEnum,
                    'value' => Carbon::now(),
                ]);

                $this->info("Notification sent to client: {$client->firstname} ({$client->phone}) with message: {$message}");
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
