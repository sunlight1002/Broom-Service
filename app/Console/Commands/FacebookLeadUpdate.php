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
use Illuminate\Support\Facades\DB;


class FacebookLeadUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:facebookleads';

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
     
                         foreach ($leadsData['data'] as $lead) {
                             $fieldData = $lead['field_data'];
                             $createdTime = $lead['created_time'];
     
                             // Convert Facebook's created_time to the database-compatible format
                             $convertedDate = Carbon::parse($createdTime)->setTimezone('Asia/Jerusalem')->format('Y-m-d H:i:s');
     
                             // Skip this lead if it doesn't fall within the required date range
                             if ($convertedDate < '2024-01-01 00:00:00' || $convertedDate > '2024-11-04 23:59:59') {
                                 continue;
                             }
     
                             $leadInfo = [
                                 'page_id' => $pageId,
                                 'form_id' => $formId,
                                 'created_time' => $convertedDate,
                             ];
     
                             foreach ($fieldData as $field) {
                                 $leadInfo[$field['name']] = $field['values'][0] ?? null;
                             }
     
                             $email = isset($leadInfo['email']) && !empty($leadInfo['email']) ? $leadInfo['email'] : 'lead' . $lead['id'] . '@lead.com';
                             $phone = isset($leadInfo['phone_number']) && !empty($leadInfo['phone_number']) ? str_replace('+', '', $leadInfo['phone_number']) : '';
     
                             if (!empty($phone) && substr($phone, 0, 1) === '0') {
                                 $phone = '972' . substr($phone, 1);
                             }
     
                             $lng = 'heb';
                             if (isset($phone) && strlen($phone) > 10 && substr($phone, 0, 3) != 972) {
                                 $lng = 'en';
                             }
     
                             // Find duplicate phone numbers from the leads in the database
                             $duplicateClients = Client::with('lead_status')
                                 ->where('phone', $phone)
                                 ->whereHas('lead_status', function ($query) {
                                     $query->where('lead_status', 'pending');
                                 })
                                 ->get();
     
                                if ($duplicateClients->isNotEmpty()) {
                                foreach ($duplicateClients as $duplicateClient) {
                                    $oldCreatedAt = DB::table('clients')
                                        ->where('id', $duplicateClient->id)
                                        ->value('created_at');
                            
                                    // Log the old and new created_at values
                                    \Log::info("Duplicate Pending Lead Found: ID {$duplicateClient->id}, Phone {$duplicateClient->phone}, Status: Pending");
                                    \Log::info("Updating created_at for Client ID {$duplicateClient->id} - Old Value: {$oldCreatedAt}, New Value: {$convertedDate}");
                            
                                    DB::table('clients')
                                        ->where('id', $duplicateClient->id)
                                        ->update(['created_at' => Carbon::parse($convertedDate), 'updated_at' => Carbon::parse($convertedDate)]); 
                                }
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
