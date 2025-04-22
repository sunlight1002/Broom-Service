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

        $specialMsg = [
            "en" => "Dear Clients,\n\nOn Wednesday, April 30th (Independence Day Eve), Broom Service will be operating until 1:00 PM only.\nOn Thursday, May 1st (Independence Day), the company will be closed.\n\nIf you are scheduled for service on that day and wish to change your appointment, please let us know as soon as possible and we will try to reschedule you.\n\nClients who would like to request service on Independence Day (for ongoing cleaning or help with hosting) are welcome to contact us to check availability.\n\nBest regards,\nBroom Service Team 🌷",
            "heb" => "לקוחות יקרים,\n\nביום רביעי 30.04 (ערב יום העצמאות) החברה תעבוד עד השעה 13:00 בלבד.\nביום חמישי 01.05 (יום העצמאות) החברה לא תעבוד.\n\nלקוחות אשר משובצים לקבלת שירות ביום זהומעוניינים לשנות את המועד – מוזמנים לעדכן אותנו בהקדם וננסה למצוא עבורם חלופה.\n\nלקוחות המעוניינים לקבל שירות דווקא ביום העצמאות (לצורך ניקיון שוטף או עזרה באירוח) – מוזמנים לפנות אלינו ונשמח לבדוק עבורם אפשרות לשיבוץ מיוחד.\n\nבברכה,\nצוות ברום סרוויס 🌸"
        ];

        $clients = Client::where('status', '2')
                ->whereHas('lead_status', function ($query) {
                    $query->where('lead_status', 'active client');
                })
                ->get();
            // dd($clients);
        foreach ($clients as $client) {
            // if(in_array($client->id, [110,112,120,121,174,203,220,221,232,233,261,270,1,2,6,8,11,13,15,21,23,24,25,30,39,40,43,45,49,51,52,54,55,57,65,67,68,70,71,79,80,85,86,88,91,135,166,179,204,215,238,240,245,246,247,333,339,394])) {
            //     echo "Already sent: " . $client->id . PHP_EOL;
            //     continue;
            // }

            if($client->monday_notification == 1 || $client->disable_notification == 1){
                \Log::info('monday notification already sent: ' . $client->id);
                continue;
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


            // $result = sendClientWhatsappMessage($client->phone, array('name' => '', 'message' => $specialMsg[$client->lng]));

            // if (!$result) {
            //     \Log::error('Failed to send message to ' . $client->phone);
            // }


            Cache::put('client_monday_msg_status_' . $client->id, 'main_monday_msg', now()->addDay(1));
            // echo $client->id . PHP_EOL;
        }
    }
}
