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


class LeadWebhookController extends Controller
{
    use ScheduleMeeting;

    protected $botMessages = [
        'main-menu' => [
            'en' => "Hi, I'm Bar, the digital representative of Broom Service. How can I help you today? 😊\n\nAt any stage, you can return to the main menu by sending the number 9 or return one menu back by sending the number 0.\n\n1. About the Service\n2. Service Areas\n3. Set an appointment for a quote\n4. Customer Service\n5. Switch to a human representative (during business hours)\n7. שפה עברית\n\nIf you no longer wish to receive messages from us, please reply with 'STOP' at any time",
            'heb' => 'היי, אני בר, הנציגה הדיגיטלית של ברום סרוויס. איך אוכל לעזור לך היום? 😊' . "\n\n" . 'בכל שלב תוכלו לחזור לתפריט הראשי ע"י שליחת המס 9 או לחזור תפריט אחד אחורה ע"י שליחת הספרה 0' . "\n\n" . '1. פרטים על השירות' . "\n" . '2. אזורי שירות' . "\n" . '3. קביעת פגישה לקבלת הצעת מחיר' . "\n" . '4. שירות ללקוחות קיימים' . "\n" . '5. מעבר לנציג אנושי (בשעות הפעילות)' . "\n" . '6. English menu' . "\n\n" . "אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת."
        ]
    ];

