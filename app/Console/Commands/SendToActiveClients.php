<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\WhatsappTemplate;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SendToActiveClients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:to-active-clients';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notification to active clients';

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

        $message = [
            'en' => 'Dear Valued Clients,

As part of our transition to a new system designed to enhance our availability, service quality, and collaboration with you, we are excited to introduce a new process:

From now on, you will receive messages from this number every Monday, where you will be asked to update us if you have any scheduling constraints, special requests, or changes for the following week.

For example:
The bot will write:
"If you have any constraints, changes, or special requests, please reply to this message with the number 1."
If you wish to update a request or change, you can reply:
1
The bot will then ask:
"What is the change or request for next week?"
Your response could be something like:
"Please add an additional booking for next week."

If there are no changes, there is no need to reply to the message sent.

For any additional questions or concerns, we are available as usual through all the regular contact channels you are familiar with.

Thank you for your cooperation,
The Broom Service Team 🌹
www.broomservice.co.il
Phone: 03-525-70-60
Email: office@broomservice.co.il',

            'heb' => 'לקוחות יקרים,

לקראת מעבר למערכת חדשה שנועדה לשפר את הזמינות, איכות השירות והעבודה שלנו מולכם, אנו שמחים לעדכן אתכם על תהליך חדש:

מעכשיו תקבלו הודעות מהטלפון הזה בימי שני, בהן תתבקשו לעדכן אם יש אילוצים, בקשות מיוחדות או שינויים לסידור העבודה לשבוע הבא.

לדוגמה:
הבוט יכתוב:
"במידה ויש לכם אילוצים, שינויים או בקשות מיוחדות, אנא השיבו להודעה עם הספרה 1."
במידה ותרצו לעדכן על בקשה או שינוי, תוכלו לענות:
1
ואז הבוט ישאל:
"מהו השינוי או הבקשה לשבוע הבא?"
תשובתכם יכולה להיות:
"אנא הוסיפו שיבוץ נוסף לשבוע הבא."

במידה ואין שינוי, אין צורך להשיב להודעה שתישלח.

לכל שאלה או עניין נוסף, אנו זמינים עבורכם כרגיל בכל ערוצי הקשר הרגילים שאתם מכירים.

תודה על שיתוף הפעולה,
צוות ברום סרוויס 🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il',

        ];

        $clients = Client::where('status', '2')
                ->whereHas('lead_status', function ($query) {
                    $query->where('lead_status', 'active client');
                })
                ->where('id', 1348)
                ->get();

        foreach ($clients as $client) {
            \Log::info('Sending message to ' . $client->phone . ' (' . $client->firstname . ')');

            $result = sendClientWhatsappMessage($client->phone, array('name' => '', 'message' => $message[$client->lng]));

            if (!$result) {
                \Log::error('Failed to send message to ' . $client->phone);
            }

            $clientData = [
                'type' => WhatsappMessageTemplateEnum::NOTIFY_MONDAY_CLIENT_FOR_SCHEDULE,
                'notificationData' => [
                    'client' => $client,
                ],
            ];
            event(new WhatsappNotificationEvent($clientData));
            $client->stop_last_message = 0;
            $client->save();
            Cache::put('client_monday_msg_status_' . $client->id, 'main_monday_msg', now()->addDay(1));
        }
    }
}
