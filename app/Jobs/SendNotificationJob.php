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

    /**
     * Create a new job instance.
     */
    public function __construct(Client $client, $newLeadStatus, $emailData)
    {
        $this->client = $client;
        $this->newLeadStatus = $newLeadStatus;
        $this->emailData = $emailData;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        if ($this->newLeadStatus === 'freeze client') {
            // Trigger WhatsApp Notification
            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::CLIENT_IN_FREEZE_STATUS,
                "notificationData" => [
                    'client' => $this->client->toArray(),
                ]
            ]));
        }

        // Handle notifications based on the client's preferred notification type
        if ($this->client->notification_type === "both") {
            $this->handleWhatsappAndEmailNotifications();
        } elseif ($this->client->notification_type === "email") {
            $this->handleEmailNotifications();
        } else {
            $this->handleWhatsappNotifications();
        }

        // Trigger contract verification notifications
        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::NOTIFY_CONTRACT_VERIFY_TO_CLIENT,
            "notificationData" => ['client' => $this->client->toArray()],
        ]));

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::NOTIFY_CONTRACT_VERIFY_TO_TEAM,
            "notificationData" => ['client' => $this->client->toArray()],
        ]));
    }

    /**
     * Handle both WhatsApp and email notifications.
     */
    protected function handleWhatsappAndEmailNotifications()
    {
        // if ($this->newLeadStatus === 'uninterested') {
        //     event(new WhatsappNotificationEvent([
        //         "type" => WhatsappMessageTemplateEnum::FOLLOW_UP_ON_OUR_CONVERSATION,
        //         "notificationData" => ['client' => $this->client->toArray()],
        //     ]));
        //     SendUninterestedClientEmail::dispatch($this->client, $this->emailData);
        // }

        // if ($this->newLeadStatus === 'unanswered') {
        //     event(new WhatsappNotificationEvent([
        //         "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
        //         "notificationData" => ['client' => $this->client->toArray()],
        //     ]));
            // App::setLocale($client['lng']);
                // Mail::send('Mails.UnansweredLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                //     $messages->to($emailData['client']['email']);
                //     $sub = __('mail.unanswered_lead.header');
                //     $messages->subject($sub);
                // });
        // }

        // if ($this->newLeadStatus === 'irrelevant') {
        //     event(new WhatsappNotificationEvent([
        //         "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
        //         "notificationData" => ['client' => $this->client->toArray()],
        //     ]));
            // App::setLocale($client['lng']);
            // Mail::send('Mails.IrrelevantLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
            //     $messages->to($emailData['client']['email']);
            //     $sub = __('mail.irrelevant_lead.header');
            //     $messages->subject($sub);
            // });
        // }
         // event(new WhatsappNotificationEvent([
                //     "type" => WhatsappMessageTemplateEnum::USER_STATUS_CHANGED,
                //     "notificationData" => [
                //         'client' => $client->toArray(),
                //         'status' => $newLeadStatus,
                //     ]
                // ]));
    }

    /**
     * Handle only email notifications.
     */
    protected function handleEmailNotifications()
    {
        // if ($this->newLeadStatus === 'uninterested') {
        //     SendUninterestedClientEmail::dispatch($this->client, $this->emailData);
        // }

        // if ($this->newLeadStatus === 'unanswered') {
             // App::setLocale($client['lng']);
                // Mail::send('Mails.UnansweredLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                //     $messages->to($emailData['client']['email']);
                //     $sub = __('mail.unanswered_lead.header');
                //     $messages->subject($sub);
                // });
        // }

        // if ($this->newLeadStatus === 'irrelevant') {
             // App::setLocale($client['lng']);
                // Mail::send('Mails.IrrelevantLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                //     $messages->to($emailData['client']['email']);
                //     $sub = __('mail.irrelevant_lead.header');
                //     $messages->subject($sub);
                // });
        // }
         // event(new WhatsappNotificationEvent([
            //     "type" => WhatsappMessageTemplateEnum::USER_STATUS_CHANGED,
            //     "notificationData" => [
            //         'client' => $client->toArray(),
            //         'status' => $newLeadStatus,
            //     ]
            // ]));
    }

    /**
     * Handle only WhatsApp notifications.
     */
    protected function handleWhatsappNotifications()
    {
        // if ($this->newLeadStatus === 'uninterested') {
        //     event(new WhatsappNotificationEvent([
        //         "type" => WhatsappMessageTemplateEnum::FOLLOW_UP_ON_OUR_CONVERSATION,
        //         "notificationData" => ['client' => $this->client->toArray()],
        //     ]));
        // }

        // if ($this->newLeadStatus === 'unanswered') {
        //     event(new WhatsappNotificationEvent([
        //         "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
        //         "notificationData" => ['client' => $this->client->toArray()],
        //     ]));
        // }

        // if ($this->newLeadStatus === 'irrelevant') {
        //     event(new WhatsappNotificationEvent([
        //         "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
        //         "notificationData" => ['client' => $this->client->toArray()],
        //     ]));
        // }
        // event(new WhatsappNotificationEvent([
                //     "type" => WhatsappMessageTemplateEnum::USER_STATUS_CHANGED,
                //     "notificationData" => [
                //         'client' => $client->toArray(),
                //         'status' => $newLeadStatus,
                //     ]
        // ]));
    }
}
