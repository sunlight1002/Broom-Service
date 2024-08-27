<?php

namespace App\Services;

use Facebook\Facebook;
use Illuminate\Support\Facades\Log;
use Exception;
use GuzzleHttp\Client;
use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Campaign;
use FacebookAds\Object\Fields\CampaignFields;
use Illuminate\Support\Facades\Http;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

class FacebookCampaignService
{
    protected $fb;

    public function __construct()
    {
        $this->fb = new Facebook([
            'app_id' => config('services.facebook.app_id'),
            'app_secret' => config('services.facebook.app_secret'),
            'default_graph_version' => 'v14.0', // Check the latest version
        ]);
    }
    public function getAccessToken()
    {
        // Implement the method to get the access token
        // For simplicity, returning a placeholder
        return config('services.facebook.access_token');
    }
    public function getCampaigns()
    {
        $accessToken = $this->getAccessToken();
        $fb = new Facebook([
            'app_id' => config('services.facebook.app_id'),
            'app_secret' => config('services.facebook.app_secret'),
            'default_graph_version' => 'v14.0',
        ]);
    
        try {
            $response = $fb->get('/act_' . config('services.facebook.ad_account_id') . '/campaigns?fields=id,name', $accessToken);
            $campaigns = $response->getGraphEdge();
            return $campaigns;
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            return 'Graph returned an error: ' . $e->getMessage();
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            return 'Facebook SDK returned an error: ' . $e->getMessage();
        }
    }
    

    public function getCampaignCost($campaignId)
    {
        $accessToken = $this->getAccessToken();
        $fb = new Facebook([
            'app_id' => config('services.facebook.app_id'),
            'app_secret' => config('services.facebook.app_secret'),
            'default_graph_version' => 'v14.0',
        ]);
    
        try {
            $response = $fb->get('/' . $campaignId . '/insights?fields=spend', $accessToken);
            $insights = $response->getGraphEdge();
            return $insights;
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            return 'Graph returned an error: ' . $e->getMessage();
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            return 'Facebook SDK returned an error: ' . $e->getMessage();
        }
    }
}
    // public function getCampaigns()
    // {
    //     $url = "https://graph.facebook.com/v17.0/act_" .config('services.facebook.account_id')."/campaigns";
    //     $params = [
    //         'access_token' => $this->longLivedToken(),
    //         'fields' => 'id,name'
    //     ];

    //     try {
    //         $response = $this->client->get($url, [
    //             'query' => $params
    //         ]);

    //         return json_decode($response->getBody()->getContents(), true);
    //     } catch (RequestException $e) {
    //         Log::error('Error fetching campaigns: ' . $e->getMessage());
    //         return null;
    //     }
    // }

    // public function getCampaignCost($campaignId)
    // {
    //     $url = "https://graph.facebook.com/v17.0/{$campaignId}/insights";
    //     $params = [
    //         'access_token' => $this->longLivedToken(),
    //         'fields' => 'spend',
    //         'level' => 'campaign'
    //     ];

    //     try {
    //         $response = $this->client->get($url, [
    //             'query' => $params
    //         ]);
    
    //         $costData = json_decode($response->getBody()->getContents(), true);
    //         Log::info('Cost for Campaign ID ' . $campaignId . ': ' . json_encode($costData)); // Log cost data
    
    //         return $costData;
    //     } catch (RequestException $e) {
    //         Log::error('Error fetching campaign cost for campaign ID ' . $campaignId . ': ' . $e->getMessage());
    //         return null;
    //     }
    // }

    // public function getAllCampaignCosts()
    // {
    //     $campaignCosts = [];
    //     if (isset($campaigns['data'])) {
    //         foreach ($campaigns['data'] as $campaign) {
    //             $cost = $this->getCampaignCost($campaign['id']);
    //             if ($cost) {
    //                 $campaignCosts[] = [
    //                     'campaign_id' => $campaign['id'],
    //                     'campaign_name' => $campaign['name'],
    //                     'cost' => $cost['data'][0]['spend'] ?? 0
    //                 ];
    //             } else {
    //                 Log::error('Failed to get cost for campaign ID ' . $campaign['id']);
    //             }
    //         }
    //     } else {
    //         Log::error('No campaigns found or error fetching campaigns');
    //     }

    //     return $campaignCosts;
    // }

    // public function createCampaign($name, $objective = 'LINK_CLICKS', $buyingType = 'AUCTION', $status = 'PAUSED')
    // {
    //     $url = "https://graph.facebook.com/v20.0/act_{$this->accountId}/campaigns";
    //     $params = [
    //         'access_token' => $this->longLivedToken(),
    //         'name' => $name,
    //         'objective' => $objective,
    //         'buying_type' => $buyingType,
    //         'status' => $status
    //     ];

    //     try {
    //         $response = $this->client->post($url, [
    //             'form_params' => $params
    //         ]);

    //         $campaignData = json_decode($response->getBody()->getContents(), true);
    //         Log::info('Created Campaign: ' . json_encode($campaignData)); // Log campaign data

    //         return $campaignData;
    //     } catch (RequestException $e) {
    //         Log::error('Error creating campaign: ' . $e->getMessage());
    //         return null;
    //     }
    // }


    // protected $fb;
  

    // public function __construct()
    // {
    //     $this->fb = new Facebook([
    //         'app_id' => config('services.facebook.app_id'),
    //         'app_secret' => config('services.facebook.app_secret'),
    //         'default_graph_version' => 'v12.0',
    //     ]);

    //     $this->fb->setDefaultAccessToken(config('services.facebook.access_token'));
    // }
           
    // public function getAppAccessToken($appId, $appSecret) {
    //     $appId = config('services.facebook.app_id');
    //     protected $appSecret = config('services.facebook.app_secret');
    //     $fb = new Facebook([
    //         'app_id' => $appId,
    //         'app_secret' => $appSecret,
    //         'default_graph_version' => 'v12.0',
    //     ]);
    
    //     try {
    //         $response = $fb->getOAuth2Client()->getAccessTokenFromClientCredentials();
    //         return $response->getValue();
    //     } catch (Facebook\Exceptions\FacebookResponseException $e) {
    //         Log::error('Graph returned an error: ' . $e->getMessage());
    //     } catch (Facebook\Exceptions\FacebookSDKException $e) {
    //         Log::error('Facebook SDK returned an error: ' . $e->getMessage());
    //     }
    
    //     return null;
    // }

