<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\WhatsAppBotClientState;
use Carbon\Carbon;
use Twilio\Rest\Client as TwilioClient;

class SendMessageForSomeStatusLeadsSinceApril extends Command
{
    protected $signature = 'leads:send-reminder-from-april';
    protected $description = 'Send WhatsApp messages to leads with specific statuses since April 1st';

    protected $twilioAccountSid;
    protected $twilioAuthToken;
    protected $twilioWhatsappNumber;
    protected $twilio;
    protected $twilioWorkerLeadWhatsappNumber;

    public function __construct()
    {
        parent::__construct();

        $this->twilioAccountSid = config('services.twilio.twilio_id');
        $this->twilioAuthToken = config('services.twilio.twilio_token');
        $this->twilioWhatsappNumber = config('services.twilio.twilio_whatsapp_number');
        $this->twilioWorkerLeadWhatsappNumber = config('services.twilio.worker_lead_whatsapp_number');

        $this->twilio = new TwilioClient($this->twilioAccountSid, $this->twilioAuthToken);
    }

    public function handle()
    {
        $aprilFirst = Carbon::createFromDate(null, 4, 1)->startOfDay();
        \Log::info('April first: ' . $aprilFirst);

        $clients = Client::where('status', '0')
            ->whereHas('lead_status', function ($query) use ($aprilFirst) {
                $query->whereIn('lead_status', ['unanswered', 'unanswered final', 'uninterested', 'irrelevant'])
                    ->where('updated_at', '>=', $aprilFirst);
            })
            ->with(['lead_status'])
            ->get();

        $this->info("Found {$clients->count()} clients to notify.");

        foreach ($clients as $client) {
            try {
                $twi = $this->twilio->messages->create(
                    "whatsapp:+" . $client->phone,
                    [
                        "from" => $this->twilioWhatsappNumber,
                        "contentSid" => "HX68cd4d0ec1d32565a4361dfaaf46f005",
                    ]
                );

                WhatsAppBotClientState::updateOrCreate([
                    'client_id' => $client->id,
                ], [
                    'menu_option' => 'since_april',
                    'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                ]);

                \Log::info($twi->sid);
                StoreWebhookResponse($twi->body ?? '', $client->phone, $twi->toArray());
                $this->info("Message sent to client ID {$client->id} ({$client->phone})");
            } catch (\Exception $e) {
                $this->error("Failed to send to {$client->phone}: " . $e->getMessage());
            }
        }

        return 0;
    }
}