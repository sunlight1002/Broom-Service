<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Form;
use App\Enums\WorkerFormTypeEnum;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

class SendReminderWithPendingForms extends Command
{

    protected $whapiApiToken;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:reminder-with-pending-forms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a reminder to both team with Worker pending forms';

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

        $users = User::where('status', 1)->get();
        $matchingUsers = collect();

        foreach ($users as $user) {
            $country = $user->country;
            $company_type = $user->company_type;

            $form101 = false;
            $contract = false;
            $safety_and_gear = false;
            $insurance = false;
            $declaration_form = false;

            $is_submitted = Form::where('user_id', $user->id)
                                        ->whereNotNull('submitted_at')
                                        ->exists() ? true : false;

            if ($is_submitted && $country != 'Israel' && $company_type === 'my-company') {

                $form101 = Form::where('user_id', $user->id)->where('type', 'form101')->whereNotNull('submitted_at')->exists() ? true : false;
                $contract = Form::where('user_id', $user->id)->where('type', 'contract')->whereNotNull('submitted_at')->exists() ? true : false;
                $safety_and_gear = Form::where('user_id', $user->id)->where('type', 'saftey-and-gear')->whereNotNull('submitted_at')->exists() ? true : false;
                $insurance = Form::where('user_id', $user->id)->where('type', 'insurance')->whereNotNull('submitted_at')->exists() ? true : false;

            } else if ($is_submitted && $country == 'Israel' && $company_type === 'my-company') {

                $form101 = Form::where('user_id', $user->id)->where('type', 'form101')->whereNotNull('submitted_at')->exists() ? true : false;
                $contract = Form::where('user_id', $user->id)->where('type', 'contract')->whereNotNull('submitted_at')->exists() ? true : false;
                $safety_and_gear = Form::where('user_id', $user->id)->where('type', 'saftey-and-gear')->whereNotNull('submitted_at')->exists() ? true : false;

            } else if ($is_submitted && $country != 'Israel' && $company_type === 'manpower') {

                $declaration_form = Form::where('user_id', $user->id)->where('type', 'manpower-saftey')->whereNotNull('submitted_at')->exists() ? true : false;
                $safety_and_gear = Form::where('user_id', $user->id)->where('type', 'saftey-and-gear')->whereNotNull('submitted_at')->exists() ? true : false;
                $insurance = Form::where('user_id', $user->id)->where('type', 'insurance')->whereNotNull('submitted_at')->exists() ? true : false;

            } else if ($is_submitted && $country == 'Israel' && $company_type === 'manpower') {

                $declaration_form = Form::where('user_id', $user->id)->where('type', 'manpower-saftey')->whereNotNull('submitted_at')->exists() ? true : false;
                $safety_and_gear = Form::where('user_id', $user->id)->where('type', 'saftey-and-gear')->whereNotNull('submitted_at')->exists() ? true : false;

            }else if (!$is_submitted && $country != 'Israel' && $company_type === 'my-company') {

                $form101 = false;
                $contract = false;
                $safety_and_gear = false;
                $insurance = false;

            } else if (!$is_submitted && $country == 'Israel' && $company_type === 'my-company') {

                $form101 = false;
                $contract = false;
                $safety_and_gear = false;

            } else if (!$is_submitted && $country != 'Israel' && $company_type === 'manpower') {

                $declaration_form = false;
                $safety_and_gear = false;
                $insurance = false;

            } else if (!$is_submitted && $country == 'Israel' && $company_type === 'manpower') {

                $declaration_form = false;
                $safety_and_gear = false;
            }
            if ($country == 'Israel' && $company_type === 'my-company') {
                $matchingUsers->push([
                    'id' => $user->id,
                    'worker_name' => $user->firstname,
                    'country' => $user->country,
                    'company_type' => $user->company_type,
                    'form101' => $form101 ? 'True' : 'False',
                    'safety_and_gear' => $safety_and_gear ? 'True' : 'False',
                    'contract' => $contract ? 'True' : 'False',
                ]);
            }
            if ($country != 'Israel' && $company_type === 'my-company') {
                $matchingUsers->push([
                    'id' => $user->id,
                    'worker_name' => $user->firstname,
                    'country' => $user->country,
                    'company_type' => $user->company_type,
                    'form101' => $form101 ? 'True' : 'False',
                    'safety_and_gear' => $safety_and_gear ? 'True' : 'False',
                    'contract' => $contract ? 'True' : 'False',
                    'insurance' => $insurance ? 'True' : 'False',
                ]);
            }
            if ($country == 'Israel' && $company_type === 'manpower') {
                $matchingUsers->push([
                    'id' => $user->id,
                    'worker_name' => $user->firstname,
                    'country' => $user->country,
                    'company_type' => $user->company_type,
                    'declaration_form' => $declaration_form ? 'True' : 'False',
                    'safety_and_gear' => $safety_and_gear ? 'True' : 'False',
                ]);
            }
            if ($country != 'Israel' && $company_type === 'manpower') {
                $matchingUsers->push([
                    'id' => $user->id,
                    'worker_name' => $user->firstname.' '.$user->lastname,
                    'country' => $user->country,
                    'company_type' => $user->company_type,
                    'declaration_form' => $declaration_form ? 'True' : 'False',
                    'safety_and_gear' => $safety_and_gear ? 'True' : 'False',
                    'insurance' => $insurance ? 'True' : 'False',
                ]);
            }
        }
    
        // Process and prepare the message
        $message = "Hi team,\n\nThe following workers didn't complete the forms:\n\n";
    
        foreach ($matchingUsers as $user) {
            $incompleteForms = collect($user)->filter(function ($value, $key) {
                return $value === 'False' && !in_array($key, ['id', 'worker_name', 'country', 'company_type']);
            });
    
            if ($incompleteForms->isNotEmpty()) {
                $userId = $user['id'];

                $forms = $incompleteForms->keys()->implode(', ');
                $message .= "{$user['worker_name']} - {$forms}\n";

                $worker = User::find($userId);

                $this->sendReminder($worker);
            }
        }

        $message .= "\n\nBest Regards,\nBroom Service Team 🌹";

        $receiverNumber = config('services.whatsapp_groups.workers_availability');

        $response = Http::withToken($this->whapiApiToken)
                    ->post($this->whapiApiEndpoint . 'messages/text', [
                        'to' => $receiverNumber,
                        'body' => $message
                    ]);

        // Log::info($response->json());
        // Log::info($message);
    }

    private function sendReminder($worker)
    {
        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::SEND_TO_WORKER_PENDING_FORMS,
            "notificationData" => [
                'worker' => $worker->toArray(),
            ]
        ]));
    }
}
