<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Facebook\Facebook;
use App\Models\Client;
use App\Models\FacebookInsights;
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

class FetchFacebookLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lead:fetch-facebook-leads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch yesterday Facebook leads and import in database';

    public $fburl = 'https://graph.facebook.com/v21.0/';
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
        $accessToken = config('services.facebook.access_token'); // System User Access Token
        $businessId = config('services.facebook.business_id');

        // Base URL for the Facebook Graph API
        $baseUrl = 'https://graph.facebook.com/v21.0/'; // Use the latest API version

        // Calculate yesterday's date range
        $yesterdayStart = Carbon::now()->startOfDay()->timestamp;
        $yesterdayEnd = Carbon::now()->timestamp;

        try {
            // Step 1: Get all Pages owned by the Business
            $pagesResponse = Http::withToken($accessToken)
                ->get($baseUrl . "$businessId/owned_pages", [
                    'fields' => 'id,name',
                ]);

            if ($pagesResponse->failed()) {
                $this->error('Error fetching pages: ' . $pagesResponse->body());
                return;
            }

            $pagesData = $pagesResponse->json();

            foreach ($pagesData['data'] as $page) {
                $pageId = $page['id'];
                $pageName = $page['name'];

                $this->info("Processing Page: $pageName (ID: $pageId)");

                // Step 2: Get Page Access Token
                $tokenResponse = Http::withToken($accessToken)
                    ->get($baseUrl . "$pageId", [
                        'fields' => 'access_token',
                    ]);

                if ($tokenResponse->failed()) {
                    $this->error('Error fetching page access token: ' . $tokenResponse->body());
                    continue;
                }

                $tokenData = $tokenResponse->json();
                $pageAccessToken = $tokenData['access_token'] ?? null;

                if (!$pageAccessToken) {
                    $this->error("No access token found for page $pageName (ID: $pageId)");
                    continue;
                }

                // Step 3: Get all Lead Forms for the Page
                $formsResponse = Http::withToken($pageAccessToken)
                    ->get($baseUrl . "$pageId/leadgen_forms", [
                        'fields' => 'id,name',
                    ]);

                if ($formsResponse->failed()) {
                    $this->error('Error fetching lead forms: ' . $formsResponse->body());
                    continue;
                }

                $formsData = $formsResponse->json();
                // \Log::info($formsData);

                if (empty($formsData['data'])) {
                    $this->info("No lead forms found for Page: $pageName");
                    continue;
                }

                foreach ($formsData['data'] as $form) {
                    $formId = $form['id'];
                    $formName = $form['name'];

                    $this->info("Fetching leads for Form: $formName (ID: $formId)");

                    // Step 4: Fetch leads for each Form
                    $afterCursor = null;

                    do {
                        $leadsParams = [
                            'fields' => 'field_data,created_time',
                            'since'  => $yesterdayStart,
                            'until'  => $yesterdayEnd,
                        ];

                        if ($afterCursor) {
                            $leadsParams['after'] = $afterCursor;
                        }

                        $leadsResponse = Http::withToken($pageAccessToken)
                            ->get($baseUrl . "$formId/leads", $leadsParams);

                        if ($leadsResponse->failed()) {
                            $this->error('Error fetching leads: ' . $leadsResponse->body());
                            break;
                        }

                        $leadsData = $leadsResponse->json();
                        if (empty($leadsData['data'])) {
                            break;
                        }

                        // Process leads
                        foreach ($leadsData['data'] as $lead) {
                            $fieldData = $lead['field_data'];
                            $createdTime = $lead['created_time'];
                            $leadId = $lead['id']; // Lead ID from Facebook

                            // Fetch lead details including ad_id
                            $leadDetailsResponse = Http::withToken($accessToken)
                                ->get($baseUrl . "$leadId", [
                                    'fields' => 'ad_id', // We are fetching only the ad_id
                                ]);

                            if ($leadDetailsResponse->failed()) {
                                $this->error('Error fetching details for lead ID ' . $leadId);
                                continue;
                            }

                            $leadDetails = $leadDetailsResponse->json();
                            $adId = $leadDetails['ad_id'] ?? null;

                            if (!$adId) {
                                $this->error("No ad ID for lead ID $leadId");
                                continue;
                            }

                            // Step 2: Fetch campaign ID by querying the ad details
                            $adDetailsResponse = Http::withToken($accessToken)
                                ->get($baseUrl . "$adId", [
                                    'fields' => 'campaign_id', // Fetch the campaign_id associated with the ad
                                ]);

                            if ($adDetailsResponse->failed()) {
                                $this->error('Error fetching details for ad ID ' . $adId);
                                continue;
                            }

                            $adDetails = $adDetailsResponse->json();
                            $campaignId = $adDetails['campaign_id'] ?? null;

                            if (!$campaignId) {
                                $this->error("No campaign ID for ad ID $adId");
                                continue;
                            }

                            // Step 3: Fetch the full campaign details to ensure it’s the main campaign
                            $campaignDetailsResponse = Http::withToken($accessToken)
                                ->get($baseUrl . "$campaignId", [
                                    'fields' => 'id,name', // Fetch campaign name and id for storing purposes
                                ]);

                            if ($campaignDetailsResponse->failed()) {
                                $this->error('Error fetching campaign details for campaign ID ' . $campaignId);
                                continue;
                            }

                            $campaignDetails = $campaignDetailsResponse->json();
                            $mainCampaignId = $campaignDetails['id'] ?? null;
                            \Log::info($mainCampaignId);
                            $campaignName = $campaignDetails['name'] ?? 'Unknown Campaign';


                            $leadInfo = [
                                'page_id' => $pageId,
                                'form_id' => $formId,
                                'created_time' => $createdTime,
                            ];

                            foreach ($fieldData as $field) {
                                $leadInfo[$field['name']] = $field['values'][0] ?? null;
                            }

                            $email = isset($leadInfo['email']) && !empty($leadInfo['email']) ? $leadInfo['email'] : 'lead' . $lead['id'] . '@lead.com';

                            $name = isset($leadInfo['full_name']) && !empty($leadInfo['full_name']) ? explode(' ', $leadInfo['full_name']) : explode(' ', 'lead ' . $lead['id']);

                            $phone = isset($leadInfo['phone_number']) && !empty($leadInfo['phone_number']) ? str_replace('+', '', $leadInfo['phone_number']) : '';

                            if (!empty($phone) && substr($phone, 0, 1) === '0') {
                                $phone = '972' . substr($phone, 1);
                            }

                            $lng = 'heb';
                            if (isset($phone) && strlen($phone) > 10 && substr($phone, 0, 3) != 972) {
                                $lng = 'en';
                            }
                            $client = Client::where('email', $email)
                                ->orWhere('phone', $phone)
                                ->first();

                            if ($client) {
                                // Update the existing client
                                // $client->update([
                                //     'payment_method' => 'cc',
                                //     'password'       => Hash::make($lead['id']),
                                //     'passcode'       => $lead['id'],
                                //     'status'         => 0,
                                //     'lng'            => $lng,
                                //     'firstname'      => $name[0] ?? null,
                                //     'lastname'       => $name[1] ?? null,
                                //     'phone'          => $phone,
                                //     'source'         => 'fblead',
                                // ]);
                            } else {
                                // Create a new client if no match is found
                                $client = Client::create([
                                    'email'          => $email,
                                    'payment_method' => 'cc',
                                    'password'       => Hash::make($lead['id']),
                                    'passcode'       => $lead['id'],
                                    'status'         => 0,
                                    'lng'            => $lng,
                                    'firstname'      => $name[0] ?? null,
                                    'lastname'       => $name[1] ?? null,
                                    'phone'          => $phone,
                                    'campaign_id'    => $mainCampaignId,
                                    'source'         => 'fblead',
                                ]);



                                try {
                                    if (!empty($phone)) {
                                        $m = "Hi, I'm Bar, the digital representative of Broom Service. How can I help you today? 😊\n\nAt any stage, you can return to the main menu by sending the number 9 or return one menu back by sending the number 0.\n\n1. About the Service\n2. Service Areas\n3. Set an appointment for a quote\n4. Customer Service\n5. Switch to a human representative (during business hours)\n7. שפה עברית";
                                        if ($lng == 'heb') {
                                            $m = 'היי, אני בר, הנציגה הדיגיטלית של ברום סרוויס. איך אוכל לעזור לך היום? 😊' . "\n\n" . 'בכל שלב תוכלו לחזור לתפריט הראשי ע"י שליחת המס 9 או לחזור תפריט אחד אחורה ע"י שליחת הספרה 0' . "\n\n" . '1. פרטים על השירות' . "\n" . '2. אזורי שירות' . "\n" . '3. קביעת פגישה לקבלת הצעת מחיר' . "\n" . '4. שירות ללקוחות קיימים' . "\n" . '5. מעבר לנציג אנושי (בשעות הפעילות)' . "\n" . '6. English menu';
                                        }
                                        sendWhatsappMessage($phone, array('name' => '', 'message' => $m), $lng == 'heb' ? 'he' : 'en');
                                    }
                                } catch (\Throwable $th) {
                                }
                                $client->lead_status()->updateOrCreate(
                                    [],
                                    ['lead_status' => LeadStatusEnum::PENDING]
                                );
                                try {
                                    // Create a notification
                                    Notification::create([
                                        'user_id' => $client->id,
                                        'user_type' => get_class($client),
                                        'type' => NotificationTypeEnum::NEW_LEAD_ARRIVED,
                                        'status' => 'created'
                                    ]);

                                    $client->load('property_addresses');
                                    // Trigger WhatsApp notification
                                    event(new WhatsappNotificationEvent([
                                        "type" => WhatsappMessageTemplateEnum::NEW_LEAD_ARRIVED,
                                        "notificationData" => [
                                            'client' => $client->toArray(),
                                            'type' => "meta"
                                        ]
                                    ]));
                                } catch (\Throwable $th) {
                                    //throw $th;
                                }

                                WhatsAppBotClientState::updateOrCreate([
                                    'client_id' => $client->id,
                                ], [
                                    'menu_option' => 'main_menu',
                                    'language' => 'he',
                                ]);

                                WebhookResponse::create([
                                    'status'        => 1,
                                    'name'          => 'whatsapp',
                                    'message'       => $m,
                                    'number'        => $phone,
                                    'read'          => 1,
                                    'flex'          => 'A',
                                ]);

                              // Step 4: Update or Create the FacebookInsights entry for the campaign
                                $facebookInsight = FacebookInsights::firstOrCreate(
                                    ['campaign_id' => $mainCampaignId],
                                    ['campaign_name' => $campaignName] // Replace with actual campaign name
                                );

                                // Update lead_count for the campaign
                                $facebookInsight->increment('lead_count', 1);
                            }

                     
                        }

                        // Check for pagination
                        $afterCursor = $leadsData['paging']['cursors']['after'] ?? null;
                    } while ($afterCursor);
                }
            }

            $this->info('All leads fetched successfully.');
        } catch (\Exception $e) {
            $this->error('General error: ' . $e->getMessage() . $e->getTraceAsString());
        }
    }
}
