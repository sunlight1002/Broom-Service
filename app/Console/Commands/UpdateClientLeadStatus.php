<?php

namespace App\Console\Commands;

use App\Enums\ContractStatusEnum;
use App\Enums\JobStatusEnum;
use App\Enums\LeadStatusEnum;
use App\Events\ClientLeadStatusChanged;
use App\Models\Client;
use App\Traits\JobSchedule;
use Illuminate\Console\Command;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use Illuminate\Support\Facades\Mail;
use App\Models\Notification;
use App\Enums\NotificationTypeEnum;



class UpdateClientLeadStatus extends Command
{
    use JobSchedule;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:update-lead-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update client lead status';

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
        $clients = Client::query()
            ->whereHas('contract', function ($q) {
                $q->whereIn('status', [ContractStatusEnum::UN_VERIFIED, ContractStatusEnum::VERIFIED]);
            })
            ->get(['id']);

        foreach ($clients as $key => $client) {
            $newLeadStatus = $this->getClientLeadStatusBasedOnJobs($client);

            if (!$client->lead_status || $client->lead_status->lead_status != $newLeadStatus) {
                $client->lead_status()->updateOrCreate(
                    [],
                    ['lead_status' => $newLeadStatus]
                );

                event(new ClientLeadStatusChanged($client, $newLeadStatus));

                $emailData = [
                    'client' => $client->toArray(),
                    'status' => $newLeadStatus,
                ];

                if($newLeadStatus === 'freeze client'){
                    // Trigger WhatsApp Notification
                    event(new WhatsappNotificationEvent([
                       "type" => WhatsappMessageTemplateEnum::CLIENT_IN_FREEZE_STATUS,
                       "notificationData" => [
                           'client' => $client->toArray(),
                       ]
                   ]));
               }
                
               if ($client->notification_type === "both") {
                if ($newLeadStatus === 'unanswered') {
          
                    event(new WhatsappNotificationEvent([
                        "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
                        "notificationData" => [
                            'client' => $client->toArray(),
                        ]
                    ]));
          
                    Mail::send('Mails.UnansweredLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                        $messages->to($emailData['client']['email']);
                        $sub = __('mail.unanswered_lead.header');
                        $messages->subject($sub);
                    });
                }
                
                if ($newLeadStatus === 'irrelevant') {
          
                    event(new WhatsappNotificationEvent([
                        "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
                        "notificationData" => [
                            'client' => $client->toArray(),
                        ]
                    ]));
          
                    Mail::send('Mails.IrrelevantLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                        $messages->to($emailData['client']['email']);
                        $sub = __('mail.irrelevant_lead.header');
                        $messages->subject($sub);
                    });
                }; 

                Notification::create([
                    'user_id' => $client->id,
                    'user_type' => Client::class,
                    'type' => NotificationTypeEnum::USER_STATUS_CHANGED, 
                    'status' => $newLeadStatus
                ]);
                    event(new WhatsappNotificationEvent([
                        "type" => WhatsappMessageTemplateEnum::USER_STATUS_CHANGED,
                        "notificationData" => [
                            'client' => $client->toArray(),
                            'status' => $newLeadStatus,
                        ]
                    ]));
          
                    Mail::send('Mails.UserChangedStatus', $emailData, function ($messages) use ($emailData) {
                        $messages->to($emailData['client']['email']);
                        $sub = __('mail.user_status_changed.header');
                        $messages->subject($sub);
                    });
                
              } elseif ($client->notification_type === "email") {
                if ($newLeadStatus === 'unanswered') {
          
                    Mail::send('Mails.UnansweredLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                        $messages->to($emailData['client']['email']);
                        $sub = __('mail.unanswered_lead.header');
                        $messages->subject($sub);
                    });
                }
                if ($newLeadStatus === 'irrelevant') {
                    
                    Mail::send('Mails.IrrelevantLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                        $messages->to($emailData['client']['email']);
                        $sub = __('mail.irrelevant_lead.header');
                        $messages->subject($sub);
                    });
                }
          
                Notification::create([
                    'user_id' => $client->id,
                    'user_type' => Client::class,
                    'type' => NotificationTypeEnum::USER_STATUS_CHANGED, 
                    'status' => $newLeadStatus
                ]);
                    Mail::send('Mails.UserChangedStatus', $emailData, function ($messages) use ($emailData) {
                        $messages->to('pratik.panchal@spexiontechnologies.com');
                        $sub = __('mail.user_status_changed.header');
                        $messages->subject($sub);
                    });
                
              } else {
                if ($newLeadStatus === 'unanswered') {
          
                    event(new WhatsappNotificationEvent([
                        "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
                        "notificationData" => [
                            'client' => $client->toArray(),
                        ]
                    ]));
                }
                if ($newLeadStatus === 'irrelevant') {
          
                    event(new WhatsappNotificationEvent([
                        "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
                        "notificationData" => [
                            'client' => $client->toArray(),
                        ]
                    ]));
                }
          
                Notification::create([
                    'user_id' => $client->id,
                    'user_type' => get_class($client),
                    'type' => NotificationTypeEnum::USER_STATUS_CHANGED,  
                    'status' => $newLeadStatus
                ]);
                    event(new WhatsappNotificationEvent([
                        "type" => WhatsappMessageTemplateEnum::USER_STATUS_CHANGED,
                        "notificationData" => [
                            'client' => $client->toArray(),
                            'status' => $newLeadStatus,
                        ]
                    ]));
                }
            }
        }

        return 0;
    }
}
