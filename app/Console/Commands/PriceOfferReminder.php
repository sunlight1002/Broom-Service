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
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use App\Models\ClientPropertyAddress;


class PriceOfferReminder extends Command
{
    protected $signature = 'team:price-offer-reminder-to-team';
    protected $description = 'Reminder to Team - Price Offer Sent (24 Hours, 3 Days, 7 Days)';

    public function handle()
    {
        $timeIntervals = [
            '24hours' => [
                'date' => Carbon::now()->subDay(1)->toDateString(),
                'label' => '24 שעות'
            ],
            '3days' => [
                'date' => Carbon::now()->subDays(3)->toDateString(),
                'label' => '3 ימים'
            ],
            '7days' => [
                'date' => Carbon::now()->subDays(7)->toDateString(),
                'label' => '7 ימים'
            ]
        ];

        foreach ($timeIntervals as $key => $info) {
            $offers = Offer::with('client')
                ->where('status', 'sent')
                ->whereDate('created_at', '>=', '2024-10-11')
                ->whereDate('created_at', $info['date'])
                ->get();

            if ($offers->isEmpty()) {
                continue;
            }

            // Send single team notification for this time interval
            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::STATUS_NOT_UPDATED,
                "notificationData" => [
                    'pending_offer_count' => $offers->count(),
                    'time_interval' => $info['label']
                ]
            ]));

            foreach ($offers as $offer) {
                $client = $offer->client;

                if (!$client) {
                    continue;
                }

                $offer->offer_pending_since = $info['label'];

                $offerArr = $offer->toArray();
                $services = json_decode($offerArr['services']);

                $s_names = '';
                $s_templates_names = '';

                if (isset($services)) {
                    foreach ($services as $k => $service) {
                        $name = $service->template !== "others"
                            ? $service->name
                            : ($service->other_title ?? $service->name);

                        $template = $service->template ?? '';

                        if ($k != count($services) - 1) {
                            $s_names .= $name . ", ";
                            $s_templates_names .= $template . ", ";
                        } else {
                            $s_names .= $name;
                            $s_templates_names .= $template;
                        }
                    }
                }

                $offerArr['services'] = $services;
                $offerArr['service_names'] = $s_names;
                $offerArr['service_template_names'] = $s_templates_names;

                $property = null;

                if (!empty($services[0]->address)) {
                    $property = ClientPropertyAddress::find($services[0]->address);
                }

                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::FOLLOW_UP_PRICE_OFFER_SENT_CLIENT,
                    "notificationData" => [
                        'client' => $client->toArray(),
                        'offer' => $offerArr,
                        'property' => $property
                    ]
                ]));
            }
        }

        return 0;
    }
}
