<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\MimeTypes;

class CustomHolidays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:custom-holidays';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send custom holidays to clients and users';

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

        $clientMsg = [
            "heb" => "שלום :client_name,
שימו לב – לקראת חג השבועות:
ביום ראשון, 1.6, ערב חג – נעבוד חצי יום בלבד.
ביום שני, 2.6, חג השבועות – לא תהיה פעילות.
 
לקוחות שמקבלים שירות בימים אלה ורוצים לבדוק אפשרות למועד חלופי (בין שלישי לשישי) – מוזמנים לעדכן אותנו ונשמח לנסות לעזור בהתאם לזמינות.
 
בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il",
            "en" => "Dear :client_name,
Please note the upcoming holiday schedule for Shavuot:
    •   On Sunday, June 1, we will operate half-day only.
    •   On Monday, June 2, there will be no service due to the holiday.
 
If your service is scheduled for one of these days and you’d like to reschedule to a different day (Tuesday to Friday), please let us know – we’ll be happy to assist based on availability.
 
Best Regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il",
        ];

        $userMsg = [
            "en" => "Hello :worker_name,
Please take note of the Shavuot holiday schedule:
	•	Sunday, June 1 (holiday eve) – half-day work.
	•	Monday, June 2 (Shavuot) – no work.
If you don’t usually work on Fridays but would like to work this Friday – please let us know.
 
Happy holiday,
Broom Service Team",
            "ru" => "Здравствуйте :worker_name,
Обратите внимание на график работы на праздник Шавуот:
	•	В воскресенье, 1 июня (канун праздника) – работаем полдня.
	•	В понедельник, 2 июня – выходной, не работаем.
Если вы обычно не работаете по пятницам, но хотите поработать в ближайшую пятницу – сообщите нам заранее.
 
С праздником!
Команда Broom Service",
            "heb" => 'שלום :worker_name,
שימו לב – לקראת חג השבועות:
	•	ביום ראשון 1.6 (ערב חג) נעבוד חצי יום בלבד.
	•	ביום שני 2.6 (חג שבועות) אין עבודה.
עובדים שלא עובדים בדרך כלל בימי שישי אך מעוניינים לעבוד ביום שישי הקרוב – מוזמנים לעדכן אותנו.
 
חג שמח,
צוות ברום סרוויס'
        ];

        $clients = Client::where('status', 2)
            ->whereHas('lead_status', function ($query) {
                $query->where('lead_status', 'active client');
            })->get();

        $users = User::where('status', 1)->get();

        foreach ($clients as $client) {
            $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));
            $personalizedMessage = str_replace(':client_name', $clientName, $clientMsg[$client->lng ?? 'en']);
            $this->sendImageWithMessage($personalizedMessage, $client->phone);

            $delay = rand(15, 30);
            $this->info("Sent to client: {$client->phone}, sleeping for {$delay} seconds...");
            sleep($delay);
        }

        foreach ($users as $user) {
            $userName = trim(trim($user->firstname ?? '') . ' ' . trim($user->lastname ?? ''));
            $personalizedMessage = str_replace(':worker_name', $userName, $userMsg[$user->lng ?? 'en']);
            $this->sendImageWithMessage($personalizedMessage, $user->phone);
            $delay = rand(15, 30);
            $this->info("Sent to user: {$user->phone}, sleeping for {$delay} seconds...");
            sleep($delay);
        }

        $this->info('message sent successfully!');
        return 0;
    }


    public function sendImageWithMessage($msg, $phone)
    {

        try {
            $messageResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.whapi.client_token'),
                'Content-Type' => 'application/json',
            ])->post(config('services.whapi.url') . 'messages/text', [
                'to' => $phone,
                'body' => $msg,
            ]);

            // Check the response status
            if ($messageResponse->successful()) {
                StoreWebhookResponse($msg ?? '', $phone, $messageResponse->json());
                return $messageResponse->json();
            } else {
                \Log::error('Error sending WhatsApp message: ', $messageResponse->json());
                return ['error' => $messageResponse->json()];
            }
        } catch (\Exception $e) {
            \Log::error('Exception during WhatsApp message send: ' . $e->getMessage());
            return ['error' => 'An error occurred while sending the message.'];
        }
    }
}
