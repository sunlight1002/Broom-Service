<?php

namespace App\Jobs;

use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Models\Client;
use App\Models\ClientPropertyAddress;
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
        $offer = $this->contract->offer;
        $offerArr = $offer->toArray();
        $services = json_decode($offerArr['services']);
        
        if (isset($services)) {
            $s_names = '';
            $s_templates_names = '';
            foreach ($services as $k => $service) {
                if ($k != count($services) - 1 && $service->template != "others") {
                    $s_names .= $service->name . ", ";
                    $s_templates_names .= $service->template . ", ";
                } else if ($service->template == "others") {
                    if ($k != count($services) - 1) {
                        $s_names .= $service->other_title . ", ";
                        $s_templates_names .= $service->template . ", ";
                    } else {
                        $s_names .= $service->other_title;
                        $s_templates_names .= $service->template;
                    }
                } else {
                    $s_names .= $service->name;
                    $s_templates_names .= $service->template;
                }
            }
        }
        $offerArr['services'] = $services;
        $offerArr['service_names'] = $s_names;
        $offerArr['service_template_names'] = $s_templates_names;

        $property = null;

        $addressId = $services[0]->address;
        if (isset($addressId)) {
            $address = ClientPropertyAddress::find($addressId);
            if (isset($address)) {
                $property = $address;
            }
        }
        // Trigger contract verification notifications
        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::NOTIFY_CONTRACT_VERIFY_TO_CLIENT,
            "notificationData" => [
                'client' => $this->client->toArray(),
                'offer' => $offerArr,
                'property' => $property,
            ],
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
