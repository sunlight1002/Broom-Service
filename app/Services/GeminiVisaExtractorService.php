<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiVisaExtractorService
{
    /**
     * Extract expiry date and number from a visa image using Google Gemini API.
     *
     * @param string $imagePath (absolute path to the image file)
     * @return array ['expiry_date' => string|null, 'number' => string|null]
     */
    public function extractExpiryDateAndNumber($imagePath)
    {
        Log::info('Starting visa extraction with Gemini', ['image_path' => $imagePath]);
        $apiKey = config('services.gemini.api_key');
        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
        // Alternatively for Gemini 1.0:
        // $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro-vision:generateContent';
    
        // Read image and encode as base64
        $imageData = base64_encode(file_get_contents($imagePath));
        $mimeType = mime_content_type($imagePath);
    
        $payload = [
            'contents' => [
                'parts' => [
                    [
                        'text' => 'You are an expert at extracting structured data from scanned documents. Given a photo of a visa, extract the expiry date and number (could be passport number, ID number, or visa number). Reply in JSON format: {"expiry_date": "...", "number": "..."}. If not found, use null. For expiry date, use YYYY-MM-DD format if possible.'
                    ],
                    [
                        'inlineData' => [  // Note the camelCase change here
                            'mimeType' => $mimeType,  // Note the camelCase change here
                            'data' => $imageData
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'topK' => 32,
                'topP' => 1,
                'maxOutputTokens' => 300,
                'responseMimeType' => 'application/json'  // New parameter to request JSON response
            ]
        ];
    
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($endpoint . '?key=' . $apiKey, $payload);
    
        if (!$response->successful()) {
            Log::error('Google Gemini API error', ['response' => $response->body()]);
            return [
                'expiry_date' => null,
                'number' => null,
            ];
        }
    
        // Updated response parsing for newer API versions
        $content = $response->json();
        $result = [
            'expiry_date' => null,
            'number' => null,
        ];
    
        // Try to extract the JSON response directly
        if (isset($content['candidates'][0]['content']['parts'][0]['text'])) {
            $textResponse = $content['candidates'][0]['content']['parts'][0]['text'];
            if (preg_match('/\{.*\}/s', $textResponse, $matches)) {
                $json = json_decode($matches[0], true);
                if (is_array($json)) {
                    $result['expiry_date'] = $json['expiry_date'] ?? null;
                    $result['number'] = $json['number'] ?? null;
                }
            }
        }
    
        Log::info('Google Gemini API result', ['result' => $result]);
    
        return $result;
    }
}