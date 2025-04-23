<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\MimeTypes;

class sendWishes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:wishes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send wishes to clients and users';

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

        // $clientMsg = "לקוחות יקרים,\nאנו מאחלים לכם חג פסח שמח, מלא באור, בריאות, רוגע והתחדשות\n.בברכה,\nצוות ברום סרוויס 🌷";
        $userMsg = [
            "en" => "Hello,
Tomorrow, on Thursday at 10:00 AM, a siren will sound in honor of Holocaust Remembrance Day.
In Israel, it is customary to stand in silence during the siren as a sign of respect for the victims of the Holocaust.
Please note – this is a planned siren, not an emergency, and there is no need to enter a shelter.
 
Thank you for your attention and respect,
Broom Service Team 🌹",
            "ru" => "Здравствуйте,
Завтра, в четверг в 10:00 утра, прозвучит сирена в честь Дня памяти Катастрофы и героизма (Йом а-Шоа).
В Израиле во время сирены принято стоять в молчании в память о жертвах Холокоста.
Пожалуйста, обратите внимание — это запланированная сирена, это не тревога, и не нужно заходить в бомбоубежище.
 
Спасибо за ваше внимание и уважение,
Команда Broom Service 🌹",
            "heb" => 'שלום,
מחר, ביום חמישי בשעה 10:00 בבוקר, תישמע צפירה לזכר יום השואה.
בישראל נהוג לעמוד דום במהלך הצפירה לזכר קורבנות השואה.
שימו לב – מדובר בצפירה מתוכננת מראש, לא מדובר במצב חירום, ואין צורך להיכנס לממ"ד.
 
תודה על תשומת הלב והכבוד,
צוות Broom Service 🌹'
        ];

        // $clients = Client::where('status', 2)
        //     ->whereHas('lead_status', function ($query) {
        //         $query->where('lead_status', 'active client');
        //     })->get();

        $users = User::where('status', 1)->get();


        // foreach ($clients as $client) {
        //     $this->sendImageWithMessage($clientMsg, $client->phone);
        // }
        foreach ($users as $user) {
            $this->sendImageWithMessage($userMsg[$user->lng ?? 'en'], $user->phone);
        }
        
        $this->info('Wishes sent successfully!');
        return 0;
    }


    public function sendImageWithMessage($msg, $phone)
    {
        // $mediaPath = storage_path('app/passover.png');
    
        // // Get MIME type correctly using Symfony MimeTypes
        // $mimeType = (new MimeTypes())->guessMimeType($mediaPath);
    
        // if (!file_exists($mediaPath)) {
        //     \Log::error("Media file not found at path: $mediaPath");
        //     return ['error' => 'File not found'];
        // }
    
        // $response = Http::withHeaders([
        //     'Authorization' => 'Bearer ' . config('services.whapi.client_token'),
        //     'Accept' => 'application/json',
        //     'Content-Type' => $mimeType,
        // ])->withBody(file_get_contents($mediaPath), $mimeType)
        //   ->post(config('services.whapi.url') . 'media');
    
    
        // if (!$response->successful()) {
        //     \Log::error('Error uploading WhatsApp media: ', $response->json());
        //     return ['error' => $response->json()];
        // }
    
        // $media = $response->json()['media'][0] ?? null;
        // if (!$media || !isset($media['id'])) {
        //     \Log::error('Media ID not found in response.');
        //     return ['error' => 'Media ID not found'];
        // }
    
        // $mediaId = $media['id'];
    
        try {
            $messageResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.whapi.client_token'),
                'Content-Type' => 'application/json',
             ])->post(config('services.whapi.url') . 'messages/text', [
                'to' => $phone,
                'body' => $msg,
                // 'media' => $mediaId, // Encode the image as base64
                // 'mime_type' => $mimeType,
                // 'caption' => $msg,
            ]);

            // Check the response status
            if ($messageResponse->successful()) {
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
