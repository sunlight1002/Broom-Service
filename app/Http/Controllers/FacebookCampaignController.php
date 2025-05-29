<?php

namespace App\Http\Controllers;
use App\Models\FacebookInsights;
use Illuminate\Http\Request;

class FacebookCampaignController extends Controller
{
    public function getCampaignName($campaign_id) 
    {
        $campaign = FacebookInsights::where('campaign_id', $campaign_id)->first();

        if (!$campaign) {
            return response()->json(['message' => 'Campaign not found'], 404);
        }
    
        return response()->json(['campaign_name' => $campaign->campaign_name]);
    }
}
