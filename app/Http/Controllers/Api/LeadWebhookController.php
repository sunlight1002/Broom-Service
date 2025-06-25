<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Job;
use App\Models\User;
use App\Models\Offer;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Fblead;
use App\Models\Setting;
use App\Models\Schedule;
use App\Models\Contract;
use App\Traits\GoogleAPI;
use App\Models\WorkerLeads;
use Illuminate\Support\Str;
use App\Models\Notification;
use App\Models\LeadActivity;
use Illuminate\Http\Request;
use App\Enums\LeadStatusEnum;
use App\Enums\SettingKeyEnum;
use App\Enums\JobStatusEnum;
use App\Models\SupervisorJob;
use App\Models\ScheduleChange;
use App\Events\SendClientLogin;
use App\Models\WebhookResponse;
use App\Traits\ScheduleMeeting;
use App\Jobs\SendMeetingMailJob;
use App\Mail\Client\LoginOtpMail;
use App\Models\WhatsappLastReply;
use Illuminate\Support\Facades\DB;
use App\Enums\NotificationTypeEnum;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\ClientPropertyAddress;
use App\Models\WhatsAppBotClientState;
use App\Events\ClientLeadStatusChanged;
use Twilio\Rest\Client as TwilioClient;
use App\Events\WhatsappNotificationEvent;
use Illuminate\Support\Facades\Validator;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Models\WhatsAppBotActiveClientState;
use App\Http\Controllers\Api\WorkerLeadWebhookController;
use Exception;

class LeadWebhookController extends Controller
{
    use ScheduleMeeting, GoogleAPI;

    protected $spreadsheetId;
    protected $googleAccessToken;
    protected $googleRefreshToken;
    protected $twilioAccountSid;
    protected $twilioAuthToken;
    protected $twilioWhatsappNumber;
    protected $twilio;
    protected $googleSheetEndpoint = 'https://sheets.googleapis.com/v4/spreadsheets/';

    protected $botMessages = [
        'main_menu' => [
            'en' => "Hi, I'm Bar, the digital representative of Broom Service. How can I help you today? 😊\n\nAt any stage, you can return to the main menu by sending the number 9 or return one menu back by sending the number 0.\n\n1. About the Service\n2. Service Areas\n3. Set an appointment for a quote\n4. Customer Service\n5. Switch to a human representative (during business hours)\n7. שפה עברית\n\nIf you no longer wish to receive messages from us, please reply with 'STOP' at any time",
            'heb' => 'היי, אני בר, הנציגה הדיגיטלית של ברום סרוויס. איך אוכל לעזור לך היום? 😊' . "\n\n" . 'בכל שלב תוכלו לחזור לתפריט הראשי ע"י שליחת המס 9 או לחזור תפריט אחד אחורה ע"י שליחת הספרה 0' . "\n\n" . '1. פרטים על השירות' . "\n" . '2. אזורי שירות' . "\n" . '3. קביעת פגישה לקבלת הצעת מחיר' . "\n" . '4. שירות ללקוחות קיימים' . "\n" . '5. מעבר לנציג אנושי (בשעות הפעילות)' . "\n" . '6. English menu' . "\n\n" . "אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת."
        ],
        'whapi_new_lead_menu' => [
            "en" => "Hi, I'm Bar – the digital assistant at Broom Service ✨\nThanks for reaching out!\nI’d love to know which city you’re looking to receive service in.\n\nPlease choose from the list below:\n\n1. Tel Aviv\n2. Ramat Gan\n3. Ramat HaSharon\n4. Herzliya\n5. Giv'atayim\n6. Kiryat Ono\n7. Kfar Shmaryahu\n8. Rishpon\n9. Other area",
            "heb" => "היי, אני בר – המזכירה הדיגיטלית של ברום סרוויס ✨\nתודה שפנית אלינו!\nאשמח לדעת באיזו עיר תרצה לקבל שירות.\nאנא בחר מהרשימה הבאה:\n\n1. תל אביב\n2. רמת גן\n3. רמת השרון\n4. הרצליה\n5. גבעתיים\n6. קריית אונו\n7. כפר שמריהו\n8. רשפון\n9. איזור אחר"
        ],
        'about_service' => [
            'en' => "Broom Service - Room service for your 🏠.\nWe’re a professional cleaning company offering ✨ top-notch services for homes or apartments, available regularly or one-time, with no 🤯 hassle. Choose from 🧹 tailored packages like routine cleaning, or extras such as post-construction, pre-move, or window cleaning at any height.\nVisit 🌐 www.broomservice.co.il for all services and details.\nOur fixed prices per visit include everything—☕️ social benefits and travel—based on your package. We employ a skilled, permanent team led by a work manager. Pay by 💳 credit card monthly or post-visit, depending on your plan.\nTo get a quote, book a free, no-obligation visit from a supervisor who’ll assess your needs and provide a detailed estimate. Office hours: 🕖 Monday-Thursday, 8:00-14:00",
            "heb" => "ברום סרוויס - שירות חדרים לביתכם 🏠.\nחברת ניקיון מקצועית המספקת שירותי ניקיון ברמה גבוהה לבתים ודירות, קבוע או חד-פעמי, ללא התעסקות מיותרת 🧹. אנו מציעים חבילות מותאמות: ניקיון קבוע, ניקיון לאחר שיפוץ, לפני מעבר דירה, ניקוי חלונות בכל גובה ועוד ✨.\nרטים באתר 🌐 www.broomservice.co.il. המחירים קבועים לביקור, כוללים הכל—תנאים סוציאליים ונסיעות 🍵—לפי החבילה. צוות קבוע ומיומן בפיקוח מנהל עבודה 👨🏻‍💼. תשלום בכרטיס אשראי בסוף החודש או לאחר ביקור 💳.\nלהצעת מחיר, תאמו פגישה חינם וללא התחייבות עם מפקח שיסייע בבחירת חבילה וישלח הצעה מפורטת 📝. שעות משרד: א-ה, 8:00-14:00 🕓. "
        ],
        'enter_phone' => [
            'en' => "Hello! To verify your account, please enter the phone number you registered with our service.",
            'heb' => 'שלום! לאימות החשבון שלך, אנא הזן את מספר הטלפון איתו נרשמת לשירות.'
        ]
    ];

    protected $activeClientBotMessages = [
        "main_menu" => [
            "en" => "Hello :client_name 🌸, I’m Gali, the digital secretary of Broom Service!\nHow can I assist you today ? 😊\n\nHere are your options:\n1️⃣ Contact me urgently\n2️⃣ When is my next service?\n3️⃣ Request a new quote\n4️⃣ Invoice and accounting inquiry\n5️⃣ Change or update schedul\n6️⃣ Access our client portal\n\n❓ If you have a question or request not listed, type 'Menu' to return to the main menu at any time.",
            "heb" => "שלום - :client_name -🌸, אני גלי, המזכירה הדיגיטלית של ברום סרוויס!\nבמה אוכל לעזור לך היום? 😊\n\nלהלן האפשרויות:\n1️⃣ צרו איתי קשר דחוף\n2️⃣ מתי מגיעים אלי?\n3️⃣ בקשה להצעת מחיר חדשה\n4️⃣ הנה'ח - פנייה למחלקת הנהלת חשבונות\n5️⃣ שינוי או עדכון שיבוץ\n6️⃣ גישה לפורטל הלקוחות שלנו\n\n❓ אם יש לך שאלה אחרת או בקשה שלא בתפריט, תוכל תמיד להחזיר אותי לתפריט הראשי על ידי כתיבת 'תפריט'."
        ],
        "not_recognized" => [
            "en" => "Hello, we couldn’t recognize your number in our system.\nAre you an existing client, or would you like to receive a quote for our service?\n 1️⃣ I am an existing client\n 2️⃣ I’d like a quote",
            "heb" => "שלום, לא זיהינו את המספר שלך במערכת.\nהאם אתה לקוח קיים או מעוניין לקבל הצעת מחיר לשירות?\n 1️⃣ אני לקוח קיים\n 2️⃣ מעוניין לקבל הצעת מחיר"
        ],
        "after_new_lead" => [
            "en" => "Thank you for reaching out!\nA representative from our team will contact you shortly.\n\nIn the meantime, feel free to read what our satisfied clients say about us here:\nhttps://www.facebook.com/brmsrvc/posts/pfbid02wFoke74Yv9fK8FvwExmLducZdYufrHheqx84Dhmn14LikcUo3ZmGscLh1BrFBzrEl\n\nBest regards,\nThe Broom Service Team 🌹\nwww.broomservice.co.il\nPhone: 03-525-70-60\noffice@broomservice.co.il",
            "heb" => "תודה על פנייתך!\nנציג מטעמנו יצור איתך קשר בקרוב.\n\nבינתיים, תוכלו לקרוא מה לקוחותינו המרוצים אומרים עלינו כאן:\nhttps://www.facebook.com/brmsrvc/posts/pfbid02wFoke74Yv9fK8FvwExmLducZdYufrHheqx84Dhmn14LikcUo3ZmGscLh1BrFBzrEl\n\nבברכה,\nצוות ברום סרוויס 🌹\nwww.broomservice.co.il\nטלפון: 03-525-70-60\noffice@broomservice.co.il"
        ],
        "enter_phone" => [
            "en" => "Hello! To verify your account, please enter the phone number you registered with our service.",
            "heb" => "שלום! לאימות החשבון שלך, אנא הזן את מספר הטלפון איתו נרשמת לשירות."
        ],
        "email_sent" => [
            "en" => "We’ve sent a code to the email address you registered with, starting with :email###@#####\nPlease enter the code to continue.",
            "heb" => "שלחנו קוד לכתובת המייל איתה נרשמת לשירות, שמתחילה ב- :email###@#####.\nאנא הזן את הקוד להמשך התהליך."
        ],
        "incorect_otp" => [
            "en" => "The code you entered is incorrect. Please try again.\nIf you'd like us to resend the code, reply with 1.",
            "heb" => "הקוד שהזנת אינו נכון. אנא נסה שוב.\nאם תרצה שנשלח את הקוד מחדש, השב 1."
        ],
        "failed_attempts" => [
            "en" => "We're sorry, but you've exceeded the maximum number of attempts.\nFor security reasons, your account is temporarily locked. Our team has been notified and will contact you shortly. \nIf urgent, you can reach out to us at: 03-525-70-60.",
            "heb" => "מצטערים, אך חרגת ממספר הניסיונות המותר.\nמטעמי אבטחה, חשבונך ננעל זמנית.\n הצוות שלנו עודכן ויצור עמך קשר בהקדם. במידה וזה דחוף, ניתן ליצור איתנו קשר בטלפון: 03-525-70-60."
        ],
        "verified" => [
            "en" => "Hi, :client_name! Your account has been successfully verified.\nYou are now being transferred to the main menu.",
            "heb" => "היי, :client_name! האימות הצליח.\nכעת תועבר לתפריט הראשי."
        ],
        "urgent_contact" => [
            "en" => "Hi :client_name, what can we help you with?\nPlease let us know the urgent matter you'd like us to address, and we'll forward it to the relevant team.",
            "heb" => "היי :client_name, במה נוכל לעזור?\nאנא ציין את הנושא הדחוף עליו תרצה שניצור איתך קשר, ונעביר את זה לצוות הרלוונטי."
        ],
        "thankyou" => [
            "en" => "Thank you! We have received your message and forwarded it to the relevant team. We will contact you shortly.",
            "heb" => "תודה, קיבלנו את הודעתך והעברנו לצוות הרלוונטי. ניצור איתך קשר בהקדם."
        ],
        "team_comment" => [
            "en" => "🔔 Client :client_name has requested an urgent callback regarding: :message\n📞 Phone: :client_phone\n:comment_link\n📄 :client_link",
            "heb" => "🔔 לקוח בשם :client_name ביקש שיחזרו אליו בדחיפות בנושא: :message\n📞 טלפון: :client_phone\n:comment_link\n📄 :client_link"
        ],
        "service_schedule" => [
            "en" => "Your service is scheduled for \n:date_time\n⏰ Please note: Arrival time may vary up to 1.5 hours from the scheduled time.",
            "heb" => "השירות בשבוע הבא מתוכנן ל- \n:date_time\n⏰ שים לב: זמן ההגעה עשוי להשתנות ולהגיע לעד כשעה וחצי משעת ההתחלה."
        ],
        "next_week_service_schedule" => [
            "en" => "Your service next week is scheduled for \n:date_time\n⏰ Please note: Arrival time may vary up to 1.5 hours from the scheduled time.",
            "heb" => "השירות בשבוע הבא מתוכנן ל- \n:date_time\n⏰ שים לב: זמן ההגעה עשוי להשתנות ולהגיע לעד כשעה וחצי משעת ההתחלה."
        ],
        "no_service_avail" => [
            "en" => "We couldn't find any upcoming bookings for you in the system.\nClick 5 to ask for more information about your schedule.",
            "heb" => "לא מצאנו שיבוצים קרובים עבורך במערכת.\nניתן ללחוץ על 5 ולבקש פרטים נוספים."
        ],
        "request_new_qoute" => [
            "en" => "Your request for a new quote has been recorded.\nOur team will contact you shortly. Thank you! 🌸",
            "heb" => "בקשתך להצעת מחיר חדשה נרשמה במערכת.\nצוותנו יחזור אליך בהקדם. תודה! 🌸"
        ],
        "team_new_qoute" => [
            "en" => "🔔 Client :client_name has requested a new quote.\n📞 Phone: :client_phone\n📄 :client_link",
            "heb" => "🔔 לקוח בשם :client_name ביקש הצעת מחיר חדשה.\n📞 טלפון: :client_phone\n📄 :client_link"
        ],
        "invoice_account" => [
            "en" => "What would you like to forward to our accounting department?\nPlease let us know your inquiry or request, and we’ll ensure to get back to you promptly.",
            "heb" => "מה תרצה להעביר למחלקת הנה\"ח שלנו?\nאנא ציין את בקשתך או השאלה שלך, ואנו נדאג להחזיר לך תשובה בהקדם."
        ],
        "thank_you_invoice_account" => [
            "en" => "Hello :client_name,\n    • Thank you for reaching out to our accounting department.\nYour request has been received, and we are forwarding it to the relevant team for review.\nWe will get back to you as soon as possible with a detailed response.",
            "heb" => "שלום :client_name,\n    • תודה על פנייתך למחלקת הנה\"ח שלנו.\nהבקשה שלך התקבלה ואנו מעבירים אותה לבדיקה של הצוות הרלוונטי.\nנחזור אליך בהקדם האפשרי עם תשובה מסודרת."
        ],
        "team_invoice_account" => [
            "en" => "🔔 Client :client_name has contacted accounting with the following message: :message\n📞 Phone: :client_phone\n:comment_link\n📄 :client_link",
            "heb" => "🔔 לקוח בשם :client_name פנה למחלקת הנה'ח עם ההודעה הבאה: :message\n📞 טלפון: :client_phone\n:comment_link\n📄 :client_link"
        ],
        "change_update_schedule" => [
            "en" => "Thank you! What changes or updates would you like to make to your schedule?\nPlease provide details, and we’ll forward your request to the relevant team.",
            "heb" => "תודה! מה תרצה לעדכן או לשנות בשיבוץ שלך?\nאנא פרט, ואנו נדאג להעביר את הבקשה לצוות הרלוונטי."
        ],
        "thank_you_change_update_schedule" => [
            "en" => "Thank you! We have received your request for a schedule change or update.\nWe’ll forward this to the team and follow up if necessary. 🌸",
            "heb" => "תודה! קיבלנו את בקשתך לשינוי או עדכון שיבוץ.\nאנו נעביר זאת לצוות ונחזור אליך במידת הצורך. 🌸"
        ],
        "team_change_update_schedule" => [
            "en" => "🔔 Client *:client_name* has requested to change or update their schedule. \nMessage logged: *:message* \n📞 Phone: :client_phone\n:comment_link\n📄 :client_link",
            "heb" => "🔔 לקוח בשם *:client_name* ביקש לשנות או לעדכן שיבוץ. ההודעה שנרשמה: *:message* \n📞 טלפון: :client_phone\n:comment_link\n📄 :client_link"
        ],
        "access_portal" => [
            "en" => "To access our client portal, please click here: :client_portal_link.",
            "heb" => "לכניסה לפורטל הלקוחות שלנו, אנא לחץ כאן: :client_portal_link."
        ],
        "sorry" => [
            "en" => "Sorry, I didn’t understand your request.\nPlease try again or type 'Menu' to return to the main menu.",
            "heb" => "מצטערים, לא הבנתי את בקשתך.\nאנא נסה שוב או הקלד 'תפריט' כדי לחזור לתפריט הראשי."
        ],
        "stop" => [
            "en" => "Hello :client_name,
We have received your request to stop receiving commercial messages.

Please note that reminders and essential notifications related to your services will still be sent from this number to ensure smooth communication.

If you have any further questions or requests, feel free to contact us.

Best regards,
Broom Service Team 🌹",
            "heb" => "שלום :client_name,
בקשתך להפסיק לקבל הודעות פרסומיות התקבלה.

לתשומת ליבך, תזכורות והתראות חשובות הקשורות לשירותיך ימשיכו להישלח ממספר זה על מנת להבטיח תקשורת חלקה.

לכל שאלה או בקשה נוספת, נשמח לעמוד לשירותך.

בברכה,
צוות ברום סרוויס 🌹"
        ],
        "schedule_preferrence" => [
            "en" => "We’d love to know your preferred time slots for the upcoming service.\nWe recommend listing more than one option.\n\nPlease note – we typically have more availability during afternoon hours, so if you’re flexible, we’ll do our best to accommodate for maximum convenience.\n\n🕒 Available days and times:\nSunday to Thursday\n\nMorning: arrival between 08:00–09:30\n\nAfternoon: arrival between 12:00–13:30\n\n📥 Please reply with your preferences.\nExample:\n4-star package, weekly, prefer Tuesday or Wednesday, afternoon is okay too.",
            "heb" => "נשמח לדעת מהי העדפתך לשיבוץ הקרוב.\nמומלץ לרשום יותר מאופציה אחת.\n\nשימו לב – לרוב יש זמינות גבוהה יותר בשעות הצהריים, כך שאם יש לכם גמישות – נשמח לתאם בהתאם כדי להבטיח זמינות מקסימלית.\n\n🕒 שעות אופציונליות:\nימים ראשון עד חמישי\n\nבוקר: הגעה בין 8:00 ל־9:30\n\nצהריים: הגעה בין 12:00 ל־13:30\n\n📥 נא לציין בהודעה חוזרת את העדפתכם.\nלדוגמה:\nחבילת 4 כוכבים, פעם בשבוע, עדיפות ליום שלישי או רביעי, אפשר גם בצהריים."
        ]
    ];

    public function __construct()
    {
        $this->twilioAccountSid = config('services.twilio.twilio_id');
        $this->twilioAuthToken = config('services.twilio.twilio_token');
        $this->twilioWhatsappNumber = config('services.twilio.twilio_whatsapp_number');

        // Initialize the Twilio client
        $this->twilio = new TwilioClient($this->twilioAccountSid, $this->twilioAuthToken);
    }

    public function statusCallback(Request $request)
    {
        $data = $request->all();

        Log::info('Twilio Callback Data:' . PHP_EOL, $data);

        return response()->json(['status' => 'success'], 200);
    }


    public function whapiGroup(Request $request)
    {
        $get_data = $request->getContent();
        $responseClientState = [];
        $data_returned = json_decode($get_data, true);
        $message = null;

        $messageId = $data_returned['messages'][0]['id'] ?? null;

        if (!$messageId) {
            return response()->json(['status' => 'Invalid message data'], 400);
        }

        // Check if the messageId exists in cache and matches
        if (Cache::get('whapi_group_processed_message_' . $messageId) === $messageId) {
            \Log::info('Already processed');
            return response()->json(['status' => 'Already processed'], 200);
        }

        // Store the messageId in the cache for 1 hour
        Cache::put('whapi_group_processed_message_' . $messageId, $messageId, now()->addHours(1));

        if (
            isset($data_returned['messages']) &&
            isset($data_returned['messages'][0]['from_me']) &&
            $data_returned['messages'][0]['from_me'] == false
        ) {
            $message_data = $data_returned['messages'];
            \Log::info($message_data);
            $from = $message_data[0]['from'];
            $lng = 'heb';

            if (Str::endsWith($message_data[0]['chat_id'], '@g.us')) {

                if ($message_data[0]['chat_id'] == config('services.whatsapp_groups.lead_client')) {

                    $messageBody = $data_returned['messages'][0]['text']['body'] ?? '';

                    // Split the message body into lines
                    $lines = explode("\n", trim($messageBody));

                    $new = trim($lines[0] ?? '');
                    $fullName = trim($lines[1] ?? '');
                    $phone = trim($lines[2] ?? '');
                    $email = trim($lines[3] ?? '');

                    if (stripos($new, 'חדש') !== false) {
                        $lng = 'heb';
                    } elseif (stripos($new, 'New') !== false) {
                        $lng = 'en';
                    } else {
                        return response()->json(['status' => 'Invalid message data'], 400);
                    }

                    if (empty($phone)) {
                        return response()->json(['status' => 'Invalid message data'], 400);
                    }

                    // Validate name and split into first and last name
                    if ($fullName) {
                        $nameParts = explode(' ', $fullName);
                        $firstName = $nameParts[0] ?? '';
                        $lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : '';
                    }

                    // Validate and format the phone number
                    if ($phone) {
                        // Remove all special characters from the phone number
                        $phone = preg_replace('/[^0-9+]/', '', $phone);

                        // Adjust phone number formatting
                        if (strpos($phone, '0') === 0) {
                            $phone = '972' . substr($phone, 1);
                        }
                        if (strpos($phone, '+') === 0) {
                            $phone = substr($phone, 1);
                        }

                        // Ensure phone starts with '972'
                        if (strlen($phone) === 9 || strlen($phone) === 10) {
                            $phone = '972' . $phone;
                        }

                        // Check if the client already exists
                        $client = Client::where('phone', $phone)->first();
                        $changed = null;

                        if (!$client) {
                            $client = new Client;
                            $client->phone = $phone;
                            $client->firstname = $firstName ?? '';
                            $client->lastname = $lastName ?? '';
                            $client->email = null;
                            $client->status = 0;
                            $client->password = Hash::make(Str::random(20));
                            $client->passcode = $phone;
                            $client->geo_address = '';
                            $client->lng = ($lng);
                            $client->source = 'whapi-group-bot';
                            $client->save();

                            $m = $lng == 'heb'
                                ? "ליד חדש נוצר בהצלחה\n" . generateShortUrl(url("admin/leads/view/" . $client->id), 'admin')
                                : "New lead created successfully\n" . generateShortUrl(url("admin/leads/view/" . $client->id), 'admin');
                        } else {

                            if ($client->status != 2) {
                                $client->status = 0;
                                $client->lead_status->update([
                                    'lead_status' => LeadStatusEnum::PENDING,
                                ]);
                                $client->created_at = Carbon::now();
                                $client->save();
                                $changed = now();
                            }

                            $m = $lng == 'heb'
                                ? "עופרת כבר קיימת\n" . generateShortUrl(url("admin/leads/view/" . $client->id), 'admin')
                                : "Lead already exists\n" . generateShortUrl(url("admin/leads/view/" . $client->id), 'admin');
                        }

                        LeadActivity::create([
                            'client_id' => $client->id,
                            'created_date' => now(),
                            'status_changed_date' => $changed ? $changed : "",
                            'changes_status' => LeadStatusEnum::PENDING,
                            'reason' => $changed ? "Changed by Bot" : "",
                        ]);

                        // Send WhatsApp message
                        $result = sendWhatsappMessage(config('services.whatsapp_groups.lead_client'), ['name' => '', 'message' => $m]);
                    }
                }

                if ($message_data[0]['chat_id'] == config('services.whatsapp_groups.relevant_with_workers')) {
                    $messageInput = strtolower($message_data[0]['text']['body'] ?? '');
                    \Log::info($messageInput);

                    $pattern1 = '/^(\+?\d{1,4}[\s\-]?\d{1,4}[\s\-]?\d{1,4}[\s\-]?\d{1,4})\s*([hnut])\s*(?(?=\2h)(\d+)|(\d+)?)$/i';
                    // '/^(\+?\d{1,4}[\s\-]?\d{1,4}[\s\-]?\d{1,4}[\s\-]?\d{1,4})\s*([hnut])\s*(\d+)?$/i'
                    $pattern2 = '/^(new|חדש)\s+([\s\S]+?)\s+(ours|mp)\s+(\+?\d{1,4}[\s\-]?\d{1,4}[\s\-]?\d{1,4}[\s\-]?\d{1,4})$/is';
                    $input = implode(' ', array_map('trim', explode("\n", $messageInput)));

                    $last_input = Cache::get('manpower');
                    \Log::info($last_input);

                    if (preg_match($pattern1, $messageInput, $matches)) {

                        $phoneNumber = trim($matches[1]); // Extracts the phone number
                        $statusInput = strtolower($matches[2]); // Extracts the status (h/n/u/t)
                        $numericValue = intval($matches[3] ?? 0); // Extracts the numeric value (e.g., 55)
                        // $numericValue = isset($matches[3]) ? intval($matches[3]) : null; // Extracts numeric value (if present)

                        \Log::info('Phone: ' . $phoneNumber . ' | Status: ' . $statusInput . ' | Value: ' . $numericValue);

                        // Find the workerLead based on the phone number
                        $workerLead = WorkerLeads::where('phone', $phoneNumber)->first();

                        if ($workerLead) {
                            // Determine the status
                            switch ($statusInput) {
                                case 'h':
                                    $workerLead->status = "hiring";
                                    $workerLead->hourly_rate = $numericValue;
                                    break;
                                case 'u':
                                    $workerLead->status = "unanswered";
                                    break;
                                case 't':
                                    $workerLead->status = "will-think";
                                    break;
                                case 'n':
                                    $workerLead->status = "not-hired";
                                    break;
                            }

                            $workerLead->save();

                            // Send appropriate WhatsApp message
                            match ($workerLead->status) {
                                "hiring" => [
                                    $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::NEW_LEAD_HIRIED_TO_TEAM),
                                    $worker = $this->createUser($workerLead),
                                    $this->sendWhatsAppMessage($worker, WhatsappMessageTemplateEnum::WORKER_FORMS)
                                ],
                                "not-hired" => $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::WORKER_LEAD_NOT_RELEVANT_BY_TEAM),
                                "unanswered" => $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::NEW_LEAD_HIRING_ALEX_REPLY_UNANSWERED),
                                "will-think" => $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::TEAM_WILL_THINK_SEND_TO_WORKER_LEAD),
                                default => null
                            };

                            return response()->json(['status' => 'Worker status updated', 'value' => $numericValue], 200);
                        }

                        return response()->json(['status' => 'Worker not found'], 404);
                    } else if ((preg_match($pattern2, $input, $matches))
                        && ($message_data[0]['chat_id'] == config('services.whatsapp_groups.relevant_with_workers'))
                    ) {
                        // Log the matches to check
                        $language = (strtolower(trim($matches[1])) == 'new') ? 'en' : 'heb';
                        $workerName = trim($matches[2]);
                        $nameParts = explode(' ', $workerName);
                        // Extract the first name (first word)
                        $firstName = $nameParts[0];
                        // Combine the remaining parts as the last name
                        $lastName = implode(' ', array_slice($nameParts, 1));

                        $companyType = ($matches[3] === 'ours') ? 'my-company' : 'manpower';
                        $phoneNumber = trim($matches[4]);

                        // Check if the worker already exists
                        $workerLead = WorkerLeads::where('phone', $phoneNumber)->first();

                        if (!$workerLead) {
                            // Create new worker lead if not exists
                            $workerLead = new WorkerLeads();
                            $workerLead->firstname = $firstName;
                            $workerLead->lastname = $lastName;
                            $workerLead->lng = $language;
                            $workerLead->role = 'Cleaner';
                            $workerLead->company_type = $companyType;
                            $workerLead->phone = $phoneNumber;
                            $workerLead->status = "pending"; // Default status
                            $workerLead->source = "whapi-group-bot";
                            $workerLead->save();

                            if ($workerLead->company_type == 'manpower') {
                                $message = "select manpower company\n";
                                $companies = ManpowerCompany::all();
                                foreach ($companies as $key => $company) {
                                    $message .= $company->id . ". " . $company->name . "\n";
                                }
                                sendTeamWhatsappMessage(config('services.whatsapp_groups.relevant_with_workers'), ['name' => '', 'message' => $message]);
                                Cache::put('manpower', $workerLead->id, now()->addDays(1));
                            } else if ($workerLead->company_type == 'my-company') {
                                $worker = $this->createUser($workerLead);
                                $this->sendWhatsAppMessage($worker, WhatsappMessageTemplateEnum::WORKER_FORMS);
                            }

                            return response()->json([
                                'status' => 'New worker added',
                                'name' => $workerName,
                                'language' => $language,
                                'company_type' => $companyType
                            ], 201);
                        }
                    }

