<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contract;
use App\Models\Client;
use App\Models\ClientPropertyAddress;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use Carbon\Carbon;
use App;
use Illuminate\Support\Facades\DB;

class ContractReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'team:contract-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reminder to Client and team - Agreement Signature (After 24 Hours, 3 Days, and 7 Days)';

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
        $staticDate = "2024-10-11"; // Static date to start notifications from
        $dates = [
            Carbon::now()->subDay(1)->toDateString(),
            Carbon::now()->subDays(3)->toDateString(),
            Carbon::now()->subDays(7)->toDateString(),
        ];

        // Fetch contracts "not-signed" that were created more than 24 hours ago, but within the last 3 days
        $contracts = Contract::with('client')
            ->where('status', 'not-signed')
            ->whereDate('created_at', '>=', $staticDate)
            ->whereIn(DB::raw('DATE(created_at)'), $dates) // Older than 24 hours but not older than 3 days
            ->get();
        // Notify the team for each contract that is "not-signed"
        foreach ($contracts as $contract) {
            $client = $contract->client;
            $offer = $contract->offer;
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

            if ($client) {
                // Trigger the team notification event
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::NOTIFY_TO_CLIENT_CONTRACT_NOT_SIGNED,
                    "notificationData" => [
                        'client' => $client->toArray(),
                        'contract' => $contract->toArray(),
                        'offer' => $offerArr,
                        'property' => $property
                    ]
                ]));

                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::NOTIFY_TO_TEAM_CONTRACT_NOT_SIGNED,
                    "notificationData" => [
                        'client' => $client->toArray(),
                        'contract' => $contract->toArray(),
                    ]
                ]));
            }
        }

        return 0;
    }
}
