<?php

namespace App\Console\Commands;

use App\Models\Offer;
use App\Models\Client;
use Illuminate\Console\Command;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\Notification; 
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class StatusNotUpdated24hours extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'StatusNotUpdated24';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify the team if status is not updated for over 24 hours, 3 days, or 7 days';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Get Offer records where status is 'sent'
        $offerStatuses = Offer::where('status', 'sent')->get();

        foreach ($offerStatuses as $offerStatus) {
            $client = Client::find($offerStatus->client_id);

            if ($client) {
                $createdAt = $offerStatus->created_at;

                // Check if the status is 'sent' for over 7 days
                if ($createdAt <= Carbon::now()->subDays(7)) {
                    $this->info("Sending final follow-up to team for client: " . $client->firstname);
                    $this->sendFinalFollowUp($client, $offerStatus);
                }
                // Check if the status is 'sent' for over 3 days (72 hours)
                elseif ($createdAt <= Carbon::now()->subDays(3)) {
                    $this->info("Sending 3-day follow-up to team for client: " . $client->firstname);
                    $this->sendFollowUp($client, $offerStatus);
                }
                // Check if the status is 'sent' for over 24 hours
                elseif ($createdAt <= Carbon::now()->subHours(24)) {
                    $this->info("Sending 24-hour notification to team for client: " . $client->firstname);
                    $this->sendNotification($client, $offerStatus);
                }
            } else {
                $this->info("Client not found for Offer Status ID: {$offerStatus->id}");
            }
        }

        return 0;
    }

    /**
     * Send the 24-hour notification to the team
     */
    protected function sendNotification($client, $offerStatus)
    {
        $response = event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::STATUS_NOT_UPDATED,
            "notificationData" => [
                'client' => $client->toArray(),
            ]
        ]));

        // Create Notification entry for 24-hour update
        Notification::create([
            'user_id' => $client->id,
            'user_type' => get_class($client),
            'type' => NotificationTypeEnum::STATUS_NOT_UPDATED, // Assuming this enum exists
            'status' => $offerStatus->status,
        ]);

        if ($response) {
            $this->info("24-hour notification sent for Offer ID: {$offerStatus->id}");
        } else {
            $this->error("Failed to send 24-hour notification for Offer ID: {$offerStatus->id}");
        }
    }

    /**
     * Send the follow-up after 3 days
     */
    protected function sendFollowUp($client, $offerStatus)
    {
        $response = event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::FOLLOW_UP_PRICE_OFFER,
            "notificationData" => [
                'client' => $client->toArray(),
            ]
        ]));

        // Create Notification entry for 3-day follow-up
        Notification::create([
            'user_id' => $client->id,
            'user_type' => get_class($client),
            'type' => NotificationTypeEnum::FOLLOW_UP_PRICE_OFFER, // Assuming this enum exists
            'status' => $offerStatus->status,
        ]);

        $emailData = [
            'client' => $client->toArray(),
            'status' => $offerStatus->status,
        ];

        Mail::send('Mails.ReminderLeadPriceOffer', ['client' => $emailData['client']], function ($messages) use ($emailData) {
            $messages->to($emailData['client']['email']);
            $sub = __('mail.price_offer_reminder.header');
            $messages->subject($sub);
        });

        if ($response) {
            $this->info("3-day follow-up sent for Offer ID: {$offerStatus->id}");
        } else {
            $this->error("Failed to send 3-day follow-up for Offer ID: {$offerStatus->id}");
        }
    }

    /**
     * Send the final follow-up after 7 days
     */
    protected function sendFinalFollowUp($client, $offerStatus)
    {
        // Trigger WhatsApp Notification
        $response = event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::FINAL_FOLLOW_UP_PRICE_OFFER,
            "notificationData" => [
                'client' => $client->toArray(),
            ]
        ]));

        // Create Notification entry for final follow-up
        Notification::create([
            'user_id' => $client->id,
            'user_type' => get_class($client),
            'type' => NotificationTypeEnum::FINAL_FOLLOW_UP_PRICE_OFFER, // Assuming this enum exists
            'status' => $offerStatus->status,
        ]);

        // Send the email
        $emailData = [
            'client' => $client->toArray(),
            'status' => $offerStatus->status,
        ];

        Mail::send('Mails.ReminderLeadPriceOffer', ['client' => $emailData['client']], function ($messages) use ($emailData) {
            $messages->to($emailData['client']['email']);
            $sub = __('mail.price_offer_reminder.header');
            $messages->subject($sub);
        });

        // Check if the WhatsApp notification was sent
        if ($response) {
            $this->info("Final follow-up sent for Offer ID: {$offerStatus->id}");
        } else {
            $this->error("Failed to send final follow-up for Offer ID: {$offerStatus->id}");
        }
    }

}