//     public function getCampaignCost($startDate, $endDate)
//     {
//         $token = $this->longLivedToken();
//         $pageToken = $this->pageAccessToken(); 

//         // Construct the API request URL
//         $url = $this->fburl . "/act_" . config('services.facebook.account_id') . "/insights";
        
//         $queryParams = Http::get($url, [
//             'fields' => 'campaign_name,spend',
//             'time_range' => json_encode(['since' => date('Y-m-d', strtotime($startDate)), 'until' => date('Y-m-d', strtotime($endDate))]),
//             'access_token' => $pageToken,
//         ]);
    
//         // Use cURL to make the API request
//         $ch = curl_init();
//         curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($queryParams));
//         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
//         $response = curl_exec($ch);
        
//         if (curl_errno($ch)) {
//             Log::error('cURL error: ' . curl_error($ch));
//             curl_close($ch);
//             return 0;
//         }
    
//         curl_close($ch);
    
//         // Decode the JSON response
//         $data = json_decode($response, true);
//         Log::info('Facebook API Response:', ['response' => $response, 'decoded' => $data]);

//         if (is_null($data)) {
//             Log::error('Failed to decode JSON response.'. $data['error']['message']);
//             return 0;
//         }
    
//         if (!isset($data['data']) || !is_array($data['data'])) {
//             Log::error('Unexpected API response structure or no data field.');
//             return 0;
//         }

//         $totalSpend = 0;

//         foreach ($data['data'] as $insight) {
//             $spend = isset($insight['spend']) ? $insight['spend'] : 0;
//             if (is_numeric($spend)) {
//                 $totalSpend += floatval($spend);
//             } else {
//                 Log::warning('Non-numeric spend value encountered: ' . $spend);
//             }
//         }
//                 return $totalSpend;
//         }    
    
// }