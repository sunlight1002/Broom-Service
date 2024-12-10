<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Facebook\Facebook;
use App\Models\Client;
use Illuminate\Support\Facades\Http;
use App\Enums\LeadStatusEnum;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\WhatsAppBotClientState;
use App\Models\WebhookResponse;
use App\Events\NewLeadArrived;
use App\Models\Notification;
use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\WhatsappNotificationEvent;

class CampaignTesting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign:testing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'campaign testing';

    public $fbleads = [];
    public $pa_token;

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
        // Fetch credentials from config or .env
        $accessToken = config('services.facebook.access_token');
        $businessId = config('services.facebook.business_id');
        $adAccountId = config('services.facebook.app_id');
        // Base URL for the Facebook Graph API
        $baseUrl = "https://graph.facebook.com/v21.0/"; // Use the latest API version

        try {
            // Make the GET request
            $accResponse = Http::withToken($accessToken)->get($baseUrl."me/adaccounts?access_token=$accessToken");
            if ($accResponse->successful()) {
                $acc_data = $accResponse->json();
                \Log::info($acc_data);
            }

            if ($accResponse->failed()) {
                $this->error('Error fetching campaigns: ' . $accResponse->body());
                return 1; // Indicate failure
            };

            $campaignId = $acc_data['data'][2]['id'];

            $this->info("Fetching insights for campaign ID: $campaignId");

            // Fetching insights for the campaign
            $campaignResponse = Http::withToken($accessToken)
                ->get($baseUrl . "$campaignId/insights", [
                    'fields' => 'campaign_name,actions,cost_per_conversion,cost_per_action_type,conversion_values,clicks,cpc,cpm,ctr,cpp,date_start,date_stop,cost_per_unique_outbound_click,cost_per_unique_inline_link_click,cost_per_unique_click,cost_per_unique_action_type,spend,reach,cost_per_ad_click',
                    'limit' => 10, // Limit to the top 10 insights
                ]);


            $campaignData = $campaignResponse->json();
            \Log::info('Insights for Campaign ID ' . $campaignId, $campaignData);


        } catch(Facebook\Exceptions\FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }
        return 0;
    }
}
