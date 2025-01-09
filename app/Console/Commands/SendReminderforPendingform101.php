<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Enums\WorkerFormTypeEnum;
use Illuminate\Support\Facades\Log;

class SendReminderforPendingform101 extends Command
{

    protected $whapiApiToken;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:reminderform101';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Reminder for Pending Form 101';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->whapiApiToken = config('services.whapi.token');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $workers = User::where('status', 1)
            ->where('company_type', 'my-company')
            ->whereHas('forms', function ($query) {
                $query->where('type', WorkerFormTypeEnum::FORM101)
                    ->whereYear('created_at', now()->year)
                    ->whereNull('submitted_at');
            })
            ->get();

        foreach ($workers as $worker) {
            // Get the pending Form 101 for the current year
            $pendingForm = $worker->forms()
                ->where('type', WorkerFormTypeEnum::FORM101)
                ->whereYear('created_at', now()->year)
                ->whereNull('submitted_at')
                ->first();

            if ($pendingForm) {
                $message = [
                    'en' => "Hello, " . $worker->firstname . " " . $worker->lastname . ",

The last message you received was incorrect. You do not need to fill out the other forms we sent you, only the 101 form again.

The 101 form is required by the tax authorities and needs to be completed anew each year. As it is now 2025, we kindly ask you to fill out this form.

If you have any questions or need assistance, feel free to ask.

Here is the form:
" . url("worker-forms/" . base64_encode($worker->id) . "/" . base64_encode($pendingForm->id)) . "

Thank you,",

                    'ru' => "Здравствуйте, " . $worker->firstname . " " . $worker->lastname . ",

Предыдущее сообщение, которое вы получили, было некорректным. Вам не нужно заполнять остальные отправленные формы, только форму 101.

Форма 101 требуется налоговыми органами и должна заполняться заново каждый год. Поскольку сейчас 2025 год, мы просим вас заполнить эту форму.

Если у вас есть вопросы или вам нужна помощь, не стесняйтесь обращаться.

Вот ссылка на форму:
" . url("worker-forms/" . base64_encode($worker->id) . "/" . base64_encode($pendingForm->id)) . "

Спасибо",

                ];

                $this->sendReminder($worker, $message[$worker->lng], $worker->phone);

            }
        }

    }

    private function sendReminder($worker, $message, $receiverNumber)
    {
        $response = Http::withToken($this->whapiApiToken)
                    ->post($this->whapiApiEndpoint . 'messages/text', [
                        'to' => $receiverNumber,
                        'body' => $message
                    ]);
    }
}
