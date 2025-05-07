<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WhatsAppBotActiveWorkerState;
use Twilio\Rest\Client as TwilioClient;

class CloseActiveWorkerBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:close-active-worker-bot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close active worker bot after 10 mins';

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
            'heb' => "השיחה נסגרה עקב חוסר פעילות. במידה ותצטרך עזרה נוספת, הקלד 'תפריט' כדי להתחיל מחדש",
            'en' => "Session closed due to inactivity. If you need further assistance, type 'menu' to restart.",
            'ru' => "Сеанс завершен из-за бездействия. Если вам нужна дополнительная помощь, введите 'меню', чтобы начать заново.",
            'spa' => "Sesión cerrada por inactividad. Si necesitas más ayuda, escribe 'menú' para reiniciar.",
        ];
        $activeWorkers = WhatsAppBotActiveWorkerState::where('menu_option', '!=', 'failed_attempts')->where('updated_at', '<', now()->subMinutes(15))->get();
        foreach ($activeWorkers as $worker)
        {
            try {
                if($worker->worker) {
                    $lng = $worker->worker->lng ?? 'en';
                    $nextMessage = $message[$lng];
                    
                    if($lng == 'heb') {
                        $sid = "HXb1b8dd0a46830a700b0e161579b9534a";
                    }else if($lng == 'en') {
                        $sid = "HX79bd5eb7e20421315b8f123b69c5fa6d";
                    }else if($lng == 'ru') {
                        $sid = "HX4dc7606ca6d5a9e43a65abeda077e618";
                    }else if($lng == 'spa') {
                        $sid = "HX01b0ebc538a841694df65a37f845dcbe";
                    }

                    $twi = $this->twilio->messages->create(
                        "whatsapp:+" . $worker->worker->phone,
                        [
                            "from" => $this->twilioWhatsappNumber, 
                            "contentSid" => $sid,
                            // "statusCallback" => config("services.twilio.webhook") . "twilio/status-callback",
                        ]
                    );

                    StoreWebhookResponse($twi->body ?? "", $worker->worker->phone, $twi->toArray());

                    $worker->delete();
                }
            } catch (\Throwable $th) {
                \Log::info($th);
                //throw $th;
            }
        }
        return 0;
    }
}
