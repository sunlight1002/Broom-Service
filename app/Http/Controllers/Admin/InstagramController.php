<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class InstagramController extends Controller
{
    protected $baseurl = 'https://graph.facebook.com/v22.0/';
    protected $instaBusinessId;
    protected $instaUsername;
    protected $accessToken;

    public function __construct()
    {
        $this->instaBusinessId = config('insta.insta_id');
        $this->instaUsername = config('insta.insta_name');
        $this->accessToken = config('facebook.access_token');
    }

    public function getInstaInfo()
    {
        try {
            $url = $this->baseurl . $this->instaBusinessId;

            $response = Http::withToken($this->accessToken)->get($url, [
                'fields' => 'business_discovery.username(' . $this->instaUsername . '){username, followers_count,media_count,media{comments_count,like_count,id,media_type,media_url,thumbnail_url,caption,timestamp}}',
            ]);
    
            // Check for a successful response
            if ($response->successful()) {
                \Log::info("insta info getting succesfully: " . json_encode($response->json()));
                return response()->json([
                    'status' => 'success',
                    'data' => $response->json(),
                ]);
            }
    
            \Log::error("Failed to get message: " . $response->body());
            return response()->json([
                'status' => 'error',
                'message' => $response->json()['message'] ?? 'Failed.',
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    // public function getInstaPostComments($mediaId)
    // {
    //     try {
    //         $url = $this->baseurl . $mediaId . '/comments';

    //         $response = Http::withToken($this->accessToken)->get($url);
    
    //         // Check for a successful response
    //         if ($response->successful()) {
    //             \Log::info("insta co getting succesfully: " . json_encode($response->json()));
    //             return response()->json([
    //                 'status' => 'success',
    //                 'data' => $response->json(),
    //             ]);
    //         }
    
    //         \Log::error("Failed to get message: " . $response->body());
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $response->json()['message'] ?? 'Failed.',
    //         ], $response->status());
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'An unexpected error occurred: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }
}
