<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WhatsAppBotActiveClientState;
use Twilio\Rest\Client as TwilioClient;

class CloseActiveClientBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:close-active-client-bot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close active client bot after 10 mins';

    protected $twilioAccountSid;
    protected $twilioAuthToken;
    protected $twilioWhatsappNumber;
    protected $twilio;

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

        // Initialize the Twilio client
        $this->twilio = new TwilioClient($this->twilioAccountSid, $this->twilioAuthToken);
    }


    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $message = [
            'heb' => "לא התקבלה תגובה ממך. השיחה תיסגר אוטומטית.\nאם תצטרך משהו, אנא אל תהסס לפנות אלינו שוב.",
            'en' => "We didn’t receive a response from you. This chat will close automatically.\nIf you need anything, please don’t hesitate to reach out again.",
        ];
        $activeClients = WhatsAppBotActiveClientState::where('menu_option', '!=', 'failed_attempts')->where('updated_at', '<', now()->subMinutes(10))->get();
        foreach ($activeClients as $client)
        {
            try {
                if($client->from) {
                    $lng = ($client->lng == 'heb' ? 'heb' : 'en');
                    $nextMessage = $message[$lng];

                    $sid = $lng == "heb" ? "HX2644430417b4d902fc511736b03ca652" : "HX60098186d1018c92154ac59afb8f92b4";
                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$client->from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "contentSid" => $sid,
                        "statusCallback" => config("services.twilio.webhook") . "twilio/status-callback",
                        ]
                    );

                    StoreWebhookResponse($twi->body ?? "", $client->from, $twi->toArray());

                    // sendClientWhatsappMessage($client->from, ['name' => '', 'message' => $nextMessage]);
                    $client->delete();
                }
            } catch (\Throwable $th) {
                \Log::info($th);
            }
        }
        return 0;
    }
}
