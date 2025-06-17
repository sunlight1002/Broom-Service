<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\User;
use Twilio\Rest\Client as TwilioClient;

class SendMessageforDeactivatedNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:messagefordeactivatednumbers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send message for deactivated numbers';

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
        $sids = [
            "en" => "HXcf49031c9fa9dccf44893fdacfdba256",
            "heb" => "HX4935aa2f8fd6bdb14afd256b5ee9490e",
            "ru" => "HX942e558fa18cfa0c0a39393c910487a8"
        ];

        $clients = Client::where('status', '2')
            ->whereHas('lead_status', function ($query) {
                $query->where('lead_status', 'active client');
            })
            ->get();

        $users = User::where('status', '!=' , 0)->get();

        foreach ($clients as $client) {
            $this->sendWhatsappMessage($client->phone, $sids[$client->lng] ?? $sids['en']);
        }

        foreach ($users as $user) {
            $this->sendWhatsappMessage($user->phone, $sids[$user->lng] ?? $sids['en']);
        }

        return 0;
    }

    public function sendWhatsappMessage($number, $sid)
    {

        $twi = $this->twilio->messages->create(
            "whatsapp:+" . $number,
            [
                "from" => $this->twilioWhatsappNumber,
                "contentSid" => $sid,
            ]
        );

        \Log::info($twi->sid);
        StoreWebhookResponse($twi->body ?? '', $number, $twi->toArray());
    }
}
