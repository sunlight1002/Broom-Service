<?php

namespace App\Console\Commands;

use App\Models\Offer;
use App\Models\Client;
use App\Models\ClientMetas;
use Illuminate\Console\Command;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\ClientMetaEnum;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\App;

class StatusNotUpdated24hours extends Command
{
    protected $signature = 'StatusNotUpdated24';
    protected $description = 'Notify the team if status is not updated for over 24 hours, 3 days, or 7 days';

    public function handle()
    {
        $offerStatuses = Offer::with('client')
            ->where('status', 'sent')
            ->whereHas('client', function ($q) {
                $q->whereDate('created_at', '>=', '2024-09-20'); 
            })
            ->whereDate('created_at', '<=', Carbon::now()->subDays(1)) // Fetch records older than 1 day
            ->get();
    
        $todayDateTime = Carbon::now()->format('Y-m-d H:i:s');
    
        // Loop through each offer to check how many days it has been in 'sent' status
        foreach ($offerStatuses as $offerStatus) {
            // dd($offerStatus);
            $client = $offerStatus->client;
    
            if ($client) {
                $createdAt = $offerStatus->created_at;
                App::setLocale($client->lng);
    
                $daysSinceCreation = Carbon::now()->diffInDays($createdAt);
    
                // Check if the status has been 'sent' for over 7 days
                if ($daysSinceCreation >= 7) {
                    if (!$this->isNotificationSent($client->id, ClientMetaEnum::NOTIFICATION_SENT_7_DAY)) {
                        $this->info("Sending final follow-up to team for client: " . $client->firstname);
                        $this->sendFinalFollowUp($client, $offerStatus);
                        $this->storeNotificationSent($client->id, ClientMetaEnum::NOTIFICATION_SENT_7_DAY);
                    }
                }
                // Check if the status has been 'sent' for over 3 days
                elseif ($daysSinceCreation >= 3) {
                    if (!$this->isNotificationSent($client->id, ClientMetaEnum::NOTIFICATION_SENT_3_DAY)) {
                        $this->info("Sending 3-day follow-up to team for client: " . $client->firstname);
                        $this->sendFollowUp($client, $offerStatus);
                        $this->storeNotificationSent($client->id, ClientMetaEnum::NOTIFICATION_SENT_3_DAY);
                    }
                }
                // Check if the status has been 'sent' for over 24 hours
                elseif ($daysSinceCreation >= 1) {
                    if (!$this->isNotificationSent($client->id, ClientMetaEnum::NOTIFICATION_SENT_24_HOURS)) {
                        $this->info("Sending 24-hour notification to team for client: " . $client->firstname);
                        $this->sendNotification($client, $offerStatus);
                        $this->storeNotificationSent($client->id, ClientMetaEnum::NOTIFICATION_SENT_24_HOURS);
                    }
                }
            } else {
                $this->info("Client not found for Offer Status ID: {$offerStatus->id}");
            }
        }
    
        return 0;
    }
    

    // Check if the notification for the given key was already sent
    protected function isNotificationSent($clientId, $key)
    {
        return ClientMetas::where('client_id', $clientId)
            ->where('key', $key)
            ->exists();
    }

    // Store that the notification for the given key was sent
    protected function storeNotificationSent($clientId, $key)
    {
        ClientMetas::create([
            'client_id' => $clientId,
            'key' => $key,
            'value' => Carbon::now()->toDateTimeString(),
        ]);
    }

    // Send the 24-hour notification to the team
    protected function sendNotification($client, $offerStatus)
    {
        $response = event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::STATUS_NOT_UPDATED,
            "notificationData" => [
                'client' => $client->toArray(),
            ]
        ]));

        Notification::create([
            'user_id' => $client->id,
            'user_type' => get_class($client),
            'type' => NotificationTypeEnum::STATUS_NOT_UPDATED,
            'status' => $offerStatus->status,
        ]);

        if ($response) {
            $this->info("24-hour notification sent for Offer ID: {$offerStatus->id}");
        } else {
            $this->error("Failed to send 24-hour notification for Offer ID: {$offerStatus->id}");
        }
    }

    // Send the follow-up after 3 days
    protected function sendFollowUp($client, $offerStatus)
    {
        $response = event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::FOLLOW_UP_PRICE_OFFER,
            "notificationData" => [
                'client' => $client->toArray(),
            ]
        ]));

        Notification::create([
            'user_id' => $client->id,
            'user_type' => get_class($client),
            'type' => NotificationTypeEnum::FOLLOW_UP_PRICE_OFFER,
            'status' => $offerStatus->status,
        ]);

        // App::setLocale($client->lng);
        // Mail::send('Mails.ReminderLeadPriceOffer', ['client' => $client->toArray()], function ($messages) use ($client) {
        //     $messages->to($client->email);
        //     $messages->subject(__('mail.price_offer_reminder.header'));
        // });

        if ($response) {
            $this->info("3-day follow-up sent for Offer ID: {$offerStatus->id}");
        } else {
            $this->error("Failed to send 3-day follow-up for Offer ID: {$offerStatus->id}");
        }
    }

    // Send the final follow-up after 7 days
    protected function sendFinalFollowUp($client, $offerStatus)
    {
        $response = event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::FINAL_FOLLOW_UP_PRICE_OFFER,
            "notificationData" => [
                'client' => $client->toArray(),
                'offer' => $offerStatus->toArray()
            ]
        ]));

        Notification::create([
            'user_id' => $client->id,
            'user_type' => get_class($client),
            'type' => NotificationTypeEnum::FINAL_FOLLOW_UP_PRICE_OFFER,
            'status' => $offerStatus->status,
        ]);

        // App::setLocale($client->lng);
        // Mail::send('Mails.ReminderLeadPriceOffer', ['client' => $client->toArray()], function ($messages) use ($client) {
        //     $messages->to($client->email);
        //     $messages->subject(__('mail.price_offer_reminder.header'));
        // });

        if ($response) {
            $this->info("Final follow-up sent for Offer ID: {$offerStatus->id}");
        } else {
            $this->error("Failed to send after 7 days final follow-up for Offer ID: {$offerStatus->id}");
        }
    }
}
