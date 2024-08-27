<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Schedule;
use App\Models\Offer;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\FacebookCampaignService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;

class LeadChartsController extends Controller
{
    /**
     * Display analytics for leads, meetings, and accepted offers.
     *
     * @return \Illuminate\Http\Response
     */

   
     protected $facebookService;

     public function __construct(FacebookCampaignService $facebookService)
     {
         $this->facebookService = $facebookService;
     }
 
     public function index()
     {
         $campaigns = $this->facebookService->getCampaigns();
 
         if ($campaigns instanceof \Facebook\GraphNodes\GraphEdge) {
             $campaigns = $campaigns->asArray();
         }
 
         return response()->json($campaigns);
     }
 
     public function cost($campaignId)
     {
         $insights = $this->facebookService->getCampaignCost($campaignId);
 
         if ($insights instanceof \Facebook\GraphNodes\GraphEdge) {
             $insights = $insights->asArray();
         }
 
         return response()->json($insights);
     }

    public function lineGraphData(Request $request,FacebookCampaignService $facebookCampaignService)
    {  
        
        $startDate = $request->query('start_date', now()->startOfMonth());
        $endDate = $request->query('end_date', now()->endOfMonth());
    
        $leads = Client::where('source', 'fblead')
                ->whereBetween('created_at', [$startDate.' 00:00:00',$endDate.' 23:59:59'])->get();
          
        $totalLeads = $leads->count();
       
        $leadIds = $leads->pluck('id');
       
        $meetings = Schedule::whereIn('client_id', $leadIds)
            ->whereBetween('created_at', [$startDate.' 00:00:00',$endDate.' 23:59:59'])
            ->get();
        $totalMeetings = $meetings->count();

        $meetingsPercentage = $totalLeads > 0 ? ($totalMeetings / $totalLeads) * 100 : 0;
    
        $totalClientOffers = Offer::whereIn('client_id', $leadIds)
            ->whereBetween('created_at', [$startDate.' 00:00:00',$endDate.' 23:59:59'])
            ->distinct('client_id')
            ->count('client_id');
        $clientsAfterMeetingsPercentage = $totalMeetings > 0 ? ($totalClientOffers / $totalMeetings) * 100 : 0;
    
        $contracts = Contract::whereIn('client_id', $leadIds)
            ->whereBetween('created_at', [$startDate.' 00:00:00',$endDate.' 23:59:59'])
            ->get();
        $totalContracts = $contracts->count();
    
        $facebookCampaignCost =$facebookCampaignService->getCampaignCost($startDate, $endDate);
        Log::info('Facebook Campaign Cost: ' . $facebookCampaignCost);

        $totalCost = $facebookCampaignCost;
    
        // Calculate cost per client
        $finalCostPerClient = $totalContracts > 0 ? $totalCost / $totalContracts : 0;
    
        // Prepare data for the chart
        $chartData = [
            'label' => "Per Day Record",
            'totalLeads' => $totalLeads,
            'totalMeetings' => $totalMeetings,
            'meetingsPercentage' => $meetingsPercentage,
            'totalClientOffers' => $totalClientOffers,
            'clientsAfterMeetingsPercentage' => $clientsAfterMeetingsPercentage,
            'totalCost' => $totalCost,
            'totalContracts' => $totalContracts,
            'finalCostPerClient' => $finalCostPerClient,
        ];
    
        return response()->json($chartData);
    }
    
    // public function showAllCampaignCosts(Request $request,FacebookCampaignService $facebookCampaignService){
    //     $startDate = $request->query('start_date', now()->startOfMonth());
    //     $endDate = $request->query('end_date', now()->endOfMonth());
    //     $facebookCampaignCost =$facebookCampaignService->getCampaignCost($startDate, $endDate);
    //     return response()->json($facebookCampaignCost);
    // }

    // public function getCampaignCost()
    // {
    //     $token = $this->longLivedToken();
    //     $pageToken = $this->pageAccessToken(); 

    //     // Construct the API request URL
    //     $url = $this->fburl . "/act_" . config('services.facebook.page_id') . "/insights";
        
    //     $queryParams = Http::get($url, [
    //         'fields' => 'campaign_name,spend',
    //         //'time_range' => json_encode(['since' => date('Y-m-d', 'until' => date('Y-m-d',)]),
    //         'access_token' =>  $pageToken,
    //     ]);
    
