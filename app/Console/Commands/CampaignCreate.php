<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\FacebookInsights;

class CampaignCreate extends Command
{
    protected $signature = 'campaign:create';
    protected $description = 'Fetch and store Facebook campaign insights for all campaigns';

    public function handle()
    {
        $accessToken = config('services.facebook.access_token');
        $baseUrl = "https://graph.facebook.com/v21.0/";

        try {
            $accResponse = Http::withToken($accessToken)->get($baseUrl . "me/adaccounts");
            if ($accResponse->failed()) {
                $this->error('Error fetching ad accounts: ' . $accResponse->body());
                return 1;
            }

            $accounts = $accResponse->json()['data'];

            // Iterate over all ad accounts to fetch campaigns
            foreach ($accounts as $account) {
                $accountId = $account['id'];

                $this->info("Fetching campaigns for ad account ID: $accountId");

                // Fetch campaigns for the account
                $campaignsResponse = Http::withToken($accessToken)
                    ->get($baseUrl . "$accountId/campaigns", [
                        'fields' => 'id,name',
                        'limit' => 10,
                    ]);

                if ($campaignsResponse->failed()) {
                    $this->error('Error fetching campaigns: ' . $campaignsResponse->body());
                    continue;
                }

                $campaigns = $campaignsResponse->json()['data'];

                // // Step 1: Insert campaigns into the database
                // foreach ($campaigns as $campaign) {
                //     $campaignId = $campaign['id'];
                //     $campaignName = $campaign['name'];

                //     $this->info("Inserting campaign ID: $campaignId");

                //     FacebookInsights::updateOrCreate(
                //         ['campaign_id' => $campaignId],
                //         [
                //             'campaign_name' => $campaignName,
                //         ]
                //     );
                // }

                // Step 2: Fetch insights for each campaign
                foreach ($campaigns as $campaign) {
                    $campaignId = $campaign['id'];

                    $this->info("Fetching insights for campaign ID: $campaignId");

                    $insightsResponse = Http::withToken($accessToken)
                        ->get($baseUrl . "$campaignId/insights", [
                            'fields' => 'campaign_id,campaign_name,actions,cost_per_conversion,cost_per_action_type,conversion_values,clicks,cpc,cpm,ctr,cpp,date_start,date_stop,spend,reach',
                            'limit' => 10,
                        ]);

                    if ($insightsResponse->failed()) {
                        $this->error('Error fetching insights for campaign ID ' . $campaignId . ': ' . $insightsResponse->body());
                        continue;
                    }

                    $insightsData = $insightsResponse->json()['data'];

                    if (count($insightsData) > 0) {
                        foreach ($insightsData as $campaignData) {
                            FacebookInsights::updateOrCreate(
                                ['campaign_id' => $campaignData['campaign_id']],
                                [
                                    'campaign_name' => $campaignData['campaign_name'] ?? null,
                                    'date_start' => $campaignData['date_start'] ?? null,
                                    'date_stop' => $campaignData['date_stop'] ?? null,
                                    'spend' => $campaignData['spend'] ?? 0,
                                    'reach' => $campaignData['reach'] ?? 0,
                                    'clicks' => $campaignData['clicks'] ?? 0,
                                    'cpc' => $campaignData['cpc'] ?? null,
                                    'cpm' => $campaignData['cpm'] ?? null,
                                    'ctr' => $campaignData['ctr'] ?? null,
                                    'cpp' => $campaignData['cpp'] ?? null,
                                ]
                            );
                        }
                    } else {
                        $this->info("No insights data available for campaign ID: $campaignId");
                    }
                }
            }

            $this->info('All campaigns processed successfully.');
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }

        return 0;
    }
}
