<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contract;
use App\Models\Client;
use App\Models\ClientMetas;
use App\Models\ClientPropertyAddress;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Enums\ClientMetaEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ContractReminder extends Command
{
    protected $signature = 'team-and-client:contract-reminder';
    protected $description = 'Reminder to Client and team - Agreement Signature (After 24 Hours, 3 Days, and 7 Days)';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $staticDate = "2024-10-11";
        $timeIntervals = [
            '24hours' => Carbon::now()->subDay(1)->toDateString(),
            '3days' => Carbon::now()->subDays(3)->toDateString(),
            '7days' => Carbon::now()->subDays(7)->toDateString(),
        ];

        $contracts = Contract::with('client')
            ->where('status', 'not-signed')
            ->whereDate('created_at', '>=', $staticDate)
            ->whereIn(DB::raw('DATE(created_at)'), $timeIntervals)
            ->get();

        foreach ($contracts as $contract) {
            $client = $contract->client;

            if (!$client) {
                continue;
            }

            $offer = $contract->offer;
            $services = $offer ? json_decode($offer->services, true) : [];
            $serviceNames = $this->getServiceNames($services);
            $property = $this->getProperty($services);

            foreach ($timeIntervals as $key => $date) {
                if ($contract->created_at->toDateString() === $date && !$this->notificationSent($client->id, $key)) {
                    $this->sendNotifications($client, $contract, $offer, $serviceNames, $property, $key);
                    $this->storeNotificationMeta($client->id, $key);
                }
            }
        }

        return 0;
    }

    private function getServiceNames($services)
    {
        $names = '';
        foreach ($services as $k => $service) {
            if (isset($service['template']) && $service['template'] !== "others") {
                $names .= $service['name'] . ", ";
            } else {
                $names .= $service['other_title'] ?? $service['name'] . ", ";
            }
        }
        return rtrim($names, ", ");
    }

    private function getProperty($services)
    {
        $addressId = $services[0]['address'] ?? null;
        return $addressId ? ClientPropertyAddress::find($addressId) : null;
    }

    private function notificationSent($clientId, $timeKey)
    {
        $metaKey = match ($timeKey) {
            '24hours' => ClientMetaEnum::NOTIFICATION_SENT_CONTRACT_NOTSIGNED_24HOURS,
            '3days' => ClientMetaEnum::NOTIFICATION_SENT_CONTRACT_NOTSIGNED_3DAYS,
            '7days' => ClientMetaEnum::NOTIFICATION_SENT_CONTRACT_NOTSIGNED_7DAYS,
        };

        return ClientMetas::where('client_id', $clientId)->where('key', $metaKey)->exists();
    }

    private function sendNotifications($client, $contract, $offer, $serviceNames, $property, $timeKey)
    {
        $offerArr = [
            'services' => $serviceNames,
        ];

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::NOTIFY_TO_CLIENT_CONTRACT_NOT_SIGNED,
            "notificationData" => [
                'client' => $client->toArray(),
                'contract' => $contract->toArray(),
                'offer' => $offerArr,
                'property' => $property,
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

    private function storeNotificationMeta($clientId, $timeKey)
    {
        $metaKey = match ($timeKey) {
            '24hours' => ClientMetaEnum::NOTIFICATION_SENT_CONTRACT_NOTSIGNED_24HOURS,
            '3days' => ClientMetaEnum::NOTIFICATION_SENT_CONTRACT_NOTSIGNED_3DAYS,
            '7days' => ClientMetaEnum::NOTIFICATION_SENT_CONTRACT_NOTSIGNED_7DAYS,
        };

        ClientMetas::create([
            'client_id' => $clientId,
            'key' => $metaKey,
            'value' => Carbon::now()->toDateTimeString(),
        ]);
    }
}