    //     // Use cURL to make the API request
    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($queryParams));
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    //     $response = curl_exec($ch);
    //     if (curl_errno($ch)) {
    //         Log::error('cURL error: ' . curl_error($ch));
    //         curl_close($ch);
    //         return 0;
    //     }
    
    //     curl_close($ch);
    
    //     // Decode the JSON response
    //     $data = json_decode($response, true);
    //     Log::info('Facebook API Response:', ['response' => $response, 'decoded' => $data]);

    //     if (is_null($data)) {
    //         Log::error('Failed to decode JSON response.'. $data['error']['message']);
    //         return 0;
    //     }
    
    //     if (!isset($data['data']) || !is_array($data['data'])) {
    //         Log::error('Unexpected API response structure or no data field.');
    //         return 0;
    //     }

    //     $totalSpend = 0;

    //     foreach ($data['data'] as $insight) {
    //         $spend = isset($insight['spend']) ? $insight['spend'] : 0;
    //         if (is_numeric($spend)) {
    //             $totalSpend += floatval($spend);
    //         } else {
    //             Log::warning('Non-numeric spend value encountered: ' . $spend);
    //         }
    //     }
    //             return $totalSpend;
    //     }    
        
    

 // protected $facebookCampaignService;

    // public function __construct(FacebookCampaignService $facebookCampaignService)
    // {
    //     $this->facebookCampaignService = $facebookCampaignService;
    // }

   
    // public function createCampaign(Request $request)
    // {
    //     try {
    //         $name = $request->input('name');
    //         $objective = $request->input('objective', 'LINK_CLICKS');
    //         $buyingType = $request->input('buying_type', 'AUCTION');
    //         $status = $request->input('status', 'PAUSED');

    //         $campaign = $this->facebookCampaignService->createCampaign($name, $objective, $buyingType, $status);

    //         Log::info('Created Campaign: ' . json_encode($campaign));

    //         // Return the created campaign as a JSON response
    //         return response()->json($campaign);
    //     } catch (\Exception $e) {
    //         Log::error('Error creating campaign: ' . $e->getMessage());

    //         // Return an error response
    //         return response()->json(['error' => 'Failed to create campaign'], 500);
    //     }
    // }


    // public function redirectToFacebook()
    // {
    //     $app_id = env('FB_APP_ID');
    //     $redirect_uri = urlencode(route('facebook.api.callback'));
    //     $fb_permissions = 'ads_read'; // Add any other required permissions here
    //     $fb_auth_url = "https://www.facebook.com/v20.0/dialog/oauth?client_id={$app_id}&redirect_uri={$redirect_uri}&scope={$fb_permissions}";

    //     return response()->json(['url' => $fb_auth_url]);
    // }

    //  public function handleFacebookCallback(Request $request)
    // {
    //     $app_id = env('FB_APP_ID');
    //     $app_secret = env('FB_APP_SECRET');
    //     $redirect_uri = route('facebook.api.callback');
    //     $code = $request->input('code');

        // // Exchange code for short-lived access token
        // $response = Http::get("https://graph.facebook.com/v17.0/oauth/access_token", [
        //     'client_id' => $app_id,
        //     'redirect_uri' => $redirect_uri,
        //     'client_secret' => $app_secret,
        //     'code' => $code,
        // ]);

        // // Log the entire response for debugging
        // Log::info('Facebook OAuth response', ['response' => $response->json()]);

        // if (!$response->successful() || !isset($response->json()['access_token'])) {
        //     return response()->json([
        //         'error' => 'Failed to obtain short-lived access token',
        //         'response' => $response->json()
        //     ], 400);
        // }

        // $short_lived_access_token = $response->json()['access_token'];

        // Exchange short-lived token for long-lived token
    //     $response = Http::get("https://graph.facebook.com/v20.0/oauth/access_token", [
    //         'grant_type' => 'fb_exchange_token',
    //         'client_id' => $app_id,
    //         'client_secret' => $app_secret,
    //         'fb_exchange_token' => env('FB_ACCESS_TOKEN'),
    //     ]);

    //     // Log the entire response for debugging
    //     Log::info('Facebook long-lived token response', ['response' => $response->json()]);

    //     if (!$response->successful() || !isset($response->json()['access_token'])) {
    //         return response()->json([
    //             'error' => 'Failed to obtain long-lived access token',
    //             'response' => $response->json()
    //         ], 400);
    //     }

    //     $long_lived_access_token = $response->json()['access_token'];

    //     // Save the long-lived access token securely
    //     // For example, save it to the database or session
    //     // Here, we are just returning it for demonstration purposes

    //     return response()->json(['access_token' => $long_lived_access_token]);
    // }

    // public function getCampaignCost(Request $request)
    // {
    //     $fb_account_id = env('FB_ACCOUNT_ID');
    //     $access_token = $request->input('access_token'); // Get the long-lived access token from the request

    //     // Fetch campaign cost
    //     $response = Http::get("https://graph.facebook.com/v17.0/act_{$fb_account_id}/campaigns", [
    //         'access_token' => $access_token,
    //         'fields' => 'name,spend', // Specify the fields you need
    //     ]);

    //     $campaigns = $response->json(['data']);

    //     // Return the campaign data as JSON
    //     return response()->json(['campaigns' => $campaigns]);
    // }
}


// Create Facebook App: Ensure you have your Facebook app created.
// Generate Authorization URL: This URL is used to request user login and permissions.
// Use Postman to Exchange Authorization Code for Access Token: Use the code obtained from the user authorization to get the access token.
// Step 1: Generate Authorization URL
// Direct the user to this URL to authorize your app and obtain an authorization code.

// Authorization URL Example:


// https://www.facebook.com/v14.0/dialog/oauth?
// client_id=YOUR_APP_ID&
// redirect_uri=YOUR_REDIRECT_URI&
// scope=ads_read,ads_management