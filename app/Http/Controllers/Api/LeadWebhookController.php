<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Job;
use App\Models\User;
use App\Models\Offer;
use App\Models\Client;
use App\Models\Fblead;
use App\Models\Setting;
use App\Models\Schedule;
use App\Models\Contract;
use App\Models\WorkerLeads;
use Illuminate\Support\Str;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Enums\LeadStatusEnum;
use App\Enums\SettingKeyEnum;
use App\Enums\JobStatusEnum;
use App\Models\ScheduleChange;
use App\Events\SendClientLogin;
use App\Models\WebhookResponse;
use App\Traits\ScheduleMeeting;
use App\Traits\GoogleAPI;
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
use Exception;

class LeadWebhookController extends Controller
{
    use ScheduleMeeting, GoogleAPI;

    protected $spreadsheetId;
    protected $googleAccessToken;
    protected $googleRefreshToken;
    protected $googleSheetEndpoint = 'https://sheets.googleapis.com/v4/spreadsheets/';

    protected $botMessages = [
        'main-menu' => [
            'en' => "Hi, I'm Bar, the digital representative of Broom Service. How can I help you today? 😊\n\nAt any stage, you can return to the main menu by sending the number 9 or return one menu back by sending the number 0.\n\n1. About the Service\n2. Service Areas\n3. Set an appointment for a quote\n4. Customer Service\n5. Switch to a human representative (during business hours)\n7. שפה עברית\n\nIf you no longer wish to receive messages from us, please reply with 'STOP' at any time",
            'heb' => 'היי, אני בר, הנציגה הדיגיטלית של ברום סרוויס. איך אוכל לעזור לך היום? 😊' . "\n\n" . 'בכל שלב תוכלו לחזור לתפריט הראשי ע"י שליחת המס 9 או לחזור תפריט אחד אחורה ע"י שליחת הספרה 0' . "\n\n" . '1. פרטים על השירות' . "\n" . '2. אזורי שירות' . "\n" . '3. קביעת פגישה לקבלת הצעת מחיר' . "\n" . '4. שירות ללקוחות קיימים' . "\n" . '5. מעבר לנציג אנושי (בשעות הפעילות)' . "\n" . '6. English menu' . "\n\n" . "אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת."
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
            "en" => "The code you entered is incorrect. Please try again.\nIf you'd like us to resend the code, reply with 0.",
            "heb" => "הקוד שהזנת אינו נכון. אנא נסה שוב.\nאם תרצה שנשלח את הקוד מחדש, השב 0."
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
            "en" => "🔔 Client :client_name has requested to change or update their schedule. \nMessage logged: :message\n📞 Phone: :client_phone\n:comment_link\n📄 :client_link",
            "heb" => "🔔 לקוח בשם :client_name ביקש לשנות או לעדכן שיבוץ. ההודעה שנרשמה: :message\n📞 טלפון: :client_phone\n:comment_link\n📄 :client_link"
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
        ]
    ];

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
            $m = $this->botMessages['main-menu']['heb'];

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
                'number'        => $request->phone,
                'read'          => 1,
                'flex'          => 'A',
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



    public function fbWebhookCurrentLive(Request $request)
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
        if (Cache::get('processed_message_' . $messageId) === $messageId) {
            \Log::info('Already processed');
            return response()->json(['status' => 'Already processed'], 200);
        }

        // Store the messageId in the cache for 1 hour
        Cache::put('processed_message_' . $messageId, $messageId, now()->addHours(1));


        if (
            isset($data_returned['messages']) &&
            isset($data_returned['messages'][0]['from_me']) &&
            $data_returned['messages'][0]['from_me'] == false
        ) {
            $message_data = $data_returned['messages'];
            $from = $message_data[0]['from'];
            Log::info($from);
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
                        $lng = 'heb';
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
                            }

