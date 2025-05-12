<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\WhatsappTemplate;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Models\WorkerMetas;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SendToActiveWorkers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:to-active-workers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notification to active workers';

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
            'en' => 'Dear Workers,

Starting today, you will receive an automated message every Monday as part of our improved work process.

The bot will ask:
"Do you need a day or half-day off next week?
We are finalizing next week’s schedule today, so please let us know as soon as possible if you have any specific requests."

### How to respond?
- *Reply 1* if you have changes.
  In this case, the bot will ask:
  "What is the change you are requesting for next week?"
  Your response will be forwarded to the management team.
- *Reply 2* if your schedule remains the same and you have no changes.

Thank you for your cooperation,
The Broom Service Team 🌹',

            'heb' => 'עובדים יקר,

החל מהיום, תקבלו הודעה אוטומטית בכל יום שני, כחלק משיפור תהליך העבודה שלנו.

הבוט ישאל:
"האם אתם זקוקים ליום חופש או חצי יום חופש בשבוע הבא?
אנו סוגרים את סידור העבודה לשבוע הבא היום, ולכן נבקש שתעדכנו אותנו בהקדם האפשרי אם יש לכם בקשות מיוחדות."

### איך לענות?
- *ענו 1* אם יש שינויים.
  במקרה כזה, הבוט ישאל:
  "מהו השינוי שאתם מבקשים לשבוע הבא?"
  תשובתכם תועבר לצוות הניהול.
- *ענו 2* אם הסידור נשאר כפי שהיה ואין שינויים.

תודה על שיתוף הפעולה,
צוות ברום סרוויס 🌹',

            'ru' => 'Уважаемые работники,

Начиная с сегодняшнего дня, вы будете получать автоматическое сообщение каждый понедельник в рамках нашего улучшенного рабочего процесса.

Бот спросит:
"Вам нужен выходной или половина дня на следующей неделе?
Мы закрываем график на следующую неделю сегодня, поэтому просим вас сообщить нам как можно скорее, если у вас есть какие-либо особые пожелания."

### Как ответить?
- *Ответьте 1*, если у вас есть изменения.
  В этом случае бот спросит:
  "Какие изменения вы хотите внести на следующую неделю?"
  Ваш ответ будет передан команде управления.
- *Ответьте 2*, если ваш график остается без изменений.

Спасибо за сотрудничество,
Команда Broom Service 🌹'

        ];

        $specialMsg = [
            "heb" => "שלום :worker_name,\n\nביום רביעי 30.04 (ערב יום העצמאות) – עובדים עד השעה 13:00 בלבד.\nביום חמישי 01.05 (יום העצמאות) – אין עבודה.\n\nאם יש לך אילוצים או בקשות מיוחדות לשבוע הזה – נא לעדכן אותנו בהקדם.\n\nתודה,\nצוות ברום סרוויס",
            "en" => "Hello :worker_name,\n\nOn Wednesday, April 30th (Independence Day Eve) – we will work until 1:00 PM only.\nOn Thursday, May 1st (Independence Day) – there is no work.\n\nIf you have any special requests or changes for this week, please let us know.\n\nThank you,\nBroom Service Team",
            "ru" => "Здравствуйте :worker_name,\n\nВ среду, 30 апреля (канун Дня независимости) – мы работаем только до 13:00.\nВ четверг, 1 мая (День независимости) – работы не будет.\n\nЕсли у вас есть пожелания или изменения на эту неделю – пожалуйста, сообщите нам.\n\nС уважением,\nКоманда Broom Service"
        ];

        // $workers = User::where('status', '1')->where('phone', '918469138538')->get();
         $workers = User::where('status', '1')->where('stop_last_message', 0)->get();
        //  dd($workers);
        foreach ($workers as $worker) {
            \Log::info('Sending message to ' . $worker->phone . ' (' . $worker->firstname . ')');

            $workerData = [
                'type' => WhatsappMessageTemplateEnum::NOTIFY_MONDAY_WORKER_FOR_SCHEDULE,
                'notificationData' => [
                    'worker' => $worker,
                ],
            ];
            event(new WhatsappNotificationEvent($workerData));
            WorkerMetas::create([
                'worker_id' => $worker->id,
                'job_id' => null,
                'key' => 'monday_msg_sent',
                'value' => now()->toISOString(),
            ]);

            // $modifyMessage = str_replace(':worker_name', trim(($worker->firstname ?? '') . ' ' . ($worker->lastname ?? '')), $specialMsg[$worker->lng ?? 'en']);

            // $result = sendClientWhatsappMessage($worker->phone, array('name' => '', 'message' => $modifyMessage));

            // if (!$result) {
            //     \Log::error('Failed to send message to ' . $worker->phone);
            // }

            Cache::put('worker_monday_msg_status_' . $worker->id, 'main_monday_msg', now()->addDay(1));
        }
    }
}
