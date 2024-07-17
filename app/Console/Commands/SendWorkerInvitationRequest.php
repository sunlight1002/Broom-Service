<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\WorkerInvitation;
use App\Models\User;
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

        $workers = WorkerInvitation::whereIn('id', [3, 18])->get();
        foreach ($workers as $key => $worker) {
            $user = User::where('email', $worker->email)->orWhere('phone', 'like', "%" . $worker->phone . "%")->first();
            if ($user && $worker->company == 1) {
                $receiverNumber = $worker->phone;
                $text = NULL;

                if ($worker->lng === 'heb') {
                    // $text .= $worker->first_name . " שלום,\nברצוננו לעדכן אתכם שאנו עוברים בקרוב למערכת חדשה שתעזור ותקל על העבודה השוטפת של כולנו מא' ועד ת'!\n\nכחלק מהמעבר, אנו מבקשים מכם ללחוץ על הלינק הבא ולמלא את הפרטים שלכם על מנת להבטיח מעבר חלק ומוצלח למערכת החדשה.\n\n" . url("worker-invitation-form/" . base64_encode($worker->id)) . "\n\nאם יש שאלה או בעיה, תוכלו לפנות לאלכס ולעדכן אותו והוא ישמח לעזור.\n\nיש להשלים את התהליך עד לסוף השבוע בבקשה.\n\nתודה על שיתוף הפעולה,\nצוות ברום סרוויס";


                    $text .= $worker->first_name . " שלום,\nזוהי תזכורת למלא את פרטיכם.\n\nהנה הקישור שוב: " . url("worker-invitation-form/" . base64_encode($worker->id));
                } elseif ($worker->lng === 'rus') {
                    // $text = "Здравствуйте " . $worker->first_name . ",\n\nМы хотим сообщить вам, что скоро мы переходим на новую систему, которая поможет упростить и облегчить наши повседневные операции от А до Я!\n\nВ рамках перехода мы просим вас нажать на следующую ссылку и заполнить ваши данные, чтобы обеспечить плавный и успешный переход на новую систему.\n\n" . url("worker-invitation-form/" . base64_encode($worker->id)) . "\n\nЕсли у вас есть вопросы или проблемы, пожалуйста, свяжитесь с Алексом и сообщите ему, он будет рад помочь.\n\nПожалуйста, завершите процесс до конца недели.\nСпасибо за ваше сотрудничество,\nКоманда Broom Service";
                    $text .= "Привет " . $worker->first_name . ",\nЭто напоминание заполнить ваши данные.\n\nВот снова ссылка: " . url("worker-invitation-form/" . base64_encode($worker->id));
                } else {
                    // $text = "Hello " . $worker->first_name . ",\n\nWe would like to inform you that we are soon transitioning to a new system that will help streamline and ease our day-to-day operations from A to Z!\n\nAs part of the transition, we ask you to click on the following link and complete your details to ensure a smooth and successful transition to the new system.\n\n" . url("worker-invitation-form/" . base64_encode($worker->id)) . "\n\nIf you have any questions or issues, please contact Alex and let him know, and he will be happy to assist.\n\nPlease complete the process by the end of the week.\n\nThank you for your cooperation, \n Broom Service Team";

                    $text .= "Hello " . $worker->first_name . ",\nThis is a reminder to fill in your details.\n\nHere is the link again: " . url("worker-invitation-form/" . base64_encode($worker->id));
                }
                $response = Http::withToken($this->whapiApiToken)
                    ->post($this->whapiApiEndpoint . 'messages/text', [
                        'to' => $receiverNumber,
                        'body' => $text
                    ]);
                sleep(1);
                Log::info($response->json());
            }
        }

        // WorkerInvitation::where('is_invitation_sent', 0)->chunk(100, function ($workers) {
        //     foreach ($workers as $key => $worker) {
        //         $user = User::where('email', $worker->email)->orWhere('phone', 'like', "%" . $worker->phone . "%")->first();

        //         if ($user && $user->id != 81 && $worker->company == 1) {
        //             $receiverNumber = $worker->phone;
        //             $text = NULL;

        //             if ($user->lng === 'heb') {
        //                 // $text .= $worker->first_name . " שלום,\nברצוננו לעדכן אתכם שאנו עוברים בקרוב למערכת חדשה שתעזור ותקל על העבודה השוטפת של כולנו מא' ועד ת'!\n\nכחלק מהמעבר, אנו מבקשים מכם ללחוץ על הלינק הבא ולמלא את הפרטים שלכם על מנת להבטיח מעבר חלק ומוצלח למערכת החדשה.\n\n" . url("worker-invitation-form/" . base64_encode($worker->id)) . "\n\nאם יש שאלה או בעיה, תוכלו לפנות לאלכס ולעדכן אותו והוא ישמח לעזור.\n\nיש להשלים את התהליך עד לסוף השבוע בבקשה.\n\nתודה על שיתוף הפעולה,\nצוות ברום סרוויס";


        //                 $text .= $worker->first_name . " שלום,\nזוהי תזכורת למלא את פרטיכם.\n\nהנה הקישור שוב: " . url("worker-forms/" . base64_encode($user->id));
        //             } elseif ($user->lng === 'ru') {
        //                 // $text = "Здравствуйте " . $worker->first_name . ",\n\nМы хотим сообщить вам, что скоро мы переходим на новую систему, которая поможет упростить и облегчить наши повседневные операции от А до Я!\n\nВ рамках перехода мы просим вас нажать на следующую ссылку и заполнить ваши данные, чтобы обеспечить плавный и успешный переход на новую систему.\n\n" . url("worker-invitation-form/" . base64_encode($worker->id)) . "\n\nЕсли у вас есть вопросы или проблемы, пожалуйста, свяжитесь с Алексом и сообщите ему, он будет рад помочь.\n\nПожалуйста, завершите процесс до конца недели.\nСпасибо за ваше сотрудничество,\nКоманда Broom Service";
        //                 $text .= "Привет " . $worker->first_name . ",\nЭто напоминание заполнить ваши данные.\n\nВот снова ссылка: " . url("worker-forms/" . base64_encode($user->id));
        //             } else {
        //                 // $text = "Hello " . $worker->first_name . ",\n\nWe would like to inform you that we are soon transitioning to a new system that will help streamline and ease our day-to-day operations from A to Z!\n\nAs part of the transition, we ask you to click on the following link and complete your details to ensure a smooth and successful transition to the new system.\n\n" . url("worker-invitation-form/" . base64_encode($worker->id)) . "\n\nIf you have any questions or issues, please contact Alex and let him know, and he will be happy to assist.\n\nPlease complete the process by the end of the week.\n\nThank you for your cooperation, \n Broom Service Team";

        //                 $text .= "Hello " . $worker->first_name . ",\nThis is a reminder to fill in your details.\n\nHere is the link again: " . url("worker-forms/" . base64_encode($user->id));
        //             }
        //             $response = Http::withToken($this->whapiApiToken)
        //                 ->post($this->whapiApiEndpoint . 'messages/text', [
        //                     'to' => $receiverNumber,
        //                     'body' => $text
        //                 ]);
        //             sleep(1);
        //             Log::info($response->json());
        //         }

        //         // if (!$user) {
        //         //     $receiverNumber = $worker->phone;
        //         //     $text = NULL;

        //         //     if ($worker->lng === 'heb') {
        //         //         // $text .= $worker->first_name . " שלום,\nברצוננו לעדכן אתכם שאנו עוברים בקרוב למערכת חדשה שתעזור ותקל על העבודה השוטפת של כולנו מא' ועד ת'!\n\nכחלק מהמעבר, אנו מבקשים מכם ללחוץ על הלינק הבא ולמלא את הפרטים שלכם על מנת להבטיח מעבר חלק ומוצלח למערכת החדשה.\n\n" . url("worker-invitation-form/" . base64_encode($worker->id)) . "\n\nאם יש שאלה או בעיה, תוכלו לפנות לאלכס ולעדכן אותו והוא ישמח לעזור.\n\nיש להשלים את התהליך עד לסוף השבוע בבקשה.\n\nתודה על שיתוף הפעולה,\nצוות ברום סרוויס";


        //         //         $text .= $worker->first_name . " שלום,\nזוהי תזכורת למלא את פרטיכם.\n\nהנה הקישור שוב: " . url("worker-invitation-form/" . base64_encode($worker->id));
        //         //     } elseif ($worker->lng === 'rus') {
        //         //         // $text = "Здравствуйте " . $worker->first_name . ",\n\nМы хотим сообщить вам, что скоро мы переходим на новую систему, которая поможет упростить и облегчить наши повседневные операции от А до Я!\n\nВ рамках перехода мы просим вас нажать на следующую ссылку и заполнить ваши данные, чтобы обеспечить плавный и успешный переход на новую систему.\n\n" . url("worker-invitation-form/" . base64_encode($worker->id)) . "\n\nЕсли у вас есть вопросы или проблемы, пожалуйста, свяжитесь с Алексом и сообщите ему, он будет рад помочь.\n\nПожалуйста, завершите процесс до конца недели.\nСпасибо за ваше сотрудничество,\nКоманда Broom Service";
        //         //         $text .= "Привет " . $worker->first_name . ",\nЭто напоминание заполнить ваши данные.\n\nВот снова ссылка: " . url("worker-invitation-form/" . base64_encode($worker->id));
        //         //     } else {
        //         //         // $text = "Hello " . $worker->first_name . ",\n\nWe would like to inform you that we are soon transitioning to a new system that will help streamline and ease our day-to-day operations from A to Z!\n\nAs part of the transition, we ask you to click on the following link and complete your details to ensure a smooth and successful transition to the new system.\n\n" . url("worker-invitation-form/" . base64_encode($worker->id)) . "\n\nIf you have any questions or issues, please contact Alex and let him know, and he will be happy to assist.\n\nPlease complete the process by the end of the week.\n\nThank you for your cooperation, \n Broom Service Team";

        //         //         $text .= "Hello " . $worker->first_name . ",\nThis is a reminder to fill in your details.\n\nHere is the link again: " . url("worker-invitation-form/" . base64_encode($worker->id));
        //         //     }
        //         //     $response = Http::withToken($this->whapiApiToken)
        //         //         ->post($this->whapiApiEndpoint . 'messages/text', [
        //         //             'to' => $receiverNumber,
        //         //             'body' => $text
        //         //         ]);
        //         //     sleep(1);
        //         //     Log::info($response->json());
        //         // }

        //         // Log::info($text);
        //     }
        // });
    }
}