                            $m = $lng == 'heb'
                                ? "עופרת כבר קיימת\n" . generateShortUrl(url("admin/leads/view/" . $client->id), 'admin')
                                : "Lead already exists\n" . generateShortUrl(url("admin/leads/view/" . $client->id), 'admin');
                        }

                        // Send WhatsApp message
                        $result = sendWhatsappMessage(config('services.whatsapp_groups.lead_client'), ['name' => '', 'message' => $m]);
                    }
                }

                return response()->json(['status' => 'Already processed'], 200);
            }


            $response = WebhookResponse::create([
                'status'        => 1,
                'name'          => 'whatsapp',
                'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                'message'       => $data_returned['messages'][0]['text']['body'] ?? '',
                'number'        => $from,
                'read'          => 0,
                'flex'          => 'C',
                'data'          => json_encode($get_data)
            ]);

            $client = null;
            if (strlen($from) > 10) {
                $client = Client::where('phone', 'like', '%' . substr($from, 2) . '%')->first();
                $user = User::where('phone', 'like', '%' . substr($from, 2) . '%')->first();
                $workerLead = WorkerLeads::where('phone', 'like', '%' . substr($from, 2) . '%')->first();
            } else {
                $client = Client::where('phone', 'like', '%' . $from . '%')->first();
                $user = User::where('phone', 'like', '%' . $from . '%')->first();
                $workerLead = WorkerLeads::where('phone', 'like', '%' . $from . '%')->first();
            }

            if ($client) {
                \Log::info('Client: ' . $client->id);
            }
            if ($user) {
                \Log::info('User: ' . $user->id);
            }
            if ($workerLead) {
                \Log::info('WorkerLead: ' . $workerLead->id);
            }

            if (!$client && !$user && !$workerLead) {
                $m = $this->botMessages['main-menu']['heb'];
                $result = sendWhatsappMessage($from, array('name' => '', 'message' => $m));

                $response = WebhookResponse::create([
                    'status'        => 1,
                    'name'          => 'whatsapp',
                    'message'       =>  $m,
                    'number'        =>  $from,
                    'read'          => 1,
                    'flex'          => 'A'
                ]);

                $lead                = new Client;
                $lead->firstname     = 'lead';
                $lead->lastname      = '';
                $lead->phone         = $from;
                $lead->email         = "";
                $lead->status        = 0;
                $lead->password      = Hash::make(Str::random(20));
                $lead->passcode      = $from;
                $lead->geo_address   = '';
                $lead->lng           = ($lng == 'heb' ? 'heb' : 'en');
                $lead->save();

                $responseClientState = WhatsAppBotClientState::updateOrCreate([
                    'client_id' => $lead->id,
                ], [
                    'menu_option' => 'main_menu',
                    'language' => $lng == 'heb' ? 'he' : 'en',
                ]);

                die('Template send to new client');
            } else if ($client && $client->disable_notification == 1) {
                \Log::info('notification disabled');
                die('notification disabled');
            }

            $responseClientState = WhatsAppBotClientState::where('client_id', $client->id)->first();
            if ($responseClientState && $responseClientState->final) {
                \Log::info('final');
                die('final');
            };

            if ($client && $data_returned['channel_id'] == 'GAMORA-MDYNP' && isset($data_returned) && isset($data_returned['messages']) && is_array($data_returned['messages'])) {
                $message = ($message_data[0]['type'] == 'text') ? $message_data[0]['text']['body'] : ($message_data[0]['button']['text'] ?? "");
                // \Log::info($message);
                $result = WhatsappLastReply::where('phone', $from)
                    ->where('updated_at', '>=', Carbon::now()->subMinutes(15))
                    ->first();

                $client_menus = WhatsAppBotClientState::where('client_id', $client->id)->first();

                if ($message == 0) {
                    $m = $this->botMessages['main-menu'][$client->lng];
                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $m));

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                        'message'       => $m,
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($get_data)
                    ]);

                    WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                    ], [
                        'menu_option' => 'main_menu',
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);
                    die("STOPPED");
                }

                if ($message === 'STOP' || $message === 'הפסק') {
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

                    event(new WhatsappNotificationEvent([
                        "type" => WhatsappMessageTemplateEnum::AFTER_STOP_TO_CLIENT,
                        "notificationData" => [
                            'client' => $client->toArray()
                        ]
                    ]));

                    $client->disable_notification = 1;
                    $client->save();

                    die("STOPPED");
                }

                // Send main menu is last menu state not found
                if (!$client_menus || $message == '9') {
                    if ($client->lng == 'heb') {
                        $m = $this->botMessages['main-menu']['heb'];
                    } else {
                        $m = $this->botMessages['main-menu']['en'];
                    }
                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $m));

                    $response = WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'message'       => $m,
                        'number'        => $from,
                        'read'          => 1,
                        'flex'          => 'A',
                    ]);
                    $responseClientState = WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                    ], [
                        'menu_option' => 'main_menu',
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);
                    Log::info('Send main menu');
                    die("Send main menu");
                }

                $menu_option = explode('->', $client_menus->menu_option);
                $last_menu = end($menu_option);
                // \Log::info($last_menu);

                $prev_step = null;
                if (count($menu_option) >= 2) {
                    $prev_step = $menu_option[count($menu_option) - 2];
                }

                // Need more help
                if (
                    (in_array($last_menu, ['need_more_help']) && (str_contains(strtolower($message), 'yes') || str_contains($message, 'כן'))) ||
                    (($prev_step == 'main_menu' || $prev_step == 'customer_service') && $message == '0')
                ) {
                    if ($client->lng == 'heb') {
                        $m = $this->botMessages['main-menu']['heb'];
                    } else {
                        $m = $this->botMessages['main-menu']['en'];
                    }
                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $m));

                    $response = WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'message'       => $m,
                        'number'        => $from,
                        'read'          => 1,
                        'flex'          => 'A',
                    ]);
                    $responseClientState = WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
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
                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                        'message'       => $msg,
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($get_data)
                    ]);
                    $responseClientState = WhatsAppBotClientState::where('client_id', $client->id)->delete();
                    $result = sendWhatsappMessage($from, array('message' => $msg));
                    die("Final message");
                }

                // Send english menu
                if ($last_menu == 'main_menu' && $message == '6') {
                    if (strlen($from) > 10) {
                        Client::where('phone', 'like', '%' . substr($from, 2) . '%')->update(['lng' => 'en']);
                    } else {
                        Client::where('phone', 'like', '%' . $from . '%')->update(['lng' => 'en']);
                    }
                    $m = $this->botMessages['main-menu']['en'];

                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $m));

                    $response = WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'message'       => $m,
                        'number'        => $from,
                        'read'          => 1,
                        'flex'          => 'A',
                    ]);
                    $responseClientState =  WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                    ], [
                        'menu_option' => 'main_menu',
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);
                    Log::info('Language switched to english');
                    die("Language switched to english");
                }

                // Send hebrew menu
                if ($last_menu == 'main_menu' && $message == '7') {
                    if (strlen($from) > 10) {
                        Client::where('phone', 'like', '%' . substr($from, 2) . '%')->update(['lng' => 'heb']);
                    } else {
                        Client::where('phone', 'like', '%' . $from . '%')->update(['lng' => 'heb']);
                    }
                    $m = $this->botMessages['main-menu']['heb'];
                    $result = sendWhatsappMessage($from, array('name' => '', 'message' => $m));

                    $response = WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'message'       => $m,
                        'number'        => $from,
                        'read'          => 1,
                        'flex'          => 'A',
                    ]);
                    $responseClientState =  WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                    ], [
                        'menu_option' => 'main_menu',
                        'language' =>  'he',
                    ]);
                    Log::info('Language switched to hebrew');
                    die("Language switched to hebrew");
                }

                // Menus Array
                $menus = [
                    'main_menu' => [
                        '1' => [
                            'title' => "About the Service",
                            'content' => [
                                'en' => 'Broom Service - Room service for your 🏠.
Broom Service is a professional cleaning company that offers ✨ high-quality cleaning services for homes or apartments, on a regular or one-time basis, without any unnecessary 🤯 hassle.
We offer a variety of 🧹 customized cleaning packages, from regular cleaning packages to additional services such as post-construction cleaning or pre-move cleaning, window cleaning at any height, and more.
You can find all of our services and packages on our website at 🌐 www.broomservice.co.il.
Our prices are fixed per visit, based on the selected package, and they include all the necessary services, including ☕️ social benefits and travel.
We work with a permanent and skilled team of employees supervised by a work manager.
Payment is made by 💳 credit card at the end of the month or after the visit, depending on the route chosen.
To receive a quote, you must schedule an appointment at your property with one of our supervisors, at no cost or obligation on your part, during which we will help you choose a package and then we will send you a detailed quote according to the requested work.
Please note that office hours are 🕖 Monday-Thursday from 8:00 to 14:00.
To schedule an appointment for a quote press 3 or ☎️ 5 to speak with a representative.',
                                'he' => 'ברום סרוויס - שירות חדרים לבית שלכם 🏠.
ברום סרוויס היא חברת ניקיון מקצועית המציעה שירותי ניקיון ברמה גבוהה לבית או לדירה, על בסיס קבוע או חד פעמי, ללא כל התעסקות מיותרת 🧹.
אנו מציעים מגוון חבילות ניקיון מותאמות אישית, החל מחבילות ניקיון על בסיס קבוע ועד לשירותים נוספים כגון, ניקיון לאחר שיפוץ או לפני מעבר דירה, ניקוי חלונות בכל גובה ועוד ✨
את כלל השירותים והחבילות שלנו תוכלו לראות באתר האינטרנט שלנו בכתובת 🌐 www.broomservice.co.il
המחירים שלנו קבועים לביקור, בהתאם לחבילה הנבחרת, והם כוללים את כל השירותים הנדרשים, לרבות תנאים סוציאליים ונסיעות 🍵.
אנו עובדים עם צוות עובדים קבוע ומיומן המפוקח על ידי מנהל עבודה 👨🏻‍💼.
התשלום מתבצע בכרטיס אשראי בסוף החודש או לאחר הביקור, בהתאם למסלול שנבחר 💳.
לקבלת הצעת מחיר, יש לתאם פגישה אצלכם בנכס עם אחד המפקחים שלנו, ללא כל עלות או התחייבות מצדכם שבמסגרתה נעזור לכם לבחור חבילה ולאחריה נשלח לכם הצעת מחיר מפורטת בהתאם לעבודה המבוקשת 📝.
נציין כי שעות הפעילות במשרד הן בימים א-ה בשעות 8:00-14:00 🕓.
לקביעת פגישה להצעת מחיר הקש 3 לשיחה עם נציג הקש ☎️ 5.'
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
                            'title' => "Schedule an appointment for a quote",
                            'content' => [
                                'en' => 'Existing customers can use our customer portal to get information, make changes to orders, and contact us on various matters.
You can also log in to our customer portal with the details you received at the time of registration at crm.broomservice.co.il.
Enter your phone number or email address with which you registered for the service 📝',
                                'he' => 'לקוחות קיימים יכולים להשתמש בפורטל הלקוחות שלנו כדי לקבל מידע, לבצע שינויים בהזמנות וליצור איתנו קשר בנושאים שונים.
תוכלו גם להיכנס לפורטל הלקוחות שלנו עם הפרטים שקיבלתם במעמד ההרשמה בכתובת crm.broomservice.co.il.
הזן את מס הטלפון או כתובת המייל איתם נרשמת לשירות 📝',
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
                if (in_array($last_menu, ['need_more_help', 'cancel_one_time']) && (str_contains(strtolower($message), 'no') || str_contains($message, 'לא'))) {
                    $msg = ($client->lng == 'heb' ? `מקווה שעזרתי! 🤗` : 'I hope I helped! 🤗');
                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                        'message'       => $msg,
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($get_data)
                    ]);
                    $responseClientState = WhatsAppBotClientState::where('client_id', $client->id)->first();

                    if ($responseClientState) {
                        $responseClientState->menu_option = 'main_menu';
                        $responseClientState->final = true;
                        $responseClientState->save();
                    }
                    // $responseClientState = WhatsAppBotClientState::where('client_id', $client->id)->delete();
                    $result = sendWhatsappMessage($from, array('message' => $msg));
                    die("Final message");
                }

                // Send appointment message
                if (($last_menu == 'about_the_service' || $last_menu == 'service_areas') && $message == '3') {
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

                        $state = "main_menu->human_representative->need_more_help";
                    } else {
                        if ($client->lng == 'heb') {
                            $msg = 'נראה שהזנת קלט שגוי. אנא בדוק ונסה שוב.';
                        } else {
                            $msg = 'It looks like you\'ve entered an incorrect input. Please check and try again.';
                        }

                        $state = "main_menu->human_representative";
                    }

                    $result = sendWhatsappMessage($from, array('message' => $msg));

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                        'message'       => $msg,
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($get_data)
                    ]);

                    $responseClientState = WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                    ], [
                        'menu_option' => $state,
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);

                    $message = null;
                    die("Human representative");
                }

                // // Store lead full name
                // if ($last_menu == 'full_name') {
                //     $names = explode(' ', $message);
                //     if (isset($names[0])) {
                //         $client->firstname = trim($names[0]);
                //     }
                //     if (isset($names[1])) {
                //         $client->lastname = trim($names[1]);
                //     }
                //     $client->save();
                //     // $client->refresh();
                //     $msg = null;
                //     if ($client->lng == 'heb') {
                //         $msg = 'כתובת מלאה (רחוב, מספר ועיר בלבד)';
                //     } else {
                //         $msg = "Please send your full address (Only street, number, and city)";
                //     }
                //     WebhookResponse::create([
                //         'status'        => 1,
                //         'name'          => 'whatsapp',
                //         'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                //         'message'       => $msg,
                //         'number'        => $from,
                //         'flex'          => 'A',
                //         'read'          => 1,
                //         'data'          => json_encode($get_data)
                //     ]);

                //     $result = sendWhatsappMessage($from, array('message' => $msg));

                //     $responseClientState = WhatsAppBotClientState::updateOrCreate([
                //         'client_id' => $client->id,
                //     ], [
                //         'menu_option' => 'main_menu->appointment->full_address',
                //         'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                //     ]);

                //     die("Store full name");
                // }

                // Check the current menu state
                if ($last_menu == 'first_name') {
                    // Store first name
                    $client->firstname = trim($message);
                    $client->save();

                    // Ask for last name
                    $msg = $client->lng == 'heb'
                        ? 'מה שם המשפחה שלך?'
                        : "Please send your last name.";

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                        'message'       => $msg,
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($get_data)
                    ]);

                    $result = sendWhatsappMessage($from, array('message' => $msg));

                    // Update client state to expect the last name
                    WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
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

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                        'message'       => $msg,
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($get_data)
                    ]);

                    $result = sendWhatsappMessage($from, array('message' => $msg));

                    // Update client state to expect the full address
                    WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
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
                            WebhookResponse::create([
                                'status'        => 1,
                                'name'          => 'whatsapp',
                                'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                                'message'       => $msg,
                                'number'        => $from,
                                'flex'          => 'A',
                                'read'          => 1,
                                'data'          => json_encode($get_data)
                            ]);
                            $responseClientState = WhatsAppBotClientState::updateOrCreate([
                                'client_id' => $client->id,
                            ], [
                                'menu_option' => 'main_menu->appointment->full_address->verify_address',
                                'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                            ]);
                            $result = sendWhatsappMessage($from, array('message' => $msg));

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
                    if (
                        ($client->lng == 'heb' && $message == 'כן') ||
                        ($client->lng == 'en' && strtolower($message) == 'yes')
                    ) {
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

                        WebhookResponse::create([
                            'status'        => 1,
                            'name'          => 'whatsapp',
                            'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                            'message'       => $msg,
                            'number'        => $from,
                            'flex'          => 'A',
                            'read'          => 1,
                            'data'          => json_encode($get_data)
                        ]);

                        $result = sendWhatsappMessage($from, array('message' => $msg));

                        $responseClientState = WhatsAppBotClientState::updateOrCreate([
                            'client_id' => $client->id,
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

                        WebhookResponse::create([
                            'status'        => 1,
                            'name'          => 'whatsapp',
                            'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                            'message'       => $msg,
                            'number'        => $from,
                            'flex'          => 'A',
                            'read'          => 1,
                            'data'          => json_encode($get_data)
                        ]);

                        $result = sendWhatsappMessage($from, array('message' => $msg));

                        $responseClientState = WhatsAppBotClientState::updateOrCreate([
                            'client_id' => $client->id,
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

                    $responseClientState = WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                        'message'       => $msg,
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($get_data)
                    ]);

                    $result = sendWhatsappMessage($from, array('message' => $msg));

                    $responseClientState = WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
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
                        $msg = 'אנא ספק את פרטי החניה עבור הכתובת הנתונה.';
                    } else {
                        $msg = "Please provide the parking details for the given address.";
                    }

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                        'message'       => $msg,
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($get_data)
                    ]);

                    $result = sendWhatsappMessage($from, array('message' => $msg));

                    $responseClientState = WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
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

                        WebhookResponse::create([
                            'status'        => 1,
                            'name'          => 'whatsapp',
                            'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                            'message'       => $msg,
                            'number'        => $from,
                            'flex'          => 'A',
                            'read'          => 1,
                            'data'          => json_encode($get_data)
                        ]);

                        $result = sendWhatsappMessage($from, array('message' => $msg));

                        $responseClientState = WhatsAppBotClientState::updateOrCreate([
                            'client_id' => $client->id,
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

                        WebhookResponse::create([
                            'status'        => 1,
                            'name'          => 'whatsapp',
                            'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                            'message'       => $msg,
                            'number'        => $from,
                            'flex'          => 'A',
                            'read'          => 1,
                            'data'          => json_encode($get_data)
                        ]);

                        $result = sendWhatsappMessage($from, array('message' => $msg));

                        $responseClientState = WhatsAppBotClientState::updateOrCreate([
                            'client_id' => $client->id,
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
                    if (filter_var($message, FILTER_VALIDATE_EMAIL)) {
                        $email_exists = Client::where('email', $message)->where('id', '!=', $client->id)->exists();
                        if ($email_exists) {
                            $msg = ($client->lng == 'heb' ? `הכתובת '` . $message . `' כבר קיימת. נא הזן כתובת דוא"ל אחרת.` : '\'' . $message . '\' is already taken. Please enter a different email address.');
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
                            } else {
                                if ($client->lng == 'heb') {
                                    $msg = "מצטערים, אין כרגע זמינות לפגישות. נציג מטעמנו ייצור עמכם קשר בהקדם. \n\nהאם יש משהו נוסף שאני יכול לעזור לך בו היום? (כן או לא) 👋";
                                } else {
                                    $msg = "Sorry, there are no available slots for an appointment at the moment.\n\nA representative from our team will contact you shortly.\n\nIs there anything else I can help you with today? (Yes or No) 👋";
                                }

                                event(new WhatsappNotificationEvent([
                                    "type" => WhatsappMessageTemplateEnum::NO_SLOT_AVAIL_CALLBACK,
                                    "notificationData" => [
                                        'client' => $client->toArray()
                                    ]
                                ]));
                            }

                            $responseClientState =  WhatsAppBotClientState::updateOrCreate([
                                'client_id' => $client->id,
                            ], [
                                'menu_option' => 'main_menu->appointment->need_more_help',
                                'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                            ]);
                        }
                    } else {
                        $msg = ($client->lng == 'heb' ? `כתובת הדוא"ל '` . $message . `' לא תקינה. בבקשה נסה שוב.` : 'The email address \'' . $message . '\' is considered invalid. Please try again.');
                    }

                    if (!empty($msg)) {
                        WebhookResponse::create([
                            'status'        => 1,
                            'name'          => 'whatsapp',
                            'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                            'message'       => $msg,
                            'number'        => $from,
                            'flex'          => 'A',
                            'read'          => 1,
                            'data'          => json_encode($get_data)
                        ]);

                        $result = sendWhatsappMessage($from, array('message' => $msg));
                    }

                    die("Store email");
                }

                // Send customer service menu
                if ($last_menu == 'customer_service') {
                    $msg = null;
                    $auth = null;
                    if (str_contains($message, '@')) {
                        $auth = Client::where('email', $message)->first();
                    } else if (is_numeric(str_replace('-', '', $message)) && strlen($message) > 5) {
                        $auth = Client::where('phone', 'like', '%' . $message . '%')->first();
                    }
                    if ($auth) {

                        $msg = $auth->lng == 'heb' ? "היי! שמנו לב שהמספר שלך כבר רשום במערכת שלנו.\nאיך נוכל לעזור לך היום? נא לבחור אחת מהאפשרויות הבאות:\n\n1 - שלחו לי שוב את פרטי ההתחברות\n2 - אני מעוניין שיצרו איתי קשר לגבי שירות חדש או חידוש"
                            : "Hello! We noticed that your number is already registered in our system.\nHow can we assist you today? Please choose one of the following options:\n\n1 - Send me my login details again\n2 - I’d like to be contacted about a new service or renewal";

                        // $auth->makeVisible('passcode');
                        // event(new SendClientLogin($auth->toArray()));

                        $responseClientState =  WhatsAppBotClientState::updateOrCreate([
                            'client_id' => $client->id,
                        ], [
                            // 'menu_option' => 'main_menu->customer_service->customer_menu',
                            'menu_option' => 'main_menu->customer_service->need_more_help',
                            'language' =>  $auth->lng == 'heb' ? 'he' : 'en',
                            'auth_id' => $auth->id,
                        ]);
                        // \Log::info($last_menu);
                    } else {
                        $msg = "I couldn't find your details based on what you sent. Please try again.";
                        if ($client->lng == 'heb') {
                            $msg = 'לא הצלחתי למצוא את הפרטים שלך על סמך מה ששלחת. בבקשה נסה שוב.';
                        }
                    }

                    if (!empty($msg)) {
                        WebhookResponse::create([
                            'status'        => 1,
                            'name'          => 'whatsapp',
                            'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                            'message'       => $msg,
                            'number'        => $from,
                            'flex'          => 'A',
                            'read'          => 1,
                            'data'          => json_encode($get_data)
                        ]);
                        $result = sendWhatsappMessage($from, array('message' => $msg));
                    }

                    // \Log::info(['message' => $message, 'last_menu' => $last_menu]);

                    die("Send service menu");
                }

                if ($last_menu == 'need_more_help' && $message == '1') {

                    $client->makeVisible('passcode');
                    event(new SendClientLogin($client->toArray()));

                    $msg = "Thank you! We’re resending your login details to your registered email address now. Please check your inbox shortly. 📧\nIs there anything else I can help you with today? (Yes or No) 👋";
                    if ($client->lng == 'heb') {
                        $msg = "תודה! אנחנו שולחים כעת את פרטי ההתחברות שלך למייל הרשום אצלנו. נא לבדוק את תיבת הדואר שלך בקרוב. 📧\nהאם יש משהו נוסף שבו אוכל לעזור לך היום? (כן או לא) 👋";
                    }

                    if (!empty($msg)) {
                        WebhookResponse::create([
                            'status'        => 1,
                            'name'          => 'whatsapp',
                            'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                            'message'       => $msg,
                            'number'        => $from,
                            'flex'          => 'A',
                            'read'          => 1,
                            'data'          => json_encode($get_data)
                        ]);
                        $result = sendWhatsappMessage($from, array('message' => $msg));
                    }

                    die("Send login details");
                } elseif ($last_menu == 'need_more_help' && $message == '2') {

                    $msg = $client->lng == 'heb' ? "הבנתי! אנחנו מעבירים אותך כעת לתפריט שירותים חדשים או חידוש\nשירותים. נא לבחור באפשרות המתאימה לך ביותר. 🛠️\nהאם יש משהו נוסף שבו אוכל לעזור לך היום? (כן או לא) 👋"
                        : "Got it! We will redirect you to the menu for new services or renewals.\nPlease select the option that best suits your needs. 🛠️\n\nIs there anything else I can help you with today? (Yes or No) 👋";

                    $result = sendWhatsappMessage($from, array('message' => $msg));

                    die('main_menu');
                }



                // Send about service message
                if ($last_menu == 'main_menu' && isset($menus[$last_menu][$message]['content'][$client->lng == 'heb' ? 'he' : 'en'])) {
                    $msg = $menus[$last_menu][$message]['content'][$client->lng == 'heb' ? 'he' : 'en'];
                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                        'message'       => $msg,
                        'number'        => $from,
                        'flex'          => 'A',
                        'read'          => 1,
                        'data'          => json_encode($get_data)
                    ]);

                    $result = sendWhatsappMessage($from, array('message' => $msg));

                    // \Log::info($message);

                    switch ($message) {
                        case '1':
                            WhatsAppBotClientState::updateOrCreate([
                                'client_id' => $client->id,
                            ], [
                                'menu_option' => 'main_menu->about_the_service',
                                'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                            ]);
                            break;

                        case '2':
                            WhatsAppBotClientState::updateOrCreate([
                                'client_id' => $client->id,
                            ], [
                                'menu_option' => 'main_menu->service_areas',
                                'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                            ]);
                            break;

                        case '3':
                            WhatsAppBotClientState::updateOrCreate([
                                'client_id' => $client->id,
                            ], [
                                'menu_option' => 'main_menu->appointment->first_name',
                                'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                            ]);
                            break;

                        case '4':
                            WhatsAppBotClientState::updateOrCreate([
                                'client_id' => $client->id,
                            ], [
                                'menu_option' => 'main_menu->customer_service',
                                'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                            ]);
                            break;

                        case '5':
                            WhatsAppBotClientState::updateOrCreate([
                                'client_id' => $client->id,
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
        $phone = preg_replace('/\D/', '', $phone);

        // Check if the phone number starts with '0'
        if (strpos($phone, '0') === 0) {
            // Remove the leading '0' and prepend '972'
            $phone = '972' . substr($phone, 1);
        } elseif (strpos($phone, '972') === 0) {
            // If the phone already starts with '972', leave it as is
            // Ensure no leading '+'
            $phone = ltrim($phone, '+');
        } elseif (strpos($phone, '+') === 0) {
            // If the phone starts with '+', remove the '+'
            $phone = substr($phone, 1);
        } else {
            // If no country code is present, prepend '972'
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
        $name = explode(' ', $request->name);

        $lead->firstname = $name[0];
        $lead->lastname = (isset($name[1])) ? $name[1] : '';
        $lead->phone = $phone;
        $lead->email = $request->email;
        $lead->status = 0;
        $lead->lng = 'heb';
        $lead->password = Hash::make(Str::random(20));
        $lead->passcode = $phone;
        $lead->save();

        if (!$lead_exists) {
            $lead->lead_status()->updateOrCreate(
                [],
                ['lead_status' => LeadStatusEnum::PENDING]
            );
        }

        $m = $this->botMessages['main-menu']['heb'];

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
            'number'        => $phone,
            'read'          => 1,
            'flex'          => 'A',
        ]);

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::NEW_LEAD_ARRIVED,
            "notificationData" => [
                'client' => $lead->toArray(),
                'type' => "website"
            ]
        ]));
    }

    public function fbActiveClientsWebhookCurrentLive(Request $request)
    {
        $get_data = $request->getContent();
        $data_returned = json_decode($get_data, true);
        $messageId = $data_returned['messages'][0]['id'] ?? null;

        \Log::info($data_returned);

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
            isset($data_returned['messages']) &&
            isset($data_returned['messages'][0]['from_me']) &&
            $data_returned['messages'][0]['from_me'] == false
        ) {
            $message_data = $data_returned['messages'];
            if (Str::endsWith($message_data[0]['chat_id'], '@g.us')) {
                die("Group message");
            }
            $from = $message_data[0]['from'];
            $input = trim($data_returned['messages'][0]['text']['body']);

            $isMonday = now()->isMonday();

            $workerLead = WorkerLeads::where('phone', $from)->first();
            if ($workerLead) {
                \Log::info('Worker lead already exists');
            }

            $user = User::where('phone', $from)
                ->where('status', 1)
                ->first();
                if ($user) {
                    \Log::info('User already exists');
                }
            $client = Client::where('phone', $from)
                ->orWhereJsonContains('extra', [['phone' => $from]])
                ->first();

            $msgStatus = null;

            if($client){
                $msgStatus = Cache::get('client_review' . $client->id);
                \Log::info($msgStatus . ' ' . $client->id);
                $input = trim($data_returned['messages'][0]['text']['body'] ?? '');
                if (!empty($msgStatus) && ($input == '7' || $input == '8')) {
                    \Log::info('Client already reviewed');
                    die('Client already reviewed');
                }

                $msgStatus = Cache::get('client_review_input2' . $client->id);
                if (!empty($msgStatus)) {
                    \Log::info('Client already reviewed');
                    die('Client already reviewed');
                }

                $msgStatus = Cache::get('client_job_confirm_msg' . $client->id);
                if ((!empty($msgStatus) && $input == '1') || (!empty($msgStatus) && $msgStatus != "main_msg")) {
                    die('Client confirm job');
                }
            }

            if($client && $client->lead_status->lead_status != LeadStatusEnum::ACTIVE_CLIENT){
                die('Client already active');
            }

            $lng = $client->lng ?? $this->detectLanguage($input);
            if ($user || $workerLead) {
                die('Worker or worker lead found');
            }

            if($client && $client->disable_notification == 1){
                \Log::info('Client disabled notification');
                die('Client disabled notification');
            }

            if ($isMonday && $client && $client->stop_last_message != 1 && !in_array(strtolower(trim($input)), ["stop", "הפסק"])) {
                if ($client->stop_last_message == 0 && in_array(strtolower(trim($input)), ["menu", "תפריט"])) {
                    $client->stop_last_message = 1;
                    $client->save();
                } else {
                    \Log::info('Monday msg reply is pending');
                    die('Monday msg reply is pending.');
                }
            }
            $clientMessageStatus = WhatsAppBotActiveClientState::where('from', $from)->first();

            $last_menu = null;
            $send_menu = null;
            if ($clientMessageStatus) {
                $lng = $clientMessageStatus->lng ?? 'heb';
                $menu_option = explode('->', $clientMessageStatus->menu_option);
                $last_menu = end($menu_option);
            }

            WebhookResponse::create([
                'status' => 1,
                'name' => 'whatsapp',
                'entry_id' => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                'message' => $data_returned['messages'][0]['text']['body'],
                'number' => $from,
                'read' => 0,
                'flex' => 'C',
                'data' => json_encode($get_data)
            ]);


            if (in_array(strtolower(trim($input)), ["stop", "הפסק"])) {
                $client->disable_notification = 1;
                $client->save();
                $send_menu = 'stop';
            } else if (empty($last_menu) || in_array(strtolower(trim($input)), ["menu", "תפריט"])) {
                if (!$client && !$user && !$workerLead) {
                    $send_menu = 'not_recognized';
                } else {
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
            } else if ($last_menu == 'not_recognized' && $input == '1') {
                $send_menu = 'enter_phone';
            } else if ($last_menu == 'not_recognized' && $input == '2') {
                $send_menu = 'new_lead';
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
            } else if ($last_menu == 'enter_phone' && !empty($input)) {
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

                $client = Client::where('phone', $phone)
                    ->orWhereJsonContains('extra', [['phone' => $phone]])
                    ->first();
                // $lng = $client->lng ?? "heb";
                if ($client && !empty($phone)) {
                    $send_menu = 'email_sent';
                } else {
                    $send_menu = 'not_recognized';
                }
            } else if ($last_menu == 'email_sent' && $input == '0') {
                $client = Client::where('phone', $clientMessageStatus->client_phone)
                    ->orWhereJsonContains('extra', [['phone' => $clientMessageStatus->client_phone]])
                    ->first();
                $send_menu = 'email_sent';
            } else if ($last_menu == 'email_sent' && !empty($input)) {
                $client = Client::where('phone', $clientMessageStatus->client_phone)
                    ->orWhereJsonContains('extra', [['phone' => $clientMessageStatus->client_phone]])
                    ->first();
                // $lng = $client->lng ?? "heb";
                if ($client->otp == $input) {
                    $send_menu = 'verified';
                } else {
                    $client->attempts = $client->attempts + 1;
                    $client->save();
                    if ($client->attempts >= 4) {
                        $send_menu = 'failed_attempts';
                    } else {
                        $send_menu = 'incorect_otp';
                    }
                }
            } else if ($last_menu == 'failed_attempts') {
                $client = Client::where('phone', $clientMessageStatus->client_phone)
                    ->orWhereJsonContains('extra', [['phone' => $clientMessageStatus->client_phone]])
                    ->first();
                $send_menu = 'failed_attempts';
            } else {
                $send_menu = 'sorry';
            }

            switch ($send_menu) {
                case 'main_menu':
                    $this->sendMainMenu($client, $from);
                    break;
                case 'not_recognized':
                    $nextMessage = $this->activeClientBotMessages['not_recognized'][$lng];
                    sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

                    WhatsAppBotActiveClientState::updateOrCreate(
                        ["from" => $from],
                        [
                            'menu_option' => 'not_recognized',
                            'lng' => $lng,
                            "from" => $from,
                        ]
                    );

                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $nextMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);
                    break;
                case 'urgent_contact':
                    $clientName = $client->firstname ?? '' . ' ' . $client->lastname ?? '';

                    $nextMessage = $this->activeClientBotMessages['urgent_contact'][$lng];
                    $personalizedMessage = str_replace(':client_name', $clientName, $nextMessage);
                    sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);
                    $clientMessageStatus->update([
                        'menu_option' => 'main_menu->urgent_contact',
                    ]);
                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $personalizedMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);
                    break;
                case 'thankyou':
                    $nextMessage = $this->activeClientBotMessages['thankyou'][$lng];
                    sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $nextMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);

                    $scheduleChange = new ScheduleChange();
                    $scheduleChange->user_type = get_class($client);
                    $scheduleChange->user_id = $client->id;
                    $scheduleChange->reason = $lng == "en" ? "Contact me urgently" : " צרו איתי קשר דחוף";
                    $scheduleChange->comments = trim($input);
                    $scheduleChange->save();

                    $nextMessage = $this->activeClientBotMessages['team_comment']["heb"];
                    $clientName = "*" .(($client->firstname ?? '') . ' ' . ($client->lastname ?? '')) . "*";

                    $scheduleLink = generateShortUrl(url('admin/schedule-requests'.'?id=' . $scheduleChange->id), 'admin');

                    $personalizedMessage = str_replace([
                        ':client_name', ':message', ':client_phone', ':comment_link',':client_link'
                    ], [
                        $clientName, '*' . trim($input) . '*', $client->phone, $scheduleLink, generateShortUrl(url("admin/clients/view/" . $client->id), 'admin')
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

                                if (($id || $email) && !empty($row[9])) {
                                    $shifts[] = trim($row[9] ?? '');
                                    if ($id == $client->id || (!empty($email) && $email == $client->email)) {
                                        $currentDateObj = Carbon::parse($currentDate); // Current date
                                        $nextWeekStart = Carbon::now()->next(Carbon::SUNDAY); // Next week's Sunday
                                        $nextWeekEnd = $nextWeekStart->copy()->addDays(6); // Next week's Saturday
                                        $shift = "";
                                        $day = $currentDateObj->format('l');
                                        if($client->lng == 'en') {
                                            switch (trim($row[9])) {
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
                                                    $shift = $row[9];
                                                    break;
                                            }
                                        } else {
                                            switch (trim($row[9])) {
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
                                                    $shift = $row[9];
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
                        foreach ($currentWeeks as $job) {
                            $dateTime .= $job['dayName'] . " " . $job['currentDate'] . " " . $job['shift'] . "," . "\n";
                        }

                        $nextMessage = $this->activeClientBotMessages['service_schedule'][$lng];
                        $personalizedMessage = str_replace(':date_time', $dateTime, $nextMessage);
                        sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);
                        WebhookResponse::create([
                            'status' => 1,
                            'name' => 'whatsapp',
                            'message' => $personalizedMessage,
                            'number' => $from,
                            'read' => 1,
                            'flex' => 'A',
                        ]);

                        $clientMessageStatus->delete();
                    } else if ($nextWeeks && count($nextWeeks) > 0) {

                        foreach ($nextWeeks as $job) {
                            $dateTime .= $job['dayName'] . " " . $job['currentDate'] . " " . $job['shift'] . "," . "\n";
                        }

                        $nextMessage = $this->activeClientBotMessages['next_week_service_schedule'][$lng];
                        $personalizedMessage = str_replace(':date_time', $dateTime, $nextMessage);
                        sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);
                        WebhookResponse::create([
                            'status' => 1,
                            'name' => 'whatsapp',
                            'message' => $personalizedMessage,
                            'number' => $from,
                            'read' => 1,
                            'flex' => 'A',
                        ]);

                        $clientMessageStatus->delete();
                    } else {
                        $nextMessage = $this->activeClientBotMessages['no_service_avail'][$lng];
                        sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);
                        $clientMessageStatus->update([
                            'menu_option' => 'main_menu->service_schedule',
                        ]);
                        WebhookResponse::create([
                            'status' => 1,
                            'name' => 'whatsapp',
                            'message' => $nextMessage,
                            'number' => $from,
                            'read' => 1,
                            'flex' => 'A',
                        ]);
                    }
                    break;
                case 'request_new_qoute':
                    $nextMessage = $this->activeClientBotMessages['request_new_qoute'][$lng];
                    sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $nextMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);

                    $nextMessage = $this->activeClientBotMessages['team_new_qoute']["heb"];
                    $clientName = "*" .(($client->firstname ?? '') . ' ' . ($client->lastname ?? '')) . "*";
                    $personalizedMessage = str_replace([
                        ':client_name', ':client_phone', ':client_link'
                    ], [
                        $clientName, $client->phone, generateShortUrl(url("admin/clients/view/" . $client->id), 'admin')
                    ], $nextMessage);

                    sendTeamWhatsappMessage(config('services.whatsapp_groups.lead_client'), ['name' => '', 'message' => $personalizedMessage]);
                    $clientMessageStatus->delete();

                    break;
                case 'invoice_account':
                    $nextMessage = $this->activeClientBotMessages['invoice_account'][$lng];
                    sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);
                    $clientMessageStatus->update([
                        'menu_option' => 'main_menu->invoice_account',
                    ]);

                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $nextMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);
                    break;
                case 'thank_you_invoice_account':
                    $nextMessage = $this->activeClientBotMessages['thank_you_invoice_account'][$lng];
                    $clientName = (($client->firstname ?? '') . ' ' . ($client->lastname ?? ''));
                    $personalizedMessage = str_replace(':client_name', $clientName, $nextMessage);
                    sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $personalizedMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);

                    $scheduleChange = new ScheduleChange();
                    $scheduleChange->user_type = get_class($client);
                    $scheduleChange->user_id = $client->id;
                    $scheduleChange->reason = $lng == "en" ? "Invoice and accounting inquiry" : 'הנה"ח - פנייה למחלקת הנהלת חשבונות';
                    $scheduleChange->comments = $input;
                    $scheduleChange->save();

                    $clientName = "*" .(($client->firstname ?? '') . ' ' . ($client->lastname ?? '')) . "*";
                    $nextMessage = $this->activeClientBotMessages['team_invoice_account']["heb"];
                    $personalizedMessage = str_replace([
                        ':client_name', ":client_phone", ":message", ":comment_link",':client_link'
                    ], [
                        $clientName, $client->phone, '*' . trim($input) . '*', generateShortUrl(url('admin/schedule-requests'.'?id=' . $scheduleChange->id), 'admin'), generateShortUrl(url("admin/clients/view/" . $client->id), 'admin')
                    ], $nextMessage);

                    sendTeamWhatsappMessage(config('services.whatsapp_groups.problem_with_payments'), ['name' => '', 'message' => $personalizedMessage]);
                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $personalizedMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);

                    $clientMessageStatus->delete();
                    break;

                case 'change_update_schedule':
                    $nextMessage = $this->activeClientBotMessages['change_update_schedule'][$lng];
                    sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

                    $clientMessageStatus->update([
                        'menu_option' => 'main_menu->change_update_schedule',
                    ]);

                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $nextMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);
                    break;

                case 'thank_you_change_update_schedule':
                    $nextMessage = $this->activeClientBotMessages['thank_you_change_update_schedule'][$lng];
                    sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);
                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $nextMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);

                    $scheduleChange = new ScheduleChange();
                    $scheduleChange->user_type = get_class($client);
                    $scheduleChange->user_id = $client->id;
                    $scheduleChange->reason = $lng == "en" ? "Change or update schedule" : 'שינוי או עדכון שיבוץ';
                    $scheduleChange->comments = $input;
                    $scheduleChange->save();

                    $nextMessage = $this->activeClientBotMessages['team_change_update_schedule']["heb"];
                    $clientName = "*" .(($client->firstname ?? '') . ' ' . ($client->lastname ?? '')) . "*";
                    $personalizedMessage = str_replace([
                        ':client_name', ":client_phone", ":message", ":comment_link",':client_link'
                    ], [
                        $clientName, $client->phone, '*' . trim($input) . '*', generateShortUrl(url('admin/schedule-requests'.'?id=' . $scheduleChange->id), 'admin'), generateShortUrl(url("admin/clients/view/" . $client->id), 'admin')
                    ], $nextMessage);

                    sendTeamWhatsappMessage(config('services.whatsapp_groups.changes_cancellation'), ['name' => '', 'message' => $personalizedMessage]);

                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $personalizedMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);

                    $clientMessageStatus->delete();
                    break;
                case 'access_portal':
                    $nextMessage = $this->activeClientBotMessages['access_portal'][$lng];
                    $personalizedMessage = str_replace(':client_portal_link', generateShortUrl(url("client/login"), 'admin'), $nextMessage);
                    sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);
                    $clientMessageStatus->delete();

                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $personalizedMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);
                    break;
                case 'enter_phone':
                    $nextMessage = $this->activeClientBotMessages['enter_phone'][$lng];
                    sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);
                    $clientMessageStatus->update([
                        'menu_option' => 'not_recognized->enter_phone',
                    ]);

                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $nextMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);
                    break;

                case 'email_sent':
                    $this->ClientOtpSend($client, $from, $lng);
                    break;

                case 'verified':
                    // Decode the `extra` field (or initialize it as an empty array if null or invalid)
                    $extra = $client->extra ? json_decode($client->extra, true) : [];

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
                    $client->extra = json_encode($extra);
                    $client->save();

                    // Send verified message
                    $nextMessage = $this->activeClientBotMessages['verified'][$lng];
                    $personalizedMessage = str_replace(':client_name', $client->firstname . ' ' . $client->lastname, $nextMessage);
                    sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

                    $clientMessageStatus->update([
                        'menu_option' => 'main_menu',
                    ]);

                    // Create webhook response
                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $nextMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);

                    $this->sendMainMenu($client, $from);
                    break;
                case 'incorect_otp':
                    $nextMessage = $this->activeClientBotMessages['incorect_otp'][$lng];
                    sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);
                    $clientMessageStatus->update([
                        'menu_option' => 'not_recognized->enter_phone->email_sent',
                    ]);

                    // Create webhook response
                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $nextMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);
                    break;
                case 'new_lead':
                    $nextMessage = $this->activeClientBotMessages['after_new_lead'][$lng];
                    sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

                    $m = $this->botMessages['main-menu']['heb'];
                    sendWhatsappMessage($from, array('name' => '', 'message' => $m));

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'message'       =>  $m,
                        'number'        =>  $from,
                        'read'          => 1,
                        'flex'          => 'A'
                    ]);

                    $lead                = new Client;
                    $lead->firstname     = 'lead';
                    $lead->lastname      = '';
                    $lead->phone         = $from;
                    $lead->email         = "";
                    $lead->status        = 0;
                    $lead->password      = Hash::make(Str::random(20));
                    $lead->passcode      = $from;
                    $lead->geo_address   = '';
                    $lead->lng           = ($lng == 'heb' ? 'heb' : 'en');
                    $lead->save();

                    WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $lead->id,
                    ], [
                        'menu_option' => 'main_menu',
                        'language' => $lng == 'heb' ? 'he' : 'en',
                    ]);

                    break;
                case 'sorry':
                    $nextMessage = $this->activeClientBotMessages['sorry'][$lng];
                    sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);
                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $nextMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);
                    break;
                case 'failed_attempts':
                    $nextMessage = $this->activeClientBotMessages['failed_attempts'][$lng];
                    sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

                    WhatsAppBotActiveClientState::updateOrCreate(
                        ["from" => $from],
                        [
                            "from" => $from,
                            'menu_option' => 'failed_attempts'
                        ]
                    );

                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $nextMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);
                    break;

                case 'team_send_message_1':
                    \Log::info('team_send_message_1');
                    $text = [
                        "en" => "Hello :client_name,
Please let us know what additional information or request you would like to add.",
                        "heb" => "שלום :client_name,
אנא עדכן אותנו מה ברצונך להוסיף או לבקש."
                    ];

                    $nextMessage = $text[$lng];
                    $clientName = "*" . ($client->firstname ?? '') . ' ' . ($client->lastname ?? '') . "*";
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
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $nextMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);
                    break;

                case "client_add_request":

                    $text = [
                        "en" => "Hello :client_name,
We’ve received your updated request:
':client_message'
Your message has been forwarded to the team for further handling. Thank you for your patience!",
                        "heb" => "שלום :client_name,
קיבלנו את עדכון הבקשה שלך:
':client_message'
ההודעה הועברה לצוות להמשך טיפול. תודה על הסבלנות!"
                    ];

                    $nextMessage = $text[$lng];
                    $clientName = "*" . ($client->firstname ?? '') . ' ' . ($client->lastname ?? '') . "*";
                    $personalizedMessage = str_replace([':client_name', ':client_message'], [$clientName, '*' . trim($input) . '*'], $nextMessage);
                    sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

                    $scheduleChange = new ScheduleChange();
                    $scheduleChange->user_type = get_class($client);
                    $scheduleChange->user_id = $client->id;
                    $scheduleChange->reason = $lng == "en" ? "additional information" : 'מידע נוסף';
                    $scheduleChange->comments = $input;
                    $scheduleChange->save();
                    $clientMessageStatus->delete();

                    break;

                case 'stop':
                    $nextMessage = $this->activeClientBotMessages['stop'][$lng];
                    $personalizedMessage = str_replace(':client_name', $client->firstname . ' ' . $client->lastname, $nextMessage);
                    sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

                    WhatsAppBotActiveClientState::updateOrCreate(
                        ["from" => $from],
                        [
                            "from" => $from,
                            'menu_option' => 'stop'
                        ]
                    );

                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $personalizedMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);
                    break;
            }
        }
    }

    public function ClientOtpSend($client, $from, $lng)
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
            $message->subject(__('mail.verification.subject'));
        });

        WhatsAppBotActiveClientState::updateOrCreate(
            ["from" => $from],
            [
                "client_phone" => $client->phone,
                'menu_option' => 'not_recognized->enter_phone->email_sent'
            ]
        );

        $nextMessage = $this->activeClientBotMessages['email_sent'][$lng];
        $personalizedMessage = str_replace(':email', substr($client->email, 0, 2), $nextMessage);
        sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

        WebhookResponse::create([
            'status' => 1,
            'name' => 'whatsapp',
            'message' => $personalizedMessage,
            'number' => $from,
            'read' => 1,
            'flex' => 'A',
        ]);

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

    public function sendMainMenu($client, $from)
    {
        $lng = $client->lng;

        // Fetch the initial message based on the selected language
        $initialMessage = $this->activeClientBotMessages['main_menu'][$lng];

        // Replace :client_name with the client's firstname and lastname
        $clientName = $client->firstname ?? '' . ' ' . $client->lastname ?? '';
        $personalizedMessage = str_replace(':client_name', $clientName, $initialMessage);
        $result = sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

        WhatsAppBotActiveClientState::updateOrCreate(
            ['client_id' => $client->id],
            [
                'from' => $from,
                'menu_option' => 'main_menu',
                'lng' => $lng
            ]
        );

        WebhookResponse::create([
            'status' => 1,
            'name' => 'whatsapp',
            'message' => $personalizedMessage,
            'number' => $from,
            'read' => 1,
            'flex' => 'A',
        ]);

        return response()->json(['status' => 'success'], 200);
    }


    public function clientReview(Request $request){
        try {
            $get_data = $request->getContent();
            $responseClientState = [];
            $data_returned = json_decode($get_data, true);
            $message = null;

            $messageId = $data_returned['messages'][0]['id'] ?? null;

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


            if (
                isset($data_returned['messages']) &&
                isset($data_returned['messages'][0]['from_me']) &&
                $data_returned['messages'][0]['from_me'] == false
            ) {
                $message_data = $data_returned['messages'];
                if (Str::endsWith($message_data[0]['chat_id'], '@g.us')) {
                    die("Group message");
                }
                $from = $message_data[0]['from'];
                $client = Client::where('phone', 'like', $from)->where('status', '2')->whereHas('lead_status', function($q) {
                    $q->where('lead_status', LeadStatusEnum::ACTIVE_CLIENT);
                })->first();

                $msgStatus = null;
                if($client){
                    $msgStatus = Cache::get('client_review' . $client->id);
                }

                if(!empty($msgStatus)){

                    $messageBody = trim($data_returned['messages'][0]['text']['body'] ?? '');
                    $last_input2 = Cache::get('client_review_input2' . $client->id) ?? null;

                    // $last_input1 = Cache::get('client_review_input1' . $client->id);

                    if(Cache::get('client_review_sorry' . $client->id) && !in_array(strtolower(trim($messageBody)), ["menu", "תפריט"])){
                        Cache::forget('client_review_sorry' . $client->id);
                        Cache::forget('client_review_input2' . $client->id);
                        Cache::forget('client_review' . $client->id);

                    }

                    if($messageBody == '7'){

                        $message = $client->lng == "en" ? "We’re delighted to hear you were satisfied with our service! 🌟\nThank you for your positive feedback. We’re here if you need anything else."
                        : "שמחים לשמוע שהייתם מרוצים מהשירות שלנו! 🌟\nתודה רבה על הפידבק החיובי. אנחנו כאן לכל דבר נוסף.";

                        sendClientWhatsappMessage($from, ['name' => '', 'message' => $message]);
                        sleep(2);
                        Cache::forget('client_review' . $client->id);

                    }else if ($messageBody == '8'){

                        $message = $client->lng == "en" ? "Thank you for your feedback!\nPlease write your comment or request here."
                        : "תודה על הפידבק שלכם!\nאנא כתבו את ההערה או הבקשה שלכם.";

                        sendClientWhatsappMessage($from, ['name' => '', 'message' => $message]);

                        Cache::put('client_review_input2' . $client->id, 'client_review_input2', now()->addDay(1));

                    } else if(!empty($last_input2) && !empty($messageBody)){
                        \Log::info('last input2');
                        $scheduleChange = ScheduleChange::create([
                            'user_type' => get_class($client),
                            'user_id' => $client->id,
                            'comments' => $messageBody,
                            "reason" => $client->lng == "en" ? "Client Feedback" : 'משוב לקוח',
                        ]);

                        $message = $client->lng == "en" ? "Thank you for your feedback! Your message has been received and will be forwarded to the supervisor for further handling.\nWe’re here for anything else you might need and will get back to you if necessary."
                        : "תודה על הפידבק שלכם! ההודעה שלכם התקבלה ותועבר למפקח להמשך טיפול.\nאנחנו כאן לכל דבר נוסף ונחזור אליכם במידת הצורך.";

                        sendClientWhatsappMessage($from, ['name' => '', 'message' => $message]);

                        $teammsg = "שלום צוות,\n\n:client_name שיתף את ההערה או הבקשה הבאה בנוגע לשירות האחרון שקיבל:\n':message'\n\nאנא בדקו וטפלו בנושא בהקדם. עדכנו את הלקוח כשהנושא טופל.\n:comment_link";
                        $clientName = "*" .(($client->firstname ?? '') . ' ' . ($client->lastname ?? '')) . "*";
                        $teammsg = str_replace([
                            ':client_name', ':message', ':comment_link'], [
                                $clientName, '*' . trim($scheduleChange->comments) . '*', generateShortUrl(url('admin/schedule-requests'.'?id=' . $scheduleChange->id), 'admin')
                            ], $teammsg);

                        sendTeamWhatsappMessage(config('services.whatsapp_groups.reviews_of_clients'), ['name' => '', 'message' => $teammsg]);
                        sleep(2);
                        Cache::forget('client_review_input2' . $client->id);
                        Cache::forget('client_review_sorry' . $client->id);
                        Cache::forget('client_review' . $client->id);
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
            $get_data = $request->getContent();
            $responseClientState = [];
            $data_returned = json_decode($get_data, true);
            $message = null;

            $messageId = $data_returned['messages'][0]['id'] ?? null;

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


            if (
                isset($data_returned['messages']) &&
                isset($data_returned['messages'][0]['from_me']) &&
                $data_returned['messages'][0]['from_me'] == false
            ) {
                $message_data = $data_returned['messages'];
                if (Str::endsWith($message_data[0]['chat_id'], '@g.us')) {
                    die("Group message");
                }
                $from = $message_data[0]['from'];
                Log::info($from);

                $client = Client::where('phone', 'like', $from)->where('status', '2')->whereHas('lead_status', function($q) {
                    $q->where('lead_status', LeadStatusEnum::ACTIVE_CLIENT);
                })->first();

                if($client){
                    $msgStatus = Cache::get('client_review' . $client->id);
                    $input = trim($data_returned['messages'][0]['text']['body'] ?? '');
                    if (!empty($msgStatus) && ($input == '7' || $input == '8')) {
                        \Log::info('Client already reviewed');
                        die('Client already reviewed');
                    }

                    $msgStatus = Cache::get('client_review_input2' . $client->id);
                    $input = trim($data_returned['messages'][0]['text']['body'] ?? '');
                    if (!empty($msgStatus)) {
                        \Log::info('Client already reviewed');
                        die('Client already reviewed');
                    }
                }

                $isMonday = now()->isMonday();
                if ($isMonday && $client && $client->stop_last_message == 0) {

                    $msgStatus = Cache::get('client_monday_msg_status_' . $client->id);
                    if(!empty($msgStatus)) {
                        $menu_option = explode('->', $msgStatus);
                        $messageBody = trim($data_returned['messages'][0]['text']['body'] ?? '');
                        $last_menu = end($menu_option);

                        if($last_menu == 'main_monday_msg' && $messageBody == '1') {
                            $m = $client->lng == 'heb'
                                ? "מהו השינוי או הבקשה לשבוע הבא?"
                                : "What is your change for next week?";

                            sendClientWhatsappMessage($from, ['name' => '', 'message' => $m]);
                            Cache::put('client_monday_msg_status_' . $client->id, 'main_monday_msg->next_week_change', now()->addDay(1));
                            WebhookResponse::create([
                                'status'        => 1,
                                'name'          => 'whatsapp',
                                'entry_id'      => $get_data['entry'][0]['id'] ?? '',
                                'message'       => $m,
                                'number'        => $from,
                                'flex'          => 'A',
                                'read'          => 1,
                                'data'          => json_encode($get_data),
                            ]);
                        } else if ($last_menu == 'next_week_change' && !empty($messageBody)) {
                            $scheduleChange = ScheduleChange::create([
                                    'user_type' => get_class($client),
                                    'user_id' => $client->id,
                                    'comments' => $messageBody,
                                    "reason" => $client->lng == "en" ? "Change or update schedule" : 'שינוי או עדכון שיבוץ',
                                ]
                            );
                            $clientName = (($client->firstname ?? '') . ' ' . ($client->lastname ?? ''));
                            $teammsg = "שלום צוות, הלקוח " . "*" . $clientName . "*" . " ביקש לבצע שינוי בסידור העבודה שלו לשבוע הבא. הבקשה שלו היא: \"". '*' . $messageBody . '*' ."\" אנא בדקו וטפלו בהתאם. בברכה, צוות ברום סרוויס \n:comment_link";
                            $personalizedMessage = str_replace(':comment_link', generateShortUrl(url('admin/schedule-requests'.'?id=' . $scheduleChange->id), 'admin') , $teammsg);

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

                            sendClientWhatsappMessage($from, ['message' => $message]);
                        } else if ($last_menu == 'review_changes' && $messageBody == '1') {
                            // Cache the user's intention to edit
                            Cache::put('client_monday_msg_status_' . $client->id, 'main_monday_msg->next_week_change->review_changes->changes', now()->addDay(1));

                            $promptMessage = $client->lng == 'heb'
                                ? "מהו השינוי או הבקשה לשבוע הבא?"
                                : "What is your change or request for next week?";
                            sendClientWhatsappMessage($from, ['message' => $promptMessage]);
                        } else if ($last_menu == 'review_changes' && $messageBody == '2') {
                            // Cache the user's intention to edit
                            Cache::put('client_monday_msg_status_' . $client->id, 'main_monday_msg->next_week_change->review_changes->additional', now()->addDay(1));

                            $promptMessage = $client->lng == 'heb'
                                ? "אנא הזן הודעה כדי להוסיף מידע נוסף."
                                : "Please enter a message to add additional information.";
                            sendClientWhatsappMessage($from, ['message' => $promptMessage]);
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
                                $teammsg = "שלום צוות, הלקוח " . "*" .$clientName . "*" . "  ביקש לבצע שינוי בסידור העבודה שלו לשבוע הבא. הבקשה שלו היא: \"". '*' . $messageBody . '*' ."\" אנא בדקו וטפלו בהתאם. בברכה, צוות ברום סרוויס\n:comment_link";
                                $personalizedMessage = str_replace(':comment_link', generateShortUrl(url('admin/schedule-requests'.'?id=' . $scheduleChange->id), 'admin') , $teammsg);

                                sendTeamWhatsappMessage(config('services.whatsapp_groups.changes_cancellation'), ['name' => '', 'message' => $personalizedMessage]);

                                $confirmationMessage = $client->lng == 'heb'
                                    ? "ההודעה שלך התקבלה ותועבר לצוות שלנו להמשך טיפול."
                                    : "Your message has been received and will be forwarded to our team for further handling.";
                                sendClientWhatsappMessage($from, ['message' => $confirmationMessage]);
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
                            $teammsg = "שלום צוות, הלקוח " . "*" .$clientName. "*" ." ביקש לבצע שינוי בסידור העבודה שלו לשבוע הבא. הבקשה שלו היא: \"". '*' . $messageBody . '*' ."\" אנא בדקו וטפלו בהתאם. בברכה, צוות ברום סרוויס\n:comment_link";
                            $personalizedMessage = str_replace(':comment_link', generateShortUrl(url('admin/schedule-requests'.'?id=' . $scheduleChange->id), 'admin') , $teammsg);

                            sendTeamWhatsappMessage(config('services.whatsapp_groups.changes_cancellation'), ['name' => '', 'message' => $personalizedMessage]);

                            $confirmationMessage = $client->lng == 'heb'
                                ? "ההודעה שלך התקבלה ותועבר לצוות שלנו להמשך טיפול."
                                : "Your message has been received and will be forwarded to our team for further handling.";
                            sendClientWhatsappMessage($from, ['message' => $confirmationMessage]);
                            $client->stop_last_message = 1 ;
                            $client->save();
                            // Clear the cache after the action is complete
                            Cache::forget('client_monday_msg_status_' . $client->id);
                        } else {
                            $follow_up_msg = $client->lng == 'heb'
                                ? "מצטערים, לא הבנו את הבקשה.\n• במידה ויש שינוי או בקשה, אנא השיבו עם הספרה 1.\n• תוכלו גם להקליד 'תפריט' כדי לחזור לתפריט הראשי"
                                : "Sorry, I didn’t quite understand that.\n• If you have a change or request, please reply with the number 1.\n• You can also type 'Menu' to return to the main menu.";

                            WebhookResponse::create([
                                'status'        => 1,
                                'name'          => 'whatsapp',
                                'entry_id'      => $get_data['entry'][0]['id'] ?? '',
                                'message'       => $follow_up_msg,
                                'number'        => $from,
                                'flex'          => 'A',
                                'read'          => 1,
                                'data'          => json_encode($get_data),
                            ]);

                            sendClientWhatsappMessage($from, ['message' => $follow_up_msg]);
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
        try {
            $get_data = $request->getContent();
            $responseClientState = [];
            $data_returned = json_decode($get_data, true);
            $message = null;

            $messageId = $data_returned['messages'][0]['id'] ?? null;

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


            if (
                isset($data_returned['messages']) &&
                isset($data_returned['messages'][0]['from_me']) &&
                $data_returned['messages'][0]['from_me'] == false
            ) {
                $message_data = $data_returned['messages'];
                if (Str::endsWith($message_data[0]['chat_id'], '@g.us')) {
                    die("Group message");
                }
                $from = $message_data[0]['from'];
                Log::info($from);
                \Log::info('$msgStatus', [$from]);
                $client = Client::where('phone', 'like', $from)->where('status', '2')->whereHas('lead_status', function($q) {
                    $q->where('lead_status', LeadStatusEnum::ACTIVE_CLIENT);
                })->first();
                if($client){

                    $msgStatus = Cache::get('client_review' . $client->id);
                    $input = trim($data_returned['messages'][0]['text']['body'] ?? '');
                    if (!empty($msgStatus) && ($input == '7' || $input == '8')) {
                        \Log::info('Client already reviewed');
                        die('Client already reviewed');
                    }

                    $msgStatus = Cache::get('client_review_input2' . $client->id);
                    $input = trim($data_returned['messages'][0]['text']['body'] ?? '');
                    if (!empty($msgStatus)) {
                        \Log::info('Client already reviewed');
                        die('Client already reviewed');
                    }
                }

                $isWednesday = now()->isWednesday();
                if ($isWednesday && $client) {

                    $msgStatus = Cache::get('client_job_confirm_msg' . $client->id);
                    \Log::info('$msgStatus', [$msgStatus]);
                    if(!empty($msgStatus)) {
                        $menu_option = explode('->', $msgStatus);
                        $messageBody = trim($data_returned['messages'][0]['text']['body'] ?? '');
                        $last_menu = end($menu_option);

                        if($last_menu == 'main_msg' && $messageBody == '1') {
                            $m = $client->lng == 'heb'
                                ? "מהו השינוי או הבקשה לשבוע הבא?"
                                : "What is your change for next week?";

                            sendClientWhatsappMessage($from, ['name' => '', 'message' => $m]);
                            Cache::put('client_job_confirm_msg' . $client->id, 'main_msg->next_week_change', now()->addDay(1));
                            WebhookResponse::create([
                                'status'        => 1,
                                'name'          => 'whatsapp',
                                'entry_id'      => $get_data['entry'][0]['id'] ?? '',
                                'message'       => $m,
                                'number'        => $from,
                                'flex'          => 'A',
                                'read'          => 1,
                                'data'          => json_encode($get_data),
                            ]);
                        } else if ($last_menu == 'next_week_change' && !empty($messageBody)) {
                            $scheduleChange = ScheduleChange::create([
                                    'user_type' => get_class($client),
                                    'user_id' => $client->id,
                                    'comments' => $messageBody,
                                    "reason" => $client->lng == "en" ? "Change or update schedule" : 'שינוי או עדכון שיבוץ',
                                ]
                            );
                            $clientName = (($client->firstname ?? '') . ' ' . ($client->lastname ?? ''));
                            $teammsg = "שלום צוות, הלקוח " ."*" .$clientName . "*". "  ביקש לבצע שינוי בסידור העבודה שלו לשבוע הבא. הבקשה שלו היא: \"". '*' . $messageBody . '*' ."\" אנא בדקו וטפלו בהתאם. בברכה, צוות ברום סרוויס\n:comment_link";
                            $personalizedMessage = str_replace(':comment_link', generateShortUrl(url('admin/schedule-requests'.'?id=' . $scheduleChange->id), 'admin'), $teammsg);

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

                            sendClientWhatsappMessage($from, ['message' => $message]);
                        } else if ($last_menu == 'review_changes' && $messageBody == '1') {
                            // Cache the user's intention to edit
                            Cache::put('client_job_confirm_msg' . $client->id, 'main_msg->next_week_change->review_changes->changes', now()->addDay(1));

                            $promptMessage = $client->lng == 'heb'
                                ? "מהו השינוי או הבקשה לשבוע הבא?"
                                : "What is your change or request for next week?";
                            sendClientWhatsappMessage($from, ['message' => $promptMessage]);
                        } else if ($last_menu == 'review_changes' && $messageBody == '2') {
                            // Cache the user's intention to edit
                            Cache::put('client_job_confirm_msg' . $client->id, 'main_msg->next_week_change->review_changes->additional', now()->addDay(1));

                            $promptMessage = $client->lng == 'heb'
                                ? "אנא הזן הודעה כדי להוסיף מידע נוסף."
                                : "Please enter a message to add additional information.";
                            sendClientWhatsappMessage($from, ['message' => $promptMessage]);
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
                                $clientName = (($client->firstname ?? '') . ' ' . ($client->lastname ?? ''));
                                $teammsg = "שלום צוות, הלקוח " . "*" .$clientName. "*" . "  ביקש לבצע שינוי בסידור העבודה שלו לשבוע הבא. הבקשה שלו היא: \"". '*' . $messageBody. '*' ."\" אנא בדקו וטפלו בהתאם. בברכה, צוות ברום סרוויס\n:comment_link";
                                $personalizedMessage = str_replace(':comment_link', generateShortUrl(url('admin/schedule-requests'.'?id=' . $scheduleChange->id), 'admin'), $teammsg);

                                sendTeamWhatsappMessage(config('services.whatsapp_groups.changes_cancellation'), ['name' => '', 'message' => $personalizedMessage]);

                                $confirmationMessage = $client->lng == 'heb'
                                    ? "ההודעה שלך התקבלה ותועבר לצוות שלנו להמשך טיפול."
                                    : "Your message has been received and will be forwarded to our team for further handling.";
                                sendClientWhatsappMessage($from, ['message' => $confirmationMessage]);
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
                            $clientName = (($client->firstname ?? '') . ' ' . ($client->lastname ?? ''));
                            $teammsg = "שלום צוות, הלקוח " ."*" .$clientName. "*". " ביקש לבצע שינוי בסידור העבודה שלו לשבוע הבא. הבקשה שלו היא: \"". '*' .$messageBody . '*' ."\" אנא בדקו וטפלו בהתאם. בברכה, צוות ברום סרוויס\n:comment_link";
                            $personalizedMessage = str_replace(':comment_link', generateShortUrl(url('admin/schedule-requests'.'?id=' . $scheduleChange->id), 'admin'), $teammsg);

                            sendTeamWhatsappMessage(config('services.whatsapp_groups.changes_cancellation'), ['name' => '', 'message' => $personalizedMessage]);

                            $confirmationMessage = $client->lng == 'heb'
                                ? "ההודעה שלך התקבלה ותועבר לצוות שלנו להמשך טיפול."
                                : "Your message has been received and will be forwarded to our team for further handling.";
                            sendClientWhatsappMessage($from, ['message' => $confirmationMessage]);
                            $client->stop_last_message = 1 ;
                            $client->save();
                            // Clear the cache after the action is complete
                            Cache::forget('client_job_confirm_msg' . $client->id);
                        } else {
                            $follow_up_msg = $client->lng == 'heb'
                                ? "מצטערים, לא הבנו את הבקשה.\n• במידה ויש שינוי או בקשה, אנא השיבו עם הספרה 1.\n• תוכלו גם להקליד 'תפריט' כדי לחזור לתפריט הראשי"
                                : "Sorry, I didn’t quite understand that.\n• If you have a change or request, please reply with the number 1.\n• You can also type 'Menu' to return to the main menu.";

                            WebhookResponse::create([
                                'status'        => 1,
                                'name'          => 'whatsapp',
                                'entry_id'      => $get_data['entry'][0]['id'] ?? '',
                                'message'       => $follow_up_msg,
                                'number'        => $from,
                                'flex'          => 'A',
                                'read'          => 1,
                                'data'          => json_encode($get_data),
                            ]);

                            sendClientWhatsappMessage($from, ['message' => $follow_up_msg]);
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
}
