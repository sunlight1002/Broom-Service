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
        $baseUrl = "https://graph.facebook.com/v21.0/act_$adAccountId/campaigns"; // Use the latest API version
        // \Log::info($baseUrl);
        $queryParams = [
        'effective_status' => '["ACTIVE","PAUSED"]', // Filter campaigns by status
        'fields' => 'name,objective' // Fields to retrieve
        ];

        try {
            // Make the GET request
            $response = Http::withToken($accessToken)->get($baseUrl, $queryParams);

            if ($response->successful()) {
                $campaigns = $response->json();
                $this->info('Fetched campaigns successfully:');
                $this->line(json_encode($campaigns, JSON_PRETTY_PRINT));
            } else {
                $this->error('Failed to fetch campaigns: ' . $response->body());
            }
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