    protected $activeClientBotMessages = [
        "main_menu" => [
            "en" => "Hello :client_name 🌸, I’m Gali, the digital secretary of Broom Service!\nHow can I assist you today ? 😊\n\nHere are your options:\n1️⃣ Contact me urgently\n2️⃣ When is my next service?\n3️⃣ Request a new quote\n4️⃣ Invoice and accounting inquiry\n5️⃣ Change or update schedul\n6️⃣ Access our client portal\n\n❓ If you have a question or request not listed, type 'Menu' to return to the main menu at any time.",
            "heb" => "שלום -CLIENT NAME-🌸, אני גלי, המזכירה הדיגיטלית של ברום סרוויס!\nבמה אוכל לעזור לך היום? 😊\n\nלהלן האפשרויות:\n1️⃣ צרו איתי קשר דחוף\n2️⃣ מתי מגיעים אלי?\n3️⃣ בקשה להצעת מחיר חדשה\n4️⃣ הנה'ח - פנייה למחלקת הנהלת חשבונות\n5️⃣ שינוי או עדכון שיבוץ\n6️⃣ גישה לפורטל הלקוחות שלנו\n\n❓ אם יש לך שאלה אחרת או בקשה שלא בתפריט, תוכל תמיד להחזיר אותי לתפריט הראשי על ידי כתיבת 'תפריט'."
        ],
        "not_recognized" => [
            "en" => "Hello, we couldn’t recognize your number in our system.\nAre you an existing client, or would you like to receive a quote for our service?\n 1️⃣ I am an existing client\n 2️⃣ I’d like a quote",
            "heb" => "שלום, לא זיהינו את המספר שלך במערכת.\nהאם אתה לקוח קיים או מעוניין לקבל הצעת מחיר לשירות?\n 1️⃣ אני לקוח קיים\n 2️⃣ מעוניין לקבל הצעת מחיר"
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
            "en" => "🔔 Client :client_name has requested an urgent callback regarding: :message\n📞 Phone: :client_phone\n📄 :client_link",
            "heb" => "🔔 לקוח בשם :client_name ביקש שיחזרו אליו בדחיפות בנושא: :message\n📞 טלפון: :client_phone\n📄 :client_link"
        ],
        "service_schedule" => [
            "en" => "Your service is scheduled for \n:date_time",
            "heb" => "השירות בשבוע הבא מתוכנן ל- \n:date_time"
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
            "heb"=> "🔔 לקוח בשם :client_name ביקש הצעת מחיר חדשה.\n📞 טלפון: :client_phone\n📄 :client_link"
        ],
        "invoice_account" => [
            "en" => "What would you like to forward to our accounting department?\nPlease let us know your inquiry or request, and we’ll ensure to get back to you promptly.",
            "heb" => 'מה תרצה להעביר למחלקת הנה"ח שלנו?\nאנא ציין את בקשתך או השאלה שלך, ואנו נדאג להחזיר לך תשובה בהקדם.'
        ],
        "thank_you_invoice_account" => [
            "en" => "Hello :client_name,\n    • Thank you for reaching out to our accounting department.\nYour request has been received, and we are forwarding it to the relevant team for review.\nWe will get back to you as soon as possible with a detailed response.",
            "heb" => 'שלום :client_name,\n    • תודה על פנייתך למחלקת הנה"ח שלנו.\nהבקשה שלך התקבלה ואנו מעבירים אותה לבדיקה של הצוות הרלוונטי.\nנחזור אליך בהקדם האפשרי עם תשובה מסודרת.'
        ],
        "team_invoice_account" => [
            "en" => "🔔 Client :client_name has contacted accounting with the following message: :message\n📞 Phone: :client_phone\n📄 :client_link",
            "heb" => "🔔 לקוח בשם :client_name פנה למחלקת הנה'ח עם ההודעה הבאה: :message\n📞 טלפון: :client_phone\n📄 :client_link"
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
            "en" => "🔔 Client :client_name has requested to change or update their schedule. \nMessage logged: :message\n📞 Phone: :client_phone\n📄 :client_link",
            "heb" => "🔔 לקוח בשם :client_name ביקש לשנות או לעדכן שיבוץ. ההודעה שנרשמה: :message\n📞 טלפון: :client_phone\n📄 :client_link"
        ],
        "access_portal" => [
            "en" => "To access our client portal, please click here: :client_portal_link.",
            "heb" => "לכניסה לפורטל הלקוחות שלנו, אנא לחץ כאן: :client_portal_link."
        ],
        "sorry" => [
            "en" => "Sorry, I didn’t understand your request.\nPlease try again or type 'Menu' to return to the main menu.",
            "heb" => "מצטערים, לא הבנתי את בקשתך.\nאנא נסה שוב או הקלד 'תפריט' כדי לחזור לתפריט הראשי."
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

            $lead_exists = Client::where('phone', $request->phone)->orWhere('email', $request->email)->exists();
            if (!$lead_exists) {
                $lead = new Client;
            } else {
                $lead = Client::where('phone', 'like', '%' . $request->phone . '%')->first();
                if (empty($lead)) {
                    $lead = Client::where('email', $request->email)->first();
                }
                $lead = Client::find($lead->id);
            }
            $nm = explode(' ', $request->name);

            $lead->firstname     = $nm[0];
            $lead->lastname     = (isset($nm[1])) ? $nm[1] : '';
            $lead->phone         = $request->phone;
            $lead->email         = $request->email;
            $lead->status        = 0;
            $lead->lng = 'en';
            $lead->password      = Hash::make($request->phone);
            $lead->passcode      = $request->phone;
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
                            $client->email = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : ($phone . '@lead.com');
                            $client->status = 0;
                            $client->password = Hash::make($phone);
                            $client->passcode = $phone;
                            $client->geo_address = '';
                            $client->lng = ($lng);
                            $client->save();
            
                            $m = $lng == 'heb' 
                                ? "ליד חדש נוצר בהצלחה\n" . url("admin/leads/view/" . $client->id) 
                                : "New lead created successfully\n" . url("admin/leads/view/" . $client->id);
                        } else {
                           
                            if($client->status != 2) {
                                $client->status = 0;
                                $client->lead_status->update([
                                    'lead_status' => LeadStatusEnum::PENDING,
                                ]);
                                $client->created_at = Carbon::now();
                                $client->save();
                            }

                            $m = $lng == 'heb' 
                                ? "עופרת כבר קיימת\n" . url("admin/leads/view/" . $client->id) 
                                : "Lead already exists\n" . url("admin/leads/view/" . $client->id);
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

            if($client) {
                \Log::info('Client: ' . $client->id);
            }
            if($user) {
                \Log::info('User: ' . $user->id);
            }
            if($workerLead) {
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
                $lead->email         = $from . '@lead.com';
                $lead->status        = 0;
                $lead->password      = Hash::make($from);
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
            } else if ($client->disable_notification == 1) {
                \Log::info('notification disabled');
                die('notification disabled');
            }

            $responseClientState = WhatsAppBotClientState::where('client_id', $client->id)->first();
            if ($responseClientState && $responseClientState->final) {
                \Log::info('final');
                die('final');
            };

            if ($client && $data_returned['channel_id'] == 'DEADPL-DAB6G' && isset($data_returned) && isset($data_returned['messages']) && is_array($data_returned['messages'])) {
                $message = ($message_data[0]['type'] == 'text') ? $message_data[0]['text']['body'] : ($message_data[0]['button']['text'] ?? "");
                // \Log::info($message);
                $result = WhatsappLastReply::where('phone', $from)
                    ->where('updated_at', '>=', Carbon::now()->subMinutes(15))
                    ->first();

                $client_menus = WhatsAppBotClientState::where('client_id', $client->id)->first();

                if($message == 0){
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
                    }else{
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
                    }else{
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

                    \Log::info($response->json());


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

                                $link = url("meeting-status/" . base64_encode($schedule->id) . "/reschedule");
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
            $lead = Client::find($lead->id);
        }
        $name = explode(' ', $request->name);

        $lead->firstname = $name[0];
        $lead->lastname = (isset($name[1])) ? $name[1] : '';
        $lead->phone = $phone;
        $lead->email = $request->email;
        $lead->status = 0;
        $lead->lng = 'en';
        $lead->password = Hash::make($phone);
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
        $lng = "en";

        \Log::info($data_returned);

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
            $input = $data_returned['messages'][0]['text']['body'];
            $lng = "heb";
            $isMonday = now()->isMonday();

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

            $workerLead = WorkerLeads::where('phone', $from)->first();
            $user = User::where('phone', $from)
                    ->where('status', 1)
                    ->first();
            $client = Client::where('phone', $from)
                    ->orWhereJsonContains('extra', [['phone' => $from]])
                    ->first();
                    // \Log::info($client);

            if($user) {
                \Log::info('User: ' . $user->id);
            }
            if($workerLead) {
                \Log::info('WorkerLead: ' . $workerLead->id);
            }
            if ($client) {
                \Log::info('Client: ' . $client->id);
                \Log::info('Client Name: ' . $client);
            }
            if($client && $client->attempts == 4) {
                \Log::info('Client: ' . $client->id);
                $nextMessage = $this->activeClientBotMessages['failed_attempts'][$client->lng];
                $result = sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

                $clientMessage = WhatsAppBotActiveClientState::updateOrCreate(
                    ['client_id' => $client->id],
                    ["from" => $from,
                    'menu_option' => 'failed_attempts']
                );

                WebhookResponse::create([
                    'status' => 1,
                    'name' => 'whatsapp',
                    'message' => $nextMessage,
                    'number' => $from,
                    'read' => 1,
                    'flex' => 'A',
                ]);
                return response()->json(['status' => 'success'], 200);
            }

            $lastMessage = WebhookResponse::where('flex', 'C')->orderBy('id', 'desc')->first();

            if($lastMessage) {
                \Log::info('lastMessage: ' . $lastMessage);
            }

            if ($client && !$user && !$workerLead && ($client->stop_last_message == 1 )) {
                $lng = $client->lng;
                $last_menu = '';

                $activeClientBot = WhatsAppBotActiveClientState::where('client_id', $client->id)->first();

                if($activeClientBot){
                    $menu_option = explode('->', $activeClientBot->menu_option);
                    $last_menu = end($menu_option);
                    \Log::info($last_menu);
                }

                if (!$activeClientBot || in_array(strtolower($input), ["menu", "תפריט"])) {
                   $this->sendMainMenu($client, $from);
                   die("end");
                }

                if($last_menu == "end"){
                    $nextMessage = $this->activeClientBotMessages['sorry'][$lng];
                    $result = sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);
                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $nextMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);
                }

                // if(in_array(strtolower($input), ["menu", "תפריט"])){
                //     $this->sendMainMenu($client);
                //    die("end");
                // }

                if($input == '1' && $last_menu !== 'end'){
                    $clientName = $client->firstname ?? ''. ' ' . $client->lastname ?? '';

                    $nextMessage = $this->activeClientBotMessages['urgent_contact'][$lng];
                    $personalizedMessage = str_replace(':client_name', $clientName, $nextMessage);
                    $result = sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $personalizedMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);

                    WhatsAppBotActiveClientState::updateOrCreate(
                        ['client_id' => $client->id],
                        ['menu_option' => 'urgent_contact->comment',
                        "from" => $from]
                    );
                    die("end");
                }

                if($last_menu == 'comment'){
                    $clientMessage = WhatsAppBotActiveClientState::updateOrCreate(
                        ['client_id' => $client->id],
                        ['menu_option' => 'end', 
                        'comment' => trim($input),
                        'final' => true,
                        "from" => $from,
                        ]
                    );

                    if($clientMessage->comment){
                        $nextMessage = $this->activeClientBotMessages['thankyou'][$lng];
                        $result = sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

                        WebhookResponse::create([
                            'status' => 1,
                            'name' => 'whatsapp',
                            'message' => $nextMessage,
                            'number' => $from,
                            'read' => 1,
                            'flex' => 'A',
                        ]);

                        $nextMessage = $this->activeClientBotMessages['team_comment']["heb"];
                        $clientName = $client->firstname ?? ''. ' ' . $client->lastname ?? '';
                        $personalizedMessage = str_replace([':client_name', ':message', ':client_phone', ':client_link'], [$clientName, $clientMessage->comment, $client->phone, url("admin/clients/view/" . $client->id)], $nextMessage);
                        // $result = sendTeamWhatsappMessage(config('services.whatsapp_groups.relevant_with_workers'), ['name' => '', 'message' => $personalizedMessage]);
                        $result = sendTeamWhatsappMessage(config('services.whatsapp_groups.lead_client'), ['name' => '', 'message' => $personalizedMessage]);


                        $scheduleChange = new ScheduleChange();
                        $scheduleChange->user_type = get_class($client);
                        $scheduleChange->user_id = $client->id;
                        $scheduleChange->reason = $lng == "en" ? "Contact me urgently" : " צרו איתי קשר דחוף";
                        $scheduleChange->comments = $clientMessage->comment;
                        $scheduleChange->save();

                        // $clientMessage->delete();
                    }
                    
                    die("end");

                }

                if($input == "2" && $last_menu !== 'end'){
                    $now = Carbon::now();
                    $today = Carbon::today()->toDateString();
                    $weekEndDate = Carbon::today()->endOfWeek()->toDateString();
                    $dateTime = '';

                    $nextWeekStart = Carbon::now()->startOfWeek()->addWeek()->format('Y-m-d'); 
                    $nextWeekEnd = Carbon::now()->endOfWeek()->addWeek()->format('Y-m-d'); 

                    // Fetch jobs for the current week
                    $currentWeekJobs = Job::where('client_id', $client->id)
                        ->whereBetween('start_date', [$today, $weekEndDate])
                        ->get();

                    if(!empty($currentWeekJobs)){
                        foreach($currentWeekJobs as $job){
                            Carbon::setLocale($lng == 'en' ? 'en' : 'he');
                            $day = Carbon::parse($job->start_date)->translatedFormat('l'); // Use translatedFormat for localized day
                            $dateTime .=  $day . ' - ' . $job->start_time . ' ' . $job->end_time . "," . "\n";
                        }

                        $nextMessage = $this->activeClientBotMessages['service_schedule'][$lng];
                        $personalizedMessage = str_replace(':date_time', $dateTime, $nextMessage);
                        $result = sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);
                        WebhookResponse::create([
                            'status' => 1,
                            'name' => 'whatsapp',
                            'message' => $personalizedMessage,
                            'number' => $from,
                            'read' => 1,
                            'flex' => 'A',
                        ]);

                        $clientMessage = WhatsAppBotActiveClientState::updateOrCreate(
                            ['client_id' => $client->id],
                            ['menu_option' => 'end', 
                            'final' => true,
                            "from" => $from,
                            ]
                        );
                    }


                    // Fetch jobs for the next week
                    $nextWeekJobs = Job::where('client_id', $client->id)
                        ->whereBetween('start_date', [$nextWeekStart, $nextWeekEnd])
                        ->get();

                    if(!empty($nextWeekJobs)){
                        foreach($nextWeekJobs as $job){
                            Carbon::setLocale($lng == 'en' ? 'en' : 'he');
                            $day = Carbon::parse($job->start_date)->translatedFormat('l'); // Use translatedFormat for localized day
                            $dateTime .= $day . ' - ' . $job->start_time . ' ' . $job->end_time . "," . "\n";
                        }

                        $nextMessage = $this->activeClientBotMessages['next_week_service_schedule'][$lng];
                        $personalizedMessage = str_replace(':date_time', $dateTime, $nextMessage);
                        $result = sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);
                        WebhookResponse::create([
                            'status' => 1,
                            'name' => 'whatsapp',
                            'message' => $personalizedMessage,
                            'number' => $from,
                            'read' => 1,
                            'flex' => 'A',
                        ]);

                        $clientMessage = WhatsAppBotActiveClientState::updateOrCreate(
                            ['client_id' => $client->id],
                            ['menu_option' => 'end', 
                            'final' => true,
                            "from" => $from,
                            ]
                        );

                    }

