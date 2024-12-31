<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WhapiController extends Controller
{
    protected $whapiApiEndpoint;
    protected $whapiApiToken;

    public function __construct()
    {
        $this->whapiApiEndpoint = config('services.whapi.url');
        $this->whapiApiToken = config('services.whapi.token');
    }

    /**
     * Fetch all chats
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllChats()
    {
        try {
            $url = $this->whapiApiEndpoint . "/contacts";
    
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->whapiApiToken,
                'accept' => 'application/json'
            ])->get($url);
    
            if ($response->successful()) {
                \Log::info($response->json());
                return response()->json([
                    'status' => 'success',
                    'data' => $response->json(),
                ]);
            }
    
            return response()->json([
                'status' => 'error',
                'message' => $response->json()['message'] ?? 'Failed to fetch chats.',
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function getChatById($chatId)
    {
        try {
            $url = $this->whapiApiEndpoint . "/chats/" . $chatId;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->whapiApiToken,
                'accept' => 'application/json'
            ])->get($url);

            if ($response->successful()) {
                \Log::info($response->json());
                return response()->json([
                    'status' => 'success',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => $response->json()['message'] ?? 'Failed to fetch chat.',
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getConversationsByNumber(Request $request, $chatId)
    {
        try {
            $url = $this->whapiApiEndpoint . "/messages/list";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->whapiApiToken,
                'accept' => 'application/json'
            ])->get($url);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'success',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => $response->json()['message'] ?? 'Failed to fetch conversations.',
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function deleteMessage(Request $request, $messageId)
    {
        \Log::info("Deleting message with ID: $messageId");
    
        try {
            // Construct the URL for the DELETE request
            $url = $this->whapiApiEndpoint . "/messages/" . $messageId;
    
            // Send DELETE request with necessary headers
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->whapiApiToken,
                'Accept' => 'application/json',
            ])->delete($url);
    
            // Check for a successful response
            if ($response->successful()) {
                \Log::info("Message deleted successfully: " . json_encode($response->json()));
                return response()->json([
                    'status' => 'success',
                    'data' => $response->json(),
                ]);
            }
    
            // Handle non-successful responses
            \Log::error("Failed to delete message: " . $response->body());
            return response()->json([
                'status' => 'error',
                'message' => $response->json()['message'] ?? 'Failed to delete the message.',
            ], $response->status());
        } catch (\Exception $e) {
            \Log::error("Exception while deleting message: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
    

}
