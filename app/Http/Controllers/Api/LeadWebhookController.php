<?php

namespace App\Http\Controllers\Api;

use App\Enums\LeadStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\SettingKeyEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\ClientLeadStatusChanged;
use App\Events\SendClientLogin;
use App\Events\WhatsappNotificationEvent;
use App\Http\Controllers\Controller;
use App\Models\Fblead;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Job;
use App\Jobs\SendMeetingMailJob;
use App\Models\Offer;
use App\Models\WebhookResponse;
use App\Models\WhatsAppBotClientState;
use App\Models\WhatsappLastReply;
use App\Models\ClientPropertyAddress;
use App\Models\Notification;
use App\Models\Schedule;
use App\Models\Setting;
use App\Traits\ScheduleMeeting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class LeadWebhookController extends Controller
{
    use ScheduleMeeting;

    protected $botMessages = [
        'main-menu' => [
            'en' => "Hi, I'm Bar, the digital representative of Broom Service. How can I help you today? 😊\n\nAt any stage, you can return to the main menu by sending the number 9 or return one menu back by sending the number 0.\n\n1. About the Service\n2. Service Areas\n3. Set an appointment for a quote\n4. Customer Service\n5. Switch to a human representative (during business hours)\n7. שפה עברית",
            'heb' => 'היי, אני בר, הנציגה הדיגיטלית של ברום סרוויס. איך אוכל לעזור לך היום? 😊' . "\n\n" . 'בכל שלב תוכלו לחזור לתפריט הראשי ע"י שליחת המס 9 או לחזור תפריט אחד אחורה ע"י שליחת הספרה 0' . "\n\n" . '1. פרטים על השירות' . "\n" . '2. אזורי שירות' . "\n" . '3. קביעת פגישה לקבלת הצעת מחיר' . "\n" . '4. שירות ללקוחות קיימים' . "\n" . '5. מעבר לנציג אנושי (בשעות הפעילות)' . "\n" . '6. English menu'
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
            $m = $this->botMessages['main-menu']['en'];

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

        $data_returned = json_decode($get_data, true);

        if (
            isset($data_returned['messages']) &&
            isset($data_returned['messages'][0]['from_me']) &&
            $data_returned['messages'][0]['from_me'] == false
        ) {
            $message_data = $data_returned['messages'];
            $from = $message_data[0]['from'];
            Log::info($from);

            $response = WebhookResponse::create([
                'status'        => 1,
                'name'          => 'whatsapp',
                'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                'message'       => $data_returned['messages'][0]['text']['body'],
                'number'        => $from,
                'read'          => 0,
                'flex'          => 'C',
                'data'          => json_encode($get_data)
            ]);

            $lng = 'heb';
            if (strlen($from) > 10 && substr($from, 0, 3) != 972) {
                $lng = 'eng';
            }

            $client = null;
            if (strlen($from) > 10) {
                $client = Client::where('phone', 'like', '%' . substr($from, 2) . '%')->first();
            } else {
                $client = Client::where('phone', 'like', '%' . $from . '%')->first();
            }

            if (!$client) {
                $m = $this->botMessages['main-menu']['en'];
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

                WhatsAppBotClientState::updateOrCreate([
                    'client_id' => $lead->id,
                ], [
                    'menu_option' => 'main_menu',
                    'language' => $lng == 'heb' ? 'he' : 'en',
                ]);

                die('Template send to new client');
            }

            if (isset($data_returned) && isset($data_returned['messages']) && is_array($data_returned['messages'])) {
                $message = ($message_data[0]['type'] == 'text') ? $message_data[0]['text']['body'] : $message_data[0]['button']['text'];
                \Log::info($message);
                $result = WhatsappLastReply::where('phone', $from)
                    ->where('updated_at', '>=', Carbon::now()->subMinutes(15))
                    ->first();

                Log::info('Result details:', ['result' => $result]);

                $client_menus = WhatsAppBotClientState::where('client_id', $client->id)->first();

                // Send main menu is last menu state not found
                if (!$client_menus || $message == '9') {
                    $m = $this->botMessages['main-menu']['en'];
                    if ($client->lng == 'heb') {
                        $m = $this->botMessages['main-menu']['heb'];
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
                    WhatsAppBotClientState::updateOrCreate([
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
                \Log::info($last_menu);
                \Log::info("lastmeny");

                $prev_step = null;
                if (count($menu_option) >= 2) {
                    $prev_step = $menu_option[count($menu_option) - 2];
                }

                // Need more help
                if (
                    (
                        in_array($last_menu, ['need_more_help']) &&
                        (str_contains(strtolower($message), 'yes') || str_contains($message, 'כן'))
                    ) ||
                    (
                        ($prev_step == 'main_menu' || $prev_step == 'customer_service') &&
                        $message == '0'
                    )
                ) {
                    $m = $this->botMessages['main-menu']['en'];
                    if ($client->lng == 'heb') {
                        $m = $this->botMessages['main-menu']['heb'];
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
                    WhatsAppBotClientState::updateOrCreate([
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
                    WhatsAppBotClientState::where('client_id', $client->id)->delete();
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
                    WhatsAppBotClientState::updateOrCreate([
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
                    WhatsAppBotClientState::updateOrCreate([
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
                                'en' => "To receive a quote, please send us messages with the following details\n\nPlease send your full name",
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
                    WhatsAppBotClientState::where('client_id', $client->id)->delete();
                    $result = sendWhatsappMessage($from, array('message' => $msg));
                    die("Final message");
                }

                // Send appointment message 
                if (($last_menu == 'about_the_service' || $last_menu == 'service_areas') && $message == '3') {
                    $last_menu = 'main_menu';
                }

                if ($last_menu == 'human_representative') {
                    $msg = null;

                    if (
                        str_contains($message, 'Human Representative') ||
                        str_contains($message, 'נציג אנושי')
                    ) {
                        event(new WhatsappNotificationEvent([
                            "type" => WhatsappMessageTemplateEnum::LEAD_NEED_HUMAN_REPRESENTATIVE,
                            "notificationData" => [
                                'client' => $client->toArray()
                            ]
                        ]));

                        if ($client->lng == 'heb') {
                            $msg = 'נציג מטעמנו יצור קשר בהקדם.
                        האם יש משהו נוסף שאוכל לעזור לך בו היום? (כן או לא) 👋';
                        } else {
                            $msg = 'A representative from our team will contact you shortly. Is there anything else I can help you with today? (Yes or No) 👋';
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
                        WhatsAppBotClientState::updateOrCreate([
                            'client_id' => $client->id,
                        ], [
                            'menu_option' => 'main_menu->human_representative->need_more_help',
                            'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                        ]);
                    } else {
                        if ($client->lng == 'heb') {
                            $msg = 'נראה שהזנת קלט שגוי. אנא בדוק ונסה שוב.';
                        } else {
                            $msg = 'It looks like you\'ve entered an incorrect input. Please check and try again.';
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
                        WhatsAppBotClientState::updateOrCreate([
                            'client_id' => $client->id,
                        ], [
                            'menu_option' => 'main_menu->human_representative',
                            'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                        ]);
                    }
                    $result = sendWhatsappMessage($from, array('message' => $msg));
                    die("Human representative");
                }

                // Store lead full name
                if ($last_menu == 'full_name') {
                    $names = explode(' ', $message);
                    if (isset($names[0])) {
                        $client->firstname = trim($names[0]);
                    }
                    if (isset($names[1])) {
                        $client->lastname = trim($names[1]);
                    }
                    $client->save();
                    // $client->refresh();
                    $msg = null;
                    if ($client->lng == 'heb') {
                        $msg = 'כתובת מלאה (רחוב, מספר ועיר בלבד)';
                    } else {
                        $msg = "Please send your full address (Only street, number, and city)";
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

                    WhatsAppBotClientState::updateOrCreate([
                        'client_id' => $client->id,
                    ], [
                        'menu_option' => 'main_menu->appointment->full_address',
                        'language' =>  $client->lng == 'heb' ? 'he' : 'en',
                    ]);

                    die("Store full name");
                }

                if ($last_menu == 'full_address') {

                    $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                        'address' => $message,
                        'key' => config('services.google.map_key')
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
                            WhatsAppBotClientState::updateOrCreate([
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

                        WhatsAppBotClientState::updateOrCreate([
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

                        WhatsAppBotClientState::updateOrCreate([
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

                    WhatsAppBotClientState::updateOrCreate([
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

                    WhatsAppBotClientState::updateOrCreate([
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

                        WhatsAppBotClientState::updateOrCreate([
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

                        WhatsAppBotClientState::updateOrCreate([
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
                                    // 'start_date'    => $nextAvailableSlot['date'],
                                    // 'start_time_standard_format' => $nextAvailableSlot['start_time'],
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

                            WhatsAppBotClientState::updateOrCreate([
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

                                //   Send quotes link
                                // if ($last_menu == 'customer_menu' && $message == '1') {
                                //     if (isset($client_menus->auth_id)) {
                                //         $auth = Client::find($client_menus->auth_id);
                                //         $msg = null;
                                //         $link_data = [];
                                //         $offers = Offer::where('client_id', $auth->id)->get();
                                //         if (count($offers) > 0) {
                                //             foreach ($offers as $offer) {
                                //                 $link_data[] = base64_encode($offer->id);
                                //             }
                                //         }

                                //         if (count($link_data) > 0) {
                                //             $message = '';
                                //             $prefix = url('/') . '/price-offer/';
                                //             foreach ($link_data as $ld) {
                                //                 $msg .= $prefix . $ld . "\n";
                                //             }
                                //         }
                                //         $msg .= ($auth->lng == 'en' ? 'Is there anything else I can help you with today? 👋' : 'האם יש משהו נוסף שאוכל לעזור לך בו היום? 👋');
                                //         WebhookResponse::create([
                                //             'status'        => 1,
                                //             'name'          => 'whatsapp',
                                //             'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                                //             'message'       => $msg,
                                //             'number'        => $from,
                                //             'flex'          => 'A',
                                //             'read'          => 1,
                                //             'data'          => json_encode($get_data)
                                //         ]);
                                //         $result = sendWhatsappMessage($from, array('message' => $msg));
                                //         WhatsAppBotClientState::updateOrCreate([
                                //             'client_id' => $client->id,
                                //         ], [
                                //             'menu_option' => 'main_menu->customer_service->customer_menu->need_more_help',
                                //             'language' =>  $auth->lng == 'heb' ? 'he' : 'en',
                                //             'auth_id' => $auth->id,
                                //         ]);
                                //     }
                                //     die("Send quotes link");
                                // }

                                // // Send contracts link
                                // if ($last_menu == 'customer_menu' && $message == '2') {
                                //     if (isset($client_menus->auth_id)) {
                                //         $auth = Client::find($client_menus->auth_id);
                                //         $msg = null;
                                //         $link_data = [];

                                //         $contracts = Contract::where('client_id', $client->id)->get();
                                //         if (count($contracts) > 0) {
                                //             foreach ($contracts as $contract) {
                                //                 $link_data[] = ($contract->unique_hash);
                                //             }
                                //         }

                                //         if (count($link_data) > 0) {
                                //             $message = '';
                                //             $prefix = url('/') . '/work-contract/';
                                //             foreach ($link_data as $ld) {
                                //                 $msg .= $prefix . $ld . "\n";
                                //             }
                                //         }
                                //         $msg .= ($auth->lng == 'en' ? 'Is there anything else I can help you with today? 👋' : 'האם יש משהו נוסף שאוכל לעזור לך בו היום? 👋');
                                //         WebhookResponse::create([
                                //             'status'        => 1,
                                //             'name'          => 'whatsapp',
                                //             'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                                //             'message'       => $msg,
                                //             'number'        => $from,
                                //             'flex'          => 'A',
                                //             'read'          => 1,
                                //             'data'          => json_encode($get_data)
                                //         ]);
                                //         $result = sendWhatsappMessage($from, array('message' => $msg));
                                //         WhatsAppBotClientState::updateOrCreate([
                                //             'client_id' => $client->id,
                                //         ], [
                                //             'menu_option' => 'main_menu->customer_service->customer_menu->need_more_help',
                                //             'language' =>  $auth->lng == 'heb' ? 'he' : 'en',
                                //             'auth_id' => $auth->id,
                                //         ]);
                                //     }
                                //     die("Send contracts link");
                                // }

                                // // Send next job detail
                                // if ($last_menu == 'customer_menu' && $message == '3') {
                                //     if (isset($client_menus->auth_id)) {
                                //         $auth = Client::find($client_menus->auth_id);
                                //         $msg = null;
                                //         $job = Job::where('client_id', $client->id)->orderBy('start_date')->first();
                                //         if ($job) {
                                //             $msg .= "Your next job details is below: \n\n";
                                //             $msg .= "Date: " . ($job->next_start_date->format('Y-m-d') ?? '') . "\n";
                                //             $msg .= "Address: " . ($job->propertyAddress->address_name ?? '') . "\n";
                                //             $msg .= "Service: " . ($job->service->name ?? '') . "\n";
                                //             $msg .= "Worker: " . ($job->worker->firstname ?? '') . ' ' . ($job->worker->lastname ?? '')  . "\n";

                                //             if ($auth->lan == 'heb') {
                                //                 $msg .= "פרטי העבודה הבאה שלך מופיעים למטה: \n\n";
                                //                 $msg .= "תאריך: " . ($job->next_start_date->format('Y-m-d') ?? '') . "\n";
                                //                 $msg .= "כתובת: " . ($job->propertyAddress->address_name ?? '') . "\n";
                                //                 $msg .= "שירות: " . ($job->service->name ?? '') . "\n";
                                //                 $msg .= "עובד: " . ($job->worker->firstname ?? '') . ' ' . ($job->worker->lastname ?? '') . "\n";
                                //             }
                                //         }
                                //         $msg .= ($auth->lng == 'en' ? 'Is there anything else I can help you with today? 👋' : 'האם יש משהו נוסף שאוכל לעזור לך בו היום? 👋');

                                //         WebhookResponse::create([
                                //             'status'        => 1,
                                //             'name'          => 'whatsapp',
                                //             'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                                //             'message'       => $msg,
                                //             'number'        => $from,
                                //             'flex'          => 'A',
                                //             'read'          => 1,
                                //             'data'          => json_encode($get_data)
                                //         ]);
                                //         $result = sendWhatsappMessage($from, array('message' => $msg));
                                //         WhatsAppBotClientState::updateOrCreate([
                                //             'client_id' => $client->id,
                                //         ], [
                                //             'menu_option' => 'main_menu->customer_service->customer_menu->need_more_help',
                                //             'language' =>  $auth->lng == 'heb' ? 'he' : 'en',
                                //             'auth_id' => $auth->id,
                                //         ]);
                                //     }
                                //     die("Send next job detail");
                                // }

                                // // Cancel one time job
                                // if ($last_menu == 'customer_menu' && $message == '4') {
                                //     if (isset($client_menus->auth_id)) {
                                //         $auth = Client::find($client_menus->auth_id);
                                //         $msg = 'Dear customer, according to the terms of service, cancellation of the service may be subject to cancellation fees. Are you sure you want to cancel the service?';

                                //         if ($auth->lng == 'heb') {
                                //             $msg = 'לקוח יקר, בהתאם לתנאי השירות, על ביטול השירות עלולים לחול דמי ביטול. האם אתה בטוח שברצונך לבטל את השירות?';
                                //         }

                                //         WebhookResponse::create([
                                //             'status'        => 1,
                                //             'name'          => 'whatsapp',
                                //             'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                                //             'message'       => $msg,
                                //             'number'        => $from,
                                //             'flex'          => 'A',
                                //             'read'          => 1,
                                //             'data'          => json_encode($get_data)
                                //         ]);
                                //         $result = sendWhatsappMessage($from, array('message' => $msg));
                                //         WhatsAppBotClientState::updateOrCreate([
                                //             'client_id' => $client->id,
                                //         ], [
                                //             'menu_option' => 'main_menu->customer_service->customer_menu->cancel_one_time',
                                //             'language' =>  $auth->lng == 'heb' ? 'he' : 'en',
                                //             'auth_id' => $auth->id,
                                //         ]);
                                //     }
                                //     die("Cancel one time job");
                                // }

                                // // Terminate the agreement
                                // if ($last_menu == 'customer_menu' && $message == '5') {
                                //     if (isset($client_menus->auth_id)) {
                                //         $auth = Client::find($client_menus->auth_id);
                                //         $msg = "A representative from our team will contact you shortly. \nIs there anything else I can help you with today? 👋";

                                //         if ($auth->lng == 'heb') {
                                //             $msg = "נציג מהצוות שלנו ייצור איתך קשר בהקדם. \n האם יש משהו נוסף שאני יכול לעזור לך בו היום? 👋";
                                //         }

                                //         WebhookResponse::create([
                                //             'status'        => 1,
                                //             'name'          => 'whatsapp',
                                //             'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                                //             'message'       => $msg,
                                //             'number'        => $from,
                                //             'flex'          => 'A',
                                //             'read'          => 1,
                                //             'data'          => json_encode($get_data)
                                //         ]);
                                //         $result = sendWhatsappMessage($from, array('message' => $msg));
                                //         WhatsAppBotClientState::updateOrCreate([
                                //             'client_id' => $client->id,
                                //         ], [
                                //             'menu_option' => 'main_menu->customer_service->customer_menu->need_more_help',
                                //             'language' =>  $auth->lng == 'heb' ? 'he' : 'en',
                                //             'auth_id' => $auth->id,
                                //         ]);
                                //     }
                                //     die("Terminate the agreement");
                                // }

                                // // Contact a representative
                                // if ($last_menu == 'customer_menu' && $message == '6') {
                                //     if (isset($client_menus->auth_id)) {
                                //         $auth = Client::find($client_menus->auth_id);
                                //         $msg = "Who would you like to speak to? \n 1. Office manager and scheduling \n 2. Customer service \n 3. Accounting and billing";

                                //         if ($auth->lng == 'heb') {
                                //             $msg = "עם מי תרצה לדבר? \n 1. מנהל משרד ותזמון \n 2. שירות לקוחות \n 3. הנהלת חשבונות וחיוב";
                                //         }

                                //         WebhookResponse::create([
                                //             'status'        => 1,
                                //             'name'          => 'whatsapp',
                                //             'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                                //             'message'       => $msg,
                                //             'number'        => $from,
                                //             'flex'          => 'A',
                                //             'read'          => 1,
                                //             'data'          => json_encode($get_data)
                                //         ]);
                                //         $result = sendWhatsappMessage($from, array('message' => $msg));
                                //         WhatsAppBotClientState::updateOrCreate([
                                //             'client_id' => $client->id,
                                //         ], [
                                //             'menu_option' => 'main_menu->customer_service->customer_menu->contact_a_representative',
                                //             'language' =>  $auth->lng == 'heb' ? 'he' : 'en',
                                //             'auth_id' => $auth->id,
                                //         ]);
                                //     }
                                //     die("Contact a representative");
                                // }

                                // // Contact a representative menu
                                // if ($last_menu == 'contact_a_representative' && in_array($message, ['1', '2', '3'])) {
                                //     if (isset($client_menus->auth_id)) {
                                //         $auth = Client::find($client_menus->auth_id);
                                //         $msg = null;
                                //         if ($client->lng == 'heb') {
                                //             $msg = 'נציג מטעמנו יצור עמכם קשר בהקדם כדי לתאם פגישה.
                                //         האם יש משהו נוסף שאוכל לעזור לך בו היום? 👋';
                                //         } else {
                                //             $msg = 'A representative from our team will contact you shortly to schedule an appointment. Is there anything else I can help you with today? 👋';
                                //         }

                                //         WebhookResponse::create([
                                //             'status'        => 1,
                                //             'name'          => 'whatsapp',
                                //             'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                                //             'message'       => $msg,
                                //             'number'        => $from,
                                //             'flex'          => 'A',
                                //             'read'          => 1,
                                //             'data'          => json_encode($get_data)
                                //         ]);
                                //         $result = sendWhatsappMessage($from, array('message' => $msg));
                                //         WhatsAppBotClientState::updateOrCreate([
                                //             'client_id' => $client->id,
                                //         ], [
                                //             'menu_option' => 'main_menu->customer_service->customer_menu->need_more_help',
                                //             'language' =>  $auth->lng == 'heb' ? 'he' : 'en',
                                //             'auth_id' => $auth->id,
                                //         ]);
                                //     }
                                //     die("Contact a representative menu");
                                // }

                                // // Send customer service menu
                                // if (($message == 0 && ($prev_step == 'customer_service' || $prev_step == 'customer_menu'))) {
                                //     if (isset($client_menus->auth_id)) {
                                //         $auth = Client::find($client_menus->auth_id);
                                //         $msg = "1. View your quotes \n2. View your contracts \n3. When is my next service? \n4. Cancel a one-time service \n5. Terminate the agreement \n6. Contact a representative";
                                //         if ($auth->lng == 'heb') {
                                //             $msg = "1. הצג את הציטוטים שלך \n2. הצג את החוזים שלך \n3. מתי השירות הבא שלי? \n4. בטל שירות חד פעמי \n5. סיים את ההסכם \n6. פנה לנציג";
                                //         }

                                //         WhatsAppBotClientState::updateOrCreate([
                                //             'client_id' => $client->id,
                                //         ], [
                                //             'menu_option' => 'main_menu->customer_service->customer_menu',
                                //             'language' =>  $auth->lng == 'heb' ? 'he' : 'en',
                                //             'auth_id' => $auth->id,
                                //         ]);
                                //         WebhookResponse::create([
                                //             'status'        => 1,
                                //             'name'          => 'whatsapp',
                                //             'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                                //             'message'       => $msg,
                                //             'number'        => $from,
                                //             'flex'          => 'A',
                                //             'read'          => 1,
                                //             'data'          => json_encode($get_data)
                                //         ]);
                                //         $result = sendWhatsappMessage($from, array('message' => $msg));
                                //     }
                                //     die("Send customer service menu");
                                // }

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
                        // $msg = "1. View your quotes \n2. View your contracts \n3. When is my next service? \n4. Cancel a one-time service \n5. Terminate the agreement \n6. Contact a representative";
                        // if ($auth->lng == 'heb') {
                        //     $msg = "1. הצג את הציטוטים שלך \n2. הצג את החוזים שלך \n3. מתי השירות הבא שלי? \n4. בטל שירות חד פעמי \n5. סיים את ההסכם \n6. פנה לנציג";
                        // }

                        event(new SendClientLogin($auth->toArray()));

                        $msg = "An email sent to the registered account with login details to our portal.";
                        if ($auth->lng == 'heb') {
                            $msg = "נשלחה הודעת דוא\"ל לחשבון הרשום עם פרטי הכניסה לפורטל שלנו.";
                        }

                        WhatsAppBotClientState::updateOrCreate([
                            'client_id' => $client->id,
                        ], [
                            // 'menu_option' => 'main_menu->customer_service->customer_menu',
                            'menu_option' => 'main_menu->customer_service->need_more_help',
                            'language' =>  $auth->lng == 'heb' ? 'he' : 'en',
                            'auth_id' => $auth->id,
                        ]);
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

                    die("Send service menu");
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
                                'menu_option' => 'main_menu->appointment->full_name',
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
                    Log::info('Send message: ' . $menus[$last_menu][$message]['title']);
                    die("Language switched to english");
                }

                // if answer not fit script and options
                $msg = "Sorry, I didn't understand your message. Could you please check and make sure you answered correctly?";

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
        $name = explode(' ', $request->name);

        $lead->firstname = $name[0];
        $lead->lastname = (isset($name[1])) ? $name[1] : '';
        $lead->phone = $request->phone;
        $lead->email = $request->email;
        $lead->status = 0;
        $lead->lng = 'en';
        $lead->password = Hash::make($request->phone);
        $lead->passcode = $request->phone;
        $lead->save();

        if (!$lead_exists) {
            $lead->lead_status()->updateOrCreate(
                [],
                ['lead_status' => LeadStatusEnum::PENDING]
            );
        }

        $m = $this->botMessages['main-menu']['en'];

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
}
