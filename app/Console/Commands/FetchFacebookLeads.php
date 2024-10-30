<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FetchFacebookLeads extends Command
{
    protected $signature = 'facebook:fetch-yesterday-leads';
    protected $description = 'Fetch leads from Facebook created yesterday and store them in the database.';

    public $fburl = 'https://graph.facebook.com/v17.0/';

    public function __construct()
    {
        parent::__construct();
    }

    // Method to get the page access token
    public function pageAccessToken()
    {
        \Log::info("Access Token", ["token" => config('services.facebook.access_token')]);

        $url = $this->fburl . config('services.facebook.app_scope_id') . '/accounts?access_token=' . config('services.facebook.access_token');
        \Log::info("URL", ["url"=> $url]);

        $ch = curl_init();
        \Log::info("CH", ["ch"=> $ch]);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        \Log::info("Row Result", ["result"=> $result]);

        curl_close($ch);
        $result = json_decode($result);
        \Log::info("Result", ["result"=> $result]);
        
        if (isset($result->error)) {
            return $result->error->message;
        }
        if (count($result->data) > 0) {
            foreach ($result->data as $r) {
                if ($r->id == config('services.facebook.account_id')) {
                    return $r->access_token;
                }
            }
        }
    }

    // cURL implementation to fetch Facebook leads
    public function fetchFacebookLeads($pageId, $accessToken, $yesterdayStart, $yesterdayEnd)
    {
        $url = $this->fburl . "{$pageId}/leads?fields=created_time,field_data&since={$yesterdayStart}&until={$yesterdayEnd}&access_token={$accessToken}";

        \Log::info("Fetching leads from URL: {$url}");

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            \Log::error('cURL error: ' . curl_error($ch));
            \Log::error('cURL request failed for URL: ' . $url);
            return null; 
        }

        \Log::info('Raw response from Facebook API:', ['response' => $result]);

        curl_close($ch);

        $decodedResponse = json_decode($result, true);

        \Log::info('Decoded response from Facebook API:', ['decodedResponse' => $decodedResponse]);

        if (isset($decodedResponse['error'])) {
            \Log::error('Error in Facebook API response:', ['error' => $decodedResponse['error']]);
            return null;
        }

        return $decodedResponse;
    }

    public function handle()
    {
        $pageId = env('FB_page_ID');
        $accessToken = $this->pageAccessToken();

        \Log::info("Access token",['accesstoken' => $accessToken]);

        $yesterdayStart = Carbon::yesterday()->startOfDay()->toIso8601String();
        $yesterdayEnd = Carbon::yesterday()->endOfDay()->toIso8601String();

        Log::info("Fetching leads for Page ID: {$pageId} from {$yesterdayStart} to {$yesterdayEnd}");

        $response = $this->fetchFacebookLeads($pageId, $accessToken, $yesterdayStart, $yesterdayEnd);

        if ($response === null) {
            Log::error('Failed to fetch Facebook leads.');
            $this->error('Failed to fetch Facebook leads.');
            return;
        }

        Log::info('Facebook API response received:', ['response' => $response]);

        if (!isset($response['data'])) {
            Log::error("'data' key is missing from the Facebook API response");
            $this->error("'data' key is missing from the Facebook API response");
            return;
        }

        $leads = $response['data'];
        Log::info('Leads received:', ['leads' => $leads]);

        if (empty($leads)) {
            $this->info('No leads found for yesterday.');
            return;
        }

        // Store each lead in the database
        foreach ($leads as $lead) {
            Log::info('Processing lead:', ['lead' => $lead]);

            if (!isset($lead['created_time'])) {
                Log::error("Lead is missing 'created_time':", ['lead' => $lead]);
                continue; 
            }

            $createdAt = Carbon::parse($lead['created_time']);
            Log::info('Parsed created_time:', ['created_at' => $createdAt]);

            if (!isset($lead['field_data'])) {
                Log::warning("Lead is missing 'field_data':", ['lead' => $lead]);
                $fieldData = [];
            } else {
                $fieldData = $lead['field_data'];
            }

            Log::info('Storing lead data:', [
                'lead_id' => $lead['id'] ?? 'missing ID',
                'field_data' => $fieldData
            ]);

            Lead::updateOrCreate(
                ['lead_id' => $lead['id'] ?? null],
                [
                    'data' => json_encode($fieldData),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]
            );
        }

        $this->info('Yesterday\'s leads fetched and stored successfully.');
    }
}