                    if ($last_input) {
                        $selectedCompanyId = intval($messageInput);
                        // Update the worker's lead with the selected company ID
                        $workerLead = WorkerLeads::where('id', $last_input)->first();
                        if ($workerLead) {
                            $workerLead->manpower_company_id = $selectedCompanyId;
                            $workerLead->save();

                            $worker = $this->createUser($workerLead);
                            $this->sendWhatsAppMessage($worker, WhatsappMessageTemplateEnum::WORKER_FORMS);

                            // // Send confirmation message to the user
                            // $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::WORKER_LEAD_FORMS_AFTER_HIRING);
                            Cache::forget('manpower', $last_input);
                        }
                    }
                    return response()->json(['status' => 'Invalid message data'], 400);
                }

                if ($message_data[0]['chat_id'] == config('services.whatsapp_groups.changes_cancellation')) {
                    $messageBody = $data_returned['messages'][0]['text']['body'] ?? '';

                    // Split the message body into lines
                    $lines = explode("\n", trim($messageBody));

                    $new = trim($lines[0] ?? '');
                    $phone = trim($lines[1] ?? '');
                    $msg = trim($lines[2] ?? '');
                    \Log::info($new . ' - ' . $phone . ' - ' . $msg);

                    if (stripos($new, 'חדש') !== false) {
                        $lng = 'heb';
                    } elseif (stripos($new, 'New') !== false) {
                        $lng = 'en';
                    } else {
                        return response()->json(['status' => 'Invalid message data'], 400);
                    }

                    if (empty($phone) || empty($msg)) {
                        \Log::info('Invalid message data - phone or msg is empty');
                        return response()->json(['status' => 'Invalid message data'], 400);
                    }

                    $client = Client::where('phone', $phone)->first();
                    \Log::info('Client: ' . json_encode($client));

                    if (!$client) {
                        return response()->json(['status' => 'Invalid message data'], 400);
                    }

                    $scheduleChange = new ScheduleChange();
                    $scheduleChange->user_type = get_class($client);
                    $scheduleChange->user_id = $client->id;
                    $scheduleChange->reason = $client->lng == "en" ? "Change or update schedule" : 'שינוי או עדכון שיבוץ';
                    $scheduleChange->comments = $msg;
                    $scheduleChange->save();

                    $teammsg = "שלום צווהבקשה נוספה\n' *:message* \n:comment_link";
                    $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));
                    $teammsg = str_replace([
                        ':client_name',
                        ':message',
                        ':comment_link'
                    ], [
                        $clientName,
                        trim($scheduleChange->comments),
                        generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin')
                    ], $teammsg);

                    sendTeamWhatsappMessage(config('services.whatsapp_groups.reviews_of_clients'), ['name' => '', 'message' => $teammsg]);

                    return response()->json(['status' => 'Invalid message data'], 400);
                }

                return response()->json(['status' => 'Already processed'], 200);
            }
        }
    }

    public function whapiPendingRequest(Request $request)
    {
        // Check if request content is JSON (likely from Whapi)
        $content = $request->getContent();
        $data = json_decode($content, true);
        $messageId = $data['messages'][0]['id'] ?? null;

        if (!$messageId) {
            \Log::info('Invalid message data');
            return response()->json(['status' => 'Invalid message data'], 400);
        }

        // Check if the messageId exists in cache and matches
        if (Cache::get('pending_processed_message_' . $messageId) === $messageId) {
            \Log::info('Already processed');
            return response()->json(['status' => 'Already processed'], 200);
        }

        // Store the messageId in the cache for 1 hour
        Cache::put('pending_processed_message_' . $messageId, $messageId, now()->addHours(1));

        $message_data = $data['messages'];
        $from = $message_data[0]['from'];
        $input = $data['messages'][0]['text']['body'] ?? "";

        $client = Client::where('phone', $from)->first();

        $clientMessageStatus = WhatsAppBotActiveClientState::where('from', $from)->first();

        $last_menu = null;
        $send_menu = null;
        if ($clientMessageStatus) {
            $lng = $clientMessageStatus->lng ?? 'heb';
            $menu_option = explode('->', $clientMessageStatus->menu_option);
            $last_menu = end($menu_option);
        }

        if ($last_menu == 'team_send_message' && trim($input) == '1') {
            $send_menu = 'team_send_message_1';
        } else if ($last_menu == 'team_send_message_1' && !empty($input)) {
            $send_menu = 'client_add_request';
        }

        switch ($send_menu) {
            case 'team_send_message_1':
                \Log::info('team_send_message_1');
                $text = [
                    "en" => "Hello :client_name,\nPlease let us know what additional information or request you would like to add.",
                    "heb" => "שלום :client_name,\nאנא עדכן אותנו מה ברצונך להוסיף או לבקש."
                ];

                $nextMessage = $text[$lng];
                $clientName = "*" . trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? '')) . "*";

                $personalizedMessage = str_replace(':client_name', $clientName, $nextMessage);
                sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

                WhatsAppBotActiveClientState::updateOrCreate(
                    ["from" => $from],
                    [
                        "from" => $from,
                        'menu_option' => 'team_send_message_1'
                    ]
                );

                WebhookResponse::create([
                    'status'        => 1,
                    'name'          => 'whatsapp',
                    'entry_id'      => $messageId,
                    'message'       => $personalizedMessage ?? '',
                    'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                    'number'        => $from,
                    'flex'          => 'A',
                    'read'          => 1,
                    // 'data'          => json_encode($twi->toArray()),
                ]);

                break;

            case "client_add_request":
                $text = [
                    "en" => "Hello :client_name,\nWe’ve received your updated request:\n':client_message'\nYour message has been forwarded to the team for further handling. Thank you for your patience!",
                    "heb" => "שלום :client_name,\nקיבלנו את עדכון הבקשה שלך:\n':client_message'\nההודעה הועברה לצוות להמשך טיפול. תודה על הסבלנות!"
                ];

                $nextMessage = $text[$lng];
                $clientName = "*" . trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? '')) . "*";

                $personalizedMessage = str_replace([':client_name', ':client_message'], [$clientName, '*' . trim($input) . '*'], $nextMessage);
                sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

                WebhookResponse::create([
                    'status'        => 1,
                    'name'          => 'whatsapp',
                    'entry_id'      => $messageId,
                    'message'       => $personalizedMessage,
                    'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                    'number'        => $from,
                    'flex'          => 'A',
                    'read'          => 1,
                    // 'data'          => json_encode($twi->toArray()),
                ]);


                $scheduleChange = new ScheduleChange();
                $scheduleChange->user_type = get_class($client);
                $scheduleChange->user_id = $client->id;
                $scheduleChange->reason = $lng == "en" ? "additional information" : 'מידע נוסף';
                $scheduleChange->comments = $input;
                $scheduleChange->save();
                $clientMessageStatus->delete();

                break;
        }
    }

    private function isJson($string)
    {
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }


    public function fbWebhookCurrentLive(Request $request)
    {
        $data = [];
        $messageId = null;
        $message = null;
        $isTwilio = false;

        // Check if request content is JSON (likely from Whapi)
        $content = $request->getContent();
        // \Log::info($content);
        if ($this->isJson($content)) {
            $this->WhapifbWebhookCurrentLive($request);
            die("whapi");
        } else {
            $data = $request->all();
            $messageId = $data['SmsMessageSid'] ?? null;
            $isTwilio = true;
        }


        if (!$messageId) {
            return response()->json(['status' => 'Invalid message data'], 400);
        }

        // Check if the messageId exists in cache and matches
        if (Cache::get('processed_message_' . $messageId) === $messageId) {
            \Log::info('Already processed');
            return response()->json(['status' => 'Already processed'], 200);
        }

        // Store the messageId in the cache for 1 hour
        Cache::put('processed_message_' . $messageId, $messageId, now()->addHours(1));

        if ($isTwilio && $data['SmsStatus'] == 'received') {
            $message = $data['Body'] ?? null;
            $listId = $data['ListId'] ?? $message;
            $ButtonPayload = $data['ButtonPayload'] ?? null;

            switch ($listId) {
                case 'About the Service':
                case 'מידע על השירות':
                    $listId = 1;
                    break;
                case 'Service Areas':
                case 'אזורי שירות':
                    $listId = 2;
                    break;
                case 'Book an Appointment':
                case 'קביעת פגישה':
                    $listId = 3;
                    break;
                case 'I am Existing client':
                case 'אני לקוח קיים':
                    $listId = 4;
                    break;
                case 'Talk to a Human (hours)':
                case 'לנציג (בשעות הפעילות)':
                    $listId = 5;
                    break;
                case 'שפה עברית':
                case 'English menu':
                    $listId = 6;
                    break;
            }

            switch ($message) {
                case 'About the Service':
                case 'מידע על השירות':
                    $message = 1;
                    break;
                case 'Service Areas':
                case 'אזורי שירות':
                    $message = 2;
                    break;
                case 'Book an Appointment':
                case 'קביעת פגישה':
                    $message = 3;
                    break;
                case 'I am Existing client':
                case 'אני לקוח קיים':
                    $message = 4;
                    break;
                case 'Talk to a Human (hours)':
                case 'לנציג (בשעות הפעילות)':
                    $message = 5;
                    break;
                case 'שפה עברית':
                case 'English menu':
                    $message = 6;
                    break;
            }

            \Log::info($listId);
            $from = $data['From'] ? str_replace("whatsapp:+", "", $data['From']) : $data['From'];
            $lng = $this->detectLanguage($message);

            $response = WebhookResponse::create([
                'status'        => 1,
                'name'          => 'whatsapp',
                'entry_id'      => $messageId,
                'message'       => $listId ?? $ButtonPayload,
                'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                'number'        => $from,
                'read'          => 0,
                'flex'          => 'C',
                'data'          => json_encode($data)
            ]);

            $client = null;
            $verifyClient = null;
            $menus = null;
            $responseClientState = null;
            $flag = null;

            $client = Client::with('lead_status')->where('phone', $from)
                ->orWhereJsonContains('extra', [['phone' => $from]])
                ->first();
            $user = User::where('phone', $from)->first();
            $workerLead = WorkerLeads::where('phone', $from)->first();

            if ($client) {
                \Log::info('Client: ' . $client->id);
            }
            if ($user && (!$client || $client->lead_status->lead_status !== "active client")) {
                $controller = app(WorkerLeadWebhookController::class);
                return $controller->fbActiveWorkersWebhookCurrentLive($request);
            }

            if ($workerLead) {
                \Log::info('WorkerLead: ' . $workerLead->id);
            }

            $responseActiveClientState = WhatsAppBotActiveClientState::where('from', $from)->where('type', "meta")->first();
            if ($responseActiveClientState) {
                $lng = $responseActiveClientState->lng;
                $menuParts = explode('->', $responseActiveClientState->menu_option);
                $flag = end($menuParts);
            } else if (!$client && !$user && !$workerLead) {
                $this->sendNewLeadMainMenu($lng, $from, 'meta-lead-bot');
            }

            if ($client && $client->disable_notification == 1) {
                \Log::info('notification disabled');
                die('notification disabled');
            } else if ($client && $client->lead_status && in_array($client->lead_status->lead_status, ['active client', 'freeze client', 'pending client'])) {
                \Log::info('active client ');
                $this->fbActiveClientsWebhookCurrentLive($request);
            }

            if ($client) {
                // if(!in_array($client->lead_status->lead_status, ['active client', 'freeze client', 'pending client'])){
                //     $client->lead_status->lead_status = "pending";
                //     $client->lead_status->updated_at = now();
                //     $client->lead_status->save();
                //     $client->created_at = now();
                //     $client->updated_at = now();
                //     $client->status = 0;
                //     $client->save();

                //     LeadActivity::create([
                //         'client_id' => $client->id,
                //         'created_date' => now(),
                //         'status_changed_date' => now(),
                //         'changes_status' => LeadStatusEnum::PENDING,
                //         'reason' => "Changed by Bot",
                //     ]);
                // }
                $responseClientState = WhatsAppBotClientState::where('client_id', $client->id)->where('type', "meta")->first();
                if (!$responseActiveClientState) {

                    WhatsAppBotActiveClientState::updateOrCreate(
                        [
                            "from" => $from,
                            'type' => 'meta'
                        ],
                        [
                            'menu_option' => 'main_menu',
                            'lng' => $lng,
                            "from" => $from,
                        ]
                    );
                }
            }

            if ($responseClientState && $responseClientState->final && in_array($client->lead_status->lead_status, ['active client', 'freeze client', 'pending client'])) {
                \Log::info('final');
                $this->fbActiveClientsWebhookCurrentLive($request);
                die('final');
            } else if ($responseClientState && $responseClientState->final && !in_array($client->lead_status->lead_status, ['active client', 'freeze client', 'pending client'])) {
                \Log::info('final');
                $responseClientState->final = 0;
                $responseClientState->save();

                $sid = $client->lng == "he" ? "HX46b1587bfcaa3e6b29869edb538f45e0" : "HXccd789be06e2fd60dd0708266ae7007f";

                $message = $this->twilio->messages->create(
                    "whatsapp:+$from",
                    [
                        "from" => $this->twilioWhatsappNumber,
                        "contentSid" => $sid,

                    ]
                );
                \Log::info($message->sid);

                WebhookResponse::create([
                    'status'        => 1,
                    'name'          => 'whatsapp',
                    'message'       =>  $message->body ?? '',
                    'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                    'number'        =>  $from,
                    'read'          => 1,
                    'flex'          => 'A',
                    'data'          => json_encode($message->toArray())
                ]);
            } else if ($responseClientState && $responseClientState->menu_option == 'new_main_menu') {
                if ($ButtonPayload != "other") {
                    $sid = null;
                    if ($client->lng == 'heb') {
                        $sid = "HX46b1587bfcaa3e6b29869edb538f45e0";
                    } else {
                        $sid = "HXccd789be06e2fd60dd0708266ae7007f";
                    }

                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "contentSid" => $sid,

                        ]
                    );

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray())
                    ]);

                    WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                        'type' => "meta"
                    ], [
                        'menu_option' => 'main_menu',
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);

                    die("STOPPED");
                } else if ($ButtonPayload == 'other') {
                    $client->lead_status->lead_status = 'irrelevant';
                    $client->lead_status->save();
                    event(new WhatsappNotificationEvent([
                        "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
                        "notificationData" => [
                            'client' => $client->toArray(),
                        ]
                    ]));

                    event(new WhatsappNotificationEvent([
                        "type" => WhatsappMessageTemplateEnum::IRRELEVANT,
                        "notificationData" => [
                            'client' => $client->toArray(),
                        ]
                    ]));

                    WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                        'type' => "meta"
                    ], [
                        'menu_option' => 'main_menu',
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);
                }
            } else if (
                $responseClientState && $responseClientState->menu_option == 'since_april' &&
                in_array($client->lead_status->lead_status, ['unanswered', 'unanswered final', 'uninterested', 'irrelevant']) &&
                !in_array(strtolower($message), ['stop', 'הפסק', 'לְהַפְסִיק'])
            ) {
                $client->lead_status->lead_status = "pending";
                $client->lead_status->updated_at = now();
                $client->lead_status->save();
                $client->created_at = now();
                $client->updated_at = now();
                $client->status = 0;
                $client->save();

                LeadActivity::create([
                    'client_id' => $client->id,
                    'created_date' => now(),
                    'status_changed_date' => now(),
                    'changes_status' => LeadStatusEnum::PENDING,
                    'reason' => "Changed by Bot",
                ]);
            }

            if ($client) {
                $result = WhatsappLastReply::where('phone', $from)
                    ->where('updated_at', '>=', Carbon::now()->subMinutes(15))
                    ->first();

                $client_menus = WhatsAppBotClientState::where('client_id', $client->id)->where('type', 'meta')->first();

                // Unified condition: send main menu
                $shouldSendMainMenu =
                    !$client_menus ||
                    $listId == '9' ||
                    $ButtonPayload == 'menu' ||
                    (($listId == 0 || $ButtonPayload == '0') && $flag != 'email_sent');

                if ($shouldSendMainMenu) {
                    $sid = null;

                    if ($client->lng === 'heb') {
                        $m = $this->botMessages['main_menu']['heb'];
                        $sid = "HX46b1587bfcaa3e6b29869edb538f45e0";
                    } else {
                        $m = $this->botMessages['main_menu']['en'];
                        $sid = "HXccd789be06e2fd60dd0708266ae7007f";
                    }

                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "contentSid" => $sid,
                        ]
                    );

                    WebhookResponse::create([
                        'status'  => 1,
                        'name'    => 'whatsapp',
                        'entry_id' => $messageId ?? null, // safe fallback if not set
                        'message' => $twi->body ?? '',
                        'from'    => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'  => $from,
                        'read'    => 1,
                        'flex'    => 'A',
                        'data'    => json_encode($twi->toArray()),
                    ]);

                    WhatsAppBotClientState::updateOrCreate(
                        [
                            'client_id' => $client->id,
                            'type'      => 'meta',
                        ],
                        [
                            'menu_option' => 'main_menu',
                            'language'    => $client->lng === 'heb' ? 'he' : 'en',
                        ]
                    );

                    \Log::info('Send main menu');
                    die("Send main menu");
                }

                if (in_array(strtolower($message), ['stop', 'הפסק', 'לְהַפְסִיק'])) {
                    $this->sendStopMessage($client, "meta");
                }

                $menu_option = explode('->', $client_menus->menu_option);
                $last_menu = end($menu_option);
                \Log::info($last_menu);

                $prev_step = null;
                if (count($menu_option) >= 2) {
                    $prev_step = $menu_option[count($menu_option) - 2];
                }

                // Need more help
                if (
                    (in_array($last_menu, ['need_more_help']) && ($ButtonPayload == "yes_1")) ||
                    (($prev_step == 'main_menu' || $prev_step == 'customer_service') && $listId == '0')
                ) {
                    $sid = null;

                    if ($client->lng == 'heb') {
                        $m = $this->botMessages['main_menu']['heb'];
                        $sid = "HX46b1587bfcaa3e6b29869edb538f45e0";
                    } else {
                        $m = $this->botMessages['main_menu']['en'];
                        $sid = "HXccd789be06e2fd60dd0708266ae7007f";
                    }

                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "contentSid" => $sid,

                        ]
                    );

                    $response = WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'read'          => 1,
                        'flex'          => 'A',
                        'data'          => json_encode($twi->toArray())

                    ]);
                    $responseClientState = WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                        'type' => 'meta',
                    ], [
                        'menu_option' => 'main_menu',
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);
                    die("Send main menu");
                }

                // Cancel job one time
                if (
                    $last_menu == 'cancel_one_time' &&
                    (str_contains(strtolower($message), 'yes') || str_contains($message, 'כן'))
                ) {
                    $msg = ($client->lng == 'heb' ? `נציג מהצוות שלנו ייצור איתך קשר בהקדם.` : 'A representative from our team will contact you shortly.');
                    $responseClientState = WhatsAppBotClientState::where('client_id', $client->id)->where('type', 'meta')->delete();

                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "body" => $msg,

                        ]
                    );

                    \Log::info($twi->sid);
                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray())
                    ]);

                    die("Final message");
                }

                // Send english menu
                if ($last_menu == 'main_menu' && $listId == '6') {
                    $lng = $client->lng;
                    if ($client->lng == 'en') {
                        $client->lng = 'heb';
                    } else {
                        $client->lng = 'en';
                    }
                    $client->save();

                    $sid = "HXccd789be06e2fd60dd0708266ae7007f";
                    if ($client->lng == 'heb') {
                        $sid = "HX46b1587bfcaa3e6b29869edb538f45e0";
                    }

                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "contentSid" => $sid,

                        ]
                    );
                    \Log::info($twi->sid);

                    $response = WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'read'          => 1,
                        'flex'          => 'A',
                        'data'          => json_encode($twi->toArray())
                    ]);
                    $responseClientState =  WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                        'type' => 'meta',
                    ], [
                        'menu_option' => 'main_menu',
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);
                    Log::info('Language switched to english');
                    die("Language switched to english");
                }

                \Log::info($flag . " - " . $last_menu);

                // Send hebrew menu
                // if ($last_menu == 'main_menu' && $listId == '7') {
                //     \Log::info('Language switched to hebrew');
                //     if (strlen($from) > 10) {
                //         Client::where('phone', 'like', '%' . substr($from, 2) . '%')->update(['lng' => 'heb']);
                //     } else {
                //         Client::where('phone', 'like', '%' . $from . '%')->update(['lng' => 'heb']);
                //     }
                //     $m = $this->botMessages['main_menu']['heb'];

                //     $sid = "HX46b1587bfcaa3e6b29869edb538f45e0";

                //     $twi = $this->twilio->messages->create(
                //         "whatsapp:+$from",
                //         [
                //             "from" => $this->twilioWhatsappNumber,
                //             "contentSid" => $sid,

                //         ]
                //     );
                //     \Log::info($twi->sid);

                //     $response = WebhookResponse::create([
                //         'status'        => 1,
                //         'name'          => 'whatsapp',
                //         'message'       => $twi->body ?? '',
                //         'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                //         'number'        => $from,
                //         'read'          => 1,
                //         'flex'          => 'A',
                //         'data'          => json_encode($twi->toArray())
                //     ]);
                //     $responseClientState =  WhatsAppBotClientState::updateOrCreate([
                //         'client_id' => $client->id,
                //     ], [
                //         'menu_option' => 'main_menu',
                //         'language' =>  'he',
                //     ]);
                //     Log::info('Language switched to hebrew');
                //     die("Language switched to hebrew");
                // }

                // Menus Array
                $menus = [
                    'main_menu' => [
                        '1' => [
                            'title' => "About the Service",
                            'content' => [
                                'en' => 'Broom Service - Room service for your 🏠.
                                    We’re a professional cleaning company offering ✨ top-notch services for homes or apartments, available regularly or one-time, with no 🤯 hassle. Choose from 🧹 tailored packages like routine cleaning, or extras such as post-construction, pre-move, or window cleaning at any height.
                                    Visit 🌐 www.broomservice.co.il for all services and details.
                                    Our fixed prices per visit include everything—☕️ social benefits and travel—based on your package. We employ a skilled, permanent team led by a work manager. Pay by 💳 credit card monthly or post-visit, depending on your plan.
                                    To get a quote, book a free, no-obligation visit from a supervisor who’ll assess your needs and provide a detailed estimate. Office hours: 🕖 Monday-Thursday, 8:00-14:00',
                                'he' => 'ברום סרוויס - שירות חדרים לביתכם 🏠.
                                    חברת ניקיון מקצועית המספקת שירותי ניקיון ברמה גבוהה לבתים ודירות, קבוע או חד-פעמי, ללא התעסקות מיותרת 🧹. אנו מציעים חבילות מותאמות: ניקיון קבוע, ניקיון לאחר שיפוץ, לפני מעבר דירה, ניקוי חלונות בכל גובה ועוד ✨.
                                    רטים באתר 🌐 www.broomservice.co.il. המחירים קבועים לביקור, כוללים הכל—תנאים סוציאליים ונסיעות 🍵—לפי החבילה. צוות קבוע ומיומן בפיקוח מנהל עבודה 👨🏻‍💼. תשלום בכרטיס אשראי בסוף החודש או לאחר ביקור 💳.
                                    להצעת מחיר, תאמו פגישה חינם וללא התחייבות עם מפקח שיסייע בבחירת חבילה וישלח הצעה מפורטת 📝. שעות משרד: א-ה, 8:00-14:00 🕓. '
                            ]
                        ],
                        '2' => [
                            'title' => "Service Areas",
                            'content' => [
                                'en' => 'We provide service in the following areas: 🗺️
                                - Tel Aviv
                                - Ramat Gan
                                - Givatayim
                                - Kiryat Ono
                                - Ganei Tikva
                                - Ramat HaSharon
                                - Kfar Shmaryahu
                                - Rishpon
                                - Herzliya

                                To schedule an appointment for a quote press 3 or ☎️ 5 to speak with a representative.',
                                'he' => 'אנו מספקים שירות באזור 🗺️:
                                - תל אביב
                                - רמת גן
                                - גבעתיים
                                - קריית אונו
                                - גני תקווה
                                - רמת השרון
                                - כפר שמריהו
                                - רשפון
                                - הרצליה

                                לקביעת פגישה להצעת מחיר הקש 3 לשיחה עם נציג הקש ☎️ 5.'
                            ]
                        ],
                        '3' => [
                            'title' => "Schedule an appointment for a quote",
                            'content' => [
                                'en' => "To receive a quote, please send us messages with the following details\n\nPlease send your first name",
                                'he' => "כדי לקבל הצעת מחיר, אנא שלחו את הפרטים הבאים: 📝\n\nשם מלא",
                            ]
                        ],
                        '4' => [
                            'title' => "coustomer_service",
                            'content' => [
                                'en' => 'Hello! To verify your account, please enter the phone number you registered with our service.',
                                'he' => "שלום! לאימות החשבון שלך, אנא הזן את מספר הטלפון איתו נרשמת לשירות."
                            ]
                        ],
                        '5' => [
                            'title' => "Switch to a Human Representative - During Business Hours",
                            'content' => [
                                'en' => 'Dear customers, office hours are Monday-Thursday from 8:00 to 14:00.
                                    If you contact us outside of business hours, a representative from our team will get back to you as soon as possible on the next business day, during business hours.
                                    If you would like to speak to a human representative, please send a message with the word "Human Representative". 🙋🏻',
                                'he' => 'לקוחות יקרים, שעות הפעילות במשרד הן בימים א-ה בשעות 8:00-14:00.
                                במידה ופניתם מעבר לשעות הפעילות נציג מטעמנו יחזור אליכם בהקדם ביום העסקים הבא, בשעות הפעילות.
                                אם אתם מעוניינים לדבר עם נציג אנושי, אנא שלחו הודעה עם המילה "נציג אנושי". 🙋🏻',
                            ]
                        ]
                    ]
                ];

                // Greeting message
                if (in_array($last_menu, ['need_more_help', 'cancel_one_time']) && ($ButtonPayload == "no_1")) {
                    $msg = ($client->lng == 'heb' ? `מקווה שעזרתי! 🤗` : 'I hope I helped! 🤗');
                    $responseClientState = WhatsAppBotClientState::where('client_id', $client->id)->where("type", "meta")->first();

                    if ($responseClientState) {
                        $responseClientState->menu_option = 'main_menu';
                        $responseClientState->final = true;
                        $responseClientState->save();
                    }

                    // $responseClientState = WhatsAppBotClientState::where('client_id', $client->id)->delete();
                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "body" => $msg,

                        ]
                    );

                    \Log::info($twi->sid);
                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray())
                    ]);
                    die("Final message");
                }

                // Send appointment message
                if (($last_menu == 'about_the_service' || $last_menu == 'service_areas') && in_array($listId, ['3', '5'])) {
                    \Log::info('Send appointment message');
                    $last_menu = 'main_menu';
                }

                if ($last_menu == 'human_representative') {
                    $msg = null;

                    if (str_contains($message, 'Human Representative') || str_contains($message, 'נציג אנושי')) {

                        event(new WhatsappNotificationEvent([
                            "type" => WhatsappMessageTemplateEnum::LEAD_NEED_HUMAN_REPRESENTATIVE,
                            "notificationData" => [
                                'client' => $client->toArray()
                            ]
                        ]));

                        if ($client->lng == 'heb') {
                            $msg = 'נציג מטעמנו יצור קשר בהקדם. האם יש משהו נוסף שאוכל לעזור לך בו היום? (כן או לא) 👋';
                        } else {
                            $msg = 'A representative from our team will contact you shortly. Is there anything else I can help you with today? (Yes or No) 👋';
                        }

                        $sid = $client->lng == "heb" ? "HXb8458527407d8b6e374e3bde0f10eb6a" : "HXea3c25fb7dcab69db546ad6e26922f57";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,

                            ]
                        );
                        \Log::info($twi->sid);

                        $state = "main_menu->human_representative->need_more_help";
                    } else {
                        // if ($client->lng == 'heb') {
                        //     $msg = 'נראה שהזנת קלט שגוי. אנא בדוק ונסה שוב.';
                        // } else {
                        //     $msg = 'It looks like you\'ve entered an incorrect input. Please check and try again.';
                        // }

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$from",
                        //     [
                        //         "from" => $this->twilioWhatsappNumber,
                        //         "body" => $msg,

                        //     ]
                        // );
                        // \Log::info($twi->sid);

                        $scheduleChange = ScheduleChange::create(
                            [
                                'user_type' => get_class($client),
                                'user_id' => $client->id,
                                'comments' => trim($message),
                                "reason" => $client->lng == "en" ? "Contact me urgently" : " צרו איתי קשר דחוף",
                            ]
                        );
                        $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));
                        // $teammsg = "שלום צוות, הלקוח " . "*" . $clientName . "*" . "  ביקש לבצע שינוי בסידור העבודה שלו לשבוע הבא. הבקשה שלו היא: \"" . '*' . $messageBody . '*' . "\" אנא בדקו וטפלו בהתאם. בברכה, צוות ברום סרוויס\n:comment_link";
                        $teammsg = "שלום צוות, לקוח " . "*" . $clientName . "*" . " ביקש. בקשתו היא: " . "*" . trim($message) . "*" . " אנא בדוק וטפל בהתאם. בברכה, צוות השירות של ברום\n:comment_link";
                        $personalizedMessage = str_replace(':comment_link', generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin'), $teammsg);

                        sendTeamWhatsappMessage(config('services.whatsapp_groups.urgent'), ['name' => '', 'message' => $personalizedMessage]);

                        $sid = $lng == "heb" ? "HXb4c7927e4693937b29866626ef467c65" : "HX20d3822e21360344c93be702ed178544";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                // "body" => $follow_up_msg,

                            ]
                        );

                        $state = "main_menu->human_representative";
                    }


                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray())
                    ]);

                    $responseClientState = WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                        'type' => 'meta',

                    ], [
                        'menu_option' => $state,
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);

                    $message = null;
                    die("Human representative");
                }

                // Check the current menu state
                if ($last_menu == 'first_name') {
                    // Store first name
                    $client->firstname = trim($message);
                    $client->save();

                    // Ask for last name
                    $msg = $client->lng == 'heb'
                        ? 'מה שם המשפחה שלך?'
                        : "Please send your last name.";


                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "body" => $msg,

                        ]
                    );
                    \Log::info($twi->sid);

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray())
                    ]);
                    // Update client state to expect the last name
                    WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                        'type' => 'meta',
                    ], [
                        'menu_option' => 'main_menu->appointment->last_name',
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);

                    die("Store first name");
                }

                if ($last_menu == 'last_name') {
                    // Store last name
                    $client->lastname = trim($message);
                    $client->save();

                    // Ask for full address
                    $msg = $client->lng == 'heb'
                        ? 'כתובת מלאה (רחוב, מספר ועיר בלבד)'
                        : "Please send your full address (Only street, number, and city).";


                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "body" => $msg,

                        ]
                    );
                    \Log::info($twi->sid);

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray())
                    ]);
                    // Update client state to expect the full address
                    WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                        'type' => 'meta',
                    ], [
                        'menu_option' => 'main_menu->appointment->full_address',
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);

                    die("Store last name");
                }

                if ($last_menu == 'full_address') {

                    $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                        'address' => $message,
                        'key' => config('services.google.map_key'),
                        'language' => $client->lng == 'heb' ? 'he' : 'en'
                    ]);


                    if ($response->successful()) {
                        $data = $response->object();
                        $result = $data->results[0] ?? null;
                        if ($result) {
                            $zipcode = null;
                            $city = null;

                            foreach ($result->address_components ?? [] as $key => $address_component) {
                                if (in_array('locality', $address_component->types)) {
                                    $city = $address_component->long_name;
                                }

                                if (in_array('postal_code', $address_component->types)) {
                                    $zipcode = $address_component->long_name;
                                }
                            }

                            $client->update([
                                'verify_last_address_with_wa_bot' => [
                                    'address_name' => $result->formatted_address ?? null,
                                    'city' => $city ?? NULL,
                                    'floor' => NULL,
                                    'apt_no' => null,
                                    'entrence_code' => null,
                                    'zipcode' => $zipcode ?? NULL,
                                    'geo_address' => $result->formatted_address ?? NULL,
                                    'latitude' => $result->geometry->location->lat ?? NULL,
                                    'longitude' => $result->geometry->location->lng ?? NULL,
                                ]
                            ]);

                            $msg = null;
                            if ($client->lng == 'heb') {
                                $msg = 'אנא אשר אם הכתובת הבאה נכונה על ידי תשובה כן או לא:' . $result->formatted_address;
                            } else {
                                $msg = "Please confirm if this address is correct by replying with Yes or No:\n\n" . $result->formatted_address;
                            }
                            $responseClientState = WhatsAppBotClientState::updateOrCreate([
                                'client_id' => $client->id,
                                'type' => 'meta',
                            ], [
                                'menu_option' => 'main_menu->appointment->full_address->verify_address',
                                'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                            ]);

                            $sid = $client->lng == 'heb' ? 'HX8137ef73ff405cd78aa49f05960654c6' : 'HX115bf3451f48b68cb0edfdb9be6b481e';

                            $twi = $this->twilio->messages->create(
                                "whatsapp:+$from",
                                [
                                    "from" => $this->twilioWhatsappNumber,
                                    "contentSid" => $sid,
                                    "contentVariables" => json_encode([
                                        "1" => $result->formatted_address
                                    ]),

                                ]
                            );
                            \Log::info($twi->sid);
                            WebhookResponse::create([
                                'status'        => 1,
                                'name'          => 'whatsapp',
                                'entry_id'      => $messageId,
                                'message'       => $twi->body ?? '',
                                'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                                'number'        => $from,
                                'flex'          => 'A',
                                'read'          => 1,
                                'data'          => json_encode($twi->toArray())
                            ]);

                            die("Verify address");
                        } else {
                            $client->update([
                                'verify_last_address_with_wa_bot' => NULL
                            ]);
                        }
                    } else {
                        $client->update([
                            'verify_last_address_with_wa_bot' => NULL
                        ]);
                    }
                }

                if ($last_menu == 'verify_address') {
                    if ($ButtonPayload == "yes_1") {
                        $lastEnteredAddress = $client->verify_last_address_with_wa_bot;

                        $propertyAddress = $client->property_addresses()
                            ->where('geo_address', $lastEnteredAddress['geo_address'])
                            ->first();

                        if (!$propertyAddress) {
                            $propertyAddress = ClientPropertyAddress::create(
                                [
                                    'client_id' => $client->id,
                                    'address_name' => $lastEnteredAddress['address_name'],
                                    'city' => $lastEnteredAddress['city'],
                                    'floor' => $lastEnteredAddress['floor'],
                                    'apt_no' => $lastEnteredAddress['apt_no'],
                                    'entrence_code' => $lastEnteredAddress['entrence_code'],
                                    'zipcode' => $lastEnteredAddress['zipcode'],
                                    'geo_address' => $lastEnteredAddress['geo_address'],
                                    'latitude' => $lastEnteredAddress['latitude'],
                                    'longitude' => $lastEnteredAddress['longitude'],
                                ]
                            );
                        }

                        $lastEnteredAddress['id'] = $propertyAddress->id;

                        $client->update([
                            'verify_last_address_with_wa_bot' => $lastEnteredAddress
                        ]);

                        $msg = null;
                        if ($client->lng == 'heb') {
                            $msg = 'באיזו קומה נמצא הנכס שלך? (אם אין השב אין)';
                        } else {
                            $msg = "What is the floor of your address? (If none then type x)";
                        }


                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "body" => $msg,

                            ]
                        );
                        \Log::info($twi->sid);
                        WebhookResponse::create([
                            'status'        => 1,
                            'name'          => 'whatsapp',
                            'entry_id'      => $messageId,
                            'message'       => $twi->body ?? '',
                            'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                            'number'        => $from,
                            'flex'          => 'A',
                            'read'          => 1,
                            'data'          => json_encode($twi->toArray())
                        ]);

                        $responseClientState = WhatsAppBotClientState::updateOrCreate([
                            'client_id' => $client->id,
                            'type' => 'meta',
                        ], [
                            'menu_option' => 'main_menu->appointment->full_address->floor',
                            'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                        ]);

                        die("Store address");
                    } else {
                        $client->update([
                            'verify_last_address_with_wa_bot' => NULL
                        ]);

                        $msg = null;
                        if ($client->lng == 'heb') {
                            $msg = 'אנא הזן את כתובתך בפירוט רב יותר.';
                        } else {
                            $msg = "Please provide more details for your address.";
                        }


                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "body" => $msg,

                            ]
                        );
                        \Log::info($twi->sid);

                        WebhookResponse::create([
                            'status'        => 1,
                            'name'          => 'whatsapp',
                            'entry_id'      => $messageId,
                            'message'       => $twi->body ?? '',
                            'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                            'number'        => $from,
                            'flex'          => 'A',
                            'read'          => 1,
                            'data'          => json_encode($twi->toArray())
                        ]);
                        $responseClientState = WhatsAppBotClientState::updateOrCreate([
                            'client_id' => $client->id,
                            'type' => 'meta',
                        ], [
                            'menu_option' => 'main_menu->appointment->full_address',
                            'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                        ]);

                        die("Re-enter address");
                    }
                }

                if ($last_menu == 'floor') {
                    $lastEnteredAddress = $client->verify_last_address_with_wa_bot;

                    $propertyAddress = $client->property_addresses()
                        ->where('id', $lastEnteredAddress['id'])
                        ->first();

                    if ($propertyAddress) {
                        if (
                            ($client->lng == 'heb' && $message == 'אין') ||
                            ($client->lng == 'en' && strtolower($message) == 'x')
                        ) {
                            $propertyAddress->update([
                                'floor' => NULL
                            ]);
                        } else {
                            $propertyAddress->update([
                                'floor' => $message
                            ]);
                        }
                    }

                    if ($client->lng == 'heb') {
                        $msg = 'מהו מספר הדירה (אם אין השב אין)';
                    } else {
                        $msg = "What is the apartment number of your address? (If none then type x)";
                    }


                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "body" => $msg,

                        ]
                    );
                    \Log::info($twi->sid);

                    $responseClientState = WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray())
                    ]);

                    $responseClientState = WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                        'type' => 'meta',
                    ], [
                        'menu_option' => 'main_menu->appointment->full_address->apartment_number',
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);

                    die("Address floor");
                }

                if ($last_menu == 'apartment_number') {
                    $lastEnteredAddress = $client->verify_last_address_with_wa_bot;

                    $propertyAddress = $client->property_addresses()
                        ->where('id', $lastEnteredAddress['id'])
                        ->first();

                    if ($propertyAddress) {
                        if (
                            ($client->lng == 'heb' && $message == 'אין') ||
                            ($client->lng == 'en' && strtolower($message) == 'x')
                        ) {
                            $propertyAddress->update([
                                'apt_no' => NULL
                            ]);
                        } else {
                            $propertyAddress->update([
                                'apt_no' => $message
                            ]);
                        }
                    }

                    if ($client->lng == 'heb') {
                        $msg = 'אנא ספק את פרטי החניה עבור הכתובת הנתונה (אם אין השב אין).';
                    } else {
                        $msg = "Please provide the parking details for the given address (If none then type x).";
                    }


                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "body" => $msg,

                        ]
                    );
                    \Log::info($twi->sid);

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray())
                    ]);
                    $responseClientState = WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                        'type' => 'meta',
                    ], [
                        'menu_option' => 'main_menu->appointment->full_address->parking',
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);

                    die("Address Apt no.");
                }

                // Store address parking
                if ($last_menu == 'parking') {
                    $lastEnteredAddress = $client->verify_last_address_with_wa_bot;

                    $propertyAddress = $client->property_addresses()
                        ->where('id', $lastEnteredAddress['id'])
                        ->first();

                    if ($propertyAddress) {
                        $propertyAddress->update([
                            'parking' => $message
                        ]);

                        $client->update([
                            'verify_last_address_with_wa_bot' => NULL
                        ]);

                        $msg = null;
                        if ($client->lng == 'heb') {
                            $msg = 'אנא ספק את כתובת האימייל שלך.';
                        } else {
                            $msg = "Please provide your email address.";
                        }


                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "body" => $msg,

                            ]
                        );
                        \Log::info($twi->sid);
                        WebhookResponse::create([
                            'status'        => 1,
                            'name'          => 'whatsapp',
                            'entry_id'      => $messageId,
                            'message'       => $twi->body ?? '',
                            'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                            'number'        => $from,
                            'flex'          => 'A',
                            'read'          => 1,
                            'data'          => json_encode($twi->toArray())
                        ]);

                        $responseClientState = WhatsAppBotClientState::updateOrCreate([
                            'client_id' => $client->id,
                            'type' => 'meta',
                        ], [
                            'menu_option' => 'main_menu->appointment->full_address->email',
                            'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                        ]);

                        die("Store address parking");
                    } else {
                        $client->update([
                            'verify_last_address_with_wa_bot' => NULL
                        ]);

                        $msg = null;
                        if ($client->lng == 'heb') {
                            $msg = 'הכתובת הנתונה לא נמצאה. אנא ספק כתובת חלופית.';
                        } else {
                            $msg = "The given address was not found. Please provide an alternative address.";
                        }


                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "body" => $msg,

                            ]
                        );
                        \Log::info($twi->sid);
                        WebhookResponse::create([
                            'status'        => 1,
                            'name'          => 'whatsapp',
                            'entry_id'      => $messageId,
                            'message'       => $twi->body ?? '',
                            'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                            'number'        => $from,
                            'flex'          => 'A',
                            'read'          => 1,
                            'data'          => json_encode($twi->toArray())
                        ]);

                        $responseClientState = WhatsAppBotClientState::updateOrCreate([
                            'client_id' => $client->id,
                            'type' => 'meta',
                        ], [
                            'menu_option' => 'main_menu->appointment->full_address',
                            'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                        ]);

                        die("Re-enter address");
                    }
                }

                // Store lead email
                if ($last_menu == 'email') {
                    $msg = null;
                    $sid = null;
                    $num = null;
                    $link = null;
                    if (filter_var($message, FILTER_VALIDATE_EMAIL)) {
                        $email_exists = Client::where('email', $message)->where('id', '!=', $client->id)->exists();
                        if ($email_exists) {
                            $msg = ($client->lng == 'heb' ? `הכתובת '` . $message . `' כבר קיימת. נא הזן כתובת דוא"ל אחרת.` : '\'' . $message . '\' is already taken. Please enter a different email address.');
                            $num = 1;
                        } else {
                            $client->email = trim($message);
                            $client->save();
                            $client->refresh();

                            $nextAvailableSlot = $this->nextAvailableMeetingSlot();
                            if ($nextAvailableSlot) {
                                $address = $client->property_addresses()->first();

                                $scheduleData = [
                                    'address_id'    => $address->id,
                                    'booking_status'    => 'pending',
                                    'client_id'     => $client->id,
                                    'meet_via'      => 'on-site',
                                    'purpose'       => 'Price offer',
                                    // 'start_date'    =>  $nextAvailableSlot['date'],
                                    // 'start_time_standard_format' =>  $nextAvailableSlot['start_time'],
                                    'team_id'       => $nextAvailableSlot['team_member_id']
                                ];

                                // $scheduleData['start_time'] = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d') . ' ' . $nextAvailableSlot['start_time'])->format('h:i A');
                                // $scheduleData['end_time'] = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d') . ' ' . $nextAvailableSlot['start_time'])->addMinutes(30)->format('h:i A');

                                $schedule = Schedule::create($scheduleData);

                                $client->lead_status()->updateOrCreate(
                                    [],
                                    ['lead_status' => LeadStatusEnum::POTENTIAL]
                                );

                                LeadActivity::create([
                                    'client_id' => $client->id,
                                    'created_date' => now(),
                                    'status_changed_date' => now(),
                                    'changes_status' => LeadStatusEnum::POTENTIAL,
                                    'reason' => "Changed by Bot",
                                ]);

                                $client->status = 1;
                                $client->save();

                                event(new ClientLeadStatusChanged($client, LeadStatusEnum::POTENTIAL));

                                $googleAccessToken = Setting::query()
                                    ->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)
                                    ->value('value');

                                if ($googleAccessToken) {
                                    $schedule->load(['client', 'team', 'propertyAddress']);

                                    try {
                                        // Initializes Google Client object
                                        $googleClient = $this->getClient();

                                        $this->saveGoogleCalendarEvent($schedule);

                                        // $this->sendMeetingMail($schedule);
                                        SendMeetingMailJob::dispatch($schedule);
                                    } catch (\Throwable $th) {
                                        //throw $th;
                                    }
                                }

                                Notification::create([
                                    'user_id' => $schedule->client_id,
                                    'user_type' => get_class($client),
                                    'type' => NotificationTypeEnum::SENT_MEETING,
                                    'meet_id' => $schedule->id,
                                    'status' => $schedule->booking_status
                                ]);

                                $link = generateShortUrl(url("meeting-status/" . base64_encode($schedule->id) . "/reschedule"), 'client');
                                if ($client->lng == 'heb') {
                                    $msg = "$link\n\nאנא בחר/י זמן לפגישה באמצעות הקישור למטה. יש משהו נוסף שבו אני יכול/ה לעזור לך היום? 😊";
                                } else {
                                    $msg = "Please choose a time slot for your appointment using the link below. Is there anything else I can help you with today? (Yes or No) 👋\n\n$link";
                                }
                                $num = 2;
                            } else {
                                if ($client->lng == 'heb') {
                                    $msg = "נציג מטעמנו ייצור עמכם קשר בהקדם. \n\nהאם יש משהו נוסף שאני יכול לעזור לך בו היום? (כן או לא) 👋";
                                } else {
                                    $msg = "A representative from our team will contact you shortly.\n\nIs there anything else I can help you with today? (Yes or No) 👋";
                                }

                                event(new WhatsappNotificationEvent([
                                    "type" => WhatsappMessageTemplateEnum::NO_SLOT_AVAIL_CALLBACK,
                                    "notificationData" => [
                                        'client' => $client->toArray()
                                    ]
                                ]));
                                $num = 3;
                            }

                            $responseClientState =  WhatsAppBotClientState::updateOrCreate([
                                'client_id' => $client->id,
                                'type' => 'meta',
                            ], [
                                'menu_option' => 'main_menu->appointment->need_more_help',
                                'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                            ]);
                        }
                    } else {
                        $msg = ($client->lng == 'heb' ? `כתובת הדוא"ל '` . $message . `' לא תקינה. בבקשה נסה שוב.` : 'The email address \'' . $message . '\' is considered invalid. Please try again.');
                        $num = 4;
                    }

                    if ($num == 1 || $num == 4) {
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "body" => $msg,

                            ]
                        );
                        \Log::info($twi->sid);
                    } elseif ($num == 2) {
                        $sid = $client->lng == "heb" ? "HX7a8812e85098315c1e44abc64805249d" : "HXf60927d6328af65091685aa6676979e5";
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => $link
                                ]),

                            ]
                        );
                    } elseif ($num == 3) {
                        $sid = $client->lng == "heb" ? "HXb943dfc068d9fae11b69867feb8cb0a5" : "HX80d69d464f2895c3cab8906912bebe04";
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,

                            ]
                        );
                    }

                    if (!empty($msg)) {
                        WebhookResponse::create([
                            'status'        => 1,
                            'name'          => 'whatsapp',
                            'entry_id'      => $messageId,
                            'message'       => $twi->body ?? '',
                            'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                            'number'        => $from,
                            'flex'          => 'A',
                            'read'          => 1,
                            'data'          => json_encode($twi->toArray())
                        ]);
                    }

                    die("Store email");
                }


                if ($last_menu == 'customer_service' && $flag == 'enter_phone' && !empty($message)) {
                    $phone = $message;

                    // 1. Remove all special characters from the phone number
                    $phone = preg_replace('/[^0-9+]/', '', $phone);

                    // 2. If there's any string or invalid characters in the phone, extract the digits
                    if (preg_match('/\d+/', $phone, $matches)) {
                        $phone = $matches[0]; // Extract the digits

                        // Reapply rules on extracted phone number
                        // If the phone number starts with 0, add 972 and remove the first 0
                        if (strpos($phone, '0') === 0) {
                            $phone = '972' . substr($phone, 1);
                        }

                        // If the phone number starts with +, remove the +
                        if (strpos($phone, '+') === 0) {
                            $phone = substr($phone, 1);
                        }
                    }

                    $phoneLength = strlen($phone);
                    if (($phoneLength === 9 || $phoneLength === 10) && strpos($phone, '972') !== 0) {
                        $phone = '972' . $phone;
                    }

                    $verifyClient = Client::where('phone', $phone)
                        ->orWhereJsonContains('extra', [['phone' => $phone]])
                        ->first();

                    if ($verifyClient && !empty($phone)) {
                        $flag = 'email_sent';
                    } else {
                        $flag = 'number_not_recognized';
                    }
                } else if ($last_menu == 'customer_service' && $flag == 'email_sent' && $ButtonPayload == '0') {
                    $verifyClient = Client::where('phone', $responseActiveClientState->client_phone)
                        ->orWhereJsonContains('extra', [['phone' => $responseActiveClientState->client_phone]])
                        ->first();
                    $flag = 'email_sent';
                } else if ($last_menu == 'customer_service' && $flag == 'email_sent' && !empty($message)) {
                    $verifyClient = Client::where('phone', $responseActiveClientState->client_phone)
                        ->orWhereJsonContains('extra', [['phone' => $responseActiveClientState->client_phone]])
                        ->first();

                    if ($verifyClient && $verifyClient->otp == $message && $verifyClient->otp_expiry >= now()) {
                        $flag = 'verified';
                    } else {
                        if ($verifyClient) {
                            $verifyClient->attempts += 1;
                            $verifyClient->save();
                            $flag = $verifyClient->attempts >= 4 ? 'failed_attempts' : 'incorect_otp';
                        } else {
                            $flag = 'number_not_recognized'; // fallback if somehow verifyClient is still null
                        }
                    }
                } else if ($last_menu == 'customer_service' && $flag == 'failed_attempts') {
                    $flag = 'failed_attempts';
                }

                if (in_array($flag, ['email_sent', 'verified', 'incorect_otp', 'failed_attempts', 'number_not_recognized'])) {
                    \Log::info($flag);
                    switch ($flag) {

                        case 'email_sent':
                            $this->ClientOtpSend($verifyClient, $from, $lng, 'meta');
                            WhatsAppBotActiveClientState::updateOrCreate(
                                [
                                    "from" => $from,
                                    "type" => "meta"
                                ],
                                [
                                    'menu_option' => 'email_sent',
                                    'lng' => $lng,
                                    "from" => $from,
                                ]
                            );
                            break;

                        case 'verified':
                            // Decode the `extra` field (or initialize it as an empty array if null or invalid)
                            $extra = $verifyClient->extra ? json_decode($verifyClient->extra, true) : [];

                            if (!is_array($extra)) {
                                $extra = [];
                            }

                            // Add or update the `from` phone in the `extra` field
                            $found = false;
                            foreach ($extra as &$entry) {
                                if ($entry['phone'] == $from) {
                                    $found = true; // `from` already exists in the `extra` array
                                    break;
                                }
                            }
                            unset($entry); // Unset reference to prevent side effects

                            if (!$found) {
                                // Add a new object with the `from` value
                                $extra[] = [
                                    "email" => "",
                                    "name"  => "",
                                    "phone" => $from,
                                ];
                            }
                            // Encode the updated `extra` array back to JSON
                            $verifyClient->extra = json_encode($extra);
                            $verifyClient->otp = null;
                            $verifyClient->otp_expiry = null;
                            $verifyClient->save();

                            // Send verified message
                            $nextMessage = $this->activeClientBotMessages['verified'][$lng];

                            $sid = $lng == "heb" ? "HX0d6d41473fae763d728c1f9a56a427f5" : "HXebdc48bc1b7e5ca4e8b32d868d778932";
                            $twi = $this->twilio->messages->create(
                                "whatsapp:+$from",
                                [
                                    "from" => $this->twilioWhatsappNumber,
                                    "contentSid" => $sid,
                                    "contentVariables" => json_encode([
                                        '1' => ($verifyClient->firstname ?? '' . ' ' . $verifyClient->lastname ?? '')
                                    ]),

                                ]
                            );
                            \Log::info($twi);

                            $personalizedMessage = str_replace(':client_name', $verifyClient->firstname . ' ' . $verifyClient->lastname, $nextMessage);
                            // sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

                            // Create webhook response
                            WebhookResponse::create([
                                'status' => 1,
                                'name' => 'whatsapp',
                                'message' => $twi->body ?? '',
                                'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                                'number' => $from,
                                'read' => 1,
                                'flex' => 'A',
                                'data'          => json_encode($twi->toArray())
                            ]);

                            $this->sendMainMenu($verifyClient, $from);

                            $client->delete();

                            break;

                        case 'incorect_otp':

                            $sid = $lng == "heb" ? "HX0e54f862ae4a74d0b29cd16f31c3289d" : "HXf11fa257dbd265ccb6ac155ef186016d";
                            $twi = $this->twilio->messages->create(
                                "whatsapp:+$from",
                                [
                                    "from" => $this->twilioWhatsappNumber,
                                    "contentSid" => $sid,

                                ]
                            );
                            \Log::info($twi);

                            $nextMessage = $this->activeClientBotMessages['incorect_otp'][$lng];
                            // sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

                            WhatsAppBotActiveClientState::updateOrCreate(
                                [
                                    "from" => $from,
                                    "type" => "meta"
                                ],
                                [
                                    'menu_option' => 'coustomer_service->email_sent',
                                    'lng' => $lng,
                                    "from" => $from,
                                ]
                            );

                            // Create webhook response
                            WebhookResponse::create([
                                'status' => 1,
                                'name' => 'whatsapp',
                                'message' => $twi->body ?? '',
                                'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                                'number' => $from,
                                'read' => 1,
                                'flex' => 'A',
                                'data'          => json_encode($twi->toArray())
                            ]);
                            break;

                        case 'failed_attempts':
                            \Log::info($client->id);
                            $sid = $lng == "heb" ? "HX7031ef0aca470c5c91cb8990d00c3533" : "HX5d496b41e236760e3532f84b6b620298";
                            $twi = $this->twilio->messages->create(
                                "whatsapp:+$from",
                                [
                                    "from" => $this->twilioWhatsappNumber,
                                    "contentSid" => $sid,

                                ]
                            );
                            \Log::info($twi);

                            $nextMessage = $this->activeClientBotMessages['failed_attempts'][$lng];
                            // sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

                            WhatsAppBotActiveClientState::updateOrCreate(
                                [
                                    "from" => $from,
                                    "type" => "meta"
                                ],
                                [
                                    "from" => $from,
                                    'menu_option' => 'failed_attempts'
                                ]
                            );

                            WebhookResponse::create([
                                'status' => 1,
                                'name' => 'whatsapp',
                                'message' => $twi->body ?? '',
                                'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                                'number' => $from,
                                'read' => 1,
                                'flex' => 'A',
                                'data'          => json_encode($twi->toArray())
                            ]);

                            break;

                        case 'number_not_recognized':

                            $sid = $lng == "heb" ? "HXa3a0276de1061b0fae83676e87eb5b0b" : "HX290e4da1875f795cb2f191e78af4c907";

                            $this->twilio->messages->create(
                                "whatsapp:+$from",
                                [
                                    "from" => $this->twilioWhatsappNumber,
                                    "contentSid" => $sid,

                                ]
                            );

                            WhatsAppBotActiveClientState::updateOrCreate(
                                [
                                    "from" => $from,
                                    "type" => "meta"
                                ],
                                [
                                    'menu_option' => 'not_recognized->enter_phone',
                                    'lng' => $lng,
                                    "from" => $from,
                                ]
                            );

                            WebhookResponse::create([
                                'status' => 1,
                                'name' => 'whatsapp',
                                'message' => $this->activeClientBotMessages['not_recognized'][$lng],
                                'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                                'number' => $from,
                                'read' => 1,
                                'flex' => 'A',
                            ]);

                            break;
                    }
                }

                if ($last_menu == 'need_more_help' && $listId == '1') {

                    $client->makeVisible('passcode');
                    event(new SendClientLogin($client->toArray()));

                    $msg = "Thank you! We’re resending your login details to your registered email address now. Please check your inbox shortly. 📧\nIs there anything else I can help you with today? (Yes or No) 👋";
                    if ($client->lng == 'heb') {
                        $msg = "תודה! אנחנו שולחים כעת את פרטי ההתחברות שלך למייל הרשום אצלנו. נא לבדוק את תיבת הדואר שלך בקרוב. 📧\nהאם יש משהו נוסף שבו אוכל לעזור לך היום? (כן או לא) 👋";
                    }

                    $sid = $client->lng == "heb" ? "HX7d9093ebdd01f1272475313e5f951e88" : "HX95020dd3e7519a1c26e3309367cc548a";

                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "contentSid" => $sid,

                        ]
                    );

                    if (!empty($msg)) {
                        WebhookResponse::create([
                            'status'        => 1,
                            'name'          => 'whatsapp',
                            'entry_id'      => $messageId,
                            'message'       => $twi->body ?? '',
                            'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                            'number'        => $from,
                            'flex'          => 'A',
                            'read'          => 1,
                            'data'          => json_encode($twi->toArray())
                        ]);
                    }

                    die("Send login details");
                } elseif ($last_menu == 'need_more_help' && $listId == '2') {

                    $sid = $client->lng == "heb" ? "HX73a583fb6f9682d11c2612ca36543f87" : "HX45fb8bd75c3f3a148bf190a57b289fac";

                    $msg = $client->lng == 'heb' ? "הבנתי! אנחנו מעבירים אותך כעת לתפריט שירותים חדשים או חידוש\nשירותים. נא לבחור באפשרות המתאימה לך ביותר. 🛠️\nהאם יש משהו נוסף שבו אוכל לעזור לך היום? (כן או לא) 👋"
                        : "Got it! We will redirect you to the menu for new services or renewals.\nPlease select the option that best suits your needs. 🛠️\n\nIs there anything else I can help you with today? (Yes or No) 👋";

                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "contentSid" => $sid,

                        ]
                    );

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray())
                    ]);

                    die('main_menu');
                }


                \Log::info(['message' => $message, 'last_menu' => $last_menu]);
                // Send about service message
                if ($last_menu == 'main_menu' && isset($menus[$last_menu][$message]['content'][$client->lng == 'heb' ? 'he' : 'en'])) {
                    $msg = $menus[$last_menu][$message]['content'][$client->lng == 'heb' ? 'he' : 'en'];
                    $title = $menus[$last_menu][$message]['title'];


                    if ($title == "Schedule an appointment for a quote") {
                        $nextMessage = $this->activeClientBotMessages['enter_phone'][$lng];
                        $sid = $lng == "heb" ? "HXed45297ce585bd31b49119c8788edfb4" : "HX741b8e40f723e2ca14474a54f6d82ec2";
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber,
                                // "contentSid" => $sid,
                                "body" => $msg

                            ]
                        );
                        \Log::info($twi);

                        // WhatsAppBotActiveClientState::updateOrCreate(
                        //     ["from" => $from],
                        //     [
                        //         'menu_option' => 'enter_phone',
                        //         'lng' => $lng,
                        //         "from" => $from,
                        //     ]
                        // );
                    } elseif ($title == "coustomer_service") {
                        $sid = $lng == "heb" ? "HXed45297ce585bd31b49119c8788edfb4" : "HX741b8e40f723e2ca14474a54f6d82ec2";
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "body" => $msg

                            ]
                        );
                        \Log::info($twi);

                        WhatsAppBotActiveClientState::updateOrCreate(
                            [
                                "from" => $from,
                                "type" => "meta"
                            ],
                            [
                                'menu_option' => 'enter_phone',
                                'lng' => $lng,
                                "from" => $from,
                            ]
                        );
                    } else if ($title == "Service Areas") {
                        $sid = $client->lng == "heb" ? "HXecc0eb8c4f810a84b1fc4f4d8642913c" : "HXc66fbd72c126251154ea831d3267ad31";
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,

                            ]
                        );
                    } elseif ($title == "Switch to a Human Representative - During Business Hours") {
                        $sid = $client->lng == "heb" ? "HX8ff855dc2d82288d5efdb46d3d4e004c" : "HX13483f5a762ab0293e36e6b97f3d0aa5";
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,

                            ]
                        );
                        \Log::info('Switch to a Human Representative - During Business Hours');
                    } else {
                        $sid = $client->lng == "heb" ? "HXeed50e16ed37bb4afd61bfb50b587aaa" : "HXc4ce692e05770052c200b582ddf22193";
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,

                            ]
                        );
                    }

                    \Log::info($twi->sid);
                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray())
                    ]);

                    switch ($message) {

                        case '1':
                            \Log::info('about_the_service');
                            WhatsAppBotClientState::updateOrCreate([
                                'client_id' => $client->id,
                                'type' => "meta"
                            ], [
                                'menu_option' => 'main_menu->about_the_service',
                                'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                            ]);
                            break;

                        case '2':
                            \Log::info('service_areas');

                            WhatsAppBotClientState::updateOrCreate([
                                'client_id' => $client->id,
                                'type' => "meta"
                            ], [
                                'menu_option' => 'main_menu->service_areas',
                                'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                            ]);

                            break;

                        case '3':
                            \Log::info('first_name');
                            WhatsAppBotClientState::updateOrCreate([
                                'client_id' => $client->id,
                                'type' => "meta"
                            ], [
                                'menu_option' => 'main_menu->appointment->first_name',
                                'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                            ]);
                            break;

                        case '4':
                            \Log::info("main_menu->customer_service");
                            WhatsAppBotClientState::updateOrCreate([
                                'client_id' => $client->id,
                                'type' => "meta"
                            ], [
                                'menu_option' => 'main_menu->customer_service',
                                'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                            ]);
                            break;

                        case '5':
                            WhatsAppBotClientState::updateOrCreate([
                                'client_id' => $client->id,
                                'type' => "meta"
                            ], [
                                'menu_option' => 'main_menu->human_representative',
                                'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                            ]);
                            break;
                    }
                    // Log::info('Send message: ' . $menus[$last_menu][$message]['title']);
                    die("Language switched to english");
                }

                if (
                    !in_array(strtolower($message), ['הפסק', 'stop']) &&
                    !in_array($flag, ['email_sent', 'verified', 'incorect_otp', 'failed_attempts', 'number_not_recognized']) &&
                    !in_array($last_menu, ['enter_phone', 'customer_service']) &&
                    (!$ButtonPayload || !$listId)
                ) {
                    $isIrrelevant = $client->lead_status->lead_status === "irrelevant";

                    $sid = $lng == "heb"
                        ? ($isIrrelevant ? "HX9794e89f810ed7a8daff3fe7cac06bb2" : "HXb4c7927e4693937b29866626ef467c65")
                        : ($isIrrelevant ? "HXe54b3fe0c45bafc90d71a41784b9c65e" : "HX20d3822e21360344c93be702ed178544");

                    $scheduleChange = ScheduleChange::create(
                        [
                            'user_type' => get_class($client),
                            'user_id' => $client->id,
                            'comments' => trim($message),
                            "reason" => $client->lng == "en" ? "Contact me urgently" : " צרו איתי קשר דחוף",
                        ]
                    );
                    $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));
                    // $teammsg = "שלום צוות, הלקוח " . "*" . $clientName . "*" . "  ביקש לבצע שינוי בסידור העבודה שלו לשבוע הבא. הבקשה שלו היא: \"" . '*' . $messageBody . '*' . "\" אנא בדקו וטפלו בהתאם. בברכה, צוות ברום סרוויס\n:comment_link";
                    $teammsg = "שלום צוות, לקוח " . "*" . $clientName . "*" . " ביקש. בקשתו היא: " . "*" . trim($message) . "*" . " אנא בדוק וטפל בהתאם. בברכה, צוות השירות של ברום\n:comment_link";
                    $personalizedMessage = str_replace(':comment_link', generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin'), $teammsg);

                    sendTeamWhatsappMessage(config('services.whatsapp_groups.urgent'), ['name' => '', 'message' => $personalizedMessage]);

                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "contentSid" => $sid,
                            // "body" => $follow_up_msg,

                        ]
                    );

                    // sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);
                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray()),
                    ]);
                }
            }
        }

        die('sent');
    }

    public function WhapifbWebhookCurrentLive(Request $request)
    {
        $data = [];
        $messageId = null;
        $message = null;
        $content = $request->getContent();
        $data = json_decode($content, true);
        $fromNumber = $data['messages'][0]['from'] ?? null;
        $number = explode('@', $data['messages'][0]['chat_id'] ?? '')[0];

        if (
            isset($data['messages']) &&
            isset($data['messages'][0]['from_me']) &&
            $data['messages'][0]['from_me'] == true &&
            $data['messages'][0]['source'] != "api"
        ) {
            if ($number) {
                // Store the number in the cache for 20 minutes
                Cache::put('cached_from_number', $number, now()->addMinutes(1));
            }
        }

        $messageId = $data['messages'][0]['id'] ?? null;

        if (!$messageId) {
            return response()->json(['status' => 'Invalid message data'], 400);
        }

        // Check if the messageId exists in cache and matches
        if ((Cache::get('whapi_processed_message_' . $messageId) === $messageId) || (Cache::get('cached_from_number') === $fromNumber)) {
            \Log::info('Already processed');
            return response()->json(['status' => 'Already processed'], 200);
        }

        // Store the messageId in the cache for 1 hour
        Cache::put('whapi_processed_message_' . $messageId, $messageId, now()->addHours(1));

        if (
            isset($data['messages']) &&
            isset($data['messages'][0]['from_me']) &&
            $data['messages'][0]['from_me'] == false
        ) {

            $client = null;
            $verifyClient = null;
            $menus = null;
            $responseClientState = null;
            $flag = null;


            $message_data = $data['messages'];
            if (Str::endsWith($message_data[0]['chat_id'], '@g.us')) {
                die("Group message");
            }
            $from = $message_data[0]['from'];

            $listId = isset($data['messages'][0]['reply']['list_reply']['id']) ? str_replace("ListV3:", "", $data['messages'][0]['reply']['list_reply']['id']) : "";
            $ButtonPayload = isset($data['messages'][0]['reply']['buttons_reply']['id']) ? str_replace("ButtonsV3:", "", $data['messages'][0]['reply']['buttons_reply']['id']) : "";
            $input = !empty($data['messages'][0]['text']['body']) ? trim($data['messages'][0]['text']['body']) : "";
            $lng = $this->detectLanguage($input);

            WebhookResponse::create([
                'status'        => 1,
                'name'          => 'whatsapp',
                'entry_id'      => $messageId,
                'message'       => !empty($input) ? $input : ($listId ?? $ButtonPayload),
                'from'          => config("services.whapi.whapi_number"),
                'number'        => $from,
                'read'          => 0,
                'flex'          => 'C',
                'data'          => json_encode($data)
            ]);

            $workerLead = WorkerLeads::where('phone', $from)->first();
            $user = User::where('phone', $from)
                ->where('status', '!=', 0)
                ->first();
            $client = Client::with('lead_status')->where('phone', $from)
                ->orWhereJsonContains('extra', [['phone' => $from]])
                ->first();

            $responseActiveClientState = WhatsAppBotActiveClientState::where('from', $from)->where('type', 'whapi')->first();

            if ($responseActiveClientState) {
                $lng = $responseActiveClientState->lng;
                $menuParts = explode('->', $responseActiveClientState->menu_option);
                $flag = end($menuParts);
            }

            if (!$client && !$user && !$workerLead) {
                $this->sendNewLeadMainMenu($lng, $from, 'whapi-lead-bot');
            } else if (
                $client && $client->lead_status &&
                in_array($client->lead_status->lead_status, ['active client', 'freeze client', 'pending client'])
            ) {
                $this->WhapifbActiveClientsWebhookCurrentLive($request);
            } else if ($user && (!$client || $client->lead_status->lead_status !== "active client")) {
                $controller = app(WorkerLeadWebhookController::class);
                return $controller->WhapifbActiveWorkersWebhookCurrentLive($request);
            }

            if ($client && $client->disable_notification == 1) {
                die('notification disabled');
            }

            if ($client) {
                $responseClientState = WhatsAppBotClientState::where('client_id', $client->id)->where('type', 'whapi')->first();
            }

            if ($responseClientState && $responseClientState->final && in_array($client->lead_status->lead_status, ['active client', 'freeze client', 'pending client'])) {
                $this->WhapifbActiveClientsWebhookCurrentLive($request);
                die('final');
            } else if ($responseClientState && $responseClientState->final && !in_array($client->lead_status->lead_status, ['active client', 'freeze client', 'pending client'])) {
                $responseClientState->final = 0;
                $responseClientState->save();

                $nextMessage = $this->botMessages['main_menu'][$lng];
                $result = sendWhatsappMessage($from, array('name' => '', 'message' => $nextMessage, 'list' => [], 'buttons' => []));
                StoreWebhookResponse($nextMessage, $from, $result, true);
            } else if ($responseClientState && $responseClientState->menu_option == 'new_main_menu') {

                $locations = [
                    '1' => ['en' => 'tel aviv', 'he' => 'תל אביב'],
                    '2' => ['en' => 'ramat gan', 'he' => 'רמת גן'],
                    '3' => ['en' => 'ramat hasharon', 'he' => 'רמת השרון'],
                    '4' => ['en' => 'herzliya', 'he' => 'הרצליה'],
                    '5' => ['en' => 'giv\'atayim', 'he' => 'גבעתיים'],
                    '6' => ['en' => 'kiryat ono', 'he' => 'קריית אונו'],
                    '7' => ['en' => 'kfar shmaryahu', 'he' => 'כפר שמריהו'],
                    '8' => ['en' => 'rishpon', 'he' => 'רשפון'],
                ];

                // Normalize input (trim, lowercase, multibyte-safe)
                $normalizedInput = mb_strtolower(trim($input));
                $matchFound = false;

                // Check if input is valid (by number or name)
                foreach ($locations as $key => $names) {
                    if (
                        $normalizedInput == $key ||
                        $normalizedInput == $names['en'] ||
                        $normalizedInput == $names['he']
                    ) {
                        $matchFound = true;
                        break;
                    }
                }

                if ($matchFound) {
                    $nextMessage = $this->botMessages['main_menu'][$lng];
                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $nextMessage, 'list' => [], 'buttons' => []));
                    StoreWebhookResponse($nextMessage, $from, $result, true);

                    WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                        'type' => 'whapi',
                    ], [
                        'menu_option' => 'main_menu',
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);

                    WhatsAppBotActiveClientState::updateOrCreate(
                        [
                            "from" => $from,
                            'type' => 'whapi',
                        ],
                        [
                            'menu_option' => 'main_menu',
                            'lng' => $client->lng,
                            "from" => $from,
                        ]
                    );

                    die("STOPPED");
                } else if ($input == '9') {
                    $client->lead_status->lead_status = 'irrelevant';
                    $client->lead_status->save();
                    event(new WhatsappNotificationEvent([
                        "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE_LEAD,
                        "notificationData" => [
                            'client' => $client->toArray(),
                        ]
                    ]));

                    event(new WhatsappNotificationEvent([
                        "type" => WhatsappMessageTemplateEnum::IRRELEVANT,
                        "notificationData" => [
                            'client' => $client->toArray(),
                        ]
                    ]));

                    WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                        'type' => 'whapi',
                    ], [
                        'menu_option' => 'main_menu',
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);

                    WhatsAppBotActiveClientState::updateOrCreate(
                        [
                            "from" => $from,
                            'type' => 'whapi',
                        ],
                        [
                            'menu_option' => 'main_menu',
                            'lng' => $client->lng,
                            "from" => $from,
                        ]
                    );
                    die("STOPPED");
                }
            } else if (
                $responseClientState && $responseClientState->menu_option == 'since_april' &&
                in_array($client->lead_status->lead_status, ['unanswered', 'unanswered final', 'uninterested', 'irrelevant']) &&
                !in_array(strtolower($input), ['stop', 'הפסק', 'לְהַפְסִיק'])
            ) {
                $client->lead_status->lead_status = "pending";
                $client->lead_status->updated_at = now();
                $client->lead_status->save();
                $client->created_at = now();
                $client->updated_at = now();
                $client->status = 0;
                $client->save();

                LeadActivity::create([
                    'client_id' => $client->id,
                    'created_date' => now(),
                    'status_changed_date' => now(),
                    'changes_status' => LeadStatusEnum::PENDING,
                    'reason' => "Changed by Bot",
                ]);
            }

            if ($client) {
                $result = WhatsappLastReply::where('phone', $from)
                    ->where('updated_at', '>=', Carbon::now()->subMinutes(15))
                    ->first();

                $client_menus = WhatsAppBotClientState::where('client_id', $client->id)->where('type', 'whapi')->first();

                if (in_array(strtolower($input), ['stop', 'הפסק', 'לְהַפְסִיק'])) {
                    $this->sendStopMessage($client, "whapi");
                }

                // Determine if the main menu should be sent
                if ((!$client_menus || $input == '9' || $input == "menu") || (($input == '0') && $flag != "email_sent")) {
                    $nextMessage = $this->botMessages['main_menu'][$lng];
                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $nextMessage, 'list' => [], 'buttons' => []));
                    StoreWebhookResponse($nextMessage, $from, $result, true);
                    \Log::info('Send main menu');
                    WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                        'type' => 'whapi',
                    ], [
                        'menu_option' => 'main_menu',
                        'language' => $client->lng == 'heb' ? 'he' : 'en',
                    ]);

                    Log::info('Send main menu');
                    die("STOPPED");
                }


                $menu_option = explode('->', $client_menus->menu_option);
                $last_menu = end($menu_option);

                $prev_step = null;
                if (count($menu_option) >= 2) {
                    $prev_step = $menu_option[count($menu_option) - 2];
                }
                if ($last_menu == 'customer_service' && $flag == 'enter_phone' && !empty($input)) {
                    \Log::info("anadr hu bhai");
                    $phone = $input;

                    // 1. Remove all special characters from the phone number
                    $phone = preg_replace('/[^0-9+]/', '', $phone);

                    // 2. If there's any string or invalid characters in the phone, extract the digits
                    if (preg_match('/\d+/', $phone, $matches)) {
                        $phone = $matches[0]; // Extract the digits

                        // Reapply rules on extracted phone number
                        // If the phone number starts with 0, add 972 and remove the first 0
                        if (strpos($phone, '0') === 0) {
                            $phone = '972' . substr($phone, 1);
                        }

                        // If the phone number starts with +, remove the +
                        if (strpos($phone, '+') === 0) {
                            $phone = substr($phone, 1);
                        }
                    }

                    $phoneLength = strlen($phone);
                    if (($phoneLength === 9 || $phoneLength === 10) && strpos($phone, '972') !== 0) {
                        $phone = '972' . $phone;
                    }

                    $verifyClient = Client::where('phone', $phone)
                        ->orWhereJsonContains('extra', [['phone' => $phone]])
                        ->first();

                    if ($verifyClient && !empty($phone)) {
                        $flag = 'email_sent';
                    } else {
                        $flag = 'number_not_recognized';
                    }
                } else if ($last_menu == 'customer_service' && $flag == 'email_sent' && $input == '1') {
                    $verifyClient = Client::where('phone', $responseActiveClientState->client_phone)
                        ->orWhereJsonContains('extra', [['phone' => $responseActiveClientState->client_phone]])
                        ->first();
                    $flag = 'email_sent';
                } else if ($last_menu == 'customer_service' && $flag == 'email_sent' && !empty($input)) {
                    $verifyClient = Client::where('phone', $responseActiveClientState->client_phone)
                        ->orWhereJsonContains('extra', [['phone' => $responseActiveClientState->client_phone]])
                        ->first();

                    if ($verifyClient && $verifyClient->otp == $input && $verifyClient->otp_expiry >= now()) {
                        $flag = 'verified';
                    } else {
                        if ($verifyClient) {
                            $verifyClient->attempts += 1;
                            $verifyClient->save();
                            $flag = $verifyClient->attempts >= 4 ? 'failed_attempts' : 'incorect_otp';
                        } else {
                            $flag = 'number_not_recognized'; // fallback if somehow verifyClient is still null
                        }
                    }
                } else if ($last_menu == 'customer_service' && $flag == 'failed_attempts') {
                    $flag = 'failed_attempts';
                }

                if (in_array($flag, ['email_sent', 'verified', 'incorect_otp', 'failed_attempts', 'number_not_recognized'])) {
                    switch ($flag) {

                        case 'email_sent':
                            $this->ClientOtpSend($verifyClient, $from, $lng, "whapi");
                            WhatsAppBotActiveClientState::updateOrCreate(
                                [
                                    "from" => $from,
                                    "type" => "whapi",
                                ],
                                [
                                    'menu_option' => 'email_sent',
                                    'lng' => $lng,
                                    "from" => $from,
                                ]
                            );
                            break;

                        case 'verified':
                            // Decode the `extra` field (or initialize it as an empty array if null or invalid)
                            $extra = $verifyClient->extra ? json_decode($verifyClient->extra, true) : [];

                            if (!is_array($extra)) {
                                $extra = [];
                            }

                            // Add or update the `from` phone in the `extra` field
                            $found = false;
                            foreach ($extra as &$entry) {
                                if ($entry['phone'] == $from) {
                                    $found = true; // `from` already exists in the `extra` array
                                    break;
                                }
                            }
                            unset($entry); // Unset reference to prevent side effects

                            if (!$found) {
                                // Add a new object with the `from` value
                                $extra[] = [
                                    "email" => "",
                                    "name"  => "",
                                    "phone" => $from,
                                ];
                            }
                            // Encode the updated `extra` array back to JSON
                            $verifyClient->extra = json_encode($extra);
                            $verifyClient->otp = null;
                            $verifyClient->otp_expiry = null;
                            $verifyClient->save();

                            // Send verified message
                            $nextMessage = $this->activeClientBotMessages['verified'][$lng];
                            $personalizedMessage = str_replace(':client_name', trim(trim($verifyClient->firstname ?? '') . ' ' . trim($verifyClient->lastname ?? '')), $nextMessage);

                            $result = sendWhatsappMessage($from, array('name' => '', 'message' => $personalizedMessage, 'list' => [], 'buttons' => []));
                            StoreWebhookResponse($personalizedMessage, $from, $result, true);

                            $this->sendWhapiMainMenu($verifyClient, $from);

                            $client->delete();

                            die("STOPPED");

                            break;

                        case 'incorect_otp':

                            $nextMessage = $this->activeClientBotMessages['incorect_otp'][$lng];
                            $result = sendWhatsappMessage($from, array('name' => '', 'message' => $nextMessage, 'list' => [], 'buttons' => []));
                            StoreWebhookResponse($nextMessage, $from, $result, true);

                            WhatsAppBotActiveClientState::updateOrCreate(
                                [
                                    "from" => $from,
                                    "type" => "whapi",
                                ],
                                [
                                    'menu_option' => 'coustomer_service->email_sent',
                                    'lng' => $lng,
                                    "from" => $from,
                                ]
                            );

                            break;

                        case 'failed_attempts':

                            $nextMessage = $this->activeClientBotMessages['failed_attempts'][$lng];
                            $result = sendWhatsappMessage($from, array('name' => '', 'message' => $nextMessage, 'list' => [], 'buttons' => []));
                            StoreWebhookResponse($nextMessage, $from, $result, true);

                            WhatsAppBotActiveClientState::updateOrCreate(
                                [
                                    "from" => $from,
                                    "type" => "whapi",
                                ],
                                [
                                    "from" => $from,
                                    'menu_option' => 'failed_attempts'
                                ]
                            );

                            break;

                        case 'number_not_recognized':

                            $msg = $lng == "heb" ? "שלום, לא הצלחנו לזהות את המספר שלך במערכת שלנו.\nאנא הזן שוב את מספר הטלפון הנכון או הקלד 'תפריט' כדי לחזור לתפריט הראשי בכל עת." : "Hello, we couldn’t recognize your number in our system.\nPlease re-enter the correct phone number or type 'Menu' to return to the main menu at any time.";

                            $result = sendWhatsappMessage($from, array('name' => '', 'message' => $msg, 'list' => [], 'buttons' => []));
                            StoreWebhookResponse($msg, $from, $result, true);

                            WhatsAppBotActiveClientState::updateOrCreate(
                                [
                                    "from" => $from,
                                    "type" => "whapi",
                                ],
                                [
                                    'menu_option' => 'not_recognized->enter_phone',
                                    'lng' => $lng,
                                    "from" => $from,
                                ]
                            );

                            break;
                    }
                }

                // Menus Array
                $menus = [
                    'main_menu' => [
                        '1' => [
                            'title' => "About the Service",
                            'content' => [
                                'en' => 'Broom Service - Room service for your 🏠.
We’re a professional cleaning company offering ✨ top-notch services for homes or apartments, available regularly or one-time, with no 🤯 hassle. Choose from 🧹 tailored packages like routine cleaning, or extras such as post-construction, pre-move, or window cleaning at any height.
Visit 🌐 www.broomservice.co.il for all services and details.
Our fixed prices per visit include everything—☕️ social benefits and travel—based on your package. We employ a skilled, permanent team led by a work manager. Pay by 💳 credit card monthly or post-visit, depending on your plan.
To get a quote, book a free, no-obligation visit from a supervisor who’ll assess your needs and provide a detailed estimate. Office hours: 🕖 Monday-Thursday, 8:00-14:00',
                                'he' => 'ברום סרוויס - שירות חדרים לביתכם 🏠.
חברת ניקיון מקצועית המספקת שירותי ניקיון ברמה גבוהה לבתים ודירות, קבוע או חד-פעמי, ללא התעסקות מיותרת 🧹. אנו מציעים חבילות מותאמות: ניקיון קבוע, ניקיון לאחר שיפוץ, לפני מעבר דירה, ניקוי חלונות בכל גובה ועוד ✨.
רטים באתר 🌐 www.broomservice.co.il. המחירים קבועים לביקור, כוללים הכל—תנאים סוציאליים ונסיעות 🍵—לפי החבילה. צוות קבוע ומיומן בפיקוח מנהל עבודה 👨🏻‍💼. תשלום בכרטיס אשראי בסוף החודש או לאחר ביקור 💳.
להצעת מחיר, תאמו פגישה חינם וללא התחייבות עם מפקח שיסייע בבחירת חבילה וישלח הצעה מפורטת 📝. שעות משרד: א-ה, 8:00-14:00 🕓. '
                            ]
                        ],
                        '2' => [
                            'title' => "Service Areas",
                            'content' => [
                                'en' => 'We provide service in the following areas: 🗺️
- Tel Aviv
- Ramat Gan
- Givatayim
- Kiryat Ono
- Ganei Tikva
- Ramat HaSharon
- Kfar Shmaryahu
- Rishpon
- Herzliya

To schedule an appointment for a quote press 3 or ☎️ 5 to speak with a representative.',
                                'he' => 'אנו מספקים שירות באזור 🗺️:
- תל אביב
- רמת גן
- גבעתיים
- קריית אונו
- גני תקווה
- רמת השרון
- כפר שמריהו
- רשפון
- הרצליה

לקביעת פגישה להצעת מחיר הקש 3 לשיחה עם נציג הקש ☎️ 5.'
                            ]
                        ],
                        '3' => [
                            'title' => "Schedule an appointment for a quote",
                            'content' => [
                                'en' => "To receive a quote, please send us messages with the following details\n\nPlease send your first name",
                                'he' => "כדי לקבל הצעת מחיר, אנא שלחו את הפרטים הבאים: 📝\n\nשם מלא",
                            ]
                        ],
                        '4' => [
                            'title' => "coustomer_service",
                            'content' => [
                                'en' => 'Hello! To verify your account, please enter the phone number you registered with our service.',
                                'he' => "שלום! לאימות החשבון שלך, אנא הזן את מספר הטלפון איתו נרשמת לשירות."
                            ]
                        ],
                        '5' => [
                            'title' => "Switch to a Human Representative - During Business Hours",
                            'content' => [
                                'en' => 'Dear customers, office hours are Monday-Thursday from 8:00 to 14:00.
If you contact us outside of business hours, a representative from our team will get back to you as soon as possible on the next business day, during business hours.
If you would like to speak to a human representative, please send a message with the word "Human Representative". 🙋🏻',
                                'he' => 'לקוחות יקרים, שעות הפעילות במשרד הן בימים א-ה בשעות 8:00-14:00.
במידה ופניתם מעבר לשעות הפעילות נציג מטעמנו יחזור אליכם בהקדם ביום העסקים הבא, בשעות הפעילות.
אם אתם מעוניינים לדבר עם נציג אנושי, אנא שלחו הודעה עם המילה "נציג אנושי". 🙋🏻',
                            ]
                        ]
                    ]
                ];


                // Send appointment message
                if (($last_menu == 'about_the_service' || $last_menu == 'service_areas') && in_array($input, ['3', '5'])) {
                    \Log::info('Send appointment message');
                    $last_menu = 'main_menu';
                }

                // Greeting message
                if (in_array($last_menu, ['need_more_help', 'cancel_one_time']) && (in_array(strtolower($input), ['no', 'לא']))) {
                    $msg = ($client->lng == 'heb' ? `מקווה שעזרתי! 🤗` : 'I hope I helped! 🤗');
                    $responseClientState = WhatsAppBotClientState::where('client_id', $client->id)->where("type", "whapi")->first();

                    if ($responseClientState) {
                        $responseClientState->menu_option = 'main_menu';
                        $responseClientState->final = true;
                        $responseClientState->save();
                    }

                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $msg, 'list' => [], 'buttons' => []));
                    StoreWebhookResponse($msg, $from, $result, true);

                    die("Final message");
                } else if (
                    (in_array($last_menu, ['need_more_help']) && in_array(strtolower($input), ['yes', 'כן'])) ||
                    (($prev_step == 'customer_service') && !in_array($input, ['3', '5']))
                ) {
                    $nextMessage = $this->botMessages['main_menu'][$lng];
                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $nextMessage, 'list' => [], 'buttons' => []));
                    StoreWebhookResponse($nextMessage, $from, $result, true);

                    $responseClientState = WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                        'type' => 'whapi',
                    ], [
                        'menu_option' => 'main_menu',
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);
                    die("Send main menu");
                } else if (
                    $last_menu == 'cancel_one_time' &&
                    (str_contains(strtolower($input), 'yes') || str_contains($input, 'כן'))
                ) {
                    $msg = ($client->lng == 'heb' ? `נציג מהצוות שלנו ייצור איתך קשר בהקדם.` : 'A representative from our team will contact you shortly.');
                    $responseClientState = WhatsAppBotClientState::where('client_id', $client->id)->where('type', 'whapi')->delete();

                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $msg, 'list' => [], 'buttons' => []));
                    StoreWebhookResponse($msg, $from, $result, true);

                    die("Final message");
                }

                if ($last_menu == 'main_menu' && $input == '6') {
                    $lng = $client->lng;
                    if ($client->lng == 'en') {
                        $client->lng = 'heb';
                    } else {
                        $client->lng = 'en';
                    }
                    $client->save();

                    $nextMessage = $this->botMessages['main_menu']["en"];
                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $nextMessage, 'list' => [], 'buttons' => []));
                    StoreWebhookResponse($nextMessage, $from, $result, true);

                    $responseClientState =  WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                        'type' => 'whapi',
                    ], [
                        'menu_option' => 'main_menu',
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);
                    Log::info('Language switched to english');
                    die("Language switched to english");
                } else if ($last_menu == 'main_menu' && $input == '7') {
                    $lng = $client->lng;
                    if ($client->lng == 'heb') {
                        $client->lng = 'en';
                    } else {
                        $client->lng = 'heb';
                    }
                    $client->save();

                    $nextMessage = $this->botMessages['main_menu']["heb"];
                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $nextMessage, 'list' => [], 'buttons' => []));
                    StoreWebhookResponse($nextMessage, $from, $result, true);

                    $responseClientState =  WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                        'type' => 'whapi',
                    ], [
                        'menu_option' => 'main_menu',
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);
                    Log::info('Language switched to english');
                    die("Language switched to english");
                } else if ($last_menu == 'human_representative') {
                    $msg = null;
                    \Log::info('Human representative');
                    if (strtolower($input) == 'human representative' || $input == 'נציג אנושי') {

                        event(new WhatsappNotificationEvent([
                            "type" => WhatsappMessageTemplateEnum::LEAD_NEED_HUMAN_REPRESENTATIVE,
                            "notificationData" => [
                                'client' => $client->toArray()
                            ]
                        ]));

                        if ($client->lng == 'heb') {
                            $msg = 'נציג מטעמנו יצור קשר בהקדם. האם יש משהו נוסף שאוכל לעזור לך בו היום? (כן או לא) 👋';
                        } else {
                            $msg = 'A representative from our team will contact you shortly. Is there anything else I can help you with today? (Yes or No) 👋';
                        }

                        $state = "main_menu->human_representative->need_more_help";
                    } else {

                        $scheduleChange = ScheduleChange::create(
                            [
                                'user_type' => get_class($client),
                                'user_id' => $client->id,
                                'comments' => trim($input),
                                "reason" => $client->lng == "en" ? "Contact me urgently" : " צרו איתי קשר דחוף",
                            ]
                        );
                        $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));
                        // $teammsg = "שלום צוות, הלקוח " . "*" . $clientName . "*" . "  ביקש לבצע שינוי בסידור העבודה שלו לשבוע הבא. הבקשה שלו היא: \"" . '*' . $messageBody . '*' . "\" אנא בדקו וטפלו בהתאם. בברכה, צוות ברום סרוויס\n:comment_link";
                        $teammsg = "שלום צוות, לקוח " . "*" . $clientName . "*" . " ביקש. בקשתו היא: " . "*" . trim($input) . "*" . " אנא בדוק וטפל בהתאם. בברכה, צוות השירות של ברום\n:comment_link";
                        $personalizedMessage = str_replace(':comment_link', generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin'), $teammsg);

                        sendTeamWhatsappMessage(config('services.whatsapp_groups.urgent'), ['name' => '', 'message' => $personalizedMessage]);

                        $msg = $lng == "heb" ? "ההודעה שלך התקבלה ותועבר לצוות \n.נחזור אליך בהקדם האפשרי\n\nאם יש לכם שאלה או בקשה שלא מופיעה ברשימה, הקלידו 'תפריט' כדי לחזור לתפריט הראשי בכל עת." : "Your message has been received and will be forwarded to our team.\nWe will get back to you as soon as possible.\n\nIf you have a question or request not listed, type 'Menu' to return to the main menu at any time.";

                        $state = "main_menu->human_representative";
                    }


                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $msg, 'list' => [], 'buttons' => []));
                    StoreWebhookResponse($msg, $from, $result, true);

                    $responseClientState = WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                        "type" => "whapi"
                    ], [
                        'menu_option' => $state,
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);

                    $message = null;
                    die("Human representative");
                } else if ($last_menu == 'first_name') {
                    // Store first name
                    $client->firstname = trim($input);
                    $client->save();

                    // Ask for last name
                    $msg = $client->lng == 'heb'
                        ? 'מה שם המשפחה שלך?'
                        : "Please send your last name.";


                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $msg, 'list' => [], 'buttons' => []));
                    StoreWebhookResponse($msg, $from, $result, true);

                    // Update client state to expect the last name
                    WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                        "type" => "whapi"
                    ], [
                        'menu_option' => 'main_menu->appointment->last_name',
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);

                    die("Store first name");
                } else if ($last_menu == 'last_name') {
                    // Store last name
                    $client->lastname = trim($input);
                    $client->save();

                    // Ask for full address
                    $msg = $client->lng == 'heb'
                        ? 'כתובת מלאה (רחוב, מספר ועיר בלבד)'
                        : "Please send your full address (Only street, number, and city).";


                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $msg, 'list' => [], 'buttons' => []));
                    StoreWebhookResponse($msg, $from, $result, true);

                    // Update client state to expect the full address
                    WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                        'type' => "whapi"
                    ], [
                        'menu_option' => 'main_menu->appointment->full_address',
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);

                    die("Store last name");
                } else if ($last_menu == 'full_address') {

                    $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                        'address' => $input,
                        'key' => config('services.google.map_key'),
                        'language' => $client->lng == 'heb' ? 'he' : 'en'
                    ]);

                    if ($response->successful()) {
                        $data = $response->object();
                        $result = $data->results[0] ?? null;
                        if ($result) {
                            $zipcode = null;
                            $city = null;

                            foreach ($result->address_components ?? [] as $key => $address_component) {
                                if (in_array('locality', $address_component->types)) {
                                    $city = $address_component->long_name;
                                }

                                if (in_array('postal_code', $address_component->types)) {
                                    $zipcode = $address_component->long_name;
                                }
                            }

                            $client->update([
                                'verify_last_address_with_wa_bot' => [
                                    'address_name' => $result->formatted_address ?? null,
                                    'city' => $city ?? NULL,
                                    'floor' => NULL,
                                    'apt_no' => null,
                                    'entrence_code' => null,
                                    'zipcode' => $zipcode ?? NULL,
                                    'geo_address' => $result->formatted_address ?? NULL,
                                    'latitude' => $result->geometry->location->lat ?? NULL,
                                    'longitude' => $result->geometry->location->lng ?? NULL,
                                ]
                            ]);

                            $msg = null;
                            if ($client->lng == 'heb') {
                                $msg = 'אנא אשר אם הכתובת הבאה נכונה על ידי תשובה כן או לא:' . $result->formatted_address;
                            } else {
                                $msg = "Please confirm if this address is correct by replying with Yes or No:\n\n" . $result->formatted_address;
                            }
                            $responseClientState = WhatsAppBotClientState::updateOrCreate([
                                'client_id' => $client->id,
                                'type' => "whapi"
                            ], [
                                'menu_option' => 'main_menu->appointment->full_address->verify_address',
                                'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                            ]);

                            $result = sendWhatsappMessage($from, array('name' => '', 'message' => $msg, 'list' => [], 'buttons' => []));
                            StoreWebhookResponse($msg, $from, $result, true);

                            die("Verify address");
                        } else {
                            $client->update([
                                'verify_last_address_with_wa_bot' => NULL
                            ]);
                        }
                    } else {
                        $client->update([
                            'verify_last_address_with_wa_bot' => NULL
                        ]);
                    }
                } else if ($last_menu == 'verify_address') {
                    if (in_array(strtolower($input), ['yes', 'כן'])) {
                        $lastEnteredAddress = $client->verify_last_address_with_wa_bot;

                        $propertyAddress = $client->property_addresses()
                            ->where('geo_address', $lastEnteredAddress['geo_address'])
                            ->first();

                        if (!$propertyAddress) {
                            $propertyAddress = ClientPropertyAddress::create(
                                [
                                    'client_id' => $client->id,
                                    'address_name' => $lastEnteredAddress['address_name'],
                                    'city' => $lastEnteredAddress['city'],
                                    'floor' => $lastEnteredAddress['floor'],
                                    'apt_no' => $lastEnteredAddress['apt_no'],
                                    'entrence_code' => $lastEnteredAddress['entrence_code'],
                                    'zipcode' => $lastEnteredAddress['zipcode'],
                                    'geo_address' => $lastEnteredAddress['geo_address'],
                                    'latitude' => $lastEnteredAddress['latitude'],
                                    'longitude' => $lastEnteredAddress['longitude'],
                                ]
                            );
                        }

                        $lastEnteredAddress['id'] = $propertyAddress->id;

                        $client->update([
                            'verify_last_address_with_wa_bot' => $lastEnteredAddress
                        ]);

                        $msg = null;
                        if ($client->lng == 'heb') {
                            $msg = 'באיזו קומה נמצא הנכס שלך? (אם אין השב אין)';
                        } else {
                            $msg = "What is the floor of your address? (If none then type x)";
                        }


                        $result = sendWhatsappMessage($from, array('name' => '', 'message' => $msg, 'list' => [], 'buttons' => []));
                        StoreWebhookResponse($msg, $from, $result, true);

                        $responseClientState = WhatsAppBotClientState::updateOrCreate([
                            'client_id' => $client->id,
                            'type' => "whapi"
                        ], [
                            'menu_option' => 'main_menu->appointment->full_address->floor',
                            'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                        ]);

                        die("Store address");
                    } else {
                        $client->update([
                            'verify_last_address_with_wa_bot' => NULL
                        ]);

                        $msg = null;
                        if ($client->lng == 'heb') {
                            $msg = 'אנא הזן את כתובתך בפירוט רב יותר.';
                        } else {
                            $msg = "Please provide more details for your address.";
                        }


                        $result = sendWhatsappMessage($from, array('name' => '', 'message' => $msg, 'list' => [], 'buttons' => []));
                        StoreWebhookResponse($msg, $from, $result, true);

                        $responseClientState = WhatsAppBotClientState::updateOrCreate([
                            'client_id' => $client->id,
                            'type' => "whapi"
                        ], [
                            'menu_option' => 'main_menu->appointment->full_address',
                            'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                        ]);

                        die("Re-enter address");
                    }
                } else if ($last_menu == 'floor') {
                    $lastEnteredAddress = $client->verify_last_address_with_wa_bot;

                    $propertyAddress = $client->property_addresses()
                        ->where('id', $lastEnteredAddress['id'])
                        ->first();

                    if ($propertyAddress) {
                        if (
                            ($client->lng == 'heb' && $input == 'אין') ||
                            ($client->lng == 'en' && strtolower($input) == 'x')
                        ) {
                            $propertyAddress->update([
                                'floor' => NULL
                            ]);
                        } else {
                            $propertyAddress->update([
                                'floor' => $input
                            ]);
                        }
                    }

                    if ($client->lng == 'heb') {
                        $msg = 'מהו מספר הדירה (אם אין השב אין)';
                    } else {
                        $msg = "What is the apartment number of your address? (If none then type x)";
                    }


                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $msg, 'list' => [], 'buttons' => []));
                    StoreWebhookResponse($msg, $from, $result, true);

                    $responseClientState = WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                        'type' => "whapi"
                    ], [
                        'menu_option' => 'main_menu->appointment->full_address->apartment_number',
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);

                    die("Address floor");
                } else if ($last_menu == 'apartment_number') {
                    $lastEnteredAddress = $client->verify_last_address_with_wa_bot;

                    $propertyAddress = $client->property_addresses()
                        ->where('id', $lastEnteredAddress['id'])
                        ->first();

                    if ($propertyAddress) {
                        if (
                            ($client->lng == 'heb' && $input == 'אין') ||
                            ($client->lng == 'en' && strtolower($input) == 'x')
                        ) {
                            $propertyAddress->update([
                                'apt_no' => NULL
                            ]);
                        } else {
                            $propertyAddress->update([
                                'apt_no' => $input
                            ]);
                        }
                    }

                    if ($client->lng == 'heb') {
                        $msg = 'אנא ספק את פרטי החניה עבור הכתובת הנתונה (אם אין השב אין).';
                    } else {
                        $msg = "Please provide the parking details for the given address (If none then type x).";
                    }

                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $msg, 'list' => [], 'buttons' => []));
                    StoreWebhookResponse($msg, $from, $result, true);

                    $responseClientState = WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                        'type' => "whapi"
                    ], [
                        'menu_option' => 'main_menu->appointment->full_address->parking',
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);

                    die("Address Apt no.");
                } else if ($last_menu == 'parking') {
                    $lastEnteredAddress = $client->verify_last_address_with_wa_bot;

                    $propertyAddress = $client->property_addresses()
                        ->where('id', $lastEnteredAddress['id'])
                        ->first();

                    if ($propertyAddress) {
                        $propertyAddress->update([
                            'parking' => $message
                        ]);

                        $client->update([
                            'verify_last_address_with_wa_bot' => NULL
                        ]);

                        $msg = null;
                        if ($client->lng == 'heb') {
                            $msg = 'אנא ספק את כתובת האימייל שלך.';
                        } else {
                            $msg = "Please provide your email address.";
                        }


                        $result = sendWhatsappMessage($from, array('name' => '', 'message' => $msg, 'list' => [], 'buttons' => []));
                        StoreWebhookResponse($msg, $from, $result, true);

                        $responseClientState = WhatsAppBotClientState::updateOrCreate([
                            'client_id' => $client->id,
                            'type' => "whapi"
                        ], [
                            'menu_option' => 'main_menu->appointment->full_address->email',
                            'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                        ]);

                        die("Store address parking");
                    } else {
                        $client->update([
                            'verify_last_address_with_wa_bot' => NULL
                        ]);

                        $msg = null;
                        if ($client->lng == 'heb') {
                            $msg = 'הכתובת הנתונה לא נמצאה. אנא ספק כתובת חלופית.';
                        } else {
                            $msg = "The given address was not found. Please provide an alternative address.";
                        }


                        $result = sendWhatsappMessage($from, array('name' => '', 'message' => $msg, 'list' => [], 'buttons' => []));
                        StoreWebhookResponse($msg, $from, $result, true);

                        $responseClientState = WhatsAppBotClientState::updateOrCreate([
                            'client_id' => $client->id,
                            'type' => "whapi"
                        ], [
                            'menu_option' => 'main_menu->appointment->full_address',
                            'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                        ]);

                        die("Re-enter address");
                    }
                } else if ($last_menu == 'email') {
                    $msg = null;
                    $sid = null;
                    $num = null;
                    $link = null;
                    if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
                        $email_exists = Client::where('email', $input)->where('id', '!=', $client->id)->exists();
                        if ($email_exists) {
                            $msg = ($client->lng == 'heb' ? `הכתובת '` . $input . `' כבר קיימת. נא הזן כתובת דוא"ל אחרת.` : '\'' . $input . '\' is already taken. Please enter a different email address.');
                            $num = 1;
                        } else {
                            $client->email = trim($input);
                            $client->save();
                            $client->refresh();

                            $nextAvailableSlot = $this->nextAvailableMeetingSlot();
                            if ($nextAvailableSlot) {
                                $address = $client->property_addresses()->first();

                                $scheduleData = [
                                    'address_id'    => $address->id,
                                    'booking_status'    => 'pending',
                                    'client_id'     => $client->id,
                                    'meet_via'      => 'on-site',
                                    'purpose'       => 'Price offer',
                                    // 'start_date'    =>  $nextAvailableSlot['date'],
                                    // 'start_time_standard_format' =>  $nextAvailableSlot['start_time'],
                                    'team_id'       => $nextAvailableSlot['team_member_id']
                                ];

                                // $scheduleData['start_time'] = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d') . ' ' . $nextAvailableSlot['start_time'])->format('h:i A');
                                // $scheduleData['end_time'] = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d') . ' ' . $nextAvailableSlot['start_time'])->addMinutes(30)->format('h:i A');

                                $schedule = Schedule::create($scheduleData);

                                $client->lead_status()->updateOrCreate(
                                    [],
                                    ['lead_status' => LeadStatusEnum::POTENTIAL]
                                );

                                LeadActivity::create([
                                    'client_id' => $client->id,
                                    'created_date' => now(),
                                    'status_changed_date' => now(),
                                    'changes_status' => LeadStatusEnum::POTENTIAL,
                                    'reason' => "Changed by Bot",
                                ]);

                                $client->status = 1;
                                $client->save();

                                event(new ClientLeadStatusChanged($client, LeadStatusEnum::POTENTIAL));

                                $googleAccessToken = Setting::query()
                                    ->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)
                                    ->value('value');

                                if ($googleAccessToken) {
                                    $schedule->load(['client', 'team', 'propertyAddress']);

                                    try {
                                        // Initializes Google Client object
                                        $googleClient = $this->getClient();

                                        $this->saveGoogleCalendarEvent($schedule);

                                        // $this->sendMeetingMail($schedule);
                                        SendMeetingMailJob::dispatch($schedule);
                                    } catch (\Throwable $th) {
                                        //throw $th;
                                    }
                                }

                                Notification::create([
                                    'user_id' => $schedule->client_id,
                                    'user_type' => get_class($client),
                                    'type' => NotificationTypeEnum::SENT_MEETING,
                                    'meet_id' => $schedule->id,
                                    'status' => $schedule->booking_status
                                ]);

                                $link = generateShortUrl(url("meeting-status/" . base64_encode($schedule->id) . "/reschedule"), 'client');
                                if ($client->lng == 'heb') {
                                    $msg = "$link\n\nאנא בחר/י זמן לפגישה באמצעות הקישור למטה. יש משהו נוסף שבו אני יכול/ה לעזור לך היום? 😊";
                                } else {
                                    $msg = "Please choose a time slot for your appointment using the link below. Is there anything else I can help you with today? (Yes or No) 👋\n\n$link";
                                }
                                $num = 2;
                            } else {
                                if ($client->lng == 'heb') {
                                    $msg = "נציג מטעמנו ייצור עמכם קשר בהקדם. \n\nהאם יש משהו נוסף שאני יכול לעזור לך בו היום? (כן או לא) 👋";
                                } else {
                                    $msg = "A representative from our team will contact you shortly.\n\nIs there anything else I can help you with today? (Yes or No) 👋";
                                }

                                event(new WhatsappNotificationEvent([
                                    "type" => WhatsappMessageTemplateEnum::NO_SLOT_AVAIL_CALLBACK,
                                    "notificationData" => [
                                        'client' => $client->toArray()
                                    ]
                                ]));
                                $num = 3;
                            }

                            $responseClientState =  WhatsAppBotClientState::updateOrCreate([
                                'client_id' => $client->id,
                                "type" => "whapi"
                            ], [
                                'menu_option' => 'main_menu->appointment->need_more_help',
                                'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                            ]);
                        }
                    } else {
                        $msg = ($client->lng == 'heb' ? `כתובת הדוא"ל '` . $message . `' לא תקינה. בבקשה נסה שוב.` : 'The email address \'' . $message . '\' is considered invalid. Please try again.');
                        $num = 4;
                    }

                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $msg, 'list' => [], 'buttons' => []));
                    StoreWebhookResponse($msg, $from, $result, true);

                    die("Store email");
                } else if ($last_menu == 'need_more_help' && $input == '1') {

                    $client->makeVisible('passcode');
                    event(new SendClientLogin($client->toArray()));

                    $msg = "Thank you! We’re resending your login details to your registered email address now. Please check your inbox shortly. 📧\nIs there anything else I can help you with today? (Yes or No) 👋";
                    if ($client->lng == 'heb') {
                        $msg = "תודה! אנחנו שולחים כעת את פרטי ההתחברות שלך למייל הרשום אצלנו. נא לבדוק את תיבת הדואר שלך בקרוב. 📧\nהאם יש משהו נוסף שבו אוכל לעזור לך היום? (כן או לא) 👋";
                    }

                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $msg, 'list' => [], 'buttons' => []));
                    StoreWebhookResponse($msg, $from, $result, true);

                    die("Send login details");
                } else if ($last_menu == 'need_more_help' && $listId == '2') {

                    $sid = $client->lng == "heb" ? "HX73a583fb6f9682d11c2612ca36543f87" : "HX45fb8bd75c3f3a148bf190a57b289fac";

                    $msg = $client->lng == 'heb' ? "הבנתי! אנחנו מעבירים אותך כעת לתפריט שירותים חדשים או חידוש\nשירותים. נא לבחור באפשרות המתאימה לך ביותר. 🛠️\nהאם יש משהו נוסף שבו אוכל לעזור לך היום? (כן או לא) 👋"
                        : "Got it! We will redirect you to the menu for new services or renewals.\nPlease select the option that best suits your needs. 🛠️\n\nIs there anything else I can help you with today? (Yes or No) 👋";

                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $msg, 'list' => [], 'buttons' => []));
                    StoreWebhookResponse($msg, $from, $result, true);

                    die('main_menu');
                }

                // Send about service message
                if ($last_menu == 'main_menu' && ctype_digit($input)) {
                    $msg = $menus[$last_menu][$input]['content'][$client->lng == 'heb' ? 'he' : 'en'];

                    if ($input == "3") {
                        $result = sendWhatsappMessage($from, array('name' => '', 'message' => $msg, 'list' => [], 'buttons' => []));
                        StoreWebhookResponse($msg, $from, $result, true);
                    } else if ($input == "4") {
                        $result = sendWhatsappMessage($from, array('name' => '', 'message' => $msg, 'list' => [], 'buttons' => []));
                        StoreWebhookResponse($msg, $from, $result, true);

                        WhatsAppBotActiveClientState::updateOrCreate(
                            [
                                "from" => $from,
                                "type" => "whapi",
                            ],
                            [
                                'menu_option' => 'enter_phone',
                                'lng' => $lng,
                                "from" => $from,
                            ]
                        );
                    } else if ($input == "2") {
                        $result = sendWhatsappMessage($from, array('name' => '', 'message' => $msg, 'list' => [], 'buttons' => []));
                        StoreWebhookResponse($msg, $from, $result, true);
                    } else if ($input == "5") {
                        $result = sendWhatsappMessage($from, array('name' => '', 'message' => $msg, 'list' => [], 'buttons' => []));
                        StoreWebhookResponse($msg, $from, $result, true);
                    } else if ($input == "1") {
                        $result = sendWhatsappMessage($from, array('name' => '', 'message' => $msg, 'list' => [], 'buttons' => []));
                        StoreWebhookResponse($msg, $from, $result, true);
                    }

                    switch ($input) {

                        case '1':
                            WhatsAppBotClientState::updateOrCreate([
                                'client_id' => $client->id,
                                "type" => "whapi",
                            ], [
                                'menu_option' => 'main_menu->about_the_service',
                                'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                            ]);
                            break;

                        case '2':
                            WhatsAppBotClientState::updateOrCreate([
                                'client_id' => $client->id,
                                "type" => "whapi",
                            ], [
                                'menu_option' => 'main_menu->service_areas',
                                'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                            ]);

                            break;

                        case '3':
                            WhatsAppBotClientState::updateOrCreate([
                                'client_id' => $client->id,
                                "type" => "whapi",
                            ], [
                                'menu_option' => 'main_menu->appointment->first_name',
                                'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                            ]);

                            break;

                        case '4':
                            WhatsAppBotClientState::updateOrCreate([
                                'client_id' => $client->id,
                                "type" => "whapi",
                            ], [
                                'menu_option' => 'main_menu->customer_service',
                                'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                            ]);
                            break;

                        case '5':
                            WhatsAppBotClientState::updateOrCreate([
                                'client_id' => $client->id,
                                "type" => "whapi",
                            ], [
                                'menu_option' => 'main_menu->human_representative',
                                'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                            ]);
                            break;
                    }
                    // Log::info('Send message: ' . $menus[$last_menu][$message]['title']);
                    die("Language switched to english");
                }
            }
        }

        die('sent');
    }

    public function MetaWhapifbActiveClientsWebhookLive(Request $request)
    {
        // Check if request content is JSON (likely from Whapi)
        $content = $request->getContent();
        if ($this->isJson($content)) {
            \Log::info('Webhook received from Whapi');
            $this->WhapifbActiveClientsWebhookCurrentLive($request);
        } else {
            \Log::info('Webhook received from Twilio');
            // Otherwise it's form-data (likely from Twilio)
            $this->fbActiveClientsWebhookCurrentLive($request);
        }
    }

    public function WhapifbActiveClientsWebhookCurrentLive(Request $request)
    {
        \Log::info('Webhook received');
        $content = $request->getContent();
        $data = json_decode($content, true);
        $chatId = $data['messages'][0]['chat_id'] ?? null;

        // if ($chatId != "918000318833@s.whatsapp.net" || !empty($data['statuses'][0]['id'])) {
        //     return response()->json(['status' => 'Invalid message data'], 400);
        // }

        $messageId = $data['messages'][0]['id'] ?? null;

        if (!$messageId) {
            return response()->json(['status' => 'Invalid message data'], 400);
        }

        // Check if the messageId exists in cache and matches
        if (Cache::get('active_client_processed_message_' . $messageId) == $messageId) {
            \Log::info('Already processed');
            return response()->json(['status' => 'Already processed'], 200);
        }

        // Store the messageId in the cache for 1 hour
        Cache::put('active_client_processed_message_' . $messageId, $messageId, now()->addHours(1));

        if (
            isset($data['messages']) &&
            isset($data['messages'][0]['from_me']) &&
            $data['messages'][0]['from_me'] == false
        ) {
            \Log::info($data);
            $list = [];
            $buttons = [];

            $from = $data['messages'][0]['from'];

            $isMonday = now()->isMonday();

            $workerLead = WorkerLeads::where('phone', $from)->first();
            if ($workerLead) {
                \Log::info('Worker lead already exists');
            }

            $user = User::where('phone', $from)
                ->where('status', '!=', 0)
                ->first();
            if ($user) {
                \Log::info('User already exists');
            }
            $client = Client::where('phone', $from)
                ->orWhereJsonContains('extra', [['phone' => $from]])
                ->first();

            $msgStatus = null;
            $input = null;

            if ($client) {
                $listId = isset($data['messages'][0]['reply']['list_reply']['id']) ? str_replace("ListV3:", "", $data['messages'][0]['reply']['list_reply']['id']) : "";
                $ButtonPayload = isset($data['messages'][0]['reply']['buttons_reply']['id']) ? str_replace("ButtonsV3:", "", $data['messages'][0]['reply']['buttons_reply']['id']) : "";
                $input = $data['messages'][0]['text']['body'] ?? $listId ?? $ButtonPayload ?? "";
            }

            if ($client && $client->lead_status->lead_status != LeadStatusEnum::ACTIVE_CLIENT) {
                die('Client already active');
            }

            $lng = $client->lng ?? $this->detectLanguage($input);
            // if ($user || $workerLead) {
            //     die('Worker or worker lead found');
            // }

            if ($client && $client->disable_notification == 1) {
                \Log::info('Client disabled notification');
                die('Client disabled notification');
            }

            if ($isMonday && $client && $client->stop_last_message != 1 && !in_array(strtolower(trim($input)), ["stop", "הפסק"])) {
                if ($client->stop_last_message == 0 && in_array(strtolower(trim($input)), ["menu", "תפריט"])) {
                    $client->stop_last_message = 1;
                    $client->save();
                }
                // else {
                //     \Log::info('Monday msg reply is pending');
                //     $this->activeClientsMonday($request);
                //     die('Monday msg reply is pending.');
                // }
            }
            $clientMessageStatus = WhatsAppBotActiveClientState::where('from', $from)
                ->where('type', 'whapi')
                ->first();

            $last_menu = null;
            $send_menu = null;
            if ($clientMessageStatus) {
                $lng = $clientMessageStatus->lng ?? 'heb';
                $menu_option = explode('->', $clientMessageStatus->menu_option);
                $last_menu = end($menu_option);
            }

            if (in_array(strtolower(trim($input)), ["stop", "הפסק"])) {
                $client->disable_notification = 1;
                $client->save();
                $send_menu = 'stop';
            } else if (empty($last_menu) || in_array(strtolower(trim($input)), ["menu", "תפריט"])) {
                if (!$client && !$user && !$workerLead) {
                    $send_menu = 'not_recognized';
                } else {
                    \Log::info('Client menu');
                    $send_menu = 'main_menu';
                }
            } else if ($last_menu == 'main_menu' && $input == '1') {
                $send_menu = 'urgent_contact';
            } else if ($last_menu == 'main_menu' && $input == '2') {
                $send_menu = 'service_schedule';
            } else if ($last_menu == 'main_menu' && $input == '3') {
                $send_menu = 'request_new_qoute';
            } else if ($last_menu == 'main_menu' && $input == '4') {
                $send_menu = 'invoice_account';
            } else if ($last_menu == 'main_menu' && $input == '5') {
                $send_menu = 'change_update_schedule';
            } else if ($last_menu == 'main_menu' && $input == '6') {
                $send_menu = 'access_portal';
            } else if ($last_menu == 'urgent_contact' && !empty($input)) {
                $send_menu = 'thankyou';
            } else if ($last_menu == 'service_schedule' && $input == '5') {
                $send_menu = 'change_update_schedule';
            } else if ($last_menu == 'invoice_account' && !empty($input)) {
                $send_menu = 'thank_you_invoice_account';
            } else if ($last_menu == 'change_update_schedule' && !empty($input)) {
                $send_menu = 'thank_you_change_update_schedule';
            } else if ($last_menu == 'team_send_message' && $input == '1') {
                $send_menu = 'team_send_message_1';
            } else if ($last_menu == 'team_send_message_1' && !empty($input)) {
                $send_menu = 'client_add_request';
            } else if ($ButtonPayload == "menu") {
                \Log::info('menu');
                $send_menu = 'main_menu';
            } else if ($last_menu == 'main_menu' && $ButtonPayload == 'schedule_preferrence') {
                \Log::info('schedule_preferrence');
                $send_menu = 'schedule_preferrence';
            } else if ($last_menu == 'schedule_preferrence' && !empty($input)) {
                $send_menu = 'schedule_preferrence_input';
            } else {
                // $msgStatus = Cache::get('client_job_confirm_msg' . $client->id);
                // $MondaymsgStatus = Cache::get('client_monday_msg_status_' . $client->id);

                // if (!empty($msgStatus) || !empty($MondaymsgStatus)) {
                //     \Log::info('Client already in (monday / wednesday) message second reply');
                //     $this->activeClientsWednesday($request);
                //     die("already client in (monday / wednesday) message");
                // }
                $send_menu = 'sorry';
            }

            switch ($send_menu) {
                case 'main_menu':
                    $this->sendWhapiMainMenu($client, $from);
                    break;

                case 'urgent_contact':
                    $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));

                    $nextMessage = $this->activeClientBotMessages['urgent_contact'][$lng];
                    $personalizedMessage = str_replace(':client_name', $clientName, $nextMessage);

                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $personalizedMessage));
                    StoreWebhookResponse($personalizedMessage, $from, $result, true);
                    // sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);
                    $clientMessageStatus->update([
                        'menu_option' => 'main_menu->urgent_contact',
                    ]);

                    break;

                case 'thankyou':
                    \Log::info('Thank you message');

                    $nextMessage = $this->activeClientBotMessages['thankyou'][$lng];
                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $nextMessage));
                    StoreWebhookResponse($nextMessage, $from, $result, true);

                    $scheduleChange = new ScheduleChange();
                    $scheduleChange->user_type = get_class($client);
                    $scheduleChange->user_id = $client->id;
                    $scheduleChange->reason = $lng == "en" ? "Contact me urgently" : " צרו איתי קשר דחוף";
                    $scheduleChange->comments = trim($input);
                    $scheduleChange->save();

                    $nextMessage = $this->activeClientBotMessages['team_comment']["heb"];
                    $clientName = "*" . trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? '')) . "*";

                    $scheduleLink = generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin');

                    $personalizedMessage = str_replace([
                        ':client_name',
                        ':message',
                        ':client_phone',
                        ':comment_link',
                        ':client_link'
                    ], [
                        $clientName,
                        '*' . trim($input) . '*',
                        $client->phone,
                        $scheduleLink,
                        generateShortUrl(url("admin/clients/view/" . $client->id), 'admin')
                    ], $nextMessage);
                    sendTeamWhatsappMessage(config('services.whatsapp_groups.urgent'), ['name' => '', 'message' => $personalizedMessage]);

                    $clientMessageStatus->delete();
                    break;

                case 'service_schedule':
                    $today = Carbon::today()->toDateString();
                    $weekEndDate = Carbon::today()->endOfWeek(Carbon::SATURDAY)->toDateString();
                    $dateTime = '';

                    $nextWeekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY)->addWeek()->format('Y-m-d');
                    $nextWeekEnd = Carbon::now()->endOfWeek(Carbon::SATURDAY)->addWeek()->format('Y-m-d');

                    // Fetch jobs for the current week
                    $currentWeekJobs = Job::where('client_id', $client->id)
                        ->whereNotIn('status', [JobStatusEnum::COMPLETED, JobStatusEnum::CANCEL])
                        ->whereBetween('start_date', [$today, $weekEndDate])
                        ->get();

                    // Fetch jobs for the next week
                    $nextWeekJobs = Job::where('client_id', $client->id)
                        ->whereNotIn('status', [JobStatusEnum::COMPLETED, JobStatusEnum::CANCEL])
                        ->whereBetween('start_date', [$nextWeekStart, $nextWeekEnd])
                        ->get();


                    $this->initGoogleConfig();
                    $sheets = $this->getAllSheetNames();
                    if (count($sheets) <= 0) {
                        Log::info("No sheet found", ['sheets' => $sheets]);
                    }
                    $currentWeeks = [];
                    $nextWeeks = [];
                    $currentDate = null;
                    $dates = [];
                    $shifts = [];
                    foreach ($sheets as $key => $sheet) {
                        $data = $this->getGoogleSheetData($sheet);
                        if (empty($data)) {
                            Log::warning("Sheet $sheet is empty.");
                            continue;
                        }

                        foreach ($data as $index => $row) {
                            if ($index == 0) {
                                continue;
                            }
                            if (!empty($row[3]) && (
                                preg_match('/(?:יום\s*)?[א-ת]+\s*\d{1,2}\.\d{1,2}/u', $row[3]) ||
                                preg_match('/(?:יום\s*)?[א-ת]+\s*\d{1,2},\d{1,2}/u', $row[3])
                                // preg_match('/(?:יום\s*)?[א-ת]+\s*\d{2}\d{2}/u', $row[3])
                            )) {
                                $currentDate = $this->convertDate($row[3], $sheet);
                                $grouped[$currentDate] = [];
                            }
                            if ($currentDate !== null && !empty($row[1]) && !empty($row[5]) && $row[5] == 'TRUE') {
                                $grouped[$currentDate][] = $row;
                                $id = null;
                                $email = null;
                                if (strpos(trim($row[1]), '#') === 0) {
                                    $id = substr(trim($row[1]), 1);
                                } else if (filter_var(trim($row[1]), FILTER_VALIDATE_EMAIL)) {
                                    $email = trim($row[1]);
                                }

                                if (($id || $email) && !empty($row[10])) {
                                    $shifts[] = trim($row[10] ?? '');
                                    if ($id == $client->id || (!empty($email) && $email == $client->email)) {
                                        $currentDateObj = Carbon::parse($currentDate); // Current date
                                        \Log::info($currentDateObj);

                                        // $today = Carbon::today()->toDateString();
                                        // $weekEndDate = Carbon::today()->endOfWeek(Carbon::SATURDAY)->toDateString();

                                        $nextWeekStart = Carbon::now()->next(Carbon::SUNDAY); // Next week's Sunday
                                        $nextWeekEnd = $nextWeekStart->copy()->addDays(6); // Next week's Saturday
                                        $shift = "";
                                        $day = $currentDateObj->format('l');
                                        if ($client->lng == 'en') {
                                            switch (trim($row[10])) {
                                                case 'יום':
                                                case 'בוקר':
                                                case '7 בבוקר':
                                                case 'בוקר 11':
                                                case 'בוקר מוקדם':
                                                case 'בוקר 6':
                                                    $shift = "Morning";
                                                    break;

                                                case 'צהריים':
                                                case 'צהריים 14':
                                                    $shift = "Noon";
                                                    break;

                                                case 'אחהצ':
                                                case 'אחה״צ':
                                                case 'ערב':
                                                case 'אחר״צ':
                                                    $shift = "After noon";
                                                    break;

                                                default:
                                                    $shift = $row[10];
                                                    break;
                                            }
                                        } else {
                                            switch (trim($row[10])) {
                                                case 'יום':
                                                case 'בוקר':
                                                case '7 בבוקר':
                                                case 'בוקר 11':
                                                case 'בוקר מוקדם':
                                                case 'בוקר 6':
                                                    $shift = "בוקר";
                                                    break;

                                                case 'צהריים':
                                                case 'צהריים 14':
                                                    $shift = 'צהריים';
                                                    break;

                                                case 'אחהצ':
                                                case 'אחה״צ':
                                                case 'ערב':
                                                case 'אחר״צ':
                                                    $shift = "אחה״צ";
                                                    break;


                                                default:
                                                    $shift = $row[10];
                                                    break;
                                            }
                                            switch ($day) {
                                                case 'Sunday':
                                                    $day = "ראשון";
                                                    break;
                                                case 'Monday':
                                                    $day = "שני";
                                                    break;
                                                case 'Tuesday':
                                                    $day = "שלישי";
                                                    break;
                                                case 'Wednesday':
                                                    $day = "רביעי";
                                                    break;
                                                case 'Thursday':
                                                    $day = "חמישי";
                                                    break;
                                                case 'Friday':
                                                    $day = "שישי";
                                                    break;
                                                case 'Saturday':
                                                    $day = "שבת";
                                                    break;
                                            }
                                        }
                                        if ($currentDateObj->lessThan($nextWeekStart) && $currentDateObj->greaterThan(now())) {
                                            $currentWeeks[] = [
                                                "shift" => $shift,
                                                "dayName" => $day,
                                                "currentDate" => $currentDateObj->format('j.n.y')
                                            ];
                                        }
                                        if ($currentDateObj->between($nextWeekStart, $nextWeekEnd)) {
                                            $nextWeeks[] = [
                                                "shift" => $shift,
                                                "dayName" => $day,
                                                "currentDate" => $currentDateObj->format('j.n.y')
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if ($currentWeeks && count($currentWeeks) > 0) {
                        $dateTime = "";
                        foreach ($currentWeeks as $job) {
                            $dateTime .= $job['dayName'] . " " . $job['currentDate'] . " " . $job['shift'] . "," . "\n";
                        }

                        $nextMessage = $this->activeClientBotMessages['service_schedule'][$lng];
                        $personalizedMessage = str_replace(':date_time', $dateTime, $nextMessage);

                        $result = sendWhatsappMessage($from, array('name' => '', 'message' => $personalizedMessage));
                        StoreWebhookResponse($personalizedMessage, $from, $result, true);

                        $clientMessageStatus->delete();
                    }

                    if ($nextWeeks && count($nextWeeks) > 0) {
                        $dateTime = "";
                        foreach ($nextWeeks as $job) {
                            $dateTime .= $job['dayName'] . " " . $job['currentDate'] . " " . $job['shift'] . "," . "\n";
                        }


                        $nextMessage = $this->activeClientBotMessages['next_week_service_schedule'][$lng];
                        $personalizedMessage = str_replace(':date_time', $dateTime, $nextMessage);

                        $result = sendWhatsappMessage($from, array('name' => '', 'message' => $personalizedMessage));
                        StoreWebhookResponse($personalizedMessage, $from, $result, true);

                        $clientMessageStatus->delete();
                    }

                    // If no jobs are found for both weeks
                    if (empty($currentWeeks) && empty($nextWeeks)) {
                        // $buttons = [
                        //     [
                        //         'type' => 'quick_reply',
                        //         'title' => $lng == "heb" ? "פרטים נוספים" : "More details",
                        //         'id' => '5',
                        //     ]
                        // ];

                        $nextMessage = $this->activeClientBotMessages['no_service_avail'][$lng];
                        $result = sendWhatsappMessage($from, array('name' => '', 'message' => $nextMessage, 'buttons' => $buttons));
                        StoreWebhookResponse($nextMessage, $from, $result, true);

                        // sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);
                        $clientMessageStatus->update([
                            'menu_option' => 'main_menu->service_schedule',
                        ]);
                    }

                    break;
                case 'request_new_qoute':
                    $nextMessage = $this->activeClientBotMessages['request_new_qoute'][$lng];
                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $nextMessage));
                    StoreWebhookResponse($nextMessage, $from, $result, true);

                    $nextMessage = $this->activeClientBotMessages['team_new_qoute']["heb"];
                    $clientName = "*" . trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? '')) . "*";
                    $personalizedMessage = str_replace([
                        ':client_name',
                        ':client_phone',
                        ':client_link'
                    ], [
                        $clientName,
                        $client->phone,
                        generateShortUrl(url("admin/clients/view/" . $client->id), 'admin')
                    ], $nextMessage);

                    sendTeamWhatsappMessage(config('services.whatsapp_groups.lead_client'), ['name' => '', 'message' => $personalizedMessage]);
                    $clientMessageStatus->delete();

                    break;
                case 'invoice_account':
                    $nextMessage = $this->activeClientBotMessages['invoice_account'][$lng];

                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $nextMessage));
                    StoreWebhookResponse($nextMessage, $from, $result, true);

                    // sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);
                    $clientMessageStatus->update([
                        'menu_option' => 'main_menu->invoice_account',
                    ]);

                    break;
                case 'thank_you_invoice_account':
                    $nextMessage = $this->activeClientBotMessages['thank_you_invoice_account'][$lng];
                    $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));
                    $personalizedMessage = str_replace(':client_name', $clientName, $nextMessage);

                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $personalizedMessage));
                    StoreWebhookResponse($personalizedMessage, $from, $result, true);

                    $scheduleChange = new ScheduleChange();
                    $scheduleChange->user_type = get_class($client);
                    $scheduleChange->user_id = $client->id;
                    $scheduleChange->reason = $lng == "en" ? "Invoice and accounting inquiry" : 'הנה"ח - פנייה למחלקת הנהלת חשבונות';
                    $scheduleChange->comments = $input;
                    $scheduleChange->save();

                    $clientName = "*" . trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? '')) . "*";
                    $nextMessage = $this->activeClientBotMessages['team_invoice_account']["heb"];
                    $personalizedMessage = str_replace([
                        ':client_name',
                        ":client_phone",
                        ":message",
                        ":comment_link",
                        ':client_link'
                    ], [
                        $clientName,
                        $client->phone,
                        '*' . trim($input) . '*',
                        generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin'),
                        generateShortUrl(url("admin/clients/view/" . $client->id), 'admin')
                    ], $nextMessage);

                    sendTeamWhatsappMessage(config('services.whatsapp_groups.problem_with_payments'), ['name' => '', 'message' => $personalizedMessage]);
                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $personalizedMessage,
                        'from'          => config("services.whapi.whapi_number"),
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);

                    $clientMessageStatus->delete();
                    break;

                case 'change_update_schedule':
                    \Log::info('Change update schedule');

                    $nextMessage = $this->activeClientBotMessages['change_update_schedule'][$lng];

                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $nextMessage));
                    StoreWebhookResponse($nextMessage, $from, $result, true);

                    $clientMessageStatus->update([
                        'menu_option' => 'main_menu->change_update_schedule',
                    ]);

                    break;

                case 'thank_you_change_update_schedule':

                    $nextMessage = $this->activeClientBotMessages['thank_you_change_update_schedule'][$lng];
                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $nextMessage));
                    StoreWebhookResponse($nextMessage, $from, $result, true);

                    $scheduleChange = new ScheduleChange();
                    $scheduleChange->user_type = get_class($client);
                    $scheduleChange->user_id = $client->id;
                    $scheduleChange->reason = $lng == "en" ? "Change or update schedule" : 'שינוי או עדכון שיבוץ';
                    $scheduleChange->comments = $input;
                    $scheduleChange->save();

                    $nextMessage = $this->activeClientBotMessages['team_change_update_schedule']["heb"];
                    $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));
                    $personalizedMessage = str_replace(
                        [':client_name', ":client_phone", ":message", ":comment_link", ':client_link'],
                        [
                            $clientName,
                            $client->phone,
                            trim($input),
                            generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin'),
                            generateShortUrl(url("admin/clients/view/" . $client->id), 'admin')
                        ],
                        $nextMessage
                    );

                    sendTeamWhatsappMessage(config('services.whatsapp_groups.changes_cancellation'), ['name' => '', 'message' => $personalizedMessage]);

                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $personalizedMessage,
                        'from'          => config("services.whapi.whapi_number"),
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);

                    $clientMessageStatus->delete();
                    break;
                case 'access_portal':

                    // $buttons = [
                    //     [
                    //         'type' => 'url',
                    //         'title' => $lng == "heb" ? "אנא לחץ כאן" : "please click here",
                    //         'id' => 'portal',
                    //         "url" => generateShortUrl(url("client/login"), 'admin')

                    //     ]
                    // ];

                    $nextMessage = $this->activeClientBotMessages['access_portal'][$lng];
                    $personalizedMessage = str_replace(':client_portal_link', generateShortUrl(url("client/login"), 'admin'), $nextMessage);

                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $personalizedMessage, 'buttons' => $buttons));
                    StoreWebhookResponse($personalizedMessage, $from, $result, true);

                    $clientMessageStatus->delete();

                    break;

                case 'sorry':
                    \Log::info('Sorry');
                    // $nextMessage = $this->activeClientBotMessages['sorry'][$lng];

                    // $result = sendWhatsappMessage($from, array('name' => '', 'message' => $msg, 'list' => [], 'buttons' => []));

                    // // sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);
                    // WebhookResponse::create([
                    //     'status'        => 1,
                    //     'name'          => 'whatsapp',
                    //     'entry_id'      => $messageId,
                    //     'message'       => $msg ?? '',
                    //     'from'          => config("services.whapi.whapi_number"),
                    //     'number'        => $from,
                    //     'flex'          => 'A',
                    //     'read'          => 1,
                    // ]);

                    break;

                case 'team_send_message_1':
                    \Log::info('team_send_message_1');
                    $text = [
                        "en" => "Hello :client_name,\nPlease let us know what additional information or request you would like to add.",
                        "heb" => "שלום :client_name,\nאנא עדכן אותנו מה ברצונך להוסיף או לבקש."
                    ];

                    $nextMessage = $text[$lng];
                    $clientName = "*" . trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? '')) . "*";
                    $personalizedMessage = str_replace(':client_name', $clientName, $nextMessage);
                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $personalizedMessage, 'buttons' => $buttons));
                    StoreWebhookResponse($personalizedMessage, $from, $result, true);
                    // sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

                    WhatsAppBotActiveClientState::updateOrCreate(
                        [
                            "from" => $from,
                            "type" => "whapi"
                        ],
                        [
                            "from" => $from,
                            'menu_option' => 'team_send_message_1'
                        ]
                    );

                    break;

                case "client_add_request":
                    $text = [
                        "en" => "Hello :client_name,\nWe’ve received your updated request:\n':client_message'Your message has been forwarded to the team for further handling. Thank you for your patience!",
                        "heb" => "שלום :client_name,קיבלנו את עדכון הבקשה שלך:':client_message'ההודעה הועברה לצוות להמשך טיפול. תודה על הסבלנות!"
                    ];

                    $nextMessage = $text[$lng];
                    $clientName = "*" . trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? '')) . "*";
                    $personalizedMessage = str_replace([':client_name', ':client_message'], [$clientName, '*' . trim($input) . '*'], $nextMessage);
                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $personalizedMessage, 'buttons' => $buttons));
                    StoreWebhookResponse($personalizedMessage, $from, $result, true);
                    // sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

                    $scheduleChange = new ScheduleChange();
                    $scheduleChange->user_type = get_class($client);
                    $scheduleChange->user_id = $client->id;
                    $scheduleChange->reason = $lng == "en" ? "additional information" : 'מידע נוסף';
                    $scheduleChange->comments = $input;
                    $scheduleChange->save();
                    $clientMessageStatus->delete();

                    break;

                case 'stop':
                    \Log::info("edfedf");
                    $nextMessage = $this->activeClientBotMessages['stop'][$lng];
                    $clientName = "*" . trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? '')) . "*";
                    $personalizedMessage = str_replace(':client_name', $clientName, $nextMessage);
                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $personalizedMessage, 'buttons' => $buttons));
                    StoreWebhookResponse($personalizedMessage, $from, $result, true);
                    // sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

                    WhatsAppBotActiveClientState::updateOrCreate(
                        [
                            "from" => $from,
                            "type" => "whapi"
                        ],
                        [
                            "from" => $from,
                            'menu_option' => 'stop'
                        ]
                    );

                    break;

                case 'schedule_preferrence':
                    \Log::info("edfedf");
                    $nextMessage = $this->activeClientBotMessages['schedule_preferrence'][$lng];
                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $nextMessage, 'buttons' => $buttons));
                    StoreWebhookResponse($nextMessage, $from, $result, true);

                    WhatsAppBotActiveClientState::updateOrCreate(
                        [
                            "from" => $from,
                            "type" => "whapi"
                        ],
                        [
                            "from" => $from,
                            'menu_option' => 'schedule_preferrence'
                        ]
                    );

                    break;

                case 'schedule_preferrence_input':
                    $text = [
                        "en" => "Hello :client_name,\nWe’ve received your request:\n':client_message'\n\n:comment_link\nYour message has been forwarded to the team for further handling. Thank you for your patience!",
                        "heb" => "שלום :client_name,\nקיבלנו את עדכון הבקשה שלך:\n':client_message'\n\n:comment_link\nההודעה הועברה לצוות להמשך טיפול. תודה על הסבלנות!"
                    ];

                    $scheduleChange = new ScheduleChange();
                    $scheduleChange->user_type = get_class($client);
                    $scheduleChange->user_id = $client->id;
                    $scheduleChange->reason = $client->lng == "en" ? "Change or update schedule" : 'שינוי או עדכון שיבוץ';
                    $scheduleChange->comments = trim($input);
                    $scheduleChange->save();

                    // Send message to team
                    $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));
                    $personalizedMessage = str_replace([':comment_link', ':client_name', ':client_message'], [generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin'), $clientName, trim($input)], $text[$client->lng]);

                    sendTeamWhatsappMessage(config('services.whatsapp_groups.changes_cancellation'), ['name' => '', 'message' => $personalizedMessage]);

                    $confirmationMessage = $client->lng == 'heb'
                        ? "ההודעה שלך התקבלה ותועבר לצוות שלנו להמשך טיפול."
                        : "Your message has been received and will be forwarded to our team for further handling.";
                    // sendClientWhatsappMessage($from, ['message' => $confirmationMessage]);

                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $confirmationMessage, 'buttons' => $buttons));
                    StoreWebhookResponse($confirmationMessage, $from, $result, true);

                    WhatsAppBotActiveClientState::updateOrCreate(
                        [
                            "from" => $from,
                            "type" => "whapi"
                        ],
                        [
                            "from" => $from,
                            'menu_option' => 'main_menu'
                        ]
                    );

                    break;
            }
        }
    }

    public function fbActiveClientsWebhookCurrentLive(Request $request)
    {
        $data = $request->all();
        $messageId = $data['SmsMessageSid'] ?? null;

        if (!$messageId) {
            return response()->json(['status' => 'Invalid message data'], 400);
        }

        // Check if the messageId exists in cache and matches
        if (Cache::get('active_client_processed_message_' . $messageId) == $messageId) {
            \Log::info('Already processed');
            return response()->json(['status' => 'Already processed'], 200);
        }

        // Store the messageId in the cache for 1 hour
        Cache::put('active_client_processed_message_' . $messageId, $messageId, now()->addHours(1));

        if ($data['SmsStatus'] == 'received') {
            $from = $data['From'] ? str_replace("whatsapp:+", "", $data['From']) : $data['From'];

            $isMonday = now()->isMonday();

            $workerLead = WorkerLeads::where('phone', $from)->first();
            if ($workerLead) {
                \Log::info('Worker lead already exists');
            }

            $user = User::where('phone', $from)
                ->where('status', '!=', 0)
                ->first();
            if ($user) {
                \Log::info('User already exists');
            }
            $client = Client::where('phone', $from)
                ->orWhereJsonContains('extra', [['phone' => $from]])
                ->first();

            $msgStatus = null;
            $input = null;

            if ($client) {
                $msgStatus = Cache::get('client_review' . $client->id);
                \Log::info($msgStatus . ' ' . $client->id);

                $input = $data['Body'] ? trim($data['Body']) : $data['Body'];
                $listId = $data['ListId'] ?? $input;
                $ButtonPayload = $data['ButtonPayload'] ?? null;
                \Log::info("wsssssssssssssssssssssssssssssssss");
                \Log::info($ButtonPayload);

                if (!empty($msgStatus)) {
                    \Log::info('Client already reviewed');
                    $this->clientReview($request);
                    die('Client already reviewed');
                }

                $msgStatus = Cache::get('client_review_input2' . $client->id);
                if (!empty($msgStatus)) {
                    \Log::info('Client already reviewed');
                    $this->clientReview($request);
                    die('Client already reviewed');
                }

                $msgStatus = Cache::get('client_job_confirm_msg' . $client->id);
                \Log::info($msgStatus . ' ' . $client->id);
                if ((!empty($msgStatus) && ($listId || $ButtonPayload) == '1') || (!empty($msgStatus) && $msgStatus != "main_msg")) {
                    \Log::info('Client already in (monday / wednesday) message first reply');
                    $this->activeClientsWednesday($request);
                    die('Client confirm job');
                }
            }

            if ($client && $client->lead_status->lead_status != LeadStatusEnum::ACTIVE_CLIENT) {
                die('Client already active');
            }

            $lng = $client->lng ?? $this->detectLanguage($input);
            // if ($user || $workerLead) {
            //     die('Worker or worker lead found');
            // }

            if ($client && $client->disable_notification == 1) {
                \Log::info('Client disabled notification');
                die('Client disabled notification');
            }

            if ($isMonday && $client && $client->stop_last_message != 1 && !in_array(strtolower(trim($input)), ["stop", "הפסק"])) {
                if ($client->stop_last_message == 0 && in_array(strtolower(trim($input)), ["menu", "תפריט"])) {
                    $client->stop_last_message = 1;
                    $client->save();
                } else {
                    \Log::info('Monday msg reply is pending');
                    $this->activeClientsMonday($request);
                    die('Monday msg reply is pending.');
                }
            }
            $clientMessageStatus = WhatsAppBotActiveClientState::where('from', $from)->where('type', "meta")->first();

            $last_menu = null;
            $send_menu = null;
            if ($clientMessageStatus) {
                $lng = $clientMessageStatus->lng ?? 'heb';
                $menu_option = explode('->', $clientMessageStatus->menu_option);
                $last_menu = end($menu_option);
            }

            // WebhookResponse::create([
            //     'status' => 1,
            //     'name' => 'whatsapp',
            //     'entry_id' => $messageId,
            //     'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
            //     'message' => $input,
            //     'number' => $from,
            //     'read' => 0,
            //     'flex' => 'C',
            //     'data' => json_encode($data)
            // ]);

            if (in_array(strtolower(trim($input)), ["stop", "הפסק"])) {
                $client->disable_notification = 1;
                $client->save();
                $send_menu = 'stop';
            } else if (empty($last_menu) || in_array(strtolower(trim($input)), ["menu", "תפריט"])) {
                if (!$client && !$user && !$workerLead) {
                    $send_menu = 'not_recognized';
                } else {
                    \Log::info('Client menu');
                    $send_menu = 'main_menu';
                }
            } else if ($last_menu == 'main_menu' && $listId == '1') {
                $send_menu = 'urgent_contact';
            } else if ($last_menu == 'main_menu' && $listId == '2') {
                $send_menu = 'service_schedule';
            } else if ($last_menu == 'main_menu' && $listId == '3') {
                $send_menu = 'request_new_qoute';
            } else if ($last_menu == 'main_menu' && $listId == '4') {
                $send_menu = 'invoice_account';
            } else if ($last_menu == 'main_menu' && $listId == '5') {
                $send_menu = 'change_update_schedule';
            } else if ($last_menu == 'main_menu' && $listId == '6') {
                $send_menu = 'access_portal';
            } else if ($last_menu == 'urgent_contact' && !empty($input)) {
                $send_menu = 'thankyou';
            } else if ($last_menu == 'service_schedule' && $ButtonPayload == '5') {
                $send_menu = 'change_update_schedule';
            } else if ($last_menu == 'invoice_account' && !empty($input)) {
                $send_menu = 'thank_you_invoice_account';
            } else if ($last_menu == 'change_update_schedule' && !empty($input)) {
                $send_menu = 'thank_you_change_update_schedule';
            } else if ($last_menu == 'team_send_message' && ($listId == '1' || $ButtonPayload == '1')) {
                $send_menu = 'team_send_message_1';
            } else if ($last_menu == 'team_send_message_1' && !empty($input)) {
                $send_menu = 'client_add_request';
            } else if ($last_menu == 'main_menu' && $ButtonPayload == 'schedule_preferrence') {
                \Log::info('schedule_preferrence');
                $send_menu = 'schedule_preferrence';
            } else if ($last_menu == 'schedule_preferrence' && !empty($input)) {
                $send_menu = 'schedule_preferrence_input';
            } else {
                $msgStatus = Cache::get('client_job_confirm_msg' . $client->id);
                $MondaymsgStatus = Cache::get('client_monday_msg_status_' . $client->id);

                if (!empty($msgStatus) || !empty($MondaymsgStatus)) {
                    \Log::info('Client already in (monday / wednesday) message second reply');
                    $this->activeClientsWednesday($request);
                    die("already client in (monday / wednesday) message");
                }
                $send_menu = 'sorry';
            }

            switch ($send_menu) {
                case 'main_menu':
                    $this->sendMainMenu($client, $from, $input);
                    break;

                case 'urgent_contact':
                    $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));

                    $sid = $lng == "heb" ? "HXc09d5e17ab1745632532697feb91f6e9" : "HXb7328df44612acd61ed1215635bce56a";
                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "contentSid" => $sid,
                            "contentVariables" => json_encode([
                                '1' => $clientName
                            ]),

                        ]
                    );

                    $nextMessage = $this->activeClientBotMessages['urgent_contact'][$lng];
                    $personalizedMessage = str_replace(':client_name', $clientName, $nextMessage);
                    // sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);
                    $clientMessageStatus->update([
                        'menu_option' => 'main_menu->urgent_contact',
                    ]);
                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId ?? '',
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray()),
                    ]);

                    break;

                case 'thankyou':
                    \Log::info('Thank you message');
                    $sid = $lng == "heb" ? "HX09026a761c1d1d37c3b5d2ea74ab6614" : "HXde01756f197908237fc2d15bd2737035";
                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "contentSid" => $sid,

                        ]
                    );

                    $nextMessage = $this->activeClientBotMessages['thankyou'][$lng];
                    // sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId ?? '',
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray()),
                    ]);

                    $scheduleChange = new ScheduleChange();
                    $scheduleChange->user_type = get_class($client);
                    $scheduleChange->user_id = $client->id;
                    $scheduleChange->reason = $lng == "en" ? "Contact me urgently" : " צרו איתי קשר דחוף";
                    $scheduleChange->comments = trim($input);
                    $scheduleChange->save();

                    $nextMessage = $this->activeClientBotMessages['team_comment']["heb"];
                    $clientName = "*" . trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? '')) . "*";

                    $scheduleLink = generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin');

                    $personalizedMessage = str_replace([
                        ':client_name',
                        ':message',
                        ':client_phone',
                        ':comment_link',
                        ':client_link'
                    ], [
                        $clientName,
                        '*' . trim($input) . '*',
                        $client->phone,
                        $scheduleLink,
                        generateShortUrl(url("admin/clients/view/" . $client->id), 'admin')
                    ], $nextMessage);
                    sendTeamWhatsappMessage(config('services.whatsapp_groups.urgent'), ['name' => '', 'message' => $personalizedMessage]);

                    $clientMessageStatus->delete();
                    break;

                case 'service_schedule':
                    $today = Carbon::today()->toDateString();
                    $weekEndDate = Carbon::today()->endOfWeek(Carbon::SATURDAY)->toDateString();
                    $dateTime = '';

                    $nextWeekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY)->addWeek()->format('Y-m-d');
                    $nextWeekEnd = Carbon::now()->endOfWeek(Carbon::SATURDAY)->addWeek()->format('Y-m-d');

                    // Fetch jobs for the current week
                    $currentWeekJobs = Job::where('client_id', $client->id)
                        ->whereNotIn('status', [JobStatusEnum::COMPLETED, JobStatusEnum::CANCEL])
                        ->whereBetween('start_date', [$today, $weekEndDate])
                        ->get();

                    // Fetch jobs for the next week
                    $nextWeekJobs = Job::where('client_id', $client->id)
                        ->whereNotIn('status', [JobStatusEnum::COMPLETED, JobStatusEnum::CANCEL])
                        ->whereBetween('start_date', [$nextWeekStart, $nextWeekEnd])
                        ->get();


                    $this->initGoogleConfig();
                    $sheets = $this->getAllSheetNames();
                    if (count($sheets) <= 0) {
                        Log::info("No sheet found", ['sheets' => $sheets]);
                    }
                    $currentWeeks = [];
                    $nextWeeks = [];
                    $currentDate = null;
                    $dates = [];
                    $shifts = [];
                    foreach ($sheets as $key => $sheet) {
                        $data = $this->getGoogleSheetData($sheet);
                        if (empty($data)) {
                            Log::warning("Sheet $sheet is empty.");
                            continue;
                        }

                        foreach ($data as $index => $row) {
                            if ($index == 0) {
                                continue;
                            }
                            if (!empty($row[3]) && (
                                preg_match('/(?:יום\s*)?[א-ת]+\s*\d{1,2}\.\d{1,2}/u', $row[3]) ||
                                preg_match('/(?:יום\s*)?[א-ת]+\s*\d{1,2},\d{1,2}/u', $row[3])
                                // preg_match('/(?:יום\s*)?[א-ת]+\s*\d{2}\d{2}/u', $row[3])
                            )) {
                                $currentDate = $this->convertDate($row[3], $sheet);
                                $grouped[$currentDate] = [];
                            }
                            if ($currentDate !== null && !empty($row[1]) && !empty($row[5]) && $row[5] == 'TRUE') {
                                $grouped[$currentDate][] = $row;
                                $id = null;
                                $email = null;
                                if (strpos(trim($row[1]), '#') === 0) {
                                    $id = substr(trim($row[1]), 1);
                                } else if (filter_var(trim($row[1]), FILTER_VALIDATE_EMAIL)) {
                                    $email = trim($row[1]);
                                }

                                if (($id || $email) && !empty($row[10])) {
                                    $shifts[] = trim($row[10] ?? '');
                                    if ($id == $client->id || (!empty($email) && $email == $client->email)) {
                                        $currentDateObj = Carbon::parse($currentDate); // Current date
                                        \Log::info($currentDateObj);

                                        // $today = Carbon::today()->toDateString();
                                        // $weekEndDate = Carbon::today()->endOfWeek(Carbon::SATURDAY)->toDateString();

                                        $nextWeekStart = Carbon::now()->next(Carbon::SUNDAY); // Next week's Sunday
                                        $nextWeekEnd = $nextWeekStart->copy()->addDays(6); // Next week's Saturday
                                        $shift = "";
                                        $day = $currentDateObj->format('l');
                                        if ($client->lng == 'en') {
                                            switch (trim($row[10])) {
                                                case 'יום':
                                                case 'בוקר':
                                                case '7 בבוקר':
                                                case 'בוקר 11':
                                                case 'בוקר מוקדם':
                                                case 'בוקר 6':
                                                    $shift = "Morning";
                                                    break;

                                                case 'צהריים':
                                                case 'צהריים 14':
                                                    $shift = "Noon";
                                                    break;

                                                case 'אחהצ':
                                                case 'אחה״צ':
                                                case 'ערב':
                                                case 'אחר״צ':
                                                    $shift = "After noon";
                                                    break;

                                                default:
                                                    $shift = $row[10];
                                                    break;
                                            }
                                        } else {
                                            switch (trim($row[10])) {
                                                case 'יום':
                                                case 'בוקר':
                                                case '7 בבוקר':
                                                case 'בוקר 11':
                                                case 'בוקר מוקדם':
                                                case 'בוקר 6':
                                                    $shift = "בוקר";
                                                    break;

                                                case 'צהריים':
                                                case 'צהריים 14':
                                                    $shift = 'צהריים';
                                                    break;

                                                case 'אחהצ':
                                                case 'אחה״צ':
                                                case 'ערב':
                                                case 'אחר״צ':
                                                    $shift = "אחה״צ";
                                                    break;


                                                default:
                                                    $shift = $row[10];
                                                    break;
                                            }
                                            switch ($day) {
                                                case 'Sunday':
                                                    $day = "ראשון";
                                                    break;
                                                case 'Monday':
                                                    $day = "שני";
                                                    break;
                                                case 'Tuesday':
                                                    $day = "שלישי";
                                                    break;
                                                case 'Wednesday':
                                                    $day = "רביעי";
                                                    break;
                                                case 'Thursday':
                                                    $day = "חמישי";
                                                    break;
                                                case 'Friday':
                                                    $day = "שישי";
                                                    break;
                                                case 'Saturday':
                                                    $day = "שבת";
                                                    break;
                                            }
                                        }
                                        $currentDateStr = $currentDateObj->toDateString(); // 'Y-m-d'
                                        $todayStr = now()->toDateString();
                                        $nextWeekStartStr = $nextWeekStart->toDateString();
                                        $nextWeekEndStr = $nextWeekEnd->toDateString();

                                        if ($currentDateStr < $nextWeekStartStr && $currentDateStr > $todayStr) {
                                            $currentWeeks[] = [
                                                "shift" => $shift,
                                                "dayName" => $day,
                                                "currentDate" => $currentDateObj->format('j.n.y')
                                            ];
                                        }
                                        if ($currentDateStr >= $nextWeekStartStr && $currentDateStr <= $nextWeekEndStr) {
                                            $nextWeeks[] = [
                                                "shift" => $shift,
                                                "dayName" => $day,
                                                "currentDate" => $currentDateObj->format('j.n.y')
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if ($currentWeeks && count($currentWeeks) > 0) {
                        $dateTime = "";
                        foreach ($currentWeeks as $job) {
                            $dateTime .= $job['dayName'] . " " . $job['currentDate'] . " " . $job['shift'] . "," . "\n";
                        }

                        $sid = $lng == "heb" ? "HX47d489975d6f2b95fa81c42437a37a85" : "HX3e08db5a79b7a1f9375d8cafa432703e";
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    '1' => $dateTime
                                ]),

                            ]
                        );
                        \Log::info($twi);

                        $nextMessage = $this->activeClientBotMessages['service_schedule'][$lng];
                        $personalizedMessage = str_replace(':date_time', $dateTime, $nextMessage);
                        // sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);
                        WebhookResponse::create([
                            'status'        => 1,
                            'name'          => 'whatsapp',
                            'entry_id'      => $messageId,
                            'message'       => $twi->body ?? '',
                            'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                            'number'        => $from,
                            'flex'          => 'A',
                            'read'          => 1,
                            'data'          => json_encode($twi->toArray()),
                        ]);

                        $clientMessageStatus->delete();
                    }

                    if ($nextWeeks && count($nextWeeks) > 0) {
                        $dateTime = "";
                        foreach ($nextWeeks as $job) {
                            $dateTime .= $job['dayName'] . " " . $job['currentDate'] . " " . $job['shift'] . "," . "\n";
                        }

                        $sid = $lng == "heb" ? "HXbc829731145350c2dda3af6de8b50488" : "HXa773c0faed2a441077d042cddfbf14b3";
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    '1' => $dateTime
                                ]),

                            ]
                        );
                        \Log::info($twi);

                        $nextMessage = $this->activeClientBotMessages['next_week_service_schedule'][$lng];
                        $personalizedMessage = str_replace(':date_time', $dateTime, $nextMessage);
                        // sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);
                        WebhookResponse::create([
                            'status'        => 1,
                            'name'          => 'whatsapp',
                            'entry_id'      => $messageId,
                            'message'       => $twi->body ?? '',
                            'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                            'number'        => $from,
                            'flex'          => 'A',
                            'read'          => 1,
                            'data'          => json_encode($twi->toArray()),
                        ]);

                        $clientMessageStatus->delete();
                    }

                    // If no jobs are found for both weeks
                    if (empty($currentWeeks) && empty($nextWeeks)) {
                        $sid = $lng == "heb" ? "HX09c8d68ed27cb84001868810a214fcf4" : "HX00c5308778ee90d34e8f26d8468ed418";
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,

                            ]
                        );
                        \Log::info($twi);

                        $nextMessage = $this->activeClientBotMessages['no_service_avail'][$lng];
                        // sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);
                        $clientMessageStatus->update([
                            'menu_option' => 'main_menu->service_schedule',
                        ]);
                        WebhookResponse::create([
                            'status'        => 1,
                            'name'          => 'whatsapp',
                            'entry_id'      => $messageId,
                            'message'       => $twi->body ?? '',
                            'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                            'number'        => $from,
                            'flex'          => 'A',
                            'read'          => 1,
                            'data'          => json_encode($twi->toArray()),
                        ]);
                    }

                    break;
                case 'request_new_qoute':
                    $sid = $lng == "heb" ? "HX5eee4fa86731de528664063ca196579a" : "HXa048b9c4fa7b871965927cc2084fee26";
                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "contentSid" => $sid,

                        ]
                    );
                    \Log::info($twi);

                    $nextMessage = $this->activeClientBotMessages['request_new_qoute'][$lng];
                    // sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray()),
                    ]);

                    $nextMessage = $this->activeClientBotMessages['team_new_qoute']["heb"];
                    $clientName = "*" . trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? '')) . "*";
                    $personalizedMessage = str_replace([
                        ':client_name',
                        ':client_phone',
                        ':client_link'
                    ], [
                        $clientName,
                        $client->phone,
                        generateShortUrl(url("admin/clients/view/" . $client->id), 'admin')
                    ], $nextMessage);

                    sendTeamWhatsappMessage(config('services.whatsapp_groups.lead_client'), ['name' => '', 'message' => $personalizedMessage]);
                    $clientMessageStatus->delete();

                    break;
                case 'invoice_account':
                    $nextMessage = $this->activeClientBotMessages['invoice_account'][$lng];

                    $sid = $lng == "heb" ? "HXeaa8fd076b3686d890dde678ee5a59a8" : "HXe8a7b7299dfab77591a97e6b6a250f06";
                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "contentSid" => $sid,

                        ]
                    );
                    \Log::info($twi);

                    // sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);
                    $clientMessageStatus->update([
                        'menu_option' => 'main_menu->invoice_account',
                    ]);

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray()),
                    ]);

                    break;
                case 'thank_you_invoice_account':
                    $nextMessage = $this->activeClientBotMessages['thank_you_invoice_account'][$lng];
                    $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));

                    $sid = $lng == "heb" ? "HX64e67fb1965d72599c71d67c123255fc" : "HX82d5bb589792ad278a11fe6d47fc9dba";
                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "contentSid" => $sid,
                            "contentVariables" => json_encode([
                                '1' => $clientName
                            ]),

                        ]
                    );
                    \Log::info($twi);

                    $personalizedMessage = str_replace(':client_name', $clientName, $nextMessage);
                    // sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray()),
                    ]);

                    $scheduleChange = new ScheduleChange();
                    $scheduleChange->user_type = get_class($client);
                    $scheduleChange->user_id = $client->id;
                    $scheduleChange->reason = $lng == "en" ? "Invoice and accounting inquiry" : 'הנה"ח - פנייה למחלקת הנהלת חשבונות';
                    $scheduleChange->comments = $input;
                    $scheduleChange->save();

                    $clientName = "*" . trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? '')) . "*";
                    $nextMessage = $this->activeClientBotMessages['team_invoice_account']["heb"];
                    $personalizedMessage = str_replace([
                        ':client_name',
                        ":client_phone",
                        ":message",
                        ":comment_link",
                        ':client_link'
                    ], [
                        $clientName,
                        $client->phone,
                        '*' . trim($input) . '*',
                        generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin'),
                        generateShortUrl(url("admin/clients/view/" . $client->id), 'admin')
                    ], $nextMessage);

                    sendTeamWhatsappMessage(config('services.whatsapp_groups.problem_with_payments'), ['name' => '', 'message' => $personalizedMessage]);
                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $personalizedMessage,
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);

                    $clientMessageStatus->delete();
                    break;

                case 'change_update_schedule':
                    \Log::info('Change update schedule');

                    $sid = $lng == "heb" ? "HX6c714fe370fc5fd5b954e84073bd771a" : "HX5afe66dbf4c622d4cb5fd280e04c4c5f";
                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "contentSid" => $sid,

                        ]
                    );
                    \Log::info($twi);

                    $nextMessage = $this->activeClientBotMessages['change_update_schedule'][$lng];
                    // sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

                    $clientMessageStatus->update([
                        'menu_option' => 'main_menu->change_update_schedule',
                    ]);

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray()),
                    ]);

                    break;

                case 'thank_you_change_update_schedule':

                    $sid = $lng == "heb" ? "HXfa84f3cb1b20c9dfb1a6449bdd07bbd0" : "HXe5375dcae347167f8e727a5c382d6f30";
                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "contentSid" => $sid,

                        ]
                    );
                    \Log::info($twi);

                    $nextMessage = $this->activeClientBotMessages['thank_you_change_update_schedule'][$lng];
                    // sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);
                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray()),
                    ]);

                    $scheduleChange = new ScheduleChange();
                    $scheduleChange->user_type = get_class($client);
                    $scheduleChange->user_id = $client->id;
                    $scheduleChange->reason = $lng == "en" ? "Change or update schedule" : 'שינוי או עדכון שיבוץ';
                    $scheduleChange->comments = $input;
                    $scheduleChange->save();

                    $nextMessage = $this->activeClientBotMessages['team_change_update_schedule']["heb"];
                    $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));
                    $personalizedMessage = str_replace(
                        [':client_name', ":client_phone", ":message", ":comment_link", ':client_link'],
                        [
                            $clientName,
                            $client->phone,
                            trim($input),
                            generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin'),
                            generateShortUrl(url("admin/clients/view/" . $client->id), 'admin')
                        ],
                        $nextMessage
                    );

                    sendTeamWhatsappMessage(config('services.whatsapp_groups.changes_cancellation'), ['name' => '', 'message' => $personalizedMessage]);

                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $personalizedMessage,
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);

                    $clientMessageStatus->delete();
                    break;
                case 'access_portal':

                    $sid = $lng == "heb" ? "HX5019bc93134e89d71f5be55daf143281" : "HX009816f83d7d283f8c732515c5a978e4";
                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "contentSid" => $sid,
                            "contentVariables" => json_encode([
                                '1' => "client/login"
                            ]),

                        ]
                    );
                    \Log::info($twi);

                    $nextMessage = $this->activeClientBotMessages['access_portal'][$lng];
                    $personalizedMessage = str_replace(':client_portal_link', generateShortUrl(url("client/login"), 'admin'), $nextMessage);
                    // sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);
                    $clientMessageStatus->delete();

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray()),
                    ]);
                    break;

                case 'sorry':
                    // $nextMessage = $this->activeClientBotMessages['sorry'][$lng];

                    // $sid = $lng == "heb" ? "HX562135f9868b46f915b86a6e793dc86f" : "HX24b12b6d91f53ec0138575dace39d98e";
                    // $twi = $this->twilio->messages->create(
                    //     "whatsapp:+$from",
                    //     [
                    //         "from" => $this->twilioWhatsappNumber,
                    //         "contentSid" => $sid,

                    //     ]
                    // );
                    // \Log::info($twi);

                    $scheduleChange = ScheduleChange::create(
                        [
                            'user_type' => get_class($client),
                            'user_id' => $client->id,
                            'comments' => trim($input),
                            "reason" => $client->lng == "en" ? "Contact me urgently" : " צרו איתי קשר דחוף",
                        ]
                    );
                    $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));
                    // $teammsg = "שלום צוות, הלקוח " . "*" . $clientName . "*" . "  ביקש לבצע שינוי בסידור העבודה שלו לשבוע הבא. הבקשה שלו היא: \"" . '*' . $messageBody . '*' . "\" אנא בדקו וטפלו בהתאם. בברכה, צוות ברום סרוויס\n:comment_link";
                    $teammsg = "שלום צוות, לקוח " . "*" . $clientName . "*" . " ביקש. בקשתו היא: " . "*" . trim($input) . "*" . " אנא בדוק וטפל בהתאם. בברכה, צוות השירות של ברום\n:comment_link";
                    $personalizedMessage = str_replace(':comment_link', generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin'), $teammsg);

                    sendTeamWhatsappMessage(config('services.whatsapp_groups.urgent'), ['name' => '', 'message' => $personalizedMessage]);

                    $sid = $lng == "heb" ? "HXb4c7927e4693937b29866626ef467c65" : "HX20d3822e21360344c93be702ed178544";

                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "contentSid" => $sid,
                            // "body" => $follow_up_msg,

                        ]
                    );

                    // sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);
                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray()),
                    ]);

                    break;

                case 'team_send_message_1':
                    \Log::info('team_send_message_1');
                    $text = [
                        "en" => "Hello :client_name,\nPlease let us know what additional information or request you would like to add.",
                        "heb" => "שלום :client_name,\nאנא עדכן אותנו מה ברצונך להוסיף או לבקש."
                    ];

                    $nextMessage = $text[$lng];
                    $clientName = "*" . trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? '')) . "*";

                    $sid = $lng == "heb" ? "HXc46b10d21adc445c1fa02dc0ed4c8a56" : "HXb1ec5e70b6c52fa089c9589d5eb3fcf8";
                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "contentSid" => $sid,
                            "contentVariables" => json_encode([
                                '1' => $clientName
                            ]),

                        ]
                    );
                    \Log::info($twi);

                    $personalizedMessage = str_replace(':client_name', $clientName, $nextMessage);
                    // sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

                    WhatsAppBotActiveClientState::updateOrCreate(
                        [
                            "from" => $from,
                            "type" => "meta"
                        ],
                        [
                            "from" => $from,
                            'menu_option' => 'team_send_message_1'
                        ]
                    );

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray()),
                    ]);

                    break;

                case "client_add_request":
                    $text = [
                        "en" => "Hello :client_name,\nWe’ve received your updated request:\n':client_message'\nYour message has been forwarded to the team for further handling. Thank you for your patience!",
                        "heb" => "שלום :client_name,\nקיבלנו את עדכון הבקשה שלך:\n':client_message'\nההודעה הועברה לצוות להמשך טיפול. תודה על הסבלנות!"
                    ];

                    $nextMessage = $text[$lng];
                    $clientName = "*" . trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? '')) . "*";

                    $sid = $lng == "heb" ? "HX0f0cfcddd27d013b226b01115c87064f" : "HX95355ef547f10d901520bc5b1bfb8d09";
                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "contentSid" => $sid,
                            "contentVariables" => json_encode([
                                '1' => $clientName,
                                '2' =>  trim($input)
                            ]),

                        ]
                    );
                    \Log::info($twi);

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray()),
                    ]);

                    $personalizedMessage = str_replace([':client_name', ':client_message'], [$clientName, '*' . trim($input) . '*'], $nextMessage);
                    // sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

                    $scheduleChange = new ScheduleChange();
                    $scheduleChange->user_type = get_class($client);
                    $scheduleChange->user_id = $client->id;
                    $scheduleChange->reason = $lng == "en" ? "additional information" : 'מידע נוסף';
                    $scheduleChange->comments = $input;
                    $scheduleChange->save();
                    $clientMessageStatus->delete();

                    break;

                case 'stop':
                    \Log::info("edfedf");
                    $nextMessage = $this->activeClientBotMessages['stop'][$lng];
                    $clientName = "*" . trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? '')) . "*";

                    $sid = $lng == "heb" ? "HX00e7bc01708b33616e1bae87d30b5f73" : "HXe9949fa1498d8835db84ed37fe7a95ec";
                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "contentSid" => $sid,
                            "contentVariables" => json_encode([
                                '1' => $clientName,
                            ]),

                        ]
                    );
                    \Log::info($twi);

                    $personalizedMessage = str_replace(':client_name', $clientName, $nextMessage);
                    // sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

                    WhatsAppBotActiveClientState::updateOrCreate(
                        [
                            "from" => $from,
                            "type" => "meta"
                        ],
                        [
                            "from" => $from,
                            'menu_option' => 'stop'
                        ]
                    );

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray()),
                    ]);

                    break;

                case 'schedule_preferrence':
                    \Log::info("edfedf");

                    $clientName = "*" . trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? '')) . "*";

                    $sid = $lng == "heb" ? "HX24009443dc6a202f914c9861b4c4052d" : "HX0cd64799afa9596f47e3cd2adcbcc3de";
                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "contentSid" => $sid,
                            "contentVariables" => json_encode([
                                '1' => $clientName,
                            ]),

                        ]
                    );

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray()),
                    ]);

                    WhatsAppBotActiveClientState::updateOrCreate(
                        [
                            "from" => $from,
                            "type" => "meta"
                        ],
                        [
                            "from" => $from,
                            'menu_option' => 'schedule_preferrence'
                        ]
                    );

                    break;

                case 'schedule_preferrence_input':
                    $scheduleChange = new ScheduleChange();
                    $scheduleChange->user_type = get_class($client);
                    $scheduleChange->user_id = $client->id;
                    $scheduleChange->reason = $client->lng == "en" ? "Change or update schedule" : 'שינוי או עדכון שיבוץ';
                    $scheduleChange->comments = trim($input);
                    $scheduleChange->save();

                    // Send message to team
                    $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));
                    $teammsg = "שלום צוות, לקוח *:client_name* ביקש. בקשתו היא: *:client_message* אנא בדוק וטפל בהתאם. בברכה, צוות השירות של ברום\n:comment_link";

                    $personalizedMessage = str_replace(
                        [':comment_link', ':client_name', ':client_message'],
                        [generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin'), $clientName, trim($input)],
                        $teammsg
                    );

                    sendTeamWhatsappMessage(config('services.whatsapp_groups.changes_cancellation'), ['name' => '', 'message' => $personalizedMessage]);

                    $confirmationMessage = $client->lng == 'heb'
                        ? "ההודעה שלך התקבלה ותועבר לצוות שלנו להמשך טיפול."
                        : "Your message has been received and will be forwarded to our team for further handling.";
                    // sendClientWhatsappMessage($from, ['message' => $confirmationMessage]);

                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$from",
                        [
                            "from" => $this->twilioWhatsappNumber,
                            "body" => $confirmationMessage,

                        ]
                    );
                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => $messageId,
                        'message'       => $twi->body ?? '',
                        'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($twi->toArray()),
                    ]);

                    WhatsAppBotActiveClientState::updateOrCreate(
                        [
                            "from" => $from,
                            "type" => "meta"
                        ],
                        [
                            "from" => $from,
                            'menu_option' => 'main_menu'
                        ]
                    );

                    break;
            }
        }
    }

    public function ClientOtpSend($client, $from, $lng, $source)
    {
        $otp = strval(random_int(100000, 999999)); // Generates a random 6-digit number

        $client->otp = $otp;
        $client->otp_expiry = now()->addMinutes(10);
        $client->save();

        $emailData = [
            'client' => $client,
        ];


        // Send Email Notification
        Mail::send('Mails.client.VerifedClient', $emailData, function ($message) use ($client) {
            $message->to($client->email);
            $message->bcc("office@broomservice.co.il");
            $message->subject(__('mail.verification.subject'));
        });

        WhatsAppBotActiveClientState::updateOrCreate(
            [
                "from" => $from,
                "type" => $source == "whapi" ? "whapi" : "meta"
            ],
            [
                "client_phone" => $client->phone,
                'menu_option' => 'not_recognized->enter_phone->email_sent'
            ]
        );

        if ($source == "whapi") {
            $nextMessage = $this->activeClientBotMessages['email_sent'][$lng];
            $personalizedMessage = str_replace(':email', substr($client->email, 0, 2), $nextMessage);
            $result = sendWhatsappMessage($from, array('name' => '', 'message' => $personalizedMessage, 'list' => [], 'buttons' => []));
            StoreWebhookResponse($personalizedMessage, $from, $result, true);
        } else {
            $sid = $lng == "heb" ? "HX3442c93620474fc3323f77783f80177a" : "HX60c74e6ee5a6faf3c777a263a789ffc7";
            $twi = $this->twilio->messages->create(
                "whatsapp:+$from",
                [
                    "from" => $this->twilioWhatsappNumber,
                    "contentSid" => $sid,
                    "contentVariables" => json_encode([
                        '1' => substr($client->email, 0, 2)
                    ]), // Pass the OTP as a variable

                ]
            );
            \Log::info($twi);
            WebhookResponse::create([
                'status'        => 1,
                'name'          => 'whatsapp',
                'message'       => $twi->body ?? '',
                'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                'number'        => $from,
                'flex'          => 'A',
                'read'          => 1,
                'data'          => json_encode($twi->toArray()),
            ]);
        }

        die();
    }

    public function detectLanguage($text)
    {
        // Regex for hebrew
        if (preg_match('/[\x{0590}-\x{05FF}]/u', $text)) {
            return 'heb';
        } else {
            return 'en';
        }
    }

    public function sendStopMessage($client, $source)
    {
        if (!$client) {
            return response()->json([
                'message' => 'User not found'
            ]);
        };

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::STOP,
            "notificationData" => [
                'client' => $client->toArray()
            ]
        ]));

        $enum = $source == "whapi" ? WhatsappMessageTemplateEnum::AFTER_STOP_TO_CLIENT_WHAPI : WhatsappMessageTemplateEnum::AFTER_STOP_TO_CLIENT;

        event(new WhatsappNotificationEvent([
            "type" => $enum,
            "notificationData" => [
                'client' => $client->toArray()
            ]
        ]));

        $client->disable_notification = 1;
        $client->save();

        die("STOPPED");
    }

    public function sendMainMenu($client, $from, $input = null)
    {
        // Check if the client is active
        $lng = $client->lng;
        $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));
        $sid = $lng == "heb" ? "HX290ac2d38673f69f4588643fd2850cab" : "HX46684b2aee6eca7848bd9a36d7a86e78";
        $twi = $this->twilio->messages->create(
            "whatsapp:+$from",
            [
                "from" => $this->twilioWhatsappNumber,
                "contentSid" => $sid,
                "contentVariables" => json_encode([
                    '1' => $clientName
                ]),

            ]
        );

        $activeClientBotstate = WhatsAppBotActiveClientState::where('from', $from)->first();
        $cacheKey = 'first_time_client_' . $from;

        if (!$activeClientBotstate && !Cache::has($cacheKey)) {
            Cache::put($cacheKey, true, now()->addHours(1));

            $scheduleChange = new ScheduleChange();
            $scheduleChange->user_type = get_class($client);
            $scheduleChange->user_id = $client->id;
            $scheduleChange->reason = $client->lng == "en" ? "Contact me urgently" : " צרו איתי קשר דחוף";
            $scheduleChange->comments = trim($input);
            $scheduleChange->save();

            // Send message to team
            $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));
            $teammsg = "שלום צוות, לקוח *:client_name* ביקש. בקשתו היא: *:client_message* אנא בדוק וטפל בהתאם. בברכה, צוות השירות של ברום\n:comment_link";

            $personalizedMessage = str_replace(
                [':comment_link', ':client_name', ':client_message'],
                [generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin'), $clientName, trim($input)],
                $teammsg
            );

            sendTeamWhatsappMessage(config('services.whatsapp_groups.changes_cancellation'), ['name' => '', 'message' => $personalizedMessage]);
        }

        WebhookResponse::create([
            'status'        => 1,
            'name'          => 'whatsapp',
            'message'       => $twi->body ?? '',
            'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
            'number'        => $from,
            'flex'          => 'A',
            'read'          => 1,
            'data'          => json_encode($twi->toArray()),
        ]);



        WhatsAppBotActiveClientState::updateOrCreate(
            [
                'from' => $from,
                'type' => 'meta'
            ],
            [
                'client_id' => $client->id,
                'menu_option' => 'main_menu',
                'lng' => $lng,
            ]
        );

        WhatsAppBotClientState::updateOrCreate([
            'client_id' => $client->id,
            'type' => 'meta'
        ], [
            'menu_option' => 'main_menu',
            'language' => $lng,
            'final' => 1,
        ]);

        return response()->json(['status' => 'success'], 200);
    }

    public function sendWhapiMainMenu($client, $from)
    {
        // Check if the client is active
        $lng = $client->lng;
        $list = [];
        $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));
        $initialMessage = $this->activeClientBotMessages['main_menu'][$lng];

        $personalizedMessage = str_replace(':client_name', $clientName, $initialMessage);
        $list = [
            'sections' => [
                [
                    'rows' => [
                        [
                            "id" => "1",
                            "title" => $client->lng == "heb" ? "צרו איתי קשר דחוף" : "Contact me urgently",
                        ],
                        [
                            "id" => "2",
                            "title" => $client->lng == "heb" ? "מתי מגיעים אלי?" : "Next service schedule?",
                        ],
                        [
                            "id" => "3",
                            "title" => $client->lng == "heb" ? "בקשה להצעת מחיר חדשה" : "Request a new quote",
                        ],
                        [
                            "id" => "4",
                            "title" => $client->lng == "heb" ? "הנה\"ח - פנייה למח' הנה\"ח" : "Invoice/account inquiry",
                        ],
                        [
                            "id" => "5",
                            "title" => $client->lng == "heb" ? "שינוי או עדכון שיבוץ" : "Change/update schedule",
                        ],
                        [
                            "id" => "6",
                            "title" => $client->lng == "heb" ? "גישה לפורטל הלקוחות שלנו" : "Access our client portal",
                        ]
                    ]
                ]
            ],
            'label' => $client->lng == 'heb' ? "להלן האפשרויות" : 'Here are options'
        ];


        $result = sendWhatsappMessage($from, array('name' => '', 'message' => $personalizedMessage, 'list' => [], 'buttons' => []));
        StoreWebhookResponse($personalizedMessage, $from, $result, true);

        WhatsAppBotActiveClientState::updateOrCreate(
            [
                'from' => $from,
                'type' => 'whapi',
            ],
            [
                'client_id' => $client->id,
                'menu_option' => 'main_menu',
                'lng' => $lng,
                'type' => 'whapi',
            ]
        );

        // WhatsAppBotActiveClientState::where('from', $from)->delete();

        WhatsAppBotClientState::updateOrCreate([
            'client_id' => $client->id,
            'type' => 'whapi',
        ], [
            'menu_option' => 'main_menu',
            'language' => $lng,
            'final' => 1,
            'type' => 'whapi',
        ]);

        return response()->json(['status' => 'success'], 200);
    }

    public function sendNewLeadMainMenu($lng, $from, $source)
    {

        if ($source == 'whapi-lead-bot') {
            $nextMessage = $this->botMessages['whapi_new_lead_menu'][$lng];
            $result = sendWhatsappMessage($from, array('name' => '', 'message' => $nextMessage, 'list' => [], 'buttons' => []));
            StoreWebhookResponse($nextMessage, $from, $result, true);
        } else {
            $sid = $lng == "heb" ? "HX3d7a626548e2c058c1fd609219588318" : "HX224fe723aaf81c50ee85b90a2ffbf859";

            $message = $this->twilio->messages->create(
                "whatsapp:+$from",
                [
                    "from" => $this->twilioWhatsappNumber,
                    "contentSid" => $sid,

                ]
            );
            \Log::info($message->sid);

            WebhookResponse::create([
                'status'        => 1,
                'name'          => 'whatsapp',
                'message'       =>  $message->body ?? '',
                'from'          =>  str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                'number'        =>  $from,
                'read'          => 1,
                'flex'          => 'A',
                'data'          => json_encode($message->toArray())
            ]);
        }

        $lead                = new Client;
        $lead->firstname     = '';
        $lead->lastname      = '';
        $lead->phone         = $from;
        $lead->email         = "";
        $lead->status        = 0;
        $lead->password      = Hash::make(Str::random(20));
        $lead->passcode      = $from;
        $lead->geo_address   = '';
        $lead->source       = $source;
        $lead->lng           = ($lng == 'heb' ? 'heb' : 'en');
        $lead->save();

        WhatsAppBotClientState::updateOrCreate([
            'client_id' => $lead->id,
            'type' => $source == 'meta-lead-bot' ? 'meta' : 'whapi',
        ], [
            'menu_option' => 'new_main_menu',
            'language' => $lng == 'heb' ? 'he' : 'en',
        ]);

        WhatsAppBotActiveClientState::updateOrCreate(
            [
                "from" => $from,
                'type' => $source == 'meta-lead-bot' ? 'meta' : 'whapi',
            ],
            [
                'menu_option' => 'new_main_menu',
                'lng' => $lng,
                "from" => $from,
            ]
        );


        return response()->json(['status' => 'success'], 200);
    }


    public function clientReview(Request $request)
    {
        try {
            $data = $request->all();
            $responseClientState = [];
            $message = null;
            $messageId = $data['SmsMessageSid'] ?? null;

            if (!$messageId) {
                return response()->json(['status' => 'Invalid message data'], 400);
            }

            // Check if the messageId exists in cache and matches
            if (Cache::get('client_review_processed_message_' . $messageId) === $messageId) {
                \Log::info('Already processed');
                return response()->json(['status' => 'Already processed'], 200);
            }

            // Store the messageId in the cache for 1 hour
            Cache::put('client_review_processed_message_' . $messageId, $messageId, now()->addHours(1));

            if ($data['SmsStatus'] == 'received') {
                $input = $data['Body'] ?? null;
                // $message_data = $data_returned['messages'];
                // if (Str::endsWith($message_data[0]['chat_id'], '@g.us')) {
                //     die("Group message");
                // }
                $from = $data['From'] ? str_replace("whatsapp:+", "", $data['From']) : $data['From'];

                $client = Client::where('phone', 'like', $from)->where('status', '2')->whereHas('lead_status', function ($q) {
                    $q->where('lead_status', LeadStatusEnum::ACTIVE_CLIENT);
                })->first();

                $msgStatus = null;
                if ($client) {
                    $msgStatus = Cache::get('client_review' . $client->id);
                }

                if (!empty($msgStatus)) {

                    $messageBody = trim($input);
                    $ButtonPayload = $data['ButtonPayload'] ?? null;
                    $last_input2 = Cache::get('client_review_input2' . $client->id) ?? null;

                    // $last_input1 = Cache::get('client_review_input1' . $client->id);

                    if (Cache::get('client_review_sorry' . $client->id) && !in_array(strtolower(trim($messageBody)), ["menu", "תפריט"])) {
                        Cache::forget('client_review_sorry' . $client->id);
                        Cache::forget('client_review_input2' . $client->id);
                        Cache::forget('client_review' . $client->id);
                    }

                    if ($ButtonPayload == '7') {

                        $message = $client->lng == "en" ? "We’re delighted to hear you were satisfied with our service! 🌟\nThank you for your positive feedback. We’re here if you need anything else."
                            : "שמחים לשמוע שהייתם מרוצים מהשירות שלנו! 🌟\nתודה רבה על הפידבק החיובי. אנחנו כאן לכל דבר נוסף.";

                        $sid = $client->lng == "heb" ? "HXc461f80819de9133a2f1e433c7f57acf" : "HXfbdae09ff346735b82dfe4a148053c58";
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,

                            ]
                        );


                        Notification::create([
                            'user_id' => $client->id,
                            'user_type' => Client::class,
                            'type' => NotificationTypeEnum::CLIENT_REVIEWED,
                            'status' => 'reviewed'
                        ]);

                        WebhookResponse::create([
                            'status'        => 1,
                            'name'          => 'whatsapp',
                            'entry_id'      => $messageId,
                            'message'       => $twi->body ?? '',
                            'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                            'number'        => $from,
                            'flex'          => 'A',
                            'read'          => 1,
                            'data'          => json_encode($twi->toArray()),
                        ]);

                        // sendClientWhatsappMessage($from, ['name' => '', 'message' => $message]);
                        sleep(2);
                        Cache::forget('client_review' . $client->id);
                    } else if ($ButtonPayload == '8') {

                        $message = $client->lng == "en" ? "Thank you for your feedback!\nPlease write your comment or request here."
                            : "תודה על הפידבק שלכם!\nאנא כתבו את ההערה או הבקשה שלכם.";

                        $sid = $client->lng == "heb" ? "HXa82657df48b6c9e6bc46e5d2642ef840" : "HXefd00a5e52e8d62ee3068e5ef379f56d";
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,

                            ]
                        );

                        WebhookResponse::create([
                            'status'        => 1,
                            'name'          => 'whatsapp',
                            'entry_id'      => $messageId,
                            'message'       => $twi->body ?? '',
                            'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                            'number'        => $from,
                            'flex'          => 'A',
                            'read'          => 1,
                            'data'          => json_encode($twi->toArray() ?? []),
                        ]);

                        // sendClientWhatsappMessage($from, ['name' => '', 'message' => $message]);

                        Cache::put('client_review_input2' . $client->id, 'client_review_input2', now()->addDay(1));
                    } else if (!empty($last_input2) && !empty($messageBody)) {

                        $job = Job::where('client_id', $client->id)
                            ->where('status', JobStatusEnum::COMPLETED)
                            ->latest()
                            ->first();

                        $admins = Admin::all();
                        $supervisor = $admins->where('role', 'supervisor')->first();
                        $existing = SupervisorJob::where('supervisor_id', $supervisor->id)
                            ->where('job_id', $job->id)
                            ->first();

                        if (!$existing) {
                            $superadmin = $admins->where('role', 'superadmin')->first();
                            // Otherwise, create the assignment
                            SupervisorJob::create([
                                'supervisor_id' => $supervisor->id,
                                'job_id' => $job->id,
                                'assigned_by_admin_id' => $superadmin->id,
                                'comment_by_client' => $messageBody
                            ]);
                        }

                        \Log::info('last input2');
                        $scheduleChange = ScheduleChange::create([
                            'user_type' => get_class($client),
                            'user_id' => $client->id,
                            'comments' => $messageBody,
                            "reason" => $client->lng == "en" ? "Client Feedback" : 'משוב לקוח',
                        ]);

                        $message = $client->lng == "en" ? "Thank you for your feedback! Your message has been received and will be forwarded to the supervisor for further handling.\nWe’re here for anything else you might need and will get back to you if necessary."
                            : "תודה על הפידבק שלכם! ההודעה שלכם התקבלה ותועבר למפקח להמשך טיפול.\nאנחנו כאן לכל דבר נוסף ונחזור אליכם במידת הצורך.";

                        $sid = $client->lng == "heb" ? "HX75abb0051f1f53d91ed0511a2a596857" : "HXbb2620ee9155ac68e0d88b6b7caf5c67";
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,

                            ]
                        );

                        Notification::create([
                            'user_id' => $client->id,
                            'user_type' => Client::class,
                            'type' => NotificationTypeEnum::CLIENT_REVIEWED,
                            'status' => 'reviewed'
                        ]);

                        WebhookResponse::create([
                            'status'        => 1,
                            'name'          => 'whatsapp',
                            'entry_id'      => $messageId,
                            'message'       => $twi->body ?? '',
                            'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                            'number'        => $from,
                            'flex'          => 'A',
                            'read'          => 1,
                            'data'          => json_encode($twi->toArray()),
                        ]);

                        // sendClientWhatsappMessage($from, ['name' => '', 'message' => $message]);

                        $teammsg = "שלום צוות,\n\n *:client_name* שיתף את ההערה או הבקשה הבאה בנוגע לשירות האחרון שקיבל:\n' *:message* \n\nאנא בדקו וטפלו בנושא בהקדם. עדכנו את הלקוח כשהנושא טופל.\n:comment_link";
                        $clientName = (($client->firstname ?? '') . ' ' . ($client->lastname ?? ''));
                        $teammsg = str_replace([
                            ':client_name',
                            ':message',
                            ':comment_link'
                        ], [
                            $clientName,
                            trim($scheduleChange->comments),
                            generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin')
                        ], $teammsg);

                        sendTeamWhatsappMessage(config('services.whatsapp_groups.reviews_of_clients'), ['name' => '', 'message' => $teammsg]);
                        sleep(2);
                        Cache::forget('client_review_input2' . $client->id);
                        Cache::forget('client_review_sorry' . $client->id);
                        Cache::forget('client_review' . $client->id);
                    } else {
                        $scheduleChange = ScheduleChange::create(
                            [
                                'user_type' => get_class($client),
                                'user_id' => $client->id,
                                'comments' => $messageBody,
                                "reason" => $client->lng == "en" ? "Contact me urgently" : " צרו איתי קשר דחוף",
                            ]
                        );
                        $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));
                        // $teammsg = "שלום צוות, הלקוח " . "*" . $clientName . "*" . "  ביקש לבצע שינוי בסידור העבודה שלו לשבוע הבא. הבקשה שלו היא: \"" . '*' . $messageBody . '*' . "\" אנא בדקו וטפלו בהתאם. בברכה, צוות ברום סרוויס\n:comment_link";
                        $teammsg = "שלום צוות, לקוח " . "*" . $clientName . "*" . " ביקש. בקשתו היא: " . "*" . trim($messageBody) . "*" . " אנא בדוק וטפל בהתאם. בברכה, צוות השירות של ברום\n:comment_link";
                        $personalizedMessage = str_replace(':comment_link', generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin'), $teammsg);

                        sendTeamWhatsappMessage(config('services.whatsapp_groups.urgent'), ['name' => '', 'message' => $personalizedMessage]);

                        $follow_up_msg = $client->lng == 'heb'
                            ? "ההודעה שלך התקבלה ותועבר לצוות \n.נחזור אליך בהקדם האפשרי"
                            : "Your message has been received and will be forwarded to our team.\nWe will get back to you as soon as possible.";


                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "body" => $follow_up_msg,

                            ]
                        );

                        WebhookResponse::create([
                            'status'        => 1,
                            'name'          => 'whatsapp',
                            'entry_id'      => $messageId,
                            'message'       => $twi->body ?? '',
                            'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                            'number'        => $from,
                            'flex'          => 'A',
                            'read'          => 1,
                            'data'          => json_encode($twi->toArray()),
                        ]);
                    }
                }
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function activeClientsMonday(Request $request)
    {
        try {
            $data = $request->all();
            \Log::info($data);
            $messageId = $data['SmsMessageSid'] ?? null;
            $responseClientState = [];
            $message = null;

            if (!$messageId) {
                return response()->json(['status' => 'Invalid message data'], 400);
            }

            // Check if the messageId exists in cache and matches
            if (Cache::get('client_monday_processed_message_' . $messageId) === $messageId) {
                \Log::info('Already processed');
                return response()->json(['status' => 'Already processed'], 200);
            }

            // Store the messageId in the cache for 1 hour
            Cache::put('client_monday_processed_message_' . $messageId, $messageId, now()->addHours(1));


            if ($data['SmsStatus'] == 'received') {
                // if (Str::endsWith($message_data[0]['chat_id'], '@g.us')) {
                //     die("Group message");
                // }
                $message = $data['Body'] ?? null;
                $listId = $data['ListId'] ?? $message;
                $ButtonPayload = $data['ButtonPayload'] ?? null;
                $from = $data['From'] ? str_replace("whatsapp:+", "", $data['From']) : $data['From'];

                $client = Client::where('phone', 'like', $from)->where('status', '2')->whereHas('lead_status', function ($q) {
                    $q->where('lead_status', LeadStatusEnum::ACTIVE_CLIENT);
                })->first();

                if ($client) {
                    $msgStatus = Cache::get('client_review' . $client->id);
                    $input = trim($message ?? '');
                    if (!empty($msgStatus) && ($input == '7' || $input == '8')) {
                        \Log::info('Client already reviewed');
                        die('Client already reviewed');
                    }

                    $msgStatus = Cache::get('client_review_input2' . $client->id);
                    if (!empty($msgStatus)) {
                        \Log::info('Client already reviewed');
                        die('Client already reviewed');
                    }
                }

                $isMonday = now()->isMonday();
                if ($client && $client->stop_last_message == 0) {

                    $msgStatus = Cache::get('client_monday_msg_status_' . $client->id);
                    if (!empty($msgStatus)) {
                        $menu_option = explode('->', $msgStatus);
                        $messageBody = trim($message ?? '');
                        $ButtonPayload = $data['ButtonPayload'] ?? null;
                        $last_menu = end($menu_option);

                        if ($last_menu == 'main_monday_msg' && ($listId == '1' || $ButtonPayload == '1')) {

                            $m = $client->lng == 'heb'
                                ? "מהו השינוי או הבקשה לשבוע הבא?"
                                : "What is your change for next week?";

                            $twi = $this->twilio->messages->create(
                                "whatsapp:+$from",
                                [
                                    "from" => $this->twilioWhatsappNumber,
                                    "body" => $m,

                                ]
                            );

                            Cache::put('client_monday_msg_status_' . $client->id, 'main_monday_msg->next_week_change', now()->addDay(1));
                            WebhookResponse::create([
                                'status'        => 1,
                                'name'          => 'whatsapp',
                                'entry_id'      => $messageId,
                                'message'       => $twi->body ?? "",
                                'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                                'number'        => $from,
                                'flex'          => 'A',
                                'read'          => 1,
                                'data'          => json_encode($twi->toArray()),
                            ]);
                        } else if ($last_menu == 'next_week_change' && !empty($messageBody)) {
                            $scheduleChange = ScheduleChange::create(
                                [
                                    'user_type' => get_class($client),
                                    'user_id' => $client->id,
                                    'comments' => $messageBody,
                                    "reason" => $client->lng == "en" ? "Change or update schedule" : 'שינוי או עדכון שיבוץ',
                                ]
                            );
                            $clientName = "*" . trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? '')) . "*";
                            $msg = trim($messageBody);

                            $teammsg = "שלום צוות, הלקוח {$clientName} ביקש לבצע שינוי בסידור העבודה שלו לשבוע הבא. הבקשה שלו היא: *{$msg}* אנא בדקו וטפלו בהתאם. בברכה, צוות ברום סרוויס \n :comment_link";

                            $personalizedMessage = str_replace(':comment_link', generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin'), $teammsg);

                            sendTeamWhatsappMessage(config('services.whatsapp_groups.changes_cancellation'), ['name' => '', 'message' => $personalizedMessage]);

                            Cache::put('client_monday_msg_status_' . $client->id, 'main_monday_msg->next_week_change->review_changes', now()->addDay(1));

                            // Send follow-up message
                            if ($client->lng == 'heb') {
                                $message = 'שלום ' . $client->firstname . " " . $client->lastname . ',

                                    ההודעה שלך התקבלה ותועבר לצוות שלנו להמשך טיפול.

                                    להלן ההודעה ששלחת:
                                    "' . $scheduleChange->comments . '"

                                    האם תרצה לשנות את ההודעה או לבקש משהו נוסף?

                                    השב 1 כדי לשנות את ההודעה.
                                    השב 2 כדי להוסיף מידע נוסף.
                                    במידה ואין שינויים או מידע נוסף, אין צורך בפעולה נוספת.

                                    המשך יום נפלא! 🌸
                                    בברכה,
                                    צוות ברום סרוויס 🌹
                                    www.broomservice.co.il
                                    טלפון: 03-525-70-60
                                    office@broomservice.co.il';
                            } else {
                                $message = 'Hello '  . $client->firstname . " " . $client->lastname . ',

                                    Your message has been received and will be forwarded to our team for further handling.

                                    Here is the message you sent:
                                    "' . $scheduleChange->comments . '"

                                    Would you like to edit your message or add anything else?

                                    Reply 1 to edit your message.
                                    Reply 2 to add additional information.
                                    If there are no changes or additional information, no further action is needed.

                                    Have a wonderful day! 🌸
                                    Best Regards,
                                    The Broom Service Team 🌹
                                    www.broomservice.co.il
                                    Phone: 03-525-70-60
                                    office@broomservice.co.il';
                            }


                            $sid = $client->lng == "heb" ? "HXb44309cfdec973dc0fa8709509c4b718" : "HX059442ac501424d65f6c225e19711d11";

                            $twi = $this->twilio->messages->create(
                                "whatsapp:+$from",
                                [
                                    "from" => $this->twilioWhatsappNumber,
                                    "contentSid" => $sid,
                                    "contentVariables" => json_encode([
                                        '1' => (($client->firstname ?? '') . ' ' . ($client->lastname ?? '')),
                                        '2' => $scheduleChange->comments,
                                    ]),

                                ]
                            );

                            WebhookResponse::create([
                                'status'        => 1,
                                'name'          => 'whatsapp',
                                'entry_id'      => $messageId,
                                'message'       => $twi->body ?? '',
                                'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                                'number'        => $from,
                                'flex'          => 'A',
                                'read'          => 1,
                                'data'          => json_encode($twi->toArray()),
                            ]);

                            // sendClientWhatsappMessage($from, ['message' => $message]);
                        } else if ($last_menu == 'review_changes' && ($listId == '1' || $ButtonPayload == '1')) {
                            // Cache the user's intention to edit
                            Cache::put('client_monday_msg_status_' . $client->id, 'main_monday_msg->next_week_change->review_changes->changes', now()->addDay(1));

                            $promptMessage = $client->lng == 'heb'
                                ? "מהו השינוי או הבקשה לשבוע הבא?"
                                : "What is your change or request for next week?";

                            $twi = $this->twilio->messages->create(
                                "whatsapp:+$from",
                                [
                                    "from" => $this->twilioWhatsappNumber,
                                    "body" => $promptMessage,

                                ]
                            );

                            WebhookResponse::create([
                                'status'        => 1,
                                'name'          => 'whatsapp',
                                'entry_id'      => $messageId,
                                'message'       => $twi->body ?? '',
                                'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                                'number'        => $from,
                                'flex'          => 'A',
                                'read'          => 1,
                                'data'          => json_encode($twi->toArray()),
                            ]);

                            // sendClientWhatsappMessage($from, ['message' => $promptMessage]);
                        } else if ($last_menu == 'review_changes' && $listId == '2') {
                            // Cache the user's intention to edit
                            Cache::put('client_monday_msg_status_' . $client->id, 'main_monday_msg->next_week_change->review_changes->additional', now()->addDay(1));

                            $promptMessage = $client->lng == 'heb'
                                ? "אנא הזן הודעה כדי להוסיף מידע נוסף."
                                : "Please enter a message to add additional information.";

                            $twi = $this->twilio->messages->create(
                                "whatsapp:+$from",
                                [
                                    "from" => $this->twilioWhatsappNumber,
                                    "body" => $promptMessage,

                                ]
                            );

                            WebhookResponse::create([
                                'status'        => 1,
                                'name'          => 'whatsapp',
                                'entry_id'      => $messageId,
                                'message'       => $twi->body ?? '',
                                'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                                'number'        => $from,
                                'flex'          => 'A',
                                'read'          => 1,
                                'data'          => json_encode($twi->toArray()),
                            ]);

                            // sendClientWhatsappMessage($from, ['message' => $promptMessage]);
                        } else if ($last_menu == 'changes' && !empty($messageBody)) {
                            // Process editing the existing message
                            $scheduleChange = ScheduleChange::where('user_type', get_class($client))
                                ->where('user_id', $client->id)
                                ->where('status', 'pending')
                                ->latest()
                                ->first();

                            if ($scheduleChange) {
                                $scheduleChange->comments = $messageBody;
                                $scheduleChange->save();
                                $clientName = (($client->firstname ?? '') . ' ' . ($client->lastname ?? ''));
                                $teammsg = "שלום צוות, הלקוח " . "*" . $clientName . "*" . "  ביקש לבצע שינוי בסידור העבודה שלו לשבוע הבא. הבקשה שלו היא: \"" . '*' . $messageBody . '*' . "\" אנא בדקו וטפלו בהתאם. בברכה, צוות ברום סרוויס\n:comment_link";
                                $personalizedMessage = str_replace(':comment_link', generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin'), $teammsg);

                                sendTeamWhatsappMessage(config('services.whatsapp_groups.changes_cancellation'), ['name' => '', 'message' => $personalizedMessage]);

                                $confirmationMessage = $client->lng == 'heb'
                                    ? "ההודעה שלך התקבלה ותועבר לצוות שלנו להמשך טיפול."
                                    : "Your message has been received and will be forwarded to our team for further handling.";

                                $twi = $this->twilio->messages->create(
                                    "whatsapp:+$from",
                                    [
                                        "from" => $this->twilioWhatsappNumber,
                                        "body" => $confirmationMessage,

                                    ]
                                );

                                WebhookResponse::create([
                                    'status'        => 1,
                                    'name'          => 'whatsapp',
                                    'entry_id'      => $messageId,
                                    'message'       => $twi->body ?? '',
                                    'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                                    'number'        => $from,
                                    'flex'          => 'A',
                                    'read'          => 1,
                                    'data'          => json_encode($twi->toArray()),
                                ]);
                                // sendClientWhatsappMessage($from, ['message' => $confirmationMessage]);
                            }
                            $client->stop_last_message = 1;
                            $client->save();

                            // Clear the cache after the action is complete
                            Cache::forget('client_monday_msg_status_' . $client->id);
                        } else if ($last_menu == 'additional' && !empty($messageBody)) {
                            // Process adding additional information
                            $scheduleChange = new ScheduleChange();
                            $scheduleChange->user_type = get_class($client);
                            $scheduleChange->user_id = $client->id;
                            $scheduleChange->reason = $client->lng == "en" ? "Change or update schedule" : 'שינוי או עדכון שיבוץ';
                            $scheduleChange->comments = $messageBody;
                            $scheduleChange->save();
                            $clientName = (($client->firstname ?? '') . ' ' . ($client->lastname ?? ''));
                            $teammsg = "שלום צוות, הלקוח " . "*" . $clientName . "*" . " ביקש לבצע שינוי בסידור העבודה שלו לשבוע הבא. הבקשה שלו היא: \"" . '*' . $messageBody . '*' . "\" אנא בדקו וטפלו בהתאם. בברכה, צוות ברום סרוויס\n:comment_link";
                            $personalizedMessage = str_replace(':comment_link', generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin'), $teammsg);

                            sendTeamWhatsappMessage(config('services.whatsapp_groups.changes_cancellation'), ['name' => '', 'message' => $personalizedMessage]);

                            $confirmationMessage = $client->lng == 'heb'
                                ? "ההודעה שלך התקבלה ותועבר לצוות שלנו להמשך טיפול."
                                : "Your message has been received and will be forwarded to our team for further handling.";

                            $twi = $this->twilio->messages->create(
                                "whatsapp:+$from",
                                [
                                    "from" => $this->twilioWhatsappNumber,
                                    "body" => $confirmationMessage,

                                ]
                            );

                            WebhookResponse::create([
                                'status'        => 1,
                                'name'          => 'whatsapp',
                                'entry_id'      => $messageId,
                                'message'       => $twi->body ?? '',
                                'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                                'number'        => $from,
                                'flex'          => 'A',
                                'read'          => 1,
                                'data'          => json_encode($twi->toArray()),
                            ]);

                            // sendClientWhatsappMessage($from, ['message' => $confirmationMessage]);
                            $client->stop_last_message = 1;
                            $client->save();
                            // Clear the cache after the action is complete
                            Cache::forget('client_monday_msg_status_' . $client->id);
                        } else if (!in_array(strtolower(trim($messageBody)), ["stop", "הפסק"])) {

                            $scheduleChange = ScheduleChange::create(
                                [
                                    'user_type' => get_class($client),
                                    'user_id' => $client->id,
                                    'comments' => $messageBody,
                                    "reason" => $client->lng == "en" ? "Contact me urgently" : " צרו איתי קשר דחוף",
                                ]
                            );
                            $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));
                            // $teammsg = "שלום צוות, הלקוח " . "*" . $clientName . "*" . "  ביקש לבצע שינוי בסידור העבודה שלו לשבוע הבא. הבקשה שלו היא: \"" . '*' . $messageBody . '*' . "\" אנא בדקו וטפלו בהתאם. בברכה, צוות ברום סרוויס\n:comment_link";
                            $teammsg = "שלום צוות, לקוח " . "*" . $clientName . "*" . " ביקש. בקשתו היא: " . "*" . trim($messageBody) . "*" . " אנא בדוק וטפל בהתאם. בברכה, צוות השירות של ברום\n:comment_link";
                            $personalizedMessage = str_replace(':comment_link', generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin'), $teammsg);

                            sendTeamWhatsappMessage(config('services.whatsapp_groups.urgent'), ['name' => '', 'message' => $personalizedMessage]);

                            $sid = $client->lng == "heb" ? "HXc7e62132b206473394802ae894c09d0b" : "HX634a3b4280e6bee8fb66d3507356629e";

                            $follow_up_msg = $client->lng == 'heb'
                                ? "ההודעה שלך התקבלה ותועבר לצוות \n.נחזור אליך בהקדם האפשרי"
                                : "Your message has been received and will be forwarded to our team.\nWe will get back to you as soon as possible.";


                            $twi = $this->twilio->messages->create(
                                "whatsapp:+$from",
                                [
                                    "from" => $this->twilioWhatsappNumber,
                                    // "contentSid" => $sid,
                                    "body" => $follow_up_msg,

                                ]
                            );

                            WebhookResponse::create([
                                'status'        => 1,
                                'name'          => 'whatsapp',
                                'entry_id'      => $messageId,
                                'message'       => $twi->body ?? '',
                                'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                                'number'        => $from,
                                'flex'          => 'A',
                                'read'          => 1,
                                'data'          => json_encode($twi->toArray()),
                            ]);

                            // sendClientWhatsappMessage($from, ['message' => $follow_up_msg]);
                        }
                    }
                }
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function activeClientsWednesday(Request $request)
    {
        \Log::info('activeClientsWednesday');
        try {
            $data = $request->all();
            $responseClientState = [];
            $message = null;

            $messageId = $data['SmsMessageSid'] ?? null;

            if (!$messageId) {
                return response()->json(['status' => 'Invalid message data'], 400);
            }

            // Check if the messageId exists in cache and matches
            if (Cache::get('client_wednesday_processed_message_' . $messageId) === $messageId) {
                \Log::info('Already processed');
                return response()->json(['status' => 'Already processed'], 200);
            }

            // Store the messageId in the cache for 1 hour
            Cache::put('client_wednesday_processed_message_' . $messageId, $messageId, now()->addHours(1));


            if ($data['SmsStatus'] == 'received') {
                $input = trim($data['Body'] ?? '');
                $listId = $data['ListId'] ?? $message;
                $ButtonPayload = $data['ButtonPayload'] ?? null;

                $from = $data['From'] ? str_replace("whatsapp:+", "", $data['From']) : $data['From'];

                $client = Client::where('phone', 'like', $from)->where('status', '2')->whereHas('lead_status', function ($q) {
                    $q->where('lead_status', LeadStatusEnum::ACTIVE_CLIENT);
                })->first();

                if ($client) {
                    $msgStatus = Cache::get('client_review' . $client->id);
                    if (!empty($msgStatus) && ($listId == '7' || $listId == '8')) {
                        \Log::info('Client already reviewed');
                        die('Client already reviewed');
                    }

                    $msgStatus = Cache::get('client_review_input2' . $client->id);
                    if (!empty($msgStatus)) {
                        \Log::info('Client already reviewed');
                        die('Client already reviewed');
                    }
                }

                $isWednesday = now()->isWednesday();

                if ($isWednesday && $client) {

                    $msgStatus = Cache::get('client_job_confirm_msg' . $client->id);
                    \Log::info('$msgStatus', [$msgStatus]);

                    if (!empty($msgStatus)) {
                        $menu_option = explode('->', $msgStatus);
                        $messageBody = trim($data['Body'] ?? '');
                        $last_menu = end($menu_option);

                        if ($last_menu == 'main_msg' && ($listId == '1' || $ButtonPayload == '1')) {
                            $m = $client->lng == 'heb'
                                ? "מהו השינוי או הבקשה לשבוע הבא?"
                                : "What is your change for next week?";

                            $twi = $this->twilio->messages->create(
                                "whatsapp:+$from",
                                [
                                    "from" => $this->twilioWhatsappNumber,
                                    "body" => $m,

                                ]
                            );
                            // sendClientWhatsappMessage($from, ['name' => '', 'message' => $m]);
                            Cache::put('client_job_confirm_msg' . $client->id, 'main_msg->next_week_change', now()->addDay(1));
                            WebhookResponse::create([
                                'status'        => 1,
                                'name'          => 'whatsapp',
                                'entry_id'      => $messageId,
                                'message'       => $twi->body ?? '',
                                'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                                'number'        => $from,
                                'flex'          => 'A',
                                'read'          => 1,
                                'data'          => json_encode($twi->toArray()),
                            ]);
                        } else if ($last_menu == 'next_week_change' && !empty($messageBody)) {
                            $scheduleChange = ScheduleChange::create(
                                [
                                    'user_type' => get_class($client),
                                    'user_id' => $client->id,
                                    'comments' => $messageBody,
                                    "reason" => $client->lng == "en" ? "Change or update schedule" : 'שינוי או עדכון שיבוץ',
                                ]
                            );
                            $clientName = (($client->firstname ?? '') . ' ' . ($client->lastname ?? ''));
                            $teammsg = "שלום צוות, הלקוח " . "*" . $clientName . "*" . "  ביקש לבצע שינוי בסידור העבודה שלו לשבוע הבא. הבקשה שלו היא: \"" . '*' . $messageBody . '*' . "\" אנא בדקו וטפלו בהתאם. בברכה, צוות ברום סרוויס\n:comment_link";
                            $personalizedMessage = str_replace(':comment_link', generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin'), $teammsg);

                            sendTeamWhatsappMessage(config('services.whatsapp_groups.changes_cancellation'), ['name' => '', 'message' => $personalizedMessage]);

                            Cache::put('client_job_confirm_msg' . $client->id, 'main_msg->next_week_change->review_changes', now()->addDay(1));

                            // Send follow-up message
                            if ($client->lng == 'heb') {
                                $message = 'שלום ' . $client->firstname . " " . $client->lastname . ',

ההודעה שלך התקבלה ותועבר לצוות שלנו להמשך טיפול.

להלן ההודעה ששלחת:
"' . $scheduleChange->comments . '"

האם תרצה לשנות את ההודעה או לבקש משהו נוסף?

השב 1 כדי לשנות את ההודעה.
השב 2 כדי להוסיף מידע נוסף.
במידה ואין שינויים או מידע נוסף, אין צורך בפעולה נוספת.

המשך יום נפלא! 🌸
בברכה,
צוות ברום סרוויס 🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il';
                            } else {
                                $message = 'Hello '  . $client->firstname . " " . $client->lastname . ',

Your message has been received and will be forwarded to our team for further handling.

Here is the message you sent:
"' . $scheduleChange->comments . '"

Would you like to edit your message or add anything else?

Reply 1 to edit your message.
Reply 2 to add additional information.
If there are no changes or additional information, no further action is needed.

Have a wonderful day! 🌸
Best Regards,
The Broom Service Team 🌹
www.broomservice.co.il
Phone: 03-525-70-60
office@broomservice.co.il';
                            }

                            $sid = $client->lng == "heb" ? "HXb44309cfdec973dc0fa8709509c4b718" : "HX059442ac501424d65f6c225e19711d11";

                            $twi = $this->twilio->messages->create(
                                "whatsapp:+$from",
                                [
                                    "from" => $this->twilioWhatsappNumber,
                                    "contentSid" => $sid,
                                    "contentVariables" => json_encode([
                                        '1' => (($client->firstname ?? '') . ' ' . ($client->lastname ?? '')),
                                        '2' => $scheduleChange->comments,
                                    ]),

                                ]
                            );

                            WebhookResponse::create([
                                'status'        => 1,
                                'name'          => 'whatsapp',
                                'entry_id'      => $messageId,
                                'message'       => $twi->body ?? '',
                                'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                                'number'        => $from,
                                'flex'          => 'A',
                                'read'          => 1,
                                'data'          => json_encode($twi->toArray()),
                            ]);

                            // sendClientWhatsappMessage($from, ['message' => $message]);
                        } else if ($last_menu == 'review_changes' && ($listId == '1' || $ButtonPayload == '1')) {
                            // Cache the user's intention to edit
                            Cache::put('client_job_confirm_msg' . $client->id, 'main_msg->next_week_change->review_changes->changes', now()->addDay(1));

                            $promptMessage = $client->lng == 'heb'
                                ? "מהו השינוי או הבקשה לשבוע הבא?"
                                : "What is your change or request for next week?";

                            $twi = $this->twilio->messages->create(
                                "whatsapp:+$from",
                                [
                                    "from" => $this->twilioWhatsappNumber,
                                    "body" => $promptMessage,

                                ]
                            );

                            WebhookResponse::create([
                                'status'        => 1,
                                'name'          => 'whatsapp',
                                'entry_id'      => $messageId,
                                'message'       => $twi->body ?? '',
                                'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                                'number'        => $from,
                                'flex'          => 'A',
                                'read'          => 1,
                                'data'          => json_encode($twi->toArray()),
                            ]);

                            // sendClientWhatsappMessage($from, ['message' => $promptMessage]);
                        } else if ($last_menu == 'review_changes' && $listId == '2') {
                            // Cache the user's intention to edit
                            Cache::put('client_job_confirm_msg' . $client->id, 'main_msg->next_week_change->review_changes->additional', now()->addDay(1));

                            $promptMessage = $client->lng == 'heb'
                                ? "אנא הזן הודעה כדי להוסיף מידע נוסף."
                                : "Please enter a message to add additional information.";

                            $twi = $this->twilio->messages->create(
                                "whatsapp:+$from",
                                [
                                    "from" => $this->twilioWhatsappNumber,
                                    "body" => $promptMessage,

                                ]
                            );

                            WebhookResponse::create([
                                'status'        => 1,
                                'name'          => 'whatsapp',
                                'entry_id'      => $messageId,
                                'message'       => $twi->body ?? '',
                                'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                                'number'        => $from,
                                'flex'          => 'A',
                                'read'          => 1,
                                'data'          => json_encode($twi->toArray()),
                            ]);

                            // sendClientWhatsappMessage($from, ['message' => $promptMessage]);
                        } else if ($last_menu == 'changes' && !empty($messageBody)) {
                            // Process editing the existing message
                            $scheduleChange = ScheduleChange::where('user_type', get_class($client))
                                ->where('user_id', $client->id)
                                ->where('status', 'pending')
                                ->latest()
                                ->first();

                            if ($scheduleChange) {
                                $scheduleChange->comments = $messageBody;
                                $scheduleChange->save();

                                // Send message to team
                                $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));
                                $teammsg = "שלום צוות, הלקוח " . "*" . $clientName . "*" . "  ביקש לבצע שינוי בסידור העבודה שלו לשבוע הבא. הבקשה שלו היא: \"" . '*' . trim($messageBody) . '*' . "\" אנא בדקו וטפלו בהתאם. בברכה, צוות ברום סרוויס\n:comment_link";
                                $personalizedMessage = str_replace(':comment_link', generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin'), $teammsg);

                                sendTeamWhatsappMessage(config('services.whatsapp_groups.changes_cancellation'), ['name' => '', 'message' => $personalizedMessage]);

                                $confirmationMessage = $client->lng == 'heb'
                                    ? "ההודעה שלך התקבלה ותועבר לצוות שלנו להמשך טיפול."
                                    : "Your message has been received and will be forwarded to our team for further handling.";

                                $twi = $this->twilio->messages->create(
                                    "whatsapp:+$from",
                                    [
                                        "from" => $this->twilioWhatsappNumber,
                                        "body" => $confirmationMessage,

                                    ]
                                );

                                WebhookResponse::create([
                                    'status'        => 1,
                                    'name'          => 'whatsapp',
                                    'entry_id'      => $messageId,
                                    'message'       => $twi->body ?? '',
                                    'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                                    'number'        => $from,
                                    'flex'          => 'A',
                                    'read'          => 1,
                                    'data'          => json_encode($twi->toArray()),
                                ]);
                                // sendClientWhatsappMessage($from, ['message' => $confirmationMessage]);
                            }
                            sleep(2);
                            // Clear the cache after the action is complete
                            Cache::forget('client_job_confirm_msg' . $client->id);
                        } else if ($last_menu == 'additional' && !empty($messageBody)) {
                            // Process adding additional information
                            $scheduleChange = new ScheduleChange();
                            $scheduleChange->user_type = get_class($client);
                            $scheduleChange->user_id = $client->id;
                            $scheduleChange->reason = $client->lng == "en" ? "Change or update schedule" : 'שינוי או עדכון שיבוץ';
                            $scheduleChange->comments = $messageBody;
                            $scheduleChange->save();

                            // Send message to team
                            $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));
                            $teammsg = "שלום צוות, הלקוח " . "*" . $clientName . "*" . " ביקש לבצע שינוי בסידור העבודה שלו לשבוע הבא. הבקשה שלו היא: \"" . '*' . $messageBody . '*' . "\" אנא בדקו וטפלו בהתאם. בברכה, צוות ברום סרוויס\n:comment_link";
                            $personalizedMessage = str_replace(':comment_link', generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin'), $teammsg);

                            sendTeamWhatsappMessage(config('services.whatsapp_groups.changes_cancellation'), ['name' => '', 'message' => $personalizedMessage]);

                            $confirmationMessage = $client->lng == 'heb'
                                ? "ההודעה שלך התקבלה ותועבר לצוות שלנו להמשך טיפול."
                                : "Your message has been received and will be forwarded to our team for further handling.";
                            // sendClientWhatsappMessage($from, ['message' => $confirmationMessage]);

                            $twi = $this->twilio->messages->create(
                                "whatsapp:+$from",
                                [
                                    "from" => $this->twilioWhatsappNumber,
                                    "body" => $confirmationMessage,

                                ]
                            );

                            WebhookResponse::create([
                                'status'        => 1,
                                'name'          => 'whatsapp',
                                'entry_id'      => $messageId,
                                'message'       => $twi->body ?? '',
                                'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                                'number'        => $from,
                                'flex'          => 'A',
                                'read'          => 1,
                                'data'          => json_encode($twi->toArray()),
                            ]);

                            $client->stop_last_message = 1;
                            $client->save();
                            // Clear the cache after the action is complete
                            Cache::forget('client_job_confirm_msg' . $client->id);
                        } else if (!in_array(strtolower(trim($messageBody)), ["stop", "הפסק"])) {

                            $scheduleChange = ScheduleChange::create(
                                [
                                    'user_type' => get_class($client),
                                    'user_id' => $client->id,
                                    'comments' => $messageBody,
                                    "reason" => $client->lng == "en" ? "Contact me urgently" : " צרו איתי קשר דחוף",
                                ]
                            );
                            $clientName = trim(trim($client->firstname ?? '') . ' ' . trim($client->lastname ?? ''));
                            // $teammsg = "שלום צוות, הלקוח " . "*" . $clientName . "*" . "  ביקש לבצע שינוי בסידור העבודה שלו לשבוע הבא. הבקשה שלו היא: \"" . '*' . $messageBody . '*' . "\" אנא בדקו וטפלו בהתאם. בברכה, צוות ברום סרוויס\n:comment_link";
                            $teammsg = "שלום צוות, לקוח " . "*" . $clientName . "*" . " ביקש. בקשתו היא: " . "*" . trim($messageBody) . "*" . " אנא בדוק וטפל בהתאם. בברכה, צוות השירות של ברום\n:comment_link";
                            $personalizedMessage = str_replace(':comment_link', generateShortUrl(url('admin/schedule-requests' . '?id=' . $scheduleChange->id), 'admin'), $teammsg);

                            sendTeamWhatsappMessage(config('services.whatsapp_groups.urgent'), ['name' => '', 'message' => $personalizedMessage]);


                            $follow_up_msg = $client->lng == 'heb'
                                ? "ההודעה שלך התקבלה ותועבר לצוות \n.נחזור אליך בהקדם האפשרי"
                                : "Your message has been received and will be forwarded to our team.\nWe will get back to you as soon as possible.";

                            $sid = $client->lng == "heb" ? "HXc7e62132b206473394802ae894c09d0b" : "HX634a3b4280e6bee8fb66d3507356629e";

                            $twi = $this->twilio->messages->create(
                                "whatsapp:+$from",
                                [
                                    "from" => $this->twilioWhatsappNumber,
                                    // "contentSid" => $sid,
                                    "body" => $follow_up_msg,

                                ]
                            );

                            WebhookResponse::create([
                                'status'        => 1,
                                'name'          => 'whatsapp',
                                'entry_id'      => $messageId,
                                'message'       => $twi->body ?? '',
                                'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                                'number'        => $from,
                                'flex'          => 'A',
                                'read'          => 1,
                                'data'          => json_encode($twi->toArray()),
                            ]);

                            // sendClientWhatsappMessage($from, ['message' => $follow_up_msg]);
                        }
                    }
                }
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function initGoogleConfig()
    {
        // Retrieve the Google Sheet ID from settings
        $this->spreadsheetId = Setting::query()
            ->where('key', SettingKeyEnum::GOOGLE_SHEET_ID)
            ->value('value');

        $this->googleRefreshToken = Setting::query()
            ->where('key', SettingKeyEnum::GOOGLE_REFRESH_TOKEN)
            ->value('value');

        if (!$this->googleRefreshToken) {
            throw new Exception('Error: Google Refresh Token not found.');
        }

        // Refresh the access token
        $googleClient = $this->getClient();
        $googleClient->refreshToken($this->googleRefreshToken);
        $response = $googleClient->fetchAccessTokenWithRefreshToken($this->googleRefreshToken);
        $this->googleAccessToken = $response['access_token'];

        // Save the new access token
        Setting::updateOrCreate(
            ['key' => SettingKeyEnum::GOOGLE_ACCESS_TOKEN],
            ['value' => $this->googleAccessToken]
        );

        if (!$this->googleAccessToken) {
            throw new Exception('Error: Google Access Token not found.');
        }
    }

    public function getAllSheetNames()
    {
        // Google Sheets API endpoint to fetch spreadsheet metadata
        $metadataUrl = $this->googleSheetEndpoint . $this->spreadsheetId;
        try {
            // Fetch metadata to get sheet names
            $metadataResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->googleAccessToken,
                'Content-Type' => 'application/json',
            ])->get($metadataUrl);
            if ($metadataResponse->successful()) {
                $metadata = $metadataResponse->json();
                $sheets = $metadata['sheets'] ?? [];
                return array_map(fn($sheet) => $sheet['properties']['title'], $sheets);
            } else {
                return [];
            }
        } catch (\Exception $e) {
            Log::error('Error occurred during fetching Google sheet', [
                'error' => $e->getMessage(),
                'spreadsheetId' => $this->spreadsheetId,
            ]);
            throw $e;
        }
    }

    public function getGoogleSheetData($sName = null)
    {
        try {
            if (!$sName) {
                return [];
            }
            $range = $sName . '!A:Z'; // Adjust range as needed
            $url = $this->googleSheetEndpoint . $this->spreadsheetId . '/values/' . $range;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->googleAccessToken,
                'Content-Type' => 'application/json',
            ])->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $rows = $data['values'] ?? [];
                return $rows;
            } else {
                return [];
            }
        } catch (\Exception $e) {
            Log::error('Error occurred during fetching Google sheet', [
                'error' => $e->getMessage(),
                'spreadsheetId' => $this->spreadsheetId,
            ]);
            throw $e;
        }
    }

    public function convertDate($dateString, $sheet)
    {
        // Extract year from the sheet (assumes format: "Month Year" e.g., "ינואר 2025" or "דצמבר 2024")
        preg_match('/\d{4}/', $sheet, $yearMatch);
        $year = $yearMatch[0] ?? date('Y'); // Default to current year if no match

        // Normalize different formats (convert ',' to '.')
        $dateString = str_replace(',', '.', $dateString);

        // Extract day and month
        if (preg_match('/(\d{1,2})\.(\d{1,2})/', $dateString, $matches)) {
            // Format: 12.01 → day = 12, month = 01
            $day = sprintf('%02d', $matches[1]);
            $month = sprintf('%02d', $matches[2]);
        } elseif (preg_match('/(\d{2})(\d{2})/', $dateString, $matches)) {
            // Format: 0401 → day = 04, month = 01
            $day = sprintf('%02d', $matches[1]);
            $month = sprintf('%02d', $matches[2]);
        } elseif (preg_match('/(\d{1,2})\s*,\s*(\d{1,2})/', $dateString, $matches)) {
            // Format: 3,1 → day = 3, month = 1
            $day = sprintf('%02d', $matches[1]);
            $month = sprintf('%02d', $matches[2]);
        } else {
            return false;
        }

        // Return formatted date
        return "$year-$month-$day";
    }

    public function saveLeadFromContactForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required_without:email'],
            'email' => ['required_without:phone|email'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $phone = $request->phone;

        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // 2. If there's any string or invalid characters in the phone, extract the digits
        if (preg_match('/\d+/', $phone, $matches)) {
            $phone = $matches[0]; // Extract the digits

            // Reapply rules on extracted phone number
            // If the phone number starts with 0, add 972 and remove the first 0
            if (strpos($phone, '0') === 0) {
                $phone = '972' . substr($phone, 1);
            }

            // If the phone number starts with +, remove the +
            if (strpos($phone, '+') === 0) {
                $phone = substr($phone, 1);
            }
        }

        $phoneLength = strlen($phone);
        if (($phoneLength === 9 || $phoneLength === 10) && strpos($phone, '972') !== 0) {
            $phone = '972' . $phone;
        }

        $lead_exists = Client::with('property_addresses')->where('phone', $phone)->orWhere('email', $request->email)->exists();
        if (!$lead_exists) {
            $lead = new Client;

            $name = explode(' ', $request->name);

            $lead->firstname = $name[0];
            $lead->lastname = (isset($name[1])) ? $name[1] : '';
            $lead->phone = $phone;
            $lead->email = $request->email;
            $lead->status = 0;
            $lead->lng = 'heb';
            $lead->password = Hash::make(Str::random(20));
            $lead->passcode = $phone;
            $lead->source = 'website';
            $lead->save();

            $lead->lead_status()->updateOrCreate(
                [],
                ['lead_status' => LeadStatusEnum::PENDING]
            );

            LeadActivity::create([
                'client_id' => $lead->id,
                'created_date' => now(),
                'status_changed_date' => now(),
                'changes_status' => LeadStatusEnum::PENDING,
                'reason' => "",
            ]);

            $m = $this->botMessages['main_menu']['heb'];
            $sid = "HX46b1587bfcaa3e6b29869edb538f45e0";

            $twi = $this->twilio->messages->create(
                "whatsapp:+$lead->phone",
                [
                    "from" => $this->twilioWhatsappNumber,
                    "contentSid" => $sid,

                ]
            );

            WhatsAppBotClientState::updateOrCreate([
                'client_id' => $lead->id,
            ], [
                'menu_option' => 'main_menu',
                'language' => 'he',
            ]);

            WebhookResponse::create([
                'status'        => 1,
                'name'          => 'whatsapp',
                'message'       => $twi->body ?? '',
                'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                'number'        => $lead->phone,
                'flex'          => 'A',
                'read'          => 1,
                'data'          => json_encode($twi->toArray()),
            ]);

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::NEW_LEAD_ARRIVED,
                "notificationData" => [
                    'client' => $lead->toArray(),
                    'type' => "website"
                ]
            ]));
        } else {
            $lead = Client::where('phone', $phone)->first();
            if (empty($lead)) {
                $lead = Client::where('email', $request->email)->first();
            }

            if ($lead->lead_status) {
                $leadStatus = $lead->lead_status;
                $leadUpdatedAt = $leadStatus->updated_at;
                $isPendingForMoreThanTwoDays = $leadStatus->lead_status === LeadStatusEnum::PENDING
                    && $leadUpdatedAt->diffInDays(now()) > 2;
                $isNotPending = $leadStatus->lead_status !== LeadStatusEnum::PENDING;

                if ($isPendingForMoreThanTwoDays || $isNotPending) {
                    $lead->lead_status()->updateOrCreate(
                        [],
                        ['lead_status' => LeadStatusEnum::PENDING]
                    );

                    LeadActivity::create([
                        'client_id' => $lead->id,
                        'created_date' => now(),
                        'status_changed_date' => now(),
                        'changes_status' => LeadStatusEnum::PENDING,
                        'reason' => "",
                    ]);

                    $lead->status = 0;
                    $lead->source = 'website';
                    $lead->save();

                    // Create a notification
                    Notification::create([
                        'user_id' => $lead->id,
                        'user_type' => get_class($lead),
                        'type' => NotificationTypeEnum::NEW_LEAD_ARRIVED,
                        'status' => 'created'
                    ]);

                    $lead->load('property_addresses');

                    // Trigger WhatsApp notification
                    event(new WhatsappNotificationEvent([
                        "type" => WhatsappMessageTemplateEnum::NEW_LEAD_ARRIVED,
                        "notificationData" => [
                            'client' => $lead->toArray(),
                            'type' => "website"
                        ]
                    ]));
                }
            }
        }
    }

    public function saveLead(Request $request)
    {
        $challenge = $request->hub_challenge;
        if (!empty($challenge)) {
            $verify_token = $request->hub_verify_token;
            if ($verify_token === config('services.facebook.webhook_token')) {
                Fblead::create(["challenge" => $challenge]);
                return $challenge;
            }
        } else {
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
                'phone'     => ['required'],
                'email'     => ['required'],
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->messages()]);
            }

            // Remove all special characters from the phone number
            $phone = preg_replace('/[^0-9+]/', '', $request->phone);

            // Adjust phone number formatting
            if (strpos($phone, '0') === 0) {
                $phone = '972' . substr($phone, 1);
            }
            if (strpos($phone, '+') === 0) {
                $phone = substr($phone, 1);
            }

            // Ensure phone starts with '972'
            if (strlen($phone) === 9 || strlen($phone) === 10) {
                $phone = '972' . $phone;
            }


            $lead_exists = Client::where('phone', $phone)->orWhere('email', $request->email)->exists();
            if (!$lead_exists) {
                $lead = new Client;
            } else {
                $lead = Client::where('phone', 'like', '%' . $phone . '%')->first();
                if (empty($lead)) {
                    $lead = Client::where('email', $request->email)->first();
                }
            }
            $nm = explode(' ', $request->name);

            $lead->firstname     = $nm[0];
            $lead->lastname     = (isset($nm[1])) ? $nm[1] : '';
            $lead->phone         = $phone;
            $lead->email         = $request->email;
            $lead->status        = 0;
            $lead->lng = 'heb';
            $lead->password      = Hash::make(Str::random(20));
            $lead->passcode      = $phone;
            $lead->geo_address   = $request->has('address') ? $request->address : '';
            $lead->save();

            if (!$lead_exists) {
                $lead->lead_status()->updateOrCreate(
                    [],
                    ['lead_status' => LeadStatusEnum::PENDING]
                );
            }
            $m = $this->botMessages['main_menu']['heb'];

            $result = sendWhatsappMessage($lead->phone, array('name' => ucfirst($lead->firstname), 'message' => $m));

            WhatsAppBotClientState::updateOrCreate([
                'client_id' => $lead->id,
            ], [
                'menu_option' => 'main_menu',
                'language' => 'he',
            ]);

            $response = WebhookResponse::create([
                'status'        => 1,
                'name'          => 'whatsapp',
                'message'       => $m,
                'from'          => str_replace("whatsapp:+", "", $this->twilioWhatsappNumber),
                'number'        => $request->phone,
                'read'          => 1,
                'flex'          => 'A',
                'data'          => json_encode($request->all()),
            ]);
        }

        return response()->json([
            'message' => $lead,
        ]);
    }

    public function contain_phone($str)
    {
        $nums  = "";
        for ($i = 0; $i < strlen($str); $i++) {
            if (ctype_digit($str[$i])) {
                $nums .= $str[$i];
            }
        }
        return ($nums != "" && strlen($nums) > 8) ? true : false;
    }
}
