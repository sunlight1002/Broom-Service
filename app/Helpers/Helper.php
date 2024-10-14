<?php

use App\Mail\MailInvoiceToClient;
use App\Models\Job;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

if (!function_exists('sendInvoicePayToClient')) {
    function sendInvoicePayToClient($id, $docurl, $docnum, $inv_id)
    {
        $job = Job::query()->with('client')->find($id);
        $job = $job->toArray();

        $data = array(
            'docurl' => $docurl,
            'docnum' => $docnum,
            'id'     => $id,
            'job'    => $job,
            'inv_id' => $inv_id
        );
        // $pdf = PDF::loadView('InvoicePdf', compact('invoice'));

        Mail::to($data['job']['client']['email'])->send(new MailInvoiceToClient($data));
    }
}

if (!function_exists('sendWhatsappMessage')) {
    function sendWhatsappMessage($number, $data = array(), $lang = 'he', $replyId = null)
    {
        // Normalize the phone number
        $mobile_no = str_replace("-", "", $number);

        // Prepend country code if necessary
        if (strlen($mobile_no) <= 10) {
            $mobile_no = '972' . $mobile_no; // Assuming '972' is the country code for Israel
        }
        
        // Build the payload for the API request
        $payload = [
            'to' => $mobile_no,
            'body' => str_replace("\t", "", $data['message'])
        ];

        // Include reply ID if provided
        if ($replyId) {
            $payload['reply_to'] = $replyId; // Adjust the key according to your API specification
        }

        // Send the message using Http Client
        $response = Http::withToken(config('services.whapi.token'))
            ->post(config('services.whapi.url') . 'messages/text', $payload);

        // Log the response for debugging
        Log::info($response->json());

        // Check the response status
        if ($response->successful()) { 
            return $response->json();
        } else {
            return $response->object(); // Return the response object on error
        }
    }
}

if (!function_exists('sendWhatsappMediaMessage')) {
    function sendWhatsappMediaMessage($number, $mediaPath, $caption = '', $lang = 'he', $replyId = null)
    {
        $mobile_no = str_replace("-", "", $number);
        if (strlen($mobile_no) <= 10) {
            $mobile_no = '972' . $mobile_no;
        }

        if (!file_exists($mediaPath)) {
            Log::error("File not found at path: $mediaPath");
            return ['error' => 'File not found'];
        }

        // \Log::info(basename($mediaPath));

        try {
            $fileMimeType = mime_content_type($mediaPath); 
            \Log::info($fileMimeType); // Log the MIME type
            $fileName = basename($mediaPath); // Get the filename

            // Upload the file as binary using withBody() and correct content type
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.whapi.token'),
                'accept' => 'application/json',
                'Content-Type' => $fileMimeType,
            ])->withBody(file_get_contents($mediaPath), $fileMimeType)
              ->post(config('services.whapi.url') . 'media');

            Log::info('WhatsApp media upload response: ', $response->json());

            if (!$response->successful()) {
                Log::error('Error uploading WhatsApp media: ', $response->json());
                return ['error' => $response->json()];
            }

            $media = $response->json()['media'][0] ?? null;
            if (!$media || !isset($media['id'])) {
                Log::error('Media ID not found in response.');
                return ['error' => 'Media ID not found'];
            }

            $mediaId = $media['id'];

        } catch (\Exception $e) {
            Log::error('Exception during WhatsApp media upload: ' . $e->getMessage());
            return ['error' => 'An error occurred while uploading the media.'];
        }

        // Send the video message using the media ID
        try {
            $messageResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('services.whapi.token'),
                    'Content-Type' => 'application/json',
                ])->post(config('services.whapi.url') . 'messages/video', [
                    'to' => $mobile_no,
                    'media' => $mediaId,
                    'caption' => $caption,
                    'mime_type' => 'video/mp4'
                ]);

            // Log the message response for debugging
            Log::info('WhatsApp send message response: ', $messageResponse->json());

            // Check the response status
            if ($messageResponse->successful()) {
                return $messageResponse->json();
            } else {
                Log::error('Error sending WhatsApp message: ', $messageResponse->json());
                return ['error' => $messageResponse->json()];
            }
        } catch (\Exception $e) {
            Log::error('Exception during WhatsApp message send: ' . $e->getMessage());
            return ['error' => 'An error occurred while sending the message.'];
        }
    }
}


