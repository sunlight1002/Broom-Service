<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FacebookInsights; // Import the model

class CampaignInsightsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Sample data to be inserted into the campaign_insights table
        $data = [
            [
                'campaign_id' => 'act_382549059597363',
                'date_start' => '2024-11-09',
                'date_stop' => '2024-12-08',
                'spend' => 7152.82,
                'reach' => 91154,
                'clicks' => 2809,
                'cpc' => 2.546394,
                'cpm' => 38.150205,
                'ctr' => 1.498205,
                'cpp' => 78.469623,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        // Insert data into the campaign_insights table using Eloquent
        foreach ($data as $campaign) {
            FacebookInsights::updateOrCreate([
                'campaign_id' => $campaign['campaign_id'],
                'date_start' => $campaign['date_start'],
                'date_stop' => $campaign['date_stop'],
                'spend' => $campaign['spend'],
                'reach' => $campaign['reach'],
                'clicks' => $campaign['clicks'],
                'cpc' => $campaign['cpc'],
                'cpm' => $campaign['cpm'],
                'ctr' => $campaign['ctr'],
                'cpp' => $campaign['cpp'],
            ]);
        }
    }
}
