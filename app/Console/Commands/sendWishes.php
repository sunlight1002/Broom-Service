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
            "en" => "Hello everyone,
 
Today is Israel’s Memorial Day for Fallen Soldiers.
There will be a two-minute siren today at 8:00 PM and again tomorrow morning at 11:00 AM to honor and remember the fallen.
 
Please note:
This is not an emergency alert, and there is no need to go to shelters.
In Israel, it is customary to stand still during the siren as a sign of respect and remembrance.
 
Thank you for your attention.",
            "ru" => "Здравствуйте всем,
 
Сегодня в Израиле отмечают День памяти павших солдат.
Сегодня в 20:00 и завтра утром в 11:00 будет звучать двухминутная сирена в память о павших.
 
Обратите внимание:
Это не тревога и нет необходимости спускаться в укрытия.
В Израиле принято стоять во время сирены в знак уважения и памяти.
 
Спасибо за ваше внимание.",
//             "heb" => 'שלום,
// מחר, ביום חמישי בשעה 10:00 בבוקר, תישמע צפירה לזכר יום השואה.
// בישראל נהוג לעמוד דום במהלך הצפירה לזכר קורבנות השואה.
// שימו לב – מדובר בצפירה מתוכננת מראש, לא מדובר במצב חירום, ואין צורך להיכנס לממ"ד.
 
// תודה על תשומת הלב והכבוד,
// צוות Broom Service 🌹'
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