                    if(empty($currentWeekJobs) && empty($nextWeekJobs)){
                        $nextMessage = $this->activeClientBotMessages['no_service_avail'][$lng];
                        $result = sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

                        WebhookResponse::create([
                            'status' => 1,
                            'name' => 'whatsapp',
                            'message' => $nextMessage,
                            'number' => $from,
                            'read' => 1,
                            'flex' => 'A',
                        ]);
                        $clientMessage = WhatsAppBotActiveClientState::updateOrCreate(
                            ['client_id' => $client->id],
                            ['menu_option' => 'main_menu']
                        );
                    }

                    // WhatsAppBotActiveClientState::where('client_id', $client->id)->delete();
                    die("end");
                }

                if($input == "3" && $last_menu !== 'end'){
                    $nextMessage = $this->activeClientBotMessages['request_new_qoute'][$lng];
                    $result = sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

                    $clientMessage = WhatsAppBotActiveClientState::updateOrCreate(
                        ['client_id' => $client->id],
                        ['menu_option' => 'end', 
                        'final' => true,
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

                    $nextMessage = $this->activeClientBotMessages['team_new_qoute']["heb"];
                    $clientName = $client->firstname ?? ''. ' ' . $client->lastname ?? '';
                    $personalizedMessage = str_replace([':client_name', ':client_phone', ':client_link'], [$clientName, $client->phone, url("admin/clients/view/" . $client->id)], $nextMessage);
                    $result = sendTeamWhatsappMessage(config('services.whatsapp_groups.relevant_with_workers'), ['name' => '', 'message' => $personalizedMessage]);
                    // WhatsAppBotActiveClientState::where('client_id', $client->id)->delete();
                    die("end");
                }

                if($input == "4" && $last_menu !== 'end'){
                    $nextMessage = $this->activeClientBotMessages['invoice_account'][$lng];
                    $result = sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

                    $clientMessage = WhatsAppBotActiveClientState::updateOrCreate(
                        ['client_id' => $client->id],
                        ['menu_option' => 'main_menu->request',
                        "from" => $from,]
                    );

                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $nextMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);

                   
                    die("end");
                }

                if($last_menu == "request"){
                    $clientMessage = WhatsAppBotActiveClientState::updateOrCreate(
                        ['client_id' => $client->id],
                        ['menu_option' => 'end', 
                        'comment' => trim($input),
                        'final' => true,
                        "from" => $from,
                        ]
                    );

                    if($clientMessage->comment){
                        $nextMessage = $this->activeClientBotMessages['thank_you_invoice_account'][$lng];
                        $clientName = $client->firstname ?? ''. ' ' . $client->lastname ?? '';
                        $personalizedMessage = str_replace(':client_name', $clientName, $nextMessage);
                        $result = sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);
                        WebhookResponse::create([
                            'status' => 1,
                            'name' => 'whatsapp',
                            'message' => $personalizedMessage,
                            'number' => $from,
                            'read' => 1,
                            'flex' => 'A',
                        ]);

                        $nextMessage = $this->activeClientBotMessages['team_invoice_account']["heb"];
                        $personalizedMessage = str_replace([':client_name', ":client_phone", ":message" ,':client_link'], [$clientName, $client->phone, $clientMessage->comment, url("admin/clients/view/" . $client->id)], $nextMessage);
                        $result = sendTeamWhatsappMessage(config('services.whatsapp_groups.relevant_with_workers'), ['name' => '', 'message' => $personalizedMessage]);
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
                        $scheduleChange->comments = $clientMessage->comment;
                        $scheduleChange->save();

                        // WhatsAppBotActiveClientState::where('client_id', $client->id)->delete();
                        die("end");
                    }
                }
                
                if($input == "5" && $last_menu !== 'end'){
                    $nextMessage = $this->activeClientBotMessages['change_update_schedule'][$lng];
                    $result = sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

                    $clientMessage = WhatsAppBotActiveClientState::updateOrCreate(
                        ['client_id' => $client->id],
                        ['menu_option' => 'main_menu->change_update_schedule',
                        "from" => $from,]
                    );

                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $nextMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);
                }

                if($last_menu == "change_update_schedule"){
                    $clientMessage = WhatsAppBotActiveClientState::updateOrCreate(
                        ['client_id' => $client->id],
                        ['menu_option' => 'end', 
                        'comment' => trim($input),
                        'final' => true,
                        "from" => $from,
                        ]
                    );

                    if($clientMessage->comment){
                        $nextMessage = $this->activeClientBotMessages['thank_you_change_update_schedule'][$lng];
                        $result = sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);
                        WebhookResponse::create([
                            'status' => 1,
                            'name' => 'whatsapp',
                            'message' => $nextMessage,
                            'number' => $from,
                            'read' => 1,
                            'flex' => 'A',
                        ]);

                        $nextMessage = $this->activeClientBotMessages['team_change_update_schedule']["heb"];
                        $clientName = $client->firstname ?? ''. ' ' . $client->lastname ?? '';
                        $personalizedMessage = str_replace([':client_name', ":client_phone", ":message" ,':client_link'], [$clientName, $client->phone, $clientMessage->comment, url("admin/clients/view/" . $client->id)], $nextMessage);
                        // $result = sendTeamWhatsappMessage(config('services.whatsapp_groups.relevant_with_workers'), ['name' => '', 'message' => $personalizedMessage]);
                        $result = sendTeamWhatsappMessage(config('services.whatsapp_groups.changes_cancellation'), ['name' => '', 'message' => $personalizedMessage]);

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
                        $scheduleChange->reason = $lng == "en" ? "Change or update schedule" : 'שינוי או עדכון שיבוץ';
                        $scheduleChange->comments = $clientMessage->comment;
                        $scheduleChange->save();

                        // WhatsAppBotActiveClientState::where('client_id', $client->id)->delete();
                        die("end");
                    }
                }

                if($input == "6" && $last_menu !== 'end'){
                    $nextMessage = $this->activeClientBotMessages['access_portal'][$lng];
                    $personalizedMessage = str_replace(':client_portal_link', url("client/login"), $nextMessage);
                    $result = sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);
                    $clientMessage = WhatsAppBotActiveClientState::updateOrCreate(
                        ['client_id' => $client->id],
                        ['menu_option' => 'end', 
                        'final' => true,
                        "from" => $from,
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
                    // WhatsAppBotActiveClientState::where('client_id', $client->id)->delete();
                    die("end");
                }
            }else {
                $lng = $this->detectLanguage($input);
                $clientMessage = WhatsAppBotActiveClientState::where('from', $from)->first();

                if(!$clientMessage){
                    $nextMessage = $this->activeClientBotMessages['not_recognized'][$lng];
                    $result = sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

                    $clientMessage = WhatsAppBotActiveClientState::updateOrCreate(
                        ["from" => $from],
                        [
                            'menu_option' => 'not_recognized',
                            'lng' => $lng,
                            "from" => $from,
                        ]
                    );
                    // \Log::info($clientMessage);

                    // if($clientMessage){
                    //     $menu_option = explode('->', $clientMessage->menu_option);
                    //     $last_menu = end($menu_option);
                    //     \Log::info($last_menu);
                    // }

                    WebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $nextMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);

                }else{

                    $menu_option = explode('->', $clientMessage->menu_option);
                    $last_menu = end($menu_option);
                    \Log::info($last_menu);
    
                    if($last_menu == "not_recognized" && $input == "1"){
                        $nextMessage = $this->activeClientBotMessages['enter_phone'][$lng];
                        $result = sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);
                        $clientMessage = WhatsAppBotActiveClientState::updateOrCreate(
                            ["from" => $from],
                            ['menu_option' => 'not_recognized->enter_phone']
                        );
    
                        WebhookResponse::create([
                            'status' => 1,
                            'name' => 'whatsapp',
                            'message' => $nextMessage,
                            'number' => $from,
                            'read' => 1,
                            'flex' => 'A',
                        ]);
                    }
    
                    if ($last_menu == "enter_phone") {
                        // Check if the input contains only numbers and optionally a "+" at the beginning
                        if (preg_match('/^\+?\d+$/', $input)) {
                            Log::info("Input contains number or +", ['input' => $input]);

                            if (strlen($input) > 10) {
                                $client = Client::where('phone', 'like', '%' . substr($input, 2) . '%')->first();
                                $user = User::where('phone', 'like', '%' . substr($input, 2) . '%')->first();
                                $workerLead = WorkerLeads::where('phone', 'like', '%' . substr($input, 2) . '%')->first();
                            } else {
                                $client = Client::where('phone', 'like', '%' . $input . '%')->first();
                                $user = User::where('phone', 'like', '%' . $input . '%')->first();
                                $workerLead = WorkerLeads::where('phone', 'like', '%' . $input . '%')->first();
                            }

                            if($client && !$user && !$workerLead){
                               $this->ClientOtpSend($client, $from, $lng);
                            }

                        } elseif (preg_match('/[^\d+]/', $input)) {
                            $nextMessage = $this->activeClientBotMessages['enter_phone'][$lng];
                            $result = sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);
                            $clientMessage = WhatsAppBotActiveClientState::updateOrCreate(
                                ["from" => $from],
                                ['menu_option' => 'not_recognized->enter_phone']
                            );
        
                            WebhookResponse::create([
                                'status' => 1,
                                'name' => 'whatsapp',
                                'message' => $nextMessage,
                                'number' => $from,
                                'read' => 1,
                                'flex' => 'A',
                            ]);
                        }
                    }

                    if($last_menu == "otp-send"){
                        $clientMessage = WhatsAppBotActiveClientState::where('from', $from)->first();

                        if ($clientMessage) {
                            $client = Client::where('phone', 'like', '%' . $clientMessage->client_phone . '%')->first();

                            if ($client) {
                                \Log::info($client->otp . " " . $input);
                            }

                            if ($client && $client->otp == $input) {
                                // Reset OTP and expiry
                                $client->otp = null;
                                $client->otp_expiry = null;
                            
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
                                $result = sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);
                            
                                // Update bot state
                                $clientMessage = WhatsAppBotActiveClientState::updateOrCreate(
                                    ['client_id' => $client->id],
                                    ["from" => $from,
                                    'menu_option' => 'main_menu']
                                );
                            
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
                                die();
                            } else {
                                if($last_menu == "otp-send" && $input != "0"){
                                    $nextMessage = $this->activeClientBotMessages['incorect_otp'][$lng];
                                    $result = sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

                                    $client->attempts = $client->attempts + 1;
                                    $client->save();
                                
                                    // Update bot state
                                    $clientMessage = WhatsAppBotActiveClientState::updateOrCreate(
                                        ["from" => $from],
                                        ['menu_option' => 'not_recognized->otp-send']
                                    );
                                
                                    // Create webhook response
                                    WebhookResponse::create([
                                        'status' => 1,
                                        'name' => 'whatsapp',
                                        'message' => $nextMessage,
                                        'number' => $from,
                                        'read' => 1,
                                        'flex' => 'A',
                                    ]);
                                    die();
                                }

                                if($last_menu == "otp-send" && $input == "0"){
                                    $this->ClientOtpSend($client, $from, $lng);
                                }
                            }
                        }
                    }

                    if ($last_menu == "not_recognized" && $input == "2") {
                        $lead                = new Client;
                        $lead->firstname     = 'lead';
                        $lead->lastname      = '';
                        $lead->phone         = $from;
                        $lead->email         = $from . '@lead.com';
                        $lead->status        = 0;
                        $lead->password      = Hash::make($from);
                        $lead->passcode      = $from;
                        $lead->geo_address   = '';
                        $lead->lng           = ($lng == 'heb' ? 'heb' : 'en');
                        $lead->save();
                       
                        $m = $this->botMessages['main-menu'][$lng];
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
                            'language' =>  $lng == 'heb' ? 'he' : 'en',
                        ]);
                        die("STOPPED");
                    }
                }

            }
                    
        }
    }

    public function ClientOtpSend($client, $from, $lng){
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
    
        $clientMessage = WhatsAppBotActiveClientState::updateOrCreate(
            ["from" => $from],
            ["client_phone" => $client->phone,
            'menu_option' => 'not_recognized->enter_phone->otp-send']
        );

        $nextMessage = $this->activeClientBotMessages['email_sent'][$lng];
        $personalizedMessage = str_replace(':email', substr($client->email, 0, 2), $nextMessage);
        $result = sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

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

    public function sendMainMenu($client, $from){
         $lng = $client->lng;

         // Fetch the initial message based on the selected language
         $initialMessage = $this->activeClientBotMessages['main_menu'][$lng];
                
         // Replace :client_name with the client's firstname and lastname
         $clientName = $client->firstname ?? ''. ' ' . $client->lastname ?? '';
         $personalizedMessage = str_replace(':client_name', $clientName, $initialMessage);
         $result = sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

         WhatsAppBotActiveClientState::updateOrCreate(
             ['client_id' => $client->id],
             [
                'from' => $from,
                'menu_option' => 'main_menu', 'lng' => $lng]
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


    public function activeClientsMonday(Request $request){
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

                if($user){
                    \Log::info('user'.$user->id);
                }

                if($workerLead){
                    \Log::info('workerLead'.$workerLead->id);
                }

                if($client){
                    \Log::info('client'.$client->id);
                }

                if ($client && !$user && !$workerLead) {
                    $responseClientState = WhatsAppBotClientState::where('client_id', $client->id)->first();
                    if ($responseClientState && $responseClientState->final) {
                        \Log::info('final');
                        die('final');
                    };
        
                    if ($client && !$user && !$workerLead) {
                        $messageBody = $data_returned['messages'][0]['text']['body'] ?? '';
                        $isMonday = now()->isMonday();
                        $tap1 = false;

                        \Log::info('messageBody'.$messageBody);
                    
                        if ($client->status == 2 && $client->lead_status->lead_status == LeadStatusEnum::ACTIVE_CLIENT) {
                            \Log::info('client'.$client->id);
                            $request = ScheduleChange::where('user_id', $client->id)
                                    ->where('user_type', get_class($client))
                                    ->latest()->first();
                                    \Log::info('request'.$request);
                    
                            $isOlderThanWeek = $request && $request->created_at->lt(now()->subWeek());
                    
                            // Handle initial input with messageBody = 1
                            if ($messageBody == 1 && $isMonday && (!$request || $isOlderThanWeek)) {
                                $tap1 = true;
                                $m = $client->lng == 'heb' 
                                    ? "מהו השינוי או הבקשה לשבוע הבא?\n    • במידה ואין שינויים, אין צורך בפעולה נוספת."
                                    : "What is your change for next week?\n    • If there are no changes, no action is needed.";
                    
                                sendClientWhatsappMessage($from, ['name' => '', 'message' => $m]);
                    
                                $client->has_input_one = true;
                                $client->stop_last_message = 1;
                                $client->save();
                    
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
                            }
                    
                            // Handle invalid input when stop_last_message != 1
                            if ($isMonday && $messageBody != '1' && $client->stop_last_message != 1) {
                                $follow_up_msg = $client->lng == 'heb' 
                                    ? "סליחה, לא הצלחתי להבין את ההודעה שלך. 🤗\nתוכל בבקשה לבדוק שוב ולשלוח את תגובתך מחדש? \n\nאם אתה זקוק לעזרה נוספת, תוכל לחזור לתפריט הראשי על ידי שליחת הספרה 9, או לחזור צעד אחד אחורה על ידי שליחת הספרה 0.\n\nאם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הסר' בכל עת."
                                    : "Sorry, I couldn't quite understand your message. 🤗\nCould you please check it and try sending it again?\n\nIf you need further assistance, you can return to the main menu by sending the number 9, or go back one step by sending the number 0.\n\nIf you no longer wish to receive messages from us, please reply with 'STOP' at any time.";
                    
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
                    
                            // Handle new or updated ScheduleChange
                            else if ($messageBody != 1 && $isMonday && (!$request || $isOlderThanWeek) && $client->has_input_one) {
                                $scheduleChange = ScheduleChange::updateOrCreate(
                                    [
                                        'user_type' => get_class($client),
                                        'user_id' => $client->id,
                                    ],
                                    [
                                        'comments' => $messageBody,
                                        "reason" => $client->lng == "en" ? "Change or update schedule" : 'שינוי או עדכון שיבוץ',
                                    ]
                                );
                    
                                $client->stop_last_message = 1;
                                $client->save();
                    
                                // Send follow-up message
                     if($client->lng == 'heb'){
                        $message = 'שלום ' . $client->firstname . " " . $client->lastname . ',  
        
ההודעה שלך התקבלה ותועבר לצוות שלנו להמשך טיפול.

להלן ההודעה ששלחת:
"' . $scheduleChange->comments . '"  

האם תרצה לשנות את ההודעה או לבקש משהו נוסף?

השב 1 כדי לשנות את ההודעה.
השב 2 כדי להוסיף מידע נוסף.
במידה ואין שינויים או מידע נוסף, אין צורך בפעולה נוספת.

פרטי קשר של ברום סרוויס:
🌐 www.broomservice.co.il
📞 טלפון: 03-525-70-60
📧 דוא"ל: office@broomservice.co.il';
                    } else{
                        $message = 'Hello '  . $client->firstname . " " . $client->lastname . ', 

Your message has been received and will be forwarded to our team for further handling.

Here is the message you sent:
"' . $scheduleChange->comments . '"  

Would you like to edit your message or add anything else?

Reply 1 to edit your message.
Reply 2 to add additional information.
If there are no changes or additional information, no further action is needed.

Broom Service Contact Information:
🌐 www.broomservice.co.il
📞 Phone: 03-525-70-60
📧 Email: office@broomservice.co.il';
                    }
        
                    
                                sendClientWhatsappMessage($from, ['message' => $message]);
                            }
                    
                           // Handle updating or appending comments
                            if ($messageBody == 1 && $isMonday && $request && $client->has_input_one) {
                                // Cache the user's intention to edit
                                Cache::put("user_{$client->id}_action", 'edit', now()->addMinutes(10));
        
                                $promptMessage = $client->lng == 'heb' 
                                    ? "מהו השינוי או הבקשה לשבוע הבא?"
                                    : "What is your change or request for next week?";
                                sendClientWhatsappMessage($from, ['message' => $promptMessage]);
                            } elseif ($messageBody == 2 && $isMonday && $request && $client->has_input_one) {
                                // Cache the user's intention to add additional information
                                Cache::put("user_{$client->id}_action", 'add', now()->addMinutes(10));
        
                                $promptMessage = $client->lng == 'heb' 
                                    ? "אנא הזן הודעה כדי להוסיף מידע נוסף."
                                    : "Please enter a message to add additional information.";
                                sendClientWhatsappMessage($from, ['message' => $promptMessage]);
                            } else {
                                // Retrieve the cached action to determine the user's intent
                                $cachedAction = Cache::get("user_{$client->id}_action");
        
                                if ($cachedAction === 'edit') {
                                    // Process editing the existing message
                                    $scheduleChange = ScheduleChange::where('user_type', get_class($client))
                                        ->where('user_id', $client->id)
                                        ->latest()
                                        ->first();
        
                                    if ($scheduleChange) {
                                        $scheduleChange->comments = $messageBody;  
                                        $scheduleChange->save();
        
                                        $confirmationMessage = $client->lng == 'heb' 
                                            ? "ההודעה שלך התקבלה ותועבר לצוות שלנו להמשך טיפול."
                                            : "Your message has been received and will be forwarded to our team for further handling.";
                                        sendClientWhatsappMessage($from, ['message' => $confirmationMessage]);
                                    }
        
                                    // Clear the cache after the action is complete
                                    Cache::forget("user_{$client->id}_action");
                                } elseif ($cachedAction === 'add') {
                                    // Process adding additional information
                                    $scheduleChange = new ScheduleChange();
                                    $scheduleChange->user_type = get_class($client);
                                    $scheduleChange->user_id = $client->id;
                                    $scheduleChange->reason = $client->lng == "en" ? "Change or update schedule" : 'שינוי או עדכון שיבוץ';
                                    $scheduleChange->comments = $messageBody;
                                    $scheduleChange->save();
        
                                    $confirmationMessage = $client->lng == 'heb' 
                                        ? "ההודעה שלך התקבלה ותועבר לצוות שלנו להמשך טיפול."
                                        : "Your message has been received and will be forwarded to our team for further handling.";
                                    sendClientWhatsappMessage($from, ['message' => $confirmationMessage]);
        
                                    // Clear the cache after the action is complete
                                    Cache::forget("user_{$client->id}_action");
                                }
                            }
        
                        }
                        // // Log older records
                        // $createdAt = $client->created_at;
                        // if ($createdAt && $createdAt->lt(now()->subHours(12))) {
                        //     \Log::info('Client record is older than 12 hours.', ['client_id' => $client->id]);
                        //     die('Client record is older than 12 hours.');
                        // }
                    }
                }
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

}
