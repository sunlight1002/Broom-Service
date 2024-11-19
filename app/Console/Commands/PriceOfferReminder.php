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

            if ($daysDiff == 1) {
                $offer->offer_pending_since = '24 שעות'; // 24 hours
            } elseif ($daysDiff == 3) {
                $offer->offer_pending_since = '3 ימים'; // 3 days
            } elseif ($daysDiff == 7) {
                $offer->offer_pending_since = '7 ימים'; // 7 days
            } else {
                $offer->offer_pending_since = '7 ימים'; // Or any default value
            }
            if ($client) {
                $offerArr = $offer->toArray();
                $offerArr['services'] = json_decode($offerArr['services']);
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
                        'offer' => $offerArr
                    ]
                ]));
            }
        }

        return 0;
    }
}
