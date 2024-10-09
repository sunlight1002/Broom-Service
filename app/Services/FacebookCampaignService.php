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