if (!function_exists('sendWhatsappImageMessage')) {
    function sendWhatsappImageMessage(
        $number,
        $fullMediaPath,
        $caption = '',
        $mimeType = 'image/jpeg', // Adjust based on the image type (e.g., 'image/png')
        $quoted = null,
        $ephemeral = null,
        $edit = null,
        $preview = null,
        $width = 0,
        $height = 0,
        $mentions = [],
        $viewOnce = false
    ) {

        $mobile_no = str_replace("-", "", $number);
        if (strlen($mobile_no) <= 10) {
            $mobile_no = '972' . $mobile_no;
        }

        if (!file_exists($fullMediaPath)) {
            Log::error("File not found at path: $fullMediaPath");
            return ['error' => 'File not found'];
        }
        $fileMimeType = mime_content_type($fullMediaPath); 

        try {

            $fileName = basename($fullMediaPath); // Get the filename

            // Upload the file as binary using withBody() and correct content type
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.whapi.token'),
                'accept' => 'application/json',
                'Content-Type' => $fileMimeType,
            ])->withBody(file_get_contents($fullMediaPath), $fileMimeType)
              ->post(config('services.whapi.url') . 'media');

            Log::info('WhatsApp media upload response: ', $response->json());

            if (!$response->successful()) {
                Log::error('Error uploading WhatsApp media: ', $response->json());
                return ['error' => $response->json()];
            }

            $media = $response->json()['media'][0] ?? null;
            if (!$media || !isset($media['id'])) {
                Log::error('Media ID not found in response.');
                return ['error' => 'Media ID not found'];
            }

            $mediaId = $media['id'];

        } catch (\Exception $e) {
            Log::error('Exception during WhatsApp media upload: ' . $e->getMessage());
            return ['error' => 'An error occurred while uploading the media.'];
        }

        try {
            $messageResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.whapi.token'),
                'Content-Type' => 'application/json',
             ])->post(config('services.whapi.url') . 'messages/image', [
                'to' => $mobile_no,
                'media' => $mediaId, // Encode the image as base64
                'mime_type' => $fileMimeType,
                'no_encode' => true, // Specify if encoding should be disabled
                'view_once' => $viewOnce,
            ]);

            // Log the message response for debugging
            Log::info('WhatsApp send message response: ', $messageResponse->json());

            // Check the response status
            if ($messageResponse->successful()) {
                return $messageResponse->json();
            } else {
                Log::error('Error sending WhatsApp message: ', $messageResponse->json());
                return ['error' => $messageResponse->json()];
            }
        } catch (\Exception $e) {
            Log::error('Exception during WhatsApp message send: ' . $e->getMessage());
            return ['error' => 'An error occurred while sending the message.'];
        }

        
    }
}

if (!function_exists('sendWorkerWhatsappMessage')) {
    function sendWorkerWhatsappMessage($number, $data = array(), $lang = 'he')
    {
        // Normalize the phone number
        $mobile_no = str_replace("-", "", $number);

        // Prepend country code if necessary
        if (strlen($mobile_no) <= 10) {
            $mobile_no = '972' . $mobile_no; // Assuming '972' is the country code for Israel
        }
        
        $response = Http::withToken(config('services.whapi.worker_token'))
            ->post(config('services.whapi.url') . 'messages/text', [
                'to' => $mobile_no,
                'body' => str_replace("\t", "", $data['message'])
            ]);
        Log::info($response->json());
        if($response->successful()) { 
            return 'message sent successfully.';
        } else {
            return $response->object();
        }

        // Send the message using Http Client
        $response = Http::withToken(config('services.whapi.token'))
            ->post(config('services.whapi.url') . 'messages/text', $payload);

        // Log the response for debugging
        Log::info($response->json());

        // Check the response status
        if ($response->successful()) { 
            return $response->json();
        } else {
            return $response->object(); // Return the response object on error
        }
    }
}

if (!function_exists('sendWhatsappMediaMessage')) {
    function sendWhatsappMediaMessage($number, $mediaPath, $caption = '', $lang = 'he', $replyId = null)
    {
        $mobile_no = str_replace("-", "", $number);
        if (strlen($mobile_no) <= 10) {
            $mobile_no = '972' . $mobile_no;
        }

        if (!file_exists($mediaPath)) {
            Log::error("File not found at path: $mediaPath");
            return ['error' => 'File not found'];
        }

        // \Log::info(basename($mediaPath));

        try {
            $fileMimeType = mime_content_type($mediaPath); 
            \Log::info($fileMimeType); // Log the MIME type
            $fileName = basename($mediaPath); // Get the filename

            // Upload the file as binary using withBody() and correct content type
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.whapi.token'),
                'accept' => 'application/json',
                'Content-Type' => $fileMimeType,
            ])->withBody(file_get_contents($mediaPath), $fileMimeType)
              ->post(config('services.whapi.url') . 'media');

            Log::info('WhatsApp media upload response: ', $response->json());

            if (!$response->successful()) {
                Log::error('Error uploading WhatsApp media: ', $response->json());
                return ['error' => $response->json()];
            }

            $media = $response->json()['media'][0] ?? null;
            if (!$media || !isset($media['id'])) {
                Log::error('Media ID not found in response.');
                return ['error' => 'Media ID not found'];
            }

            $mediaId = $media['id'];

        } catch (\Exception $e) {
            Log::error('Exception during WhatsApp media upload: ' . $e->getMessage());
            return ['error' => 'An error occurred while uploading the media.'];
        }

        // Send the video message using the media ID
        try {
            $messageResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('services.whapi.token'),
                    'Content-Type' => 'application/json',
                ])->post(config('services.whapi.url') . 'messages/video', [
                    'to' => $mobile_no,
                    'media' => $mediaId,
                    'caption' => $caption,
                    'mime_type' => 'video/mp4'
                ]);

            // Log the message response for debugging
            Log::info('WhatsApp send message response: ', $messageResponse->json());

            // Check the response status
            if ($messageResponse->successful()) {
                return $messageResponse->json();
            } else {
                Log::error('Error sending WhatsApp message: ', $messageResponse->json());
                return ['error' => $messageResponse->json()];
            }
        } catch (\Exception $e) {
            Log::error('Exception during WhatsApp message send: ' . $e->getMessage());
            return ['error' => 'An error occurred while sending the message.'];
        }
    }
}


if (!function_exists('sendWhatsappImageMessage')) {
    function sendWhatsappImageMessage(
        $number,
        $fullMediaPath,
        $caption = '',
        $mimeType = 'image/jpeg', // Adjust based on the image type (e.g., 'image/png')
        $quoted = null,
        $ephemeral = null,
        $edit = null,
        $preview = null,
        $width = 0,
        $height = 0,
        $mentions = [],
        $viewOnce = false
    ) {

        $mobile_no = str_replace("-", "", $number);
        if (strlen($mobile_no) <= 10) {
            $mobile_no = '972' . $mobile_no;
        }

        if (!file_exists($fullMediaPath)) {
            Log::error("File not found at path: $fullMediaPath");
            return ['error' => 'File not found'];
        }
        $fileMimeType = mime_content_type($fullMediaPath); 

        try {

            $fileName = basename($fullMediaPath); // Get the filename

            // Upload the file as binary using withBody() and correct content type
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.whapi.token'),
                'accept' => 'application/json',
                'Content-Type' => $fileMimeType,
            ])->withBody(file_get_contents($fullMediaPath), $fileMimeType)
              ->post(config('services.whapi.url') . 'media');

            Log::info('WhatsApp media upload response: ', $response->json());

            if (!$response->successful()) {
                Log::error('Error uploading WhatsApp media: ', $response->json());
                return ['error' => $response->json()];
            }

            $media = $response->json()['media'][0] ?? null;
            if (!$media || !isset($media['id'])) {
                Log::error('Media ID not found in response.');
                return ['error' => 'Media ID not found'];
            }

            $mediaId = $media['id'];

        } catch (\Exception $e) {
            Log::error('Exception during WhatsApp media upload: ' . $e->getMessage());
            return ['error' => 'An error occurred while uploading the media.'];
        }

        try {
            $messageResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.whapi.token'),
                'Content-Type' => 'application/json',
             ])->post(config('services.whapi.url') . 'messages/image', [
                'to' => $mobile_no,
                'media' => $mediaId, // Encode the image as base64
                'mime_type' => $fileMimeType,
                'no_encode' => true, // Specify if encoding should be disabled
                'view_once' => $viewOnce,
            ]);

            // Log the message response for debugging
            Log::info('WhatsApp send message response: ', $messageResponse->json());

            // Check the response status
            if ($messageResponse->successful()) {
                return $messageResponse->json();
            } else {
                Log::error('Error sending WhatsApp message: ', $messageResponse->json());
                return ['error' => $messageResponse->json()];
            }
        } catch (\Exception $e) {
            Log::error('Exception during WhatsApp message send: ' . $e->getMessage());
            return ['error' => 'An error occurred while sending the message.'];
        }

        
    }
}

if (!function_exists('sendJobWANotification')) {
    function sendJobWANotification($emailData)
    {
        if (isset($emailData['job']['worker']) && !empty($emailData['job']['worker']['phone'])) {
            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::NEW_JOB,
                "notificationData" => $emailData
            ]));
        }
    }
}

if (!function_exists('get_setting')) {
    function get_setting($key)
    {
        $value =  Setting::where('key', $key)->first();

        $return = (!is_null($value)) ? $value->value : '';

        return $return;
    }
}

class FPDF extends TCPDF
{
}
