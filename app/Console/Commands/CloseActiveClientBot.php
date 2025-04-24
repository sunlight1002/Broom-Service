<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WhatsAppBotActiveClientState;

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

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
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
                    $lng = $client->lng ?? 'en';
                    $nextMessage = $message[$lng];

                    $sid = $lng == "heb" ? "HX2644430417b4d902fc511736b03ca652" : "HX60098186d1018c92154ac59afb8f92b4";
                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$client->phone",
                        [
                            "from" => $this->twilioWhatsappNumber, 
                            "contentSid" => $sid,
                        ]
                    );
                    \Log::info($twi);

                    // sendClientWhatsappMessage($client->from, ['name' => '', 'message' => $nextMessage]);
                    $client->delete();
                }
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
        return 0;
    }
}
