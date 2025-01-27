<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WhatsAppBotActiveWorkerState;

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
                    $lng = $client->lng ?? 'en';
                    $nextMessage = $message[$lng];
                    sendClientWhatsappMessage($worker->worker->phone, ['name' => '', 'message' => $nextMessage]);
                    $worker->delete();
                }
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
        return 0;
    }
}
