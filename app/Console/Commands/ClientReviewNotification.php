<?php

namespace App\Console\Commands;

use App\Enums\JobStatusEnum;
use App\Events\JobReviewRequest;
use App\Models\Job;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\WhatsAppBotActiveClientState;
use App\Models\WebhookResponse;
use Illuminate\Support\Facades\Http;
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
    protected $message = [
        "en" => "Hello :client_name,
We hope you enjoyed the service provided by our team.
We’d love to hear your feedback about your experience.
Your input is important to us to maintain our high standards and ensure every visit meets your expectations.

We’d love to hear your feedback about the service you received:
1️⃣ If you were satisfied with the service.
2️⃣ If you have any comments or requests for the supervisor.

Please reply with the appropriate number.",
        "heb" => "שלום :client_name,
אנו מקווים שנהניתם מהשירות שניתן על ידי הצוות שלנו.
נשמח לשמוע את דעתכם ועל החוויה שלכם.
המשוב שלכם חשוב לנו כדי לשמור על הסטנדרטים הגבוהים שלנו ולוודא שכל ביקור יעמוד בציפיותיכם.

נשמח לדעת איך התרשמתם מהשירות שקיבלתם:
1️⃣ אם הייתם מרוצים מהשירות שקיבלתם.
2️⃣ אם יש לכם הערות או בקשות שנוכל להעביר למפקח להמשך טיפול.

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
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $clients = Client::where('status', '2')
        ->whereHas('lead_status', function ($query) {
            $query->where('lead_status', 'active client');
        })
        ->get();

        foreach ($clients as $client) {
            WhatsAppBotActiveClientState::where('client_id', $client->id)->delete();
            $clientName = ($client->firstname ?? '') . ' ' . ($client->lastname ?? '');
            $personalizedMessage = str_replace(':client_name', $clientName, $this->message[$client->lng]);
            sendClientWhatsappMessage($client->phone, ['name' => '', 'message' => $personalizedMessage]);
            Cache::put('client_review' . $client->id, 'client_review', now()->addDay(1));
        }
    }
}
