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
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Notify:UnansweredClients';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify Unanswered Clients on after 3, 7, and 8 days';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $currentDate = Carbon::now()->startOfDay();

        $dateRanges = [
            3 => $currentDate->copy()->subDays(3),
            7 => $currentDate->copy()->subDays(7),
            8 => $currentDate->copy()->subDays(8),
        ];

        $unansweredLeads = LeadStatus::where('lead_status', LeadStatusEnum::UNANSWERED)
            ->where(function ($query) use ($dateRanges) {
                foreach ($dateRanges as $days => $date) {
                    $query->orWhereDate('created_at', '<=', $date);
                }
            })
            ->with('client')
            ->get();

        foreach ($unansweredLeads as $lead) {
            $client = $lead->client;

            if ($client) {
                $daysOld = $lead->created_at->diffInDays($currentDate);

                $metaEnum = null;
                switch ($daysOld) {
                    case 3:
                        $metaEnum = ClientMetaEnum::NOTIFICATION_SENT_UNANSWERED_3DAYS;
                        $enum =  WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_3_DAYS;
                        $message = "Hello {$client->firstname}, we noticed you haven’t responded for 3 days. Please get back to us at your earliest convenience.";
                        break;
                    case 7:
                        $metaEnum = ClientMetaEnum::NOTIFICATION_SENT_UNANSWERED_7DAYS;
                        $enum =  WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_7_DAYS;
                        $message = "Hi {$client->firstname}, it’s been 7 days since we last heard from you. Please let us know how we can assist you.";
                        break;
                    case 8:
                        $metaEnum = ClientMetaEnum::NOTIFICATION_SENT_UNANSWERED_8DAYS;
                        $enum = WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_8_DAYS;
                        $message = "Dear {$client->firstname}, this is a reminder that it has been 8 days since your query. We’re here to help—please reply.";

                        if ($lead->lead_status === LeadStatusEnum::UNANSWERED) {
                            $lead->update(['lead_status' => LeadStatusEnum::UNANSWERED_FINAL]);
                            $this->info("Client status updated to UNANSWERED_FINAL for {$client->firstname}.");
                        }
                        break;
                    default:
                        continue 2; 
                }

                // Check if the notification for this day has already been sent
                $notificationSent = ClientMetas::where('client_id', $client->id)
                    ->where('key', $metaEnum)
                    ->exists();

                if ($notificationSent) {
                    $this->info("Notification already sent to client: {$client->firstname} for {$daysOld} days.");
                    continue;
                }

                $this->sendWhatsAppMessage($client, $enum);

                // Log the notification in ClientMetas
                ClientMetas::create([
                    'client_id' => $client->id,
                    'key' => $metaEnum,
                    'value' => Carbon::now(),
                ]);

                $this->info("Notification sent to client: {$client->firstname} ({$client->phone}) with message: {$message}");
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
