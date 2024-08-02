<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WorkerInvitation;
use App\Models\User;
use App\Models\Form;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
        $workerInvitations = WorkerInvitation::all();
        
        foreach ($workerInvitations as $invitation) {
            $user = User::where(function($q) use($invitation) {
                $q->where('email', $invitation->email)
                ->orWhere('phone', $invitation->phone);
            })
            ->where('is_existing_worker', 1)
            ->first();
            
            $receiverNumber = $invitation->phone;
            $mediaIds = [];
        
            $lng = $invitation->lng;

            if ($lng === 'heb') {
                $text = "שלום," . " " .$invitation->first_name . "\n";
                $text .= "זוהי תזכורת למלא את פרטיכם עד מחר כדי למנוע בעיות או עיכובים בתשלום.\n\n";
                $text .= "אם יש לכם שאלות או בעיות, אנא התקשרו לאלקס. הוא ישמח לעזור.\n\n";
                $text .= "תודה על שיתוף הפעולה.\n\n";
                $text .= "בברכה,\nBroom Service";
            } elseif ($lng === 'rus') {
                $text = "Уважаемый(ая)". " " .$invitation->first_name . "\n";
                $text .= "Это дружеское напоминание о необходимости заполнить и отправить требуемые формы до завтрашнего дня, чтобы избежать проблем или задержек с выплатами.\n\n";
                $text .= "Если у вас возникнут вопросы или проблемы, пожалуйста, звоните Алексу. Он будет рад вам помочь.\n\n";
                $text .= "Спасибо за ваше сотрудничество.\n\n";
                $text .= "С уважением,\nBroom Service";
            } else {
                $text = "Dear" . " " . $invitation->first_name . "\n";
                $text .= "This is a reminder to complete and submit the required forms by tomorrow to avoid any problems or delays with your payment.\n\n";
                $text .= "If you encounter any issues or have questions, please feel free to call Alex. He will be happy to assist you.\n\n";
                $text .= "Thank you for your cooperation.\n\n";
                $text .= "Best regards,\nBroom Service";
            }
                        
            if ($user) {
                if($user->country !== "Israel"){
                    $mediaIds[] = "mp4-84d4883e-1110-47a3-b11e-f8d42b6526f7";
                }else{
                    $mediaIds[] = "mp4-c1b57c27-b656-44d5-95eb-608ed9d73cc7";
                }

                $form101 = Form::where('user_id', $user->id)->where('type', 'form101')->whereNotNull('submitted_at')->exists() ? true : false;
                $contract = Form::where('user_id', $user->id)->where('type', 'contract')->whereNotNull('submitted_at')->exists() ? true : false;
                $safety_and_gear = Form::where('user_id', $user->id)->where('type', 'saftey-and-gear')->whereNotNull('submitted_at')->exists() ? true : false;
    
                $profile_completed = ($form101 && $contract && $safety_and_gear) || $user->company_type == "manpower";
    
                // Log::info($profile_completed);

                if (!$profile_completed) {
                    // Send the text message
                    Http::withToken($this->whapiApiToken)
                        ->post($this->whapiApiEndpoint . 'messages/text', [
                            'to' => $receiverNumber . '@s.whatsapp.net',
                            'body' => $text
                        ]);

                    // Send video messages
                    foreach ($mediaIds as $mediaId) {
                        Http::withHeaders([
                            'Authorization' => 'Bearer ' . $this->whapiApiToken,
                            'Content-Type' => 'application/json',
                        ])->post($this->whapiApiEndpoint . 'messages/video', [
                            'to' => $receiverNumber . '@s.whatsapp.net',
                            'media' => $mediaId,
                            'mime_type' => 'video/mp4',
                        ]);
                    }

                    // Send URL to the worker's form
                    $encodedUserId = base64_encode($user->id);
                    $url = url("worker-forms/{$encodedUserId}");
                    
                    Http::withToken($this->whapiApiToken)
                        ->post($this->whapiApiEndpoint . 'messages/text', [
                            'to' => $receiverNumber . '@s.whatsapp.net',
                            'body' => $url
                        ]);
                        sleep(1); 
                } 
                
            } else {

                if($invitation->country !== "Israel"){
                    $mediaIds[] = "mp4-84d4883e-1110-47a3-b11e-f8d42b6526f7";
                }else{
                    $mediaIds[] = "mp4-c1b57c27-b656-44d5-95eb-608ed9d73cc7";
                }

                // Send the text message
                Http::withToken($this->whapiApiToken)
                    ->post($this->whapiApiEndpoint . 'messages/text', [
                        'to' => $receiverNumber . '@s.whatsapp.net',
                        'body' => $text
                    ]);

                // Send video messages
                foreach ($mediaIds as $mediaId) {
                    Http::withHeaders([
                        'Authorization' => 'Bearer ' . $this->whapiApiToken,
                        'Content-Type' => 'application/json',
                    ])->post($this->whapiApiEndpoint . 'messages/video', [
                        'to' => $receiverNumber . '@s.whatsapp.net',
                        'media' => $mediaId,
                        'mime_type' => 'video/mp4',
                    ]);
                }

                // Send URL to the worker's form
                $encodedUserId = base64_encode($invitation->id);
                $url = url("worker-invitation-form/{$encodedUserId}");
                
                // Send the text message with the clickable URL
                $response = Http::withToken($this->whapiApiToken)
                    ->post($this->whapiApiEndpoint . 'messages/text', [
                        'to' => $receiverNumber . '@s.whatsapp.net',
                        'body' => $url
                    ]);
                
                sleep(1);
            }
        }

        return 0;
    }
}
