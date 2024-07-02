<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\WorkerInvitation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWorkerInvitationRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worker:send_invitation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send worker invitation';

    protected $whapiApiEndpoint, $whapiApiToken;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->whapiApiEndpoint = config('services.whapi.url');
        $this->whapiApiToken = config('services.whapi.token');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $headers[] = 'Authorization: Bearer ' . config('services.whatsapp_api.auth_token');
        $headers[] = 'Content-Type: application/json';
        WorkerInvitation::where('is_invitation_sent', 0)->chunk(100, function ($workers) {
            foreach ($workers as $key => $worker) {
                $receiverNumber = $worker->phone;
                $text = NULL;

                if ($worker->lng === 'heb') {
                    $text .= $worker->first_name . " שלום,

ברצוננו לעדכן אתכם שאנו עוברים בקרוב למערכת חדשה שתעזור ותקל על העבודה השוטפת של כולנו מא' ועד ת'!

כחלק מהמעבר, אנו מבקשים מכם ללחוץ על הלינק הבא ולמלא את הפרטים שלכם על מנת להבטיח מעבר חלק ומוצלח למערכת החדשה.

" . url("worker-invitation-form/" . base64_encode($worker->id)) . "

אם יש שאלה או בעיה, תוכלו לפנות לאלכס ולעדכן אותו והוא ישמח לעזור.

יש להשלים את התהליך עד לסוף השבוע בבקשה.

תודה על שיתוף הפעולה,  
צוות ברום סרוויס";
                } elseif ($worker->lng === 'res') {
                    $text = "Здравствуйте " . $worker->first_name . ",

Мы хотим сообщить вам, что скоро мы переходим на новую систему, которая поможет упростить и облегчить наши повседневные операции от А до Я!

В рамках перехода мы просим вас нажать на следующую ссылку и заполнить ваши данные, чтобы обеспечить плавный и успешный переход на новую систему.

" . url("worker-invitation-form/" . base64_encode($worker->id)) . "

Если у вас есть вопросы или проблемы, пожалуйста, свяжитесь с Алексом и сообщите ему, он будет рад помочь.

Пожалуйста, завершите процесс до конца недели.

Спасибо за ваше сотрудничество,  
Команда Broom Service";
                } else {
                    $text = "Hello " . $worker->first_name . ",

We would like to inform you that we are soon transitioning to a new system that will help streamline and ease our day-to-day operations from A to Z!

As part of the transition, we ask you to click on the following link and complete your details to ensure a smooth and successful transition to the new system.

" . url("worker-invitation-form/" . base64_encode($worker->id)) . "

If you have any questions or issues, please contact Alex and let him know, and he will be happy to assist.

Please complete the process by the end of the week.

Thank you for your cooperation,  
Broom Service Team";
                }
                // $response = Http::withToken($this->whapiApiToken)
                //         ->post($this->whapiApiEndpoint . 'messages/text', [
                //             'to' => $receiverNumber,
                //             'body' => $text
                //         ]);

                // Log::info($response->json());
                Log::info($text);
            }
        });
    }
}
