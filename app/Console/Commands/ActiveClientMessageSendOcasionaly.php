<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use Twilio\Rest\Client as TwilioClient;


class ActiveClientMessageSendOcasionaly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:active_client_message_ocasionaly';
    protected $twilioAccountSid, $twilioAuthToken, $twilioWhatsappNumber, $twilioWorkerLeadWhatsappNumber, $twilio;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send active client message ocasionaly';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->twilioAccountSid = config('services.twilio.twilio_id');
        $this->twilioAuthToken = config('services.twilio.twilio_token');
        $this->twilioWhatsappNumber = config('services.twilio.twilio_whatsapp_number');
        $this->twilioWorkerLeadWhatsappNumber = config('services.twilio.worker_lead_whatsapp_number');

        $this->twilio = new TwilioClient($this->twilioAccountSid, $this->twilioAuthToken);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $clients = Client::where('status', '2')
            ->whereHas('lead_status', function ($query) {
                $query->where('lead_status', 'active client');
            })
            ->get();

        foreach ($clients as $client) {

            if ($client->disable_notification == 1) {
                \Log::info('monday notification already sent: ' . $client->id);
                continue;
            }

            // $sid = $client->lng == "heb" ? "HX9a0295f9e43f2a5903d3a87a8a708b8b" : "HXf9769a88d05e82ca7037aae128ea5b76";

            $twi = $this->twilio->messages->create(
                "whatsapp:+" . $client->phone,
                [
                    "from" => $this->twilioWhatsappNumber,
                    "contentSid" => "HX9e21e0fae3a59fc26a28972193c6acab",
                ]
            );

            \Log::info($twi->sid);
            StoreWebhookResponse($twi->body ?? '', $client->phone, $twi->toArray());
        }

        return 0;
    }
}
