<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\WebhookResponse;
use App\Models\LeadStatus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Twilio\TwiML\VoiceResponse;

class TwilioController extends Controller
{
    public function webhook(Request $request)
    {
        $request_data = $request->all();

        $webhook_response_client = Client::updateOrCreate([
            'phone' => $request_data['From'],
        ], [
            'email'             => $request_data['From'] . '@lead.com',
            'payment_method'    => 'cc',
            'password'          => Hash::make($request_data['From']),
            'status'            => 0,
            'lng'               => 'heb',
            'firstname'         => 'lead_' . $request_data['From']
        ]);

        LeadStatus::UpdateOrCreate(
            [
                'client_id' => $webhook_response_client->id
            ],
            [
                'client_id' => $webhook_response_client->id,
                'lead_status' => 'Pending'
            ]
        );

        $webhook_response = WebhookResponse::create([
            'number'    => $request_data['From'],
            'read'      => 1,
            'name'      => 'twilio-voice-call',
            'data'      => json_encode($request_data)
        ]);

        // send voice response back to user (in hebrew)
        $response = new VoiceResponse;
        $response->say(
            "תודה שהתקשרת! שיהיה לך יום טוב.",
            ['voice' => 'Google.he-IL-Wavenet-A', 'language' => 'he-IL']
        );
        Log::info($response);
        echo $response;
    }
}
