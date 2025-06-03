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
        $staticDate = "2025-01-01";
        $timeIntervals = [
            '24hours' => Carbon::now()->subHours(24),
            '3days' => Carbon::now()->subHours(72),
            '7days' => Carbon::now()->subHours(168),
        ];

        foreach ($timeIntervals as $key => $targetDateTime) {
            $start = $targetDateTime->copy()->startOfHour();
            $end = $targetDateTime->copy()->endOfHour();

            $contracts = Contract::with(['client', 'offer'])
                ->where('status', 'not-signed')
                ->whereDate('created_at', '>=', $staticDate)
                ->whereBetween('created_at', [$start, $end])
                ->get();

            $hebKey = match ($key) {
                '24hours' => '24 שעות',
                '3days' => '3 ימים',
                '7days' => '7 ימים',
            };

            if ($contracts->count() > 0) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::NOTIFY_TO_TEAM_CONTRACT_NOT_SIGNED,
                    "notificationData" => [
                        'pending_contracts_count' => $contracts->count(),
                        'time_interval' => $hebKey
                    ]
                ]));
            }

            foreach ($contracts as $contract) {
                $client = $contract->client;
                $offer = $contract->offer;

                if (!$client || $this->notificationSent($client->id, $key)) {
                    continue;
                }

                $services = $offer ? json_decode($offer->services, true) : [];
                $serviceNames = $this->getServiceNames($services);
                $property = $this->getProperty($services);

                $this->sendNotificationsToClient($client, $contract, $offer, $serviceNames, $property);
                $this->storeNotificationMeta($client->id, $key);
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

    private function sendNotificationsToClient($client, $contract, $offer, $serviceNames, $property)
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
