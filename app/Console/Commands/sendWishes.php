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

        $clientMsg = "לקוחות יקרים,\nאנו מאחלים לכם חג פסח שמח, מלא באור, בריאות, רוגע והתחדשות\n.בברכה,\nצוות ברום סרוויס 🌷";
        $userMsg = [
            "en" => "Dear Team,\nWishing you and your families a joyful, meaningful, and peaceful Passover.\nWarm regards,\nBroom Service Management",
            "ru" => "Уважаемые коллеги,\nЖелаем вам и вашим семьям радостного, значимого и мирного праздника Песах.\nС уважением,\nРуководство Broom Service"
        ];

        $clients = Client::where('status', 2)
        ->where("id", "194")
            ->whereHas('lead_status', function ($query) {
                $query->where('lead_status', 'active client');
            })->get();

        $users = User::where('status', 1)->get();


        foreach ($clients as $client) {
            $this->sendImageWithMessage($clientMsg, $client->phone);
        }
        // foreach ($users as $user) {
        //     $this->sendImageWithMessage($userMsg[$user->lng == "ru"? "ru" :"en"], $user->phone);
        // }
        
        $this->info('Wishes sent successfully!');
        return 0;
    }


    public function sendImageWithMessage($msg, $phone)
    {
        $mediaPath = storage_path('app/passover.png');
    
        // Get MIME type correctly using Symfony MimeTypes
        $mimeType = (new MimeTypes())->guessMimeType($mediaPath);
    
        if (!file_exists($mediaPath)) {
            \Log::error("Media file not found at path: $mediaPath");
            return ['error' => 'File not found'];
        }
    
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.whapi.client_token'),
            'Accept' => 'application/json',
            'Content-Type' => $mimeType,
        ])->withBody(file_get_contents($mediaPath), $mimeType)
          ->post(config('services.whapi.url') . 'media');
    
    
        if (!$response->successful()) {
            \Log::error('Error uploading WhatsApp media: ', $response->json());
            return ['error' => $response->json()];
        }
    
        $media = $response->json()['media'][0] ?? null;
        if (!$media || !isset($media['id'])) {
            \Log::error('Media ID not found in response.');
            return ['error' => 'Media ID not found'];
        }
    
        $mediaId = $media['id'];
        \Log::info('Media ID: ' . $mediaId);

        sleep(2); // Sleep for 10 seconds to avoid rate limiting
    
        try {
            $messageResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.whapi.client_token'),
                'Content-Type' => 'application/json',
             ])->post(config('services.whapi.url') . 'messages/image', [
                'to' => $phone,
                'media' => $mediaId, // Encode the image as base64
                'mime_type' => $mimeType,
                'caption' => $msg,
                'no_encode' => true, // Specify if encoding should be disabled
            ]);

            // Log the message response for debugging
            \Log::info('WhatsApp send message response: ', $messageResponse->json());

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
