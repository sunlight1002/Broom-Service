<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\MimeTypes;
use Twilio\Rest\Client as TwilioClient;


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
    protected $twilioAccountSid;
    protected $twilioAuthToken;
    protected $twilioWhatsappNumber;
    protected $twilio;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->twilioAccountSid = config('services.twilio.twilio_id');
        $this->twilioAuthToken = config('services.twilio.twilio_token');
        $this->twilioWhatsappNumber = config('services.twilio.twilio_whatsapp_number');

        // Initialize the Twilio client
        $this->twilio = new TwilioClient($this->twilioAccountSid, $this->twilioAuthToken);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $clientMsg = [
            "heb" => "HXee657579aef59e6e2b096227376b06d8",
            "en" => "hxa332d93a65950633b7e883818661b4da",
        ];

        $userMsg = [
            "en" => "HXf8c033547e3214b9d2320ec688727e6f",
            "ru" => "HX9f1fda338b3a89c13e62b7e78f646485",
            "heb" => "HX87fb8a45dd3eee7eaa5e304973356ed9"
        ];

        $clients = Client::where('status', 2)
            ->whereHas('lead_status', function ($query) {
                $query->where('lead_status', 'active client');
            })->get();

        $users = User::where('status', '!=' , 0)->get();

        foreach ($clients as $client) {
            $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));
            $this->sendImageWithMessage($clientMsg[$client->lng ?? 'en'], $client->phone, $clientName);

            $delay = rand(15, 30);
            $this->info("Sent to client: {$client->phone}, sleeping for {$delay} seconds...");
            // sleep($delay);
        }

        foreach ($users as $user) {
            $userName = trim(trim($user->firstname ?? '') . ' ' . trim($user->lastname ?? ''));
            $this->sendImageWithMessage($userMsg[$user->lng ?? 'en'], $user->phone, $userName);
            
            $delay = rand(15, 30);
            $this->info("Sent to user: {$user->phone}, sleeping for {$delay} seconds...");
            // sleep($delay);
        }

        $this->info('message sent successfully!');
        return 0;
    }


    public function sendImageWithMessage($sid, $phone, $name)
    {

        try {

            $message = $this->twilio->messages->create(
                "whatsapp:+" . $phone,
                [
                    "from" => $this->twilioWhatsappNumber,
                    "contentSid" => $sid,
                    "contentVariables" => json_encode([
                        '1' => $name,
                    ])
                ]
            );
            \Log::info($message->body ?? '');
            StoreWebhookResponse($message->body ?? '', $phone, $message->toArray());
        } catch (\Exception $e) {
            \Log::error('Exception during WhatsApp message send: ' . $e->getMessage());
            return ['error' => 'An error occurred while sending the message.'];
        }
    }
}
