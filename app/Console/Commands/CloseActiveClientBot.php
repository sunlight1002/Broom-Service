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
            'heb' => "מצטערים, לא הבנתי את בקשתך.\nאנא נסה שוב או הקלד \"תפריט\" כדי לחזור לתפריט הראשי.",
            'en' => "We didn’t receive a response from you. This chat will close automatically.\nIf you need anything, please don’t hesitate to reach out again.",
        ];
        $activeClients = WhatsAppBotActiveClientState::where('menu_option', '!=', 'failed_attempts')->where('updated_at', '<', now()->subMinutes(10))->get();
        foreach ($activeClients as $client)
        {
            try {
                if($client->from) {
                    $lng = $client->lng ?? 'heb';
                    $nextMessage = $message[$lng];
                    sendClientWhatsappMessage($client->from, ['name' => '', 'message' => $nextMessage]);
                    $client->delete();
                }
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
        return 0;
    }
}
