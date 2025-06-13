<?php

namespace App\Console\Commands;

use App\Enums\JobStatusEnum;
use App\Enums\LeadStatusEnum;
use App\Events\JobReviewRequest;
use App\Models\Job;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\WhatsAppBotActiveClientState;
use App\Models\WebhookResponse;
use Illuminate\Support\Facades\Http;
use Twilio\Rest\Client as TwilioClient;
use Illuminate\Support\Facades\Cache;

class ClientReviewNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:review-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Client Review Notification';
    protected $twilioAccountSid;
    protected $twilioAuthToken;
    protected $twilioWhatsappNumber;
    protected $twilio;
    protected $message = [
        "en" => "Hello :client_name,
We hope you enjoyed the service provided by our team.
We’d love to hear your feedback about your experience.
Your input is important to us to maintain our high standards and ensure every visit meets your expectations.

We’d love to hear your feedback about the service you received:
7️⃣ If you were satisfied with the service.
8️⃣ If you have any comments or requests for the supervisor.

Please reply with the appropriate number.",
        "heb" => "שלום :client_name,
אנו מקווים שנהניתם מהשירות שניתן על ידי הצוות שלנו.
נשמח לשמוע את דעתכם ועל החוויה שלכם.
המשוב שלכם חשוב לנו כדי לשמור על הסטנדרטים הגבוהים שלנו ולוודא שכל ביקור יעמוד בציפיותיכם.

נשמח לדעת איך התרשמתם מהשירות שקיבלתם:
7️⃣ אם הייתם מרוצים מהשירות שקיבלתם.
8️⃣ אם יש לכם הערות או בקשות שנוכל להעביר למפקח להמשך טיפול.

אנא השיבו עם המספר המתאים."
    ];

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
        $clients = Client::where('status', '2')
        ->where('id', "3832")
        ->whereHas('lead_status', function ($query) {
            $query->where('lead_status', LeadStatusEnum::ACTIVE_CLIENT);
        })
        ->get();
        
        foreach ($clients as $client) {
            WhatsAppBotActiveClientState::where('client_id', $client->id)->delete();
            $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));
            $sid = $client->lng == "heb" ? "HX1c07428ae8fa5b4688d71e11fa8101bb" : "HX230e572381fa582bbb37949bd7798916";
            $twi = $this->twilio->messages->create(
                "whatsapp:+$client->phone",
                [
                    "from" => $this->twilioWhatsappNumber, 
                    "contentSid" => $sid,
                    "contentVariables" => json_encode([
                        "1" => $clientName,
                    ]),
                ]
            );
            $personalizedMessage = str_replace(':client_name', $clientName, $this->message[$client->lng]);

            StoreWebhookResponse($twi->body ?? "", $client->phone, $twi->toArray());
           
            // sendClientWhatsappMessage($client->phone, ['name' => '', 'message' => $personalizedMessage]);
            Cache::put('client_review' . $client->id, true, now()->addDay(1));
        }
    }
}
