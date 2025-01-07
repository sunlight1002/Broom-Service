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
        $dates = [
            Carbon::now()->subDay(1)->toDateString(),
            Carbon::now()->subDays(3)->toDateString(),
            Carbon::now()->subDays(7)->toDateString(),
        ];

        $offers = Offer::with('client')
            ->where('status', 'sent')
            ->whereDate('created_at', '>=', '2024-10-11')
            ->whereIn(DB::raw('DATE(created_at)'), $dates)
            ->get();
        // Loop through each offer to check how many days it has been in 'sent' status
        foreach ($offers as $offer) {
            $client = $offer->client;
            $daysDiff = Carbon::now()->diffInDays(Carbon::parse($offer->created_at));
            \Log::info($daysDiff);

            if ($daysDiff <= 1) {
                $offer->offer_pending_since = '24 שעות'; // 24 hours
            } elseif ($daysDiff >= 3) {
                $offer->offer_pending_since = '3 ימים'; // 3 days
            } elseif ($daysDiff >= 7) {
                $offer->offer_pending_since = '7 ימים'; // 7 days
            } else {
                $offer->offer_pending_since = '7 ימים'; // Or any default value
            }
            if ($client) {
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
                
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::STATUS_NOT_UPDATED,
                    "notificationData" => [
                        'client' => $client->toArray(),
                        'offer' => $offer->toArray()
                    ]
                ]));

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
