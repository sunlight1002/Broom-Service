<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WorkerLeads;
use App\Models\WorkerWebhookResponse;
use App\Models\WhatsAppBotWorkerState;
use Carbon\Carbon;

class SendWorkerLeadReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:worker-lead-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders when worker lead is created and time has passed';

    protected $first_reminder = [
        'en' => "👋 Just checking in!\n\nPlease let us know if you’re interested in our house cleaning job.\nWe’re hiring in the Tel Aviv area, and we’d love to hear from you.\n\n✅ Please reply with:\n1.Do you have house cleaning experience\n**Yes / No**",
        'heb' => "👋 רק מבצע צ'ק אין!\n\nאנא הודע לנו אם אתה מעוניין בעבודת ניקיון הבית שלנו.\nאנחנו מגייסים עובדים באזור תל אביב, ונשמח לשמוע ממך. \n\n✅ אנא השב עם:\n1.האם יש לך ניסיון בניקיון הבית\n**כן / לא**",
        'spa' => "👋 ¡Solo para informarme!\n\nPor favor, háganos saber si está interesado en nuestro trabajo de limpieza de casas.\nEstamos contratando en el área de Tel Aviv y nos encantaría saber de usted.\n\n✅ Por favor, responda con:\n1.¿Tiene experiencia en limpieza de casas?\n**Sí / No**",
        'rus' => "👋 Просто проверяю!\n\nПожалуйста, дайте нам знать, если вас интересует наша работа по уборке дома.\nМы нанимаем сотрудников в районе Тель-Авива, и мы будем рады услышать от вас.\n\n✅ Пожалуйста, ответьте:\n1.Есть ли у вас опыт уборки дома\n**Да / Нет**",
    ];

    protected $second_reminder = [
        'en' => "🌟 Hi again!\n\nWe noticed you haven’t replied yet. If you’re still interested in a house cleaning job, please answer the following:\n\n1.Do you have house cleaning experience\n**Yes / No**",
        'heb' => "🌟 שלום שוב!\n\nשמנו לב שעדיין לא ענית. אם אתה עדיין מעוניין בעבודת ניקיון בתים, אנא ענה על הפרטים הבאים:\n\n1. האם יש לך ניסיון בניקיון בתים \n**כן / לא**",
        'spa' => "🌟 ¡Hola de nuevo!\n\nNotamos que aún no has respondido. Si todavía estás interesado en un trabajo de limpieza de casas, responde lo siguiente:\n\n1. ¿Tienes experiencia en limpieza de casas?\n**Sí / No**",
        'rus' => "🌟 Привет снова!\n\nМы заметили, что вы еще не ответили. Если вы все еще заинтересованы в работе по уборке дома, пожалуйста, ответьте на следующие вопросы:\n\n1. Есть ли у вас опыт уборки дома\n**Да / Нет**",
    ];

    protected $final_reminder = [
        'en' => "👋 Final check!\n\nIf you’re interested in working as a house cleaner in Tel Aviv, we’d love to hear from you. Please answer:\n\n1.Do you have house cleaning experience\n**Yes / No**\n\nIf you don’t reply, we’ll assume you’re no longer interested. Feel free to contact us anytime in the future. 🌟",
        'heb' => "👋 בדיקה סופית!\n\nאם אתה מעוניין לעבוד כמנקה בתים בתל אביב, נשמח לשמוע ממך. אנא ענו:\n\n1. האם יש לכם ניקיון בתים ניסיון\n**כן / לא**\n\nאם לא תשיב, אנו נניח שאינך מעוניין יותר. אל תהסס לפנות אלינו בכל עת בעתיד.",
        'spa' => "👋 ¡Última verificación!\n\nSi estás interesado en trabajar como limpiador de casas en Tel Aviv, nos encantaría saber de ti. Por favor, responde:\n\n1.¿Tienes experiencia en limpieza de casas?\n**Sí / No**\n\nSi no respondes, asumiremos que ya no estás interesado. No dudes en contactarnos en cualquier momento en el futuro. 🌟",
        'rus' => "👋 Последняя проверка!\n\nЕсли вы заинтересованы в работе уборщицей в Тель-Авиве, мы будем рады услышать от вас. Пожалуйста, ответьте:\n\n1.Есть ли у вас опыт уборки домов\n**Да / Нет**\n\nЕсли вы не ответите, мы будем считать, что вы больше не заинтересованы. Не стесняйтесь обращаться к нам в любое время в будущем. 🌟",
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $workerLeads = WorkerLeads::where('status', 'pending')->get();

        // Process each worker lead
        foreach ($workerLeads as $workerLead) {
            // Retrieve the worker state associated with this worker lead
            $workerState = WhatsAppBotWorkerState::where('worker_lead_id', $workerLead->id)->first();
            
            if ($workerState) {
                $timePassed = $workerLead->created_at->diffInHours(now());

                // Handle reminders based on time passed
                if ($timePassed >= 1 && $timePassed < 24 && !$workerState->first_reminder) {
                    $this->sendReminder($workerLead, $workerState, 'first_reminder');
                } elseif ($timePassed >= 24 && $timePassed < 48 && !$workerState->second_reminder) {
                    $this->sendReminder($workerLead, $workerState, 'second_reminder');
                } elseif ($timePassed >= 48 && !$workerState->final_reminder) {
                    $this->sendReminder($workerLead, $workerState, 'final_reminder');
                }
            }
        }

        return 0;
    }

    /**
     * Send reminder to the worker lead.
     *
     * @param  \App\Models\WorkerLeads  $workerLead
     * @param  \App\Models\WhatsAppBotWorkerState  $workerState
     * @param  string  $reminderType
     * @return void
     */
    protected function sendReminder($workerLead, $workerState, $reminderType)
    {
        $language = $workerState->language;
        $reminderMessage = $this->{$reminderType}[$language];  // Dynamically select the reminder message

        // Send the message (you may use your existing helper function for this)
        sendWorkerWhatsappMessage($workerLead->phone, ['name' => '', 'message' => $reminderMessage]);

        // Save the admin message for the reminder
        WorkerWebhookResponse::create([
            'status' => 1,
            'name' => 'whatsapp',
            'message' => $reminderMessage,
            'number' => $workerLead->phone,
            'read' => 1,
            'flex' => 'A',
        ]);

        // Update the worker state to mark the reminder as sent
        $workerState->update([$reminderType => true]);

        // Optionally log the reminder action
        \Log::info("Reminder sent to {$workerLead->phone} for {$reminderType}.");
    }
}
