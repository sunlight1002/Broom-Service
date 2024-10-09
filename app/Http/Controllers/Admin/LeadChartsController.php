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
}