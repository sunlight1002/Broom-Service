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
        'en' => [
            1 => "👋 Just checking in!\n\nPlease let us know if you’re interested in our house cleaning job.\nWe’re hiring in the Tel Aviv area, and we’d love to hear from you.\n\n✅ Please reply with:\n\n1. 'Yes' or 'No' – Do you have house cleaning experience?",
            2 => "👋 Just checking in!\n\nPlease let us know if you’re interested in our house cleaning job.\nWe’re hiring in the Tel Aviv area, and we’d love to hear from you.\n\n✅ Please reply with:\n\n2. 'Yes' or 'No' – Do you have a valid work visa (Israeli ID, B1 visa, or refugee visa)?",
        ],
        'rus' => [
            1 => "👋 Просто проверяю!\n\nПожалуйста, дайте нам знать, если вас интересует наша работа по уборке дома.\nМы нанимаем сотрудников в районе Тель-Авива, и мы будем рады услышать от вас.\n\n✅ Пожалуйста, ответьте:\n\n1. 'Да' или 'Нет' - Есть ли у вас опыт уборки дома",
            2 => "👋 Просто проверяю!\n\nПожалуйста, дайте нам знать, если вас интересует наша работа по уборке дома.\nМы нанимаем сотрудников в районе Тель-Авива, и мы будем рады услышать от вас.\n\n✅ Пожалуйста, ответьте:\n\n2. 'Да' или 'Нет' – Есть ли у вас действующая рабочая виза (израильское удостоверение, виза B1 или статус беженца)?",
        ]
    ];

    protected $second_reminder = [
        'en' => [
            1 => "🌟 Hi again!\n\nWe noticed you haven’t replied yet. If you’re still interested in a house cleaning job, please answer the following:\n\n1. 'Yes' or 'No' – Do you have house cleaning experience?",
            2 => "🌟 Hi again!\n\nWe noticed you haven’t replied yet. If you’re still interested in a house cleaning job, please answer the following:\n\n2. 'Yes' or 'No' – Do you have a valid work visa (Israeli ID, B1 visa, or refugee visa)?",
        ],
        'rus' => [
            1 => "🌟 Привет снова!\n\nМы заметили, что вы еще не ответили. Если вы все еще заинтересованы в работе по уборке дома, пожалуйста, ответьте на следующие вопросы:\n\n1. 'Да' или 'Нет' - Есть ли у вас опыт уборки дома",
            2 => "🌟 Привет снова!\n\nМы заметили, что вы еще не ответили. Если вы все еще заинтересованы в работе по уборке дома, пожалуйста, ответьте на следующие вопросы:\n\n2. 'Да' или 'Нет' – Есть ли у вас действующая рабочая виза (израильское удостоверение, виза B1 или статус беженца)?",
        ]
    ];

    protected $final_reminder = [
        'en' => [
            1 => "👋 Final check!\n\nIf you’re interested in working as a house cleaner in Tel Aviv, we’d love to hear from you. Please answer:\n\n1. 'Yes' or 'No' – Do you have house cleaning experience?\n\nIf you don’t reply, we’ll assume you’re no longer interested. Feel free to contact us anytime in the future. 🌟",
            2 => "👋 Final check!\n\nIf you’re interested in working as a house cleaner in Tel Aviv, we’d love to hear from you. Please answer:\n\n2. 'Yes' or 'No' – Do you have a valid work visa (Israeli ID, B1 visa, or refugee visa)?\n\nIf you don’t reply, we’ll assume you’re no longer interested. Feel free to contact us anytime in the future. 🌟",
        ],
        'rus' => [
            1 => "👋 Последняя проверка!\n\nЕсли вы заинтересованы в работе уборщицей в Тель-Авиве, мы будем рады услышать от вас. Пожалуйста, ответьте:\n\n1. 'Да' или 'Нет' - Есть ли у вас опыт уборки дома?\n\nЕсли вы не ответите, мы будем считать, что вы больше не заинтересованы. Не стесняйтесь обращаться к нам в любое время в будущем. 🌟",
            2 => "👋 Последняя проверка!\n\nЕсли вы заинтересованы в работе уборщицей в Тель-Авиве, мы будем рады услышать от вас. Пожалуйста, ответьте:\n\n2. 'Да' или 'Нет' – Есть ли у вас действующая рабочая виза (израильское удостоверение, виза B1 или статус беженца)?\n\nЕсли вы не ответите, мы будем считать, что вы больше не заинтересованы. Не стесняйтесь обращаться к нам в любое время в будущем. 🌟",
        ]
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
        $reminderMessage = '';


        if ( !$workerLead->experience_in_house_cleaning && $workerState->step < 4) {
            $reminderMessage = $this->{$reminderType}[$language][1]; 
       } else if ( !$workerLead->you_have_valid_work_visa && $workerState->step < 4) {
            $reminderMessage = $this->{$reminderType}[$language][2];
       }else{
            return;
       }

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
