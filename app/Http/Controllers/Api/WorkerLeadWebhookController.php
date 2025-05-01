<?php

namespace App\Http\Controllers\Api;

use App\Enums\LeadStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\JobStatusEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\WhatsappNotificationEvent;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Client;
use App\Models\Job;
use App\Jobs\SendMeetingMailJob;
use App\Models\Offer;
use App\Models\WorkerWebhookResponse;
use App\Models\WhatsAppBotWorkerState;
use App\Models\Notification;
use App\Models\WorkerMetas;
use App\Models\WorkerLeads;
use App\Models\ScheduleChange;
use App\Models\ManpowerCompany;
use App\Models\WhatsAppBotActiveWorkerState;
use App\Models\WorkerInvitation;
use App\Models\WorkerAvailability;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Twilio\Rest\Client as TwilioClient;



class WorkerLeadWebhookController extends Controller
{
    protected $twilioAccountSid;
    protected $twilioAuthToken;
    protected $twilioWhatsappNumber;
    protected $twilio;

    protected $botMessages = [
        'step0' => [
            'en' => "🌟 Thank you for contacting Job4Service! 🌟\n\nWe are hiring house cleaning professionals for part-time and full-time positions in the Tel Aviv area.\n\n✅ To apply, you must have one of the following:\n- Israeli ID\n- B1 Work Visa\n- Refugee (blue) visa\n\nPlease answer these two questions to proceed:\n1. Do you have experience in house cleaning?\n(Please reply with 'Yes' or 'No')",
            'ru' => "🌟 Спасибо, что связались с Job4Service! 🌟\n\nМы ищем сотрудников для уборки домов на полную и частичную занятость в районе Тель-Авива.\n✅ Для подачи заявки у вас должен быть один из следующих документов:\n- Израильское удостоверение личности\n- Рабочая виза B1\n- Статус беженца (синяя виза)\n\nОтветьте, пожалуйста, на два вопроса:\n1. У вас есть опыт работы по уборке домов?\n(Пожалуйста, ответьте \"Да\" или \"Нет\" на каждый вопрос.)",
       ],
        'step1' => [
            'en' => "We didn’t quite understand your answer.\n\n✅ Please respond clearly with:\n\n1. \"Yes\" or \"No\" – Do you have experience in house cleaning?\n\nLet’s continue when you’re ready! 😊",
            'ru' => "Мы не совсем поняли ваш ответ.\n\n✅ Пожалуйста, ответьте четко:\n\n1. \"Да\" или \"Нет\" – Есть ли у вас опыт работы по уборке?\n\nПродолжим, как только вы будете готовы! 😊  ",
        ],
        'step2' => [
            'en' => "2. Do you have a valid visa or ID as mentioned above?\n(Please reply with 'Yes' or 'No')",
            'ru' => "2. У вас есть действующая рабочая виза или удостоверение личности?\n(Пожалуйста, ответьте \"Да\" или \"Нет\" на каждый вопрос.)",
        ],
        'step3' => [
            'en' => "We didn’t quite understand your answer.\n\n✅ Please respond clearly with:\n\n2. \"Yes\" or \"No\" – Do you have a valid work visa (Israeli ID, B1 visa, or refugee visa)?\n\nLet’s continue when you’re ready! 😊",
            'ru' => "Мы не совсем поняли ваш ответ.\n\n✅ Пожалуйста, ответьте четко:\n\n2. \"Да\" или \"Нет\" – Есть ли у вас действующая рабочая виза (израильское удостоверение, виза B1 или статус беженца)?\n\nПродолжим, как только вы будете готовы! 😊",
        ],
    ];

    protected $activeWorkersbotMessages = [
        'main_menu' => [
            'en' => "Hi, :worker_name!\nWelcome to Gali, the Broom Service digital assistant bot.\nHow can I assist you today? 🌟\n\n1️⃣ Talk to a manager urgently.\n2️⃣ Change my work schedule.\n3️⃣ What's my schedule for today and tomorrow?\n4️⃣ Access the employee portal.\n\nAt any time, you can return to the main menu by typing 'Menu'.\nPlease reply with the number of your choice.",
            'heb' => "היי, :worker_name!\nברוך הבא לגלי, הבוט הדיגיטלי של ברום סרוויס.\nאיך אפשר לעזור לך היום? 🌟\n\n1️⃣ לדבר עם מנהל בדחיפות.\n2️⃣ שינוי סידור העבודה שלי.\n3️⃣ מה הלוז שלי להיום ולמחר?\n4️⃣ גישה לפורטל העובדים שלנו.\n\nבכל שלב ניתן לחזור לתפריט הראשי על ידי הקלדת 'תפריט'.\nנא להשיב עם המספר המתאים.",
            'ru' => "Привет, :worker_name!\nДобро пожаловать в Гали, цифровой бот Broom Service.\nЧем могу помочь вам сегодня? 🌟\n\n1️⃣ Срочно связаться с менеджером.\n2️⃣ Изменить мой график работы.\n3️⃣ Какое у меня расписание на сегодня и завтра?\n4️⃣ Доступ к порталу сотрудников.\n\nНа любом этапе вы можете вернуться в главное меню, отправив сообщение 'меню'.\nПожалуйста, ответьте номером вашего выбора.",
            'spa' => "Hola, :worker_name!\nBienvenido a Gali, el bot asistente digital de Broom Service.\n¿Cómo puedo ayudarte hoy? 🌟\n\n1️⃣ Habla con un gerente urgentemente.\n2️⃣ Cambia mi horario de trabajo.\n3️⃣ ¿Cuál es mi horario para hoy y mañana?\n4️⃣ Accede al portal de empleados.\n\nEn cualquier momento, puedes regresar al menú principal escribiendo 'Menú'.\nResponde con el número de tu elección.",
        ],
        'talk_to_manager' => [
            
            'en' => "Please tell us the reason for contacting a manager. Your request will be forwarded to the relevant team.\nAt any time, you can return to the main menu by typing 'Menu'.",
            'heb' => "אנא פרט את הסיבה שבגללה תרצה לדבר עם מנהל. הבקשה שלך תועבר לצוות הרלוונטי.\nבכל שלב ניתן לחזור לתפריט הראשי על ידי הקלדת 'תפריט'.",
            'ru' => "Пожалуйста, укажите причину, по которой вы хотите связаться с менеджером. Ваш запрос будет передан соответствующей команде.\nНа любом этапе вы можете вернуться в главное меню, отправив сообщение 'меню'.",
            'spa' => "Por favor, indica la razón de la llamada. Tu solicitud se enviará a la equipo relevante.\nEn cualquier momento, puedes regresar al menú principal escribiendo 'Menú'.",
        ],
        'comment' => [
            'en' => "Hello :worker_name,\nWe received your message:\n\n':message'\n\nYour request has been forwarded to the relevant manager for further handling.",
            'heb' => "שלום :worker_name,\nקיבלנו את ההודעה שלך:\n\n':message'\n\nהבקשה שלך הועברה למנהל הרלוונטי להמשך טיפול.",
            'ru' => "Здравствуйте, :worker_name,\nМы получили ваше сообщение:\n\n':message'\n\nВаш запрос передан соответствующему менеджеру для дальнейшей обработки.",
            'spa' => "Hola, :worker_name,\nRecibimos tu mensaje:\n\n':message'\n\nTu solicitud ha sido enviada al gerente relevante para su posterior tratamiento.",
        ],
        'team_comment' => [
            'en' => "🚨 :worker_name requested to speak to a manager urgently. \nReason: :message. \nPlease contact them immediately.",
        ],
        'change_schedule' => [
            'en' => "Please share the changes you'd like to make to your schedule. We will review your request and get back to you.\nAt any time, you can return to the main menu by typing 'Menu'.",
            'heb' => "אנא עדכן אותנו על השינויים שתרצה לבצע בסידור העבודה שלך. נבדוק את הבקשה ונחזור אליך.\nבכל שלב ניתן לחזור לתפריט הראשי על ידי הקלדת 'תפריט'.",
            'ru' => "Пожалуйста, сообщите нам об изменениях, которые вы хотите внести в свой график работы. Мы проверим ваш запрос и свяжемся с вами.\nНа любом этапе вы можете вернуться в главное меню, отправив сообщение 'меню'.",
            'spa' => "Indique los cambios que desea realizar en su agenda. Revisaremos su solicitud y nos comunicaremos con usted. En cualquier momento, puede regresar al menú principal escribiendo 'Menú'."
        ],
        'team_schedule_change' => [
            'en' => ":worker_name requested a schedule change: :message. \nPlease review and handle accordingly..",
        ],
        'change_schedule_comment' => [
            'en' => "We received your request for schedule changes.\nHere’s your request:\n':message'\nYour request has been forwarded to our team for review and action.",
            'heb' => "קיבלנו את בקשתך לשינויים בסידור העבודה.\nלהלן הבקשה שלך:\n':message'\nהבקשה הועברה לצוות שלנו לבדיקה וטיפול.",
            'ru' => "Мы получили ваш запрос на изменение графика.\nВот ваш запрос:\n':message'\nВаш запрос передан нашей команде для проверки и обработки.",
            'spa' => "Hemos recibido tu solicitud de cambios en el horario.\nAquí está tu solicitud:\n':message'\nTu solicitud ha sido enviada a nuestro equipo para su revisión y acción.",
        ],
        'sorry' => [
            'en' => "I'm sorry, I didn’t understand your response.\n• Reply with a number from the menu options.\n• Type 'menu' to return to the main menu.",
            'heb' => "מצטערים, לא הבנו את תשובתך.\n• אנא השב עם מספר מאחת האפשרויות בתפריט.\n• הקלד 'תפריט' כדי לחזור לתפריט הראשי",
            'ru' => "Извините, я вас не понял.\n• Ответьте номером из вариантов меню.\n• Напишите 'меню', чтобы вернуться в главное меню",
            'spa' => "Lo siento, no entendí tu respuesta.\n• Responde con un número de las opciones del menú.\n• Escribe 'menú' para volver al menú principal.",
        ],
        'access_employee_portal' => [
            'en' => "Here is the link to the employee portal: 🌐\n:link\nLog in with your credentials to access your account and details.\nAt any time, you can return to the main menu by typing 'Menu'.",
            'heb' => "הנה הקישור לפורטל העובדים: 🌐\n:link\nהיכנס עם הפרטים שלך כדי לגשת לחשבונך.\nבכל שלב ניתן לחזור לתפריט הראשי על ידי הקלדת 'תפריט'.",
            'ru' => "Вот ссылка на портал сотрудников: 🌐\n:link\nВойдите с помощью своих учетных данных, чтобы получить доступ к своему аккаунту и деталям.\nНа любом этапе вы можете вернуться в главное меню, отправив сообщение 'меню'.",
            'spa' => "Aquí está el enlace al portal de empleados: 🌐\n:link\nInicia sesión con tus credenciales para acceder a tu cuenta y detalles.\nEn cualquier momento, puedes volver al menú principal escribiendo 'Menú'.",
        ],
        'today_and_tomorrow_schedule' => [
            'en' => "Your schedule is as follows:\nToday: :today_schedule\nTomorrow: :tomorrow_schedule\n\nAt any time, you can return to the main menu by typing 'Menu'.",
            'heb' => "סידור העבודה שלך הוא:\nהיום: :today_schedule\nמחר: :tomorrow_schedule\n\nבכל שלב ניתן לחזור לתפריט הראשי על ידי הקלדת 'תפריט'.",
            'ru' => "Ваш график следующий:\nСегодня: :today_schedule\nЗавтра: :tomorrow_schedule\n\nНа любом этапе вы можете вернуться в главное меню, отправив сообщение 'меню'.",
            'spa' => "Tu horario es el siguiente:\nHoy: :today_schedule\nMañana: :tomorrow_schedule\n\nEn cualquier momento, puedes volver al menú principal escribiendo 'Menú'.",
        ],
        'attempts' => [
            "en" => "We couldn’t verify your request. Please contact the team directly for assistance.",
            "heb" => "לא הצלחנו לאמת את בקשתך. אנא צור קשר עם הצוות ישירות לעזרה.",
            "ru" => "Мы не смогли обработать ваш запрос. Пожалуйста, свяжитесь с командой напрямую для помощи.",
        ],
        "team_attempts" => [
            "en" => ":worker_name failed to complete their request. Please reach out to them.",
            "heb" => ":worker_name לא השלים את בקשתו. נא ליצור קשר עמו.",
            "ru" => ":worker_name не смог обработать свою заявку. Пожалуйста, свяжитесь с ним.",
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


    public function fbWebhookCurrentLive(Request $request)
    {
        $data = $request->all();
        \Log::info($data);
        $messageId = $data['SmsMessageSid'] ?? null;
        $lng = "en";
        
        if (!$messageId) {
            \Log::info('Invalid message data');
            return response()->json(['status' => 'Invalid message data'], 400);
        }

        // Check if the messageId exists in cache and matches
        if (Cache::get('worker_processed_message_' . $messageId) === $messageId) {
            \Log::info('Already processed');
            return response()->json(['status' => 'Already processed'], 200);
        }

        // Store the messageId in the cache for 1 hour
        Cache::put('worker_processed_message_' . $messageId, $messageId, now()->addHours(1));

        if ($data['SmsStatus'] == 'received') {

            $from =  Str::replace('whatsapp:+', '', $data['From']) ?? null;
            $input = $data['Body'] ? trim($data['Body']) : "";
            $listId = $data['ListId'] ?? $input;
            $ButtonPayload = $data['ButtonPayload'] ?? $listId;

            $status = $data['SmsStatus'] ?? null;
            
            $lng = $this->detectLanguage($input);
            $currentStep = 0;

            WorkerWebhookResponse::create([
                'status' => 1,
                'name' => 'whatsapp',
                'entry_id' => $messageId,
                'message' => $input,
                'number' => $from,
                'read' => 0,
                'flex' => 'W',
                'data' => json_encode($data)
            ]);

            $workerLead = WorkerLeads::where('phone', $from)->first();
            $user = User::where('phone', $from)
                    ->where('status', 1)
                    ->first();
            $client = Client::where('phone', $from)->first();

            if($client){
                \Log::info('client already exist ...'. $client->id);
                die("client already exist");
            }

            if($user){
                \Log::info('user already exist ...');
                die("user already exist");
            }

            if (!$workerLead) {
                $workerLead = WorkerLeads::create([
                    'phone' => $from,
                    'lng' => $lng
                ]);
                WhatsAppBotWorkerState::updateOrCreate(
                    ['worker_lead_id' => $workerLead->id],
                    ['step' => 0, 'language' => $lng]
                );

                $sid = $workerLead->lng == 'en' ? 'HX868e85a56d9f6af3fa9cb46c47370e49' : 'HXd0f88505bf55200b5b0db725e40a6331';

                $twi = $this->twilio->messages->create(
                    "whatsapp:+$from",
                    [
                        "from" => $this->twilioWhatsappNumber, 
                        "contentSid" => $sid, 
                        
                    ]
                );
                \Log::info("twilio response". $twi->sid);

                // Send the step0 message
                $initialMessage = $this->botMessages['step0'][$lng];
                // Save the admin message for step0
                WorkerWebhookResponse::create([
                    'status' => 1,
                    'name' => 'whatsapp',
                    'message' => $initialMessage,
                    'number' => $from,
                    'read' => 1,
                    'flex' => 'A',
                ]);
                return;
            }

            $workerState = WhatsAppBotWorkerState::where("worker_lead_id", $workerLead->id)->first();

            if ($workerState && $workerState->step == 4) {
                // Conversation is complete, no further processing
                return response()->json(['status' => 'Conversation complete'], 200);
            }

            if (in_array($input, [1, 2])) {
                $languageMap = [1 => 'en', 2 => 'ru'];
                $lng = $languageMap[$input];

                WhatsAppBotWorkerState::updateOrCreate(
                    ['worker_lead_id' => $workerLead->id],
                    ['step' => 0, 'language' => $lng]
                );
                WorkerLeads::updateOrCreate(
                    ['id' => $workerLead->id],
                    ['lng' => $lng]
                );

                $sid = $workerLead->lng == 'en' ? 'HX868e85a56d9f6af3fa9cb46c47370e49' : 'HXd0f88505bf55200b5b0db725e40a6331';

                $twi = $this->twilio->messages->create(
                    "whatsapp:+$from",
                    [
                        "from" => $this->twilioWhatsappNumber, 
                        "contentSid" => $sid, 
                        
                    ]
                );
                \Log::info("twilio response". $twi->sid);

                $switchMessage = $this->botMessages['step0'][$lng];

                WorkerWebhookResponse::create([
                    'status' => 1,
                    'name' => 'whatsapp',
                    'message' => $switchMessage,
                    'number' => $from,
                    'read' => 1,
                    'flex' => 'A',
                ]);

                return;
            }else{
                // Process user response based on current step
                $currentStep = $workerState->step;
                $nextMessage = $this->processWorkerResponse($workerLead, $ButtonPayload, $currentStep, $workerState);

                if ($nextMessage) {
                    $result = sendWorkerWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);
                    // Save admin message for next step
                    WorkerWebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $nextMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);
                }
            }
        }
    }

    // public function createUser($workerLead){
    //     $firstname = explode(" ", $workerLead->name)[0];
    //     $worker = User::create([
    //         'firstname'     => $request->firstname,
    //         'lastname'      => ($request->lastname) ? $request->lastname : '',
    //         'phone'         => $request->phone,
    //         'email'         => null,
    //         'role'          => $role,
    //         'lng'           => $request->lng,
    //         'passcode'      => $request->password,
    //         'password'      => Hash::make($request->password),
    //         'company_type'  => $request->company_type,
    //         'status'        => $request->status,
    //         'manpower_company_id'       => $request->company_type == "manpower"
    //             ? $request->manpower_company_id
    //             : NULL,
    //         'step' => 0
    //     ]);
    // }

    public function hebdetectLanguage($text)
    {
        // Regex for hebrew
        if (preg_match('/[\x{0590}-\x{05FF}]/u', $text)) {
            return 'heb';
        } else {
            return 'en';
        }
    }


    // public function fbActiveWorkersWebhookCurrentLive(Request $request)
    // {
    //     $get_data = $request->getContent();
    //     $data_returned = json_decode($get_data, true);
    //     $messageId = $data_returned['messages'][0]['id'] ?? null;
    //     $lng = "en";

    //     if (!$messageId) {
    //         return response()->json(['status' => 'Invalid message data'], 400);
    //     }

    //     // Check if the messageId exists in cache and matches
    //     if (Cache::get('active_worker_processed_message_' . $messageId) === $messageId) {
    //         \Log::info('Already processed');
    //         return response()->json(['status' => 'Already processed'], 200);
    //     }

    //     // Store the messageId in the cache for 1 hour
    //     Cache::put('active_worker_processed_message_' . $messageId, $messageId, now()->addHours(1));

    //     if (
    //         isset($data_returned['messages']) &&
    //         isset($data_returned['messages'][0]['from_me']) &&
    //         $data_returned['messages'][0]['from_me'] == false
    //     ) {
    //         $message_data = $data_returned['messages'];
    //         if (Str::endsWith($message_data[0]['chat_id'], '@g.us')) {
    //             die("Group message");
    //         }
    //         $from = $message_data[0]['from'];
    //         $input = trim($data_returned['messages'][0]['text']['body'] ?? '');
    //         $lng = "heb";

    //         WorkerWebhookResponse::create([
    //             'status' => 1,
    //             'name' => 'whatsapp',
    //             'entry_id' => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
    //             'message' => $data_returned['messages'][0]['text']['body'] ?? '',
    //             'number' => $from,
    //             'read' => 0,
    //             'flex' => 'W',
    //             'data' => json_encode($get_data)
    //         ]);

    //         $user = User::where('phone', $from)
    //                 ->where('status', 1)
    //                 ->first();
    //         if ($user) {
    //             \Log::info('User found activeWorker: ' . $user);
    //         }

    //         if ($user && $user->stop_last_message == 1) {
    //             $lng = $user->lng;
    //             $last_menu = null;
    //             $send_menu = null;
    //             $activeWorkerBot = WhatsAppBotActiveWorkerState::where('worker_id', $user->id)->first();

    //             if($activeWorkerBot){
    //                 $menu_option = explode('->', $activeWorkerBot->menu_option);
    //                 $last_menu = end($menu_option);
    //             }

    //             $cacheKey = 'send_menu_sorry_count_' . $from;

    //             // Initialize the cache if not already set
    //             if (!Cache::has($cacheKey)) {
    //                 Cache::put($cacheKey, 0, now()->addHours(24));
    //             }

    //             if(empty($last_menu) || in_array(strtolower($input), ["menu", "меню", "תפריט", "menú"])) {
    //                 $send_menu = 'main_menu';
    //             } else if ($last_menu == 'main_menu' && $input == '1') {
    //                 $send_menu = 'talk_to_manager';
    //             } else if ($last_menu == 'talk_to_manager' && !empty($input)) {
    //                 $send_menu = 'comment';
    //             } else if ($last_menu == 'main_menu' && $input == '2') {
    //                 $send_menu = 'change_schedule';
    //             } else if ($last_menu == 'change_schedule' && !empty($input)) {
    //                 $send_menu = 'change_schedule_comment';
    //             } else if ($last_menu == 'main_menu' && $input == '3') {
    //                 $send_menu = 'today_and_tomorrow_schedule';
    //             } else if ($last_menu == 'main_menu' && $input == '4') {
    //                 $send_menu = 'access_employee_portal';
    //             } else {
    //                 // Handle 'sorry' case
    //                 $send_menu = 'sorry';
    //                 $sorryCount = Cache::increment($cacheKey);
    //                 if ($sorryCount > 4) {
    //                     Cache::put($cacheKey, 0, now()->addHours(24)); // Reset to 0 and keep the cache expiration
    //                     $send_menu = 'attempts_exceeded'; // Handle as 'attempts_exceeded'
    //                 } elseif ($sorryCount == 4) {
    //                     $send_menu = 'attempts_exceeded';
    //                 }
    //             }

    //             switch ($send_menu) {
    //                 case 'main_menu':
    //                     $initialMessage = $this->activeWorkersbotMessages['main_menu'][$lng];
    //                     WhatsAppBotActiveWorkerState::updateOrCreate(
    //                         ["worker_id" => $user->id],
    //                         [
    //                             'menu_option' => 'main_menu',
    //                             'lng' => $lng,
    //                         ]
    //                     );
    //                     // Replace :worker_name with the user's firstname and lastname
    //                     $workerName = "*".(($user->firstname ?? ''). ' ' . ($user->lastname ?? ''))."*";
    //                     $personalizedMessage = str_replace(':worker_name', $workerName, $initialMessage);
    //                     sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

    //                     WorkerWebhookResponse::create([
    //                         'status' => 1,
    //                         'name' => 'whatsapp',
    //                         'message' => $personalizedMessage,
    //                         'number' => $from,
    //                         'read' => 1,
    //                         'flex' => 'A',
    //                     ]);
    //                     break;

    //                 case 'talk_to_manager':
    //                     $nextMessage = $this->activeWorkersbotMessages['talk_to_manager'][$lng];
    //                     sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

    //                     $activeWorkerBot->update(['menu_option' => 'main_menu->talk_to_manager', 'lng' => $lng]);

    //                     WorkerWebhookResponse::create([
    //                         'status' => 1,
    //                         'name' => 'whatsapp',
    //                         'message' => $nextMessage,
    //                         'number' => $from,
    //                         'read' => 1,
    //                         'flex' => 'A',
    //                     ]);
    //                     break;

    //                 case 'comment':
    //                     $nextMessage = $this->activeWorkersbotMessages['comment'][$lng];
    //                     $workerName = (($user->firstname ?? ''). ' ' . ($user->lastname ?? ''));
    //                     $personalizedMessage = str_replace([':worker_name', ':message'], [$workerName, $input], $nextMessage);
    //                     sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

    //                     $nextMessage = $this->activeWorkersbotMessages['team_comment']["en"];
    //                     $personalizedMessage = str_replace([':worker_name', ':message'], [$workerName, $input], $nextMessage);
    //                     sendTeamWhatsappMessage(config('services.whatsapp_groups.problem_with_workers'), ['name' => '', 'message' => $personalizedMessage]);
    //                     $activeWorkerBot->delete();
    //                     break;

    //                 case 'change_schedule':
    //                     $nextMessage = $this->activeWorkersbotMessages['change_schedule'][$lng];
    //                     sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

    //                     $activeWorkerBot->update(['menu_option' => 'main_menu->change_schedule', 'lng' => $lng]);

    //                     WorkerWebhookResponse::create([
    //                         'status' => 1,
    //                         'name' => 'whatsapp',
    //                         'message' => $nextMessage,
    //                         'number' => $from,
    //                         'read' => 1,
    //                         'flex' => 'A',
    //                     ]);
    //                     break;

    //                 case 'change_schedule_comment':
    //                     if ($lng == 'heb') {
    //                         $reason = "שנה לוח זמנים";
    //                     }else if($lng == 'spa'){
    //                         $reason = "Cambiar horario";
    //                     }else if($lng == 'ru'){
    //                         $reason = "Изменить расписание";
    //                     }else{
    //                         $reason = "Change Schedule";
    //                     }
    //                     $scheduleChange = new ScheduleChange();
    //                     $scheduleChange->user_type = get_class($user);
    //                     $scheduleChange->user_id = $user->id;
    //                     $scheduleChange->reason = $reason;
    //                     $scheduleChange->comments = $input;
    //                     $scheduleChange->save();

    //                     $nextMessage = $this->activeWorkersbotMessages['team_schedule_change']["en"];
    //                     $workerName = (($user->firstname ?? ''). ' ' . ($user->lastname ?? ''));
    //                     $personalizedMessage = str_replace([':worker_name', ':message'], [$workerName, $input], $nextMessage);
    //                     sendTeamWhatsappMessage(config('services.whatsapp_groups.workers_availability'), ['name' => '', 'message' => $personalizedMessage]);

    //                     $message = $this->activeWorkersbotMessages['change_schedule_comment'][$lng];
    //                     $message = str_replace([':message'], [$input], $message);
    //                     sendClientWhatsappMessage($from, array('message' => $message));
    //                     $activeWorkerBot->delete();
    //                     break;

    //                 case 'access_employee_portal':
    //                     $nextMessage = $this->activeWorkersbotMessages['access_employee_portal'][$lng];
    //                     $personalizedMessage = str_replace(':link', generateShortUrl(url("worker/login"), 'worker'), $nextMessage);
    //                     sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);
    //                     $activeWorkerBot->delete();
    //                     break;

    //                 case 'sorry':
    //                     $message = $this->activeWorkersbotMessages['sorry'][$lng];
    //                     sendClientWhatsappMessage($from, array('message' => $message));
    //                     break;

    //                 case 'today_and_tomorrow_schedule':
    //                     $nextMessage = $this->activeWorkersbotMessages['today_and_tomorrow_schedule'][$lng];
    //                     $todayJobs = Job::where('worker_id', $user->id)
    //                     ->whereNotIn('status', [JobStatusEnum::COMPLETED, JobStatusEnum::CANCEL])
    //                     ->whereDate('start_date', now())
    //                     ->get();

    //                     $tomorrowJobs = Job::where('worker_id', $user->id)
    //                     ->whereNotIn('status', [JobStatusEnum::COMPLETED, JobStatusEnum::CANCEL])
    //                     ->whereDate('start_date', now()->addDay(1))
    //                     ->get();

    //                     $todaySchedule = "";
    //                     $tomorrowSchedule = "";
    //                     if ($todayJobs && $todayJobs->count() > 0) {
    //                         foreach ($todayJobs as $job) {
    //                             Carbon::setLocale($lng == 'en' ? 'en' : 'he');
    //                             $day = Carbon::parse($job->start_date)->translatedFormat('l'); // Use translatedFormat for localized day
    //                             if($job->service) {
    //                                 $todaySchedule .= $job->service->name . ', ';
    //                             }
    //                             $todaySchedule .=  $day . ' - ' . $job->start_time . ' ' . $job->end_time . ", ";
    //                             if($job->propertyAddress) {
    //                                 $todaySchedule .= $job->propertyAddress->geo_address . ', ';
    //                             }
    //                             if($job->client) {
    //                                 $todaySchedule .= $job->client->firstname . ' ' . $job->client->lastname;
    //                             }
    //                             $todaySchedule .= "\n";
    //                         }
    //                     }else{
    //                         if ($lng == 'heb') {
    //                             $reason = "לא מתוכננות משרות היום";
    //                         }else if($lng == 'spa'){
    //                             $reason = "No hay trabajos programados para hoy";
    //                         }else if($lng == 'ru'){
    //                             $reason = "Сегодня нет запланированных работ";
    //                         }else{
    //                             $reason = "No today jobs scheduled";
    //                         }
    //                         $todaySchedule = $reason;
    //                     }

    //                     if ($tomorrowJobs && $tomorrowJobs->count() > 0) {
    //                         foreach ($tomorrowJobs as $job) {
    //                             Carbon::setLocale($lng == 'en' ? 'en' : 'he');
    //                             $day = Carbon::parse($job->start_date)->translatedFormat('l'); // Use translatedFormat for localized day
    //                             if($job->service) {
    //                                 $tomorrowSchedule .= $job->service->name . ', ';
    //                             }
    //                             $tomorrowSchedule .=  $day . ' - ' . $job->start_time . ' ' . $job->end_time . ", ";
    //                             if($job->propertyAddress) {
    //                                 $tomorrowSchedule .= $job->propertyAddress->geo_address . ', ';
    //                             }
    //                             if($job->client) {
    //                                 $tomorrowSchedule .= $job->client->firstname . ' ' . $job->client->lastname;
    //                             }
    //                             $tomorrowSchedule .= "\n";
    //                         }
    //                     }else{
    //                         if ($lng == 'heb') {
    //                             $reason = "לא מתוכננות עבודות מחר";
    //                         }else if($lng == 'spa'){
    //                             $reason = "No hay trabajos programados para mañana";
    //                         }else if($lng == 'ru'){
    //                             $reason = "Завтра не запланировано никаких работ";
    //                         }else{
    //                             $reason = "No tomorrow jobs scheduled";
    //                         }
    //                         $tomorrowSchedule = $reason;
    //                     }
    //                     $nextMessage = str_replace(':today_schedule', $todaySchedule, $nextMessage);
    //                     $nextMessage = str_replace(':tomorrow_schedule', $tomorrowSchedule, $nextMessage);
    //                     sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);
    //                     $activeWorkerBot->delete();
    //                     break;

    //                 case 'attempts_exceeded':
    //                     // Handle attempts exceeded logic
    //                     $message = $this->activeWorkersbotMessages['attempts'][$lng];
    //                     sendClientWhatsappMessage($from, array('message' => $message));

    //                     // Notify the team
    //                     $nextMessage = $this->activeWorkersbotMessages['team_attempts']["heb"];
    //                     $workerName = "*".(($user->firstname ?? ''). ' ' . ($user->lastname ?? ''))."*";
    //                     $personalizedMessage = str_replace(':worker_name', $workerName, $nextMessage);
    //                     sendTeamWhatsappMessage(config('services.whatsapp_groups.workers_availability'), ['name' => '', 'message' => $personalizedMessage]);
    //                     // Reset the cache
    //                     Cache::forget($cacheKey);
    //                     $activeWorkerBot->delete();

    //                     break;

    //                 default:
    //                     # code...
    //                     break;
    //             }
    //         }
    //     }
    // }

    public function fbActiveWorkersWebhookCurrentLive(Request $request)
    {
        \Log::info('fbActiveWorkersWebhookCurrentLive');
        $data = $request->all();
        \Log::info($data);
        $messageId = $data['SmsMessageSid'] ?? null;
        $lng = "en";

        if (!$messageId) {
            return response()->json(['status' => 'Invalid message data'], 400);
        }

        // // Check if the messageId exists in cache and matches
        // if (Cache::get('active_worker_processed_message_' . $messageId) === $messageId) {
        //     \Log::info('Already processed');
        //     return response()->json(['status' => 'Already processed'], 200);
        // }

        // Store the messageId in the cache for 1 hour
        Cache::put('active_worker_processed_message_' . $messageId, $messageId, now()->addHours(1));

        if ($data['SmsStatus'] == 'received') {
            $from =  Str::replace('whatsapp:+', '', $data['From']) ?? null;
            $input = $data['Body'] ? trim($data['Body']) : "";
            $listId = $data['ListId'] ?? $input;
            \Log::info($listId);
            $ButtonPayload = $data['ButtonPayload'] ?? $listId;

            $status = $data['SmsStatus'] ?? null;
            $lng = "heb";

            WorkerWebhookResponse::create([
                'status' => 1,
                'name' => 'whatsapp',
                'entry_id' => $data['SmsMessageSid'],
                'message' => $input,
                'number' => $from,
                'read' => 0,
                'flex' => 'W',
                'data' => json_encode($data)
            ]);

            $user = User::where('phone', $from)
                    ->where('status', 1)
                    ->first();

            if ($user) {
                \Log::info('User found activeWorker: ' . $user->id);
            }

            if ($user && $user->stop_last_message == 1) {
                $lng = $user->lng;
                $last_menu = null;
                $send_menu = null;
                $sid = null;
                $activeWorkerBot = WhatsAppBotActiveWorkerState::where('worker_id', $user->id)->first();

                if($activeWorkerBot){
                    $menu_option = explode('->', $activeWorkerBot->menu_option);
                    $last_menu = end($menu_option);
                }

                $cacheKey = 'send_menu_sorry_count_' . $from;

                // Initialize the cache if not already set
                if (!Cache::has($cacheKey)) {
                    Cache::put($cacheKey, 0, now()->addHours(24));
                }

                if(empty($last_menu) || in_array(strtolower($ButtonPayload), ["menu", "меню", "תפריט", "menú"])) {
                    $send_menu = 'main_menu';
                } else if ($last_menu == 'main_menu' && $ButtonPayload == '1') {
                    $send_menu = 'talk_to_manager';
                } else if ($last_menu == 'talk_to_manager' && !empty($input)) {
                    $send_menu = 'comment';
                } else if ($last_menu == 'main_menu' && $ButtonPayload == '2') {
                    $send_menu = 'change_schedule';
                } else if ($last_menu == 'change_schedule' && !empty($input)) {
                    $send_menu = 'change_schedule_comment';
                } else if ($last_menu == 'main_menu' && $ButtonPayload == '3') {
                    $send_menu = 'today_and_tomorrow_schedule';
                } else if ($last_menu == 'main_menu' && $ButtonPayload == '4') {
                    $send_menu = 'access_employee_portal';
                } else {
                    // Handle 'sorry' case
                    $send_menu = 'sorry';
                    $sorryCount = Cache::increment($cacheKey);
                    if ($sorryCount > 4) {
                        Cache::put($cacheKey, 0, now()->addHours(24)); // Reset to 0 and keep the cache expiration
                        $send_menu = 'attempts_exceeded'; // Handle as 'attempts_exceeded'
                    } elseif ($sorryCount == 4) {
                        $send_menu = 'attempts_exceeded';
                    }
                }

                switch ($send_menu) {
                    case 'main_menu':
                        $initialMessage = $this->activeWorkersbotMessages['main_menu'][$lng];
                        WhatsAppBotActiveWorkerState::updateOrCreate(
                            ["worker_id" => $user->id],
                            [
                                'menu_option' => 'main_menu',
                                'lng' => $lng,
                            ]
                        );


                        // Replace :worker_name with the user's firstname and lastname
                        $workerName = "*".(($user->firstname ?? ''). ' ' . ($user->lastname ?? ''))."*";
                        $personalizedMessage = str_replace(':worker_name', $workerName, $initialMessage);

                        if($user->lng == 'heb'){
                            $sid = 'HXfb2e6d4bb7951bd6a69cb57c607032bb';
                        }else if($user->lng == 'ru'){
                            $sid = 'HX95ed8770de994312a121061620a9933d';
                        }else if($user->lng == 'spa'){
                            $sid = 'HX371de9c7eaaef192fc3fe81140e5ad6a';
                        }else{
                            $sid = 'HX6d60d866a1e260aad0588277667b1372';
                        }

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    '1' => $workerName
                                ]),
                                
                            ]
                        );
                        \Log::info("twilio response". $twi->sid);

                        WorkerWebhookResponse::create([
                            'status' => 1,
                            'name' => 'whatsapp',
                            'message' => $personalizedMessage,
                            'number' => $from,
                            'read' => 1,
                            'flex' => 'A',
                        ]);
                        break;

                    case 'talk_to_manager':
                        $nextMessage = $this->activeWorkersbotMessages['talk_to_manager'][$lng];

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "body" => $nextMessage, 
                                
                            ]
                        );
                        \Log::info("twilio response". $twi->sid);

                        $activeWorkerBot->update(['menu_option' => 'main_menu->talk_to_manager', 'lng' => $lng]);

                        WorkerWebhookResponse::create([
                            'status' => 1,
                            'name' => 'whatsapp',
                            'message' => $nextMessage,
                            'number' => $from,
                            'read' => 1,
                            'flex' => 'A',
                        ]);
                        break;

                    case 'comment':
                        \Log::info('comment');
                        $nextMessage = $this->activeWorkersbotMessages['comment'][$lng];
                        $workerName = (($user->firstname ?? ''). ' ' . ($user->lastname ?? ''));
                        $personalizedMessage = str_replace([':worker_name', ':message'], [$workerName, $input], $nextMessage);

                        if($user->lng == 'heb'){
                            $sid = 'HXa57cdbf018f25ca83d3bf87b305c0c93';
                        }else if($user->lng == 'ru'){
                            $sid = 'HX6469d8d9794b5d5ab75471379455c3fe';
                        }else if($user->lng == 'spa'){
                            $sid = 'HXcbec2b9e02025331306d6ef385adff23';
                        }else{
                            $sid = 'HX25f788ffb51c26d6ab5973c8cfc1fe53';
                        }

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid, 
                                "contentVariables" => json_encode([
                                    "1" => $workerName,
                                    "2" => trim($input)
                                ])
                            ]
                        );
                        \Log::info("twilio response". $twi->sid);

                        if($lng == 'heb'){
                            $reason = 'צרו איתי קשר דחוף';
                        }else if($lng == 'ru'){
                            $reason = 'Свяжитесь со мной срочно';
                        }else if($lng == 'spa'){
                            $reason = 'Contáctame urgentemente';
                        }else{
                            $reason = 'Contact me urgently';
                        }

                        $scheduleChange = new ScheduleChange();
                        $scheduleChange->user_type = get_class($user);
                        $scheduleChange->user_id = $user->id;
                        $scheduleChange->reason = $reason;
                        $scheduleChange->comments = trim($input);
                        $scheduleChange->save();

                        $nextMessage = $this->activeWorkersbotMessages['team_comment']["en"];
                        $personalizedMessage = str_replace([':worker_name', ':message'], [$workerName, $input], $nextMessage);
                        sendTeamWhatsappMessage(config('services.whatsapp_groups.problem_with_workers'), ['name' => '', 'message' => $personalizedMessage]);
                        $activeWorkerBot->delete();
                        break;

                    case 'change_schedule':
                        $nextMessage = $this->activeWorkersbotMessages['change_schedule'][$lng];
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "body" => $nextMessage, 
                                
                            ]
                        );
                        \Log::info("twilio response". $twi->sid);

                        $activeWorkerBot->update(['menu_option' => 'main_menu->change_schedule', 'lng' => $lng]);

                        WorkerWebhookResponse::create([
                            'status' => 1,
                            'name' => 'whatsapp',
                            'message' => $nextMessage,
                            'number' => $from,
                            'read' => 1,
                            'flex' => 'A',
                        ]);
                        break;

                    case 'change_schedule_comment':
                        if ($lng == 'heb') {
                            $reason = "שנה לוח זמנים";
                        }else if($lng == 'spa'){
                            $reason = "Cambiar horario";
                        }else if($lng == 'ru'){
                            $reason = "Изменить расписание";
                        }else{
                            $reason = "Change Schedule";
                        }
                        $scheduleChange = new ScheduleChange();
                        $scheduleChange->user_type = get_class($user);
                        $scheduleChange->user_id = $user->id;
                        $scheduleChange->reason = $reason;
                        $scheduleChange->comments = $input;
                        $scheduleChange->save();

                        $nextMessage = $this->activeWorkersbotMessages['team_schedule_change']["en"];
                        $workerName = (($user->firstname ?? ''). ' ' . ($user->lastname ?? ''));
                        $personalizedMessage = str_replace([':worker_name', ':message'], [$workerName, $input], $nextMessage);
                        sendTeamWhatsappMessage(config('services.whatsapp_groups.workers_availability'), ['name' => '', 'message' => $personalizedMessage]);

                        $message = $this->activeWorkersbotMessages['change_schedule_comment'][$lng];
                        $message = str_replace([':message'], [$input], $message);

                        if($user->lng == 'heb'){
                            $sid = 'HXf2b8715efecea4b55740e7f04c7656b8';
                        }else if($user->lng == 'ru'){
                            $sid = 'HX2c81729043db64e39ad6cda705e9d786';
                        }else if($user->lng == 'spa'){
                            $sid = 'HX2c81729043db64e39ad6cda705e9d786';
                        }else{
                            $sid = 'HXb8c3eb8b5f3b946d18fc288165ef7cd0';
                        }

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid, 
                                "contentVariables" => json_encode([
                                    "1" => trim($input)
                                ])
                            ]
                        );
                        \Log::info("twilio response". $twi->sid);

                        $activeWorkerBot->delete();
                        break;

                    case 'access_employee_portal':
                        $nextMessage = $this->activeWorkersbotMessages['access_employee_portal'][$lng];
                        $personalizedMessage = str_replace(':link', generateShortUrl(url("worker/login"), 'worker'), $nextMessage);

                        if($user->lng == 'heb'){
                            $sid = 'HX98bf3431b173310f6381032ebd227ace';
                        }else if($user->lng == 'ru'){
                            $sid = 'HXe3faeff57212e8181e6463b3ee432a3b';
                        }else if($user->lng == 'spa'){
                            $sid = 'HXa1110d7c89955ddb21d166152074c3bc';
                        }else{
                            $sid = 'HX929da6f775cd8a2cc15fdcef32e62769';
                        }

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid, 
                                "contentVariables" => json_encode([
                                    "1" => "worker/login"
                                ])
                            ]
                        );
                        \Log::info("twilio response". $twi->sid);
                        $activeWorkerBot->delete();
                        break;

                    case 'sorry':
                        $message = $this->activeWorkersbotMessages['sorry'][$lng];
                        if($user->lng == 'heb'){
                            $sid = 'HX4c481f10769a8a22d942f900e4623bb6';
                        }else if($user->lng == 'ru'){
                            $sid = 'HX970a1874a503822d4443ce5c58cccefb';
                        }else if($user->lng == 'spa'){
                            $sid = 'HX7d8e43ac45cb7088f3fa24a5e0ba5a16';
                        }else{
                            $sid = 'HXa3dc5005a3421b1160162844e26235ec';
                        }

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid, 
                                "contentVariables" => json_encode([
                                    "1" => trim($input)
                                ])
                            ]
                        );
                        \Log::info("twilio response". $twi->sid);

                        break;

                    case 'today_and_tomorrow_schedule':
                        $nextMessage = $this->activeWorkersbotMessages['today_and_tomorrow_schedule'][$lng];
                        $todayJobs = Job::where('worker_id', $user->id)
                        ->whereNotIn('status', [JobStatusEnum::COMPLETED, JobStatusEnum::CANCEL])
                        ->whereDate('start_date', now())
                        ->get();

                        $tomorrowJobs = Job::where('worker_id', $user->id)
                        ->whereNotIn('status', [JobStatusEnum::COMPLETED, JobStatusEnum::CANCEL])
                        ->whereDate('start_date', now()->addDay(1))
                        ->get();

                        $todaySchedule = "";
                        $tomorrowSchedule = "";
                        if ($todayJobs && $todayJobs->count() > 0) {
                            foreach ($todayJobs as $job) {
                                Carbon::setLocale($lng == 'en' ? 'en' : 'he');
                                $day = Carbon::parse($job->start_date)->translatedFormat('l'); // Use translatedFormat for localized day
                                if($job->service) {
                                    $todaySchedule .= $job->service->name . ', ';
                                }
                                $todaySchedule .=  $day . ' - ' . $job->start_time . ' ' . $job->end_time . ", ";
                                if($job->propertyAddress) {
                                    $todaySchedule .= $job->propertyAddress->geo_address . ', ';
                                }
                                if($job->client) {
                                    $todaySchedule .= $job->client->firstname . ' ' . $job->client->lastname;
                                }
                                $todaySchedule .= "\n";
                            }
                        }else{
                            if ($lng == 'heb') {
                                $reason = "לא מתוכננות משרות היום";
                            }else if($lng == 'spa'){
                                $reason = "No hay trabajos programados para hoy";
                            }else if($lng == 'ru'){
                                $reason = "Сегодня нет запланированных работ";
                            }else{
                                $reason = "No today jobs scheduled";
                            }
                            $todaySchedule = $reason;
                        }

                        if ($tomorrowJobs && $tomorrowJobs->count() > 0) {
                            foreach ($tomorrowJobs as $job) {
                                Carbon::setLocale($lng == 'en' ? 'en' : 'he');
                                $day = Carbon::parse($job->start_date)->translatedFormat('l'); // Use translatedFormat for localized day
                                if($job->service) {
                                    $tomorrowSchedule .= $job->service->name . ', ';
                                }
                                $tomorrowSchedule .=  $day . ' - ' . $job->start_time . ' ' . $job->end_time . ", ";
                                if($job->propertyAddress) {
                                    $tomorrowSchedule .= $job->propertyAddress->geo_address . ', ';
                                }
                                if($job->client) {
                                    $tomorrowSchedule .= $job->client->firstname . ' ' . $job->client->lastname;
                                }
                                $tomorrowSchedule .= "\n";
                            }
                        }else{
                            if ($lng == 'heb') {
                                $reason = "לא מתוכננות עבודות מחר";
                            }else if($lng == 'spa'){
                                $reason = "No hay trabajos programados para mañana";
                            }else if($lng == 'ru'){
                                $reason = "Завтра не запланировано никаких работ";
                            }else{
                                $reason = "No tomorrow jobs scheduled";
                            }
                            $tomorrowSchedule = $reason;
                        }
                        $nextMessage = str_replace(':today_schedule', $todaySchedule, $nextMessage);
                        $nextMessage = str_replace(':tomorrow_schedule', $tomorrowSchedule, $nextMessage);

                        if($user->lng == 'heb'){
                            $sid = 'HX6b127de82fb4e7aae3432d4431e2306f';
                        }else if($user->lng == 'ru'){
                            $sid = 'HXc8c04d84c80604f6d7715c1ef82ef60e';
                        }else if($user->lng == 'spa'){
                            $sid = 'HX26ff074838d19f700928395e7b9478ef';
                        }else{
                            $sid = 'HXd01a4e3a6b40b3fe11c9feb6bd711204';
                        }

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid, 
                                "contentVariables" => json_encode([
                                    "1" => trim($todaySchedule),
                                    "2" => trim($tomorrowSchedule)
                                ])
                            ]
                        );
                        \Log::info("twilio response". $twi->sid);
                        $activeWorkerBot->delete();
                        break;

                    case 'attempts_exceeded':
                        // Handle attempts exceeded logic
                        $message = $this->activeWorkersbotMessages['attempts'][$lng];
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "body" => $message, 
                                
                            ]
                        );
                        \Log::info("twilio response". $twi->sid);
                        // Notify the team
                        $nextMessage = $this->activeWorkersbotMessages['team_attempts']["heb"];
                        $workerName = "*".(($user->firstname ?? ''). ' ' . ($user->lastname ?? ''))."*";
                        $personalizedMessage = str_replace(':worker_name', $workerName, $nextMessage);
                        sendTeamWhatsappMessage(config('services.whatsapp_groups.workers_availability'), ['name' => '', 'message' => $personalizedMessage]);
                        // Reset the cache
                        Cache::forget($cacheKey);
                        $activeWorkerBot->delete();

                        break;

                    default:
                        # code...
                        break;
                }
            }else if($user && $user->stop_last_message == 0){
                $this->activeWorkersMonday($request);
            }
        }
    }

    public function activeWorkersMonday(Request $request)
    {
        $data = $request->all();
        \Log::info($data);
        $messageId = $data['SmsMessageSid'] ?? null;
        $lng = "en";

        if (!$messageId) {
            return response()->json(['status' => 'Invalid message data'], 400);
        }

        // Check if the messageId exists in cache and matches
        if (Cache::get('worker_monday_processed_message_' . $messageId) === $messageId) {
            \Log::info('Already processed');
            return response()->json(['status' => 'Already processed'], 200);
        }

        // Store the messageId in the cache for 1 hour
        Cache::put('worker_monday_processed_message_' . $messageId, $messageId, now()->addHours(1));

        if ($data['SmsStatus'] == 'received') {

            $from =  Str::replace('whatsapp:+', '', $data['From']) ?? null;
            $input = $data['Body'] ? trim($data['Body']) : "";
            $listId = $data['ListId'] ?? $input;
            $ButtonPayload = $data['ButtonPayload'] ?? $listId;

            $status = $data['SmsStatus'] ?? null;

            $user = User::where('phone', $from)
                    ->where('status', 1)
                    ->first();

            if ($user && $user->stop_last_message == 0) {
                $m = null;

                $msgStatus = Cache::get('worker_monday_msg_status_' . $user->id);

                if(empty($msgStatus)) {
                    $msgStatus = 'main_monday_msg';
                }

                if(!empty($msgStatus)) {
                    $menu_option = explode('->', $msgStatus);
                    $messageBody = $input;
                    $last_menu = end($menu_option);

                    if($last_menu == 'main_monday_msg' && $messageBody == '1') {
                        // Send appropriate message
                        if ($user->lng == 'heb') {
                            $m = "מהו השינוי שאתה מבקש לשבוע הבא? תשובתך תועבר לצוות.";
                        } else if ($user->lng == 'ru') {
                            $m = "Какие у вас изменения на следующую неделю? Ваш ответ будет отправлен команде.";
                        } else if ($user->lng == 'en') {
                            $m = "What is your change for next week? Your response will be forwarded to the team.";
                        } else {
                            $m = "¿Cuál es tu cambio para la próxima semana? Tu respuesta será enviada al equipo.";
                        }


                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "body" => $m, 
                                
                            ]
                        );
                        \Log::info("twilio response". $twi->sid);

                        Cache::put('worker_monday_msg_status_' . $user->id, 'next_week_change', now()->addDay(1));
                        WorkerWebhookResponse::create([
                            'status' => 1,
                            'name' => 'whatsapp',
                            'message' => $m,
                            'number' => $from,
                            'read' => 1,
                            'flex' => 'A',
                        ]);
                    } else if ($last_menu == 'main_monday_msg' && $messageBody == '2') {


                        $message = null;

                        if($user->lng == 'heb'){
                            $message = 'שלום ' . ($user->firstname ?? '' . " " . $user->lastname ?? '') . ',
קיבלנו את תגובתך. אין שינויים בסידור העבודה שלך לשבוע הבא.

בברכה,
צוות ברום סרוויס 🌹';
                        } else if($user->lng == 'ru'){
                            $message = 'Здравствуйте, '  . ($user->firstname ?? '' . " " . $user->lastname ?? '') .',
Мы получили ваш ответ. Ваш график на следующую неделю остается без изменений.

С уважением,
Команда Broom Service 🌹';
                        } else{
                            $message = 'Hello '  . ($user->firstname ?? '' . " " . $user->lastname ?? '') . ',
We received your response. There are no changes to your schedule for next week.

Best Regards,
Broom Service Team 🌹 ';
                        }

                        
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "body" => $message, 
                                
                            ]
                        );
                        \Log::info("twilio response". $twi->sid);

                        Cache::forget('worker_monday_msg_status_' . $user->id);
                        WorkerMetas::where('worker_id', $user->id)->where('key', 'monday_msg_sent')->delete();
                        $user->stop_last_message = 1;
                        $user->save();
                    } else if ($last_menu == 'next_week_change' && !empty($messageBody)) {
                        $scheduleChange = new ScheduleChange();
                        $scheduleChange->user_type = get_class($user);
                        $scheduleChange->user_id = $user->id;
                        $scheduleChange->reason = $user->lng == "en" ? "Change or update schedule" : 'שינוי או עדכון שיבוץ';
                        $scheduleChange->comments = $messageBody;
                        $scheduleChange->save();

                        $personalizedMessage = "שלום צוות,\n" . ($user->firstname ?? '') . " " . ($user->lastname ?? '') . " ביקש לבצע שינוי בסידור העבודה שלו לשבוע הבא.\nהבקשה שלו היא:\n\"".$messageBody."\"\nאנא בדקו וטפלו בהתאם.\nבברכה,\nצוות ברום סרוויס";

                        sendTeamWhatsappMessage(config('services.whatsapp_groups.workers_availability'), ['name' => '', 'message' => $personalizedMessage]);

                        $message = null;

                        if($user->lng == 'heb'){
                            $message = 'שלום ' . ($user->firstname ?? '') . " " . ($user->lastname ?? '') . ',
קיבלנו את תגובתך. בקשתך לשינויים בסידור העבודה התקבלה והועברה לצוות שלנו לבדיקה וטיפול.

להלן הבקשה שלך:
"' . $scheduleChange->comments . '"

בברכה,
צוות ברום סרוויס 🌹';
                        } else if($user->lng == 'ru'){
                            $message = 'Здравствуйте, '  . ($user->firstname ?? '') . " " . ($user->lastname ?? '') .',
Мы получили ваш ответ. Ваш запрос на изменения в графике получен и передан нашей команде для проверки и обработки.

Вот ваш запрос:
"' . $scheduleChange->comments . '"

С уважением,
Команда Broom Service 🌹';
                        } else{
                            $message = 'Hello '  . ($user->firstname ?? '') . " " . ($user->lastname ?? '') . ',
We received your response. Your request for changes to your schedule has been received and forwarded to our team for review and action.

Here’s your request:
"' . $scheduleChange->comments . '"

Best Regards,
Broom Service Team 🌹 ';
                        }

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "body" => $message, 
                                
                            ]
                        );
                        \Log::info("twilio response". $twi->sid);

                        Cache::forget('worker_monday_msg_status_' . $user->id);
                        WorkerMetas::where('worker_id', $user->id)->where('key', 'monday_msg_sent')->delete();
                        $user->stop_last_message = 1;
                        $user->save();
                    } else {
                        // Follow-up message for returning to the menu, with translation based on the client's language
                        if ($user->lng == 'heb') {
                            $follow_up_msg = "מצטערים, לא הבנו. אנא השב עם הספרה 1 אם יש לך שינויים, או 2 אם הסידור נשאר כפי שהיה.\n\nאם לא תתקבל תשובה תוך 5 שעות, הנושא יועבר לטיפול הצוות.\n\nבברכה,\nצוות ברום סרוויס 🌹";
                        }else if ($user->lng == 'ru') {
                            $follow_up_msg = "Извините, я вас не понял. Пожалуйста, ответьте 1, если у вас есть изменения, или 2, если график остается без изменений.\n\nЕсли ответа не будет в течение 5 часов, проблема будет передана команде.\n\nС уважением,\nКоманда Broom Service 🌹";
                        } else if($user->lng == 'en') {
                            $follow_up_msg = "Sorry, I didn’t quite understand that. Please reply with the number 1 if you have changes or 2 if your schedule remains the same.\n\nIf no response is received within 5 hours, the issue will be escalated to the team.\n\nBest Regards,\nBroom Service Team 🌹";
                        }else{
                            $follow_up_msg = "Sorry, I didn’t quite understand that. Please reply with the number 1 if you have changes or 2 if your schedule remains the same.\n\nIf no response is received within 5 hours, the issue will be escalated to the team.\n\nBest Regards,\nBroom Service Team 🌹";
                        }
                        
                        if($user->lng == 'heb'){
                            $sid = 'HXc67d7e37adca24d7a05e09dff74c7e1a';
                        }else if($user->lng == 'ru'){
                            $sid = 'HXc8db59a575fcd5104a659b758e5e3fc1';
                        }else if($user->lng == 'spa'){
                            $sid = 'HX8168e5915abd7c464c0afc1a1b881079';
                        }else{
                            $sid = 'HXc431d620e4063a0f80527acce896ecff';
                        }

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$from",
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid, 
                                // "contentVariables" => json_encode([
                                //     "1" => trim($todaySchedule),
                                //     "2" => trim($tomorrowSchedule)
                                // ])
                            ]
                        );
                        \Log::info("twilio response". $twi->sid);

                        WorkerWebhookResponse::create([
                            'status' => 1,
                            'name' => 'whatsapp',
                            'entry_id' => $messageId,
                            'message' => $follow_up_msg,
                            'number' => $from,
                            'read' => 1,
                            'flex' => 'A',
                            'data' => json_encode($data)
                        ]);

                    }
                }
            }
        }
    }

    public function processWorkerResponse($workerLead, $input, $currentStep,$workerState)
    {
        $messages = $this->botMessages;
        $lng = $workerState->language;
        $response = strtolower(trim($input));

        switch ($currentStep) {
            case 0:
                if ($input == "yes") {
                    $workerLead->experience_in_house_cleaning = true;
                    $workerState->step = 1;
                    $workerState->save();
                    $workerLead->save();

                    $sid = $lng == "ru" ? "HX78f4491dca237fc9d526c4ed6bdc3782" : "HX158b195044a2fe87cf1855c7ff90de09";

                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$workerLead->phone",
                        [
                            "from" => $this->twilioWhatsappNumber, 
                            "contentSid" => $sid, 
                            
                        ]
                    );
                    \Log::info("twilio response". $twi->sid);

                    return $messages['step2'][$lng];
                } elseif ($input == "no") {
                    $workerLead->experience_in_house_cleaning = false;
                    $workerState->step = 1;
                    $workerState->save();
                    $workerLead->save();

                    $sid = $lng == "ru" ? "HX78f4491dca237fc9d526c4ed6bdc3782" : "HX158b195044a2fe87cf1855c7ff90de09";

                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$workerLead->phone",
                        [
                            "from" => $this->twilioWhatsappNumber, 
                            "contentSid" => $sid, 
                            
                        ]
                    );
                    \Log::info("twilio response". $twi->sid);

                    return $messages['step2'][$lng];
                } else {
                    $workerState->step = 0;
                    $workerState->save();

                    $sid = $lng == 'ru' ? 'HX815f6780363af98516b549254a2f7958' : 'HXa8ebb4eec38a019b248aa176fac5088e';

                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$workerLead->phone",
                        [
                            "from" => $this->twilioWhatsappNumber, 
                            "contentSid" => $sid, 
                            
                        ]
                    );
                    \Log::info("twilio response". $twi->sid);

                    return $messages['step1'][$lng];
                }

            case 1:
                if ($input == "yes") {
                    $workerLead->you_have_valid_work_visa = true;
                    $workerLead->save();
                    return $this->sendMessageToTeamOrLead($workerLead, $input);
                } elseif ($input == "no") {
                    $workerLead->you_have_valid_work_visa = false;
                    $workerLead->save();
                    return $this->sendMessageToTeamOrLead($workerLead, $input);
                } else {
                    $sid = $lng == "ru" ? "HXd72fdbd778950fad6c176fe38962b353" : "HXcc15b8cf7729e4854efa3893271b4b37";

                    $twi = $this->twilio->messages->create(
                        "whatsapp:+$workerLead->phone",
                        [
                            "from" => $this->twilioWhatsappNumber, 
                            "contentSid" => $sid, 
                            
                        ]
                    );
                    \Log::info("twilio response". $twi->sid);
                    return $messages['step3'][$lng];
                }

            case 2:
               $this->sendMessageToTeamOrLead($workerLead, $input);
        }
    }

    protected function sendMessageToTeamOrLead($workerLead, $input)
       {
           if ( $workerLead->you_have_valid_work_visa ) {

                $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::NEW_LEAD_FOR_HIRING_TO_TEAM);

                WhatsAppBotWorkerState::updateOrCreate(
                    ['worker_lead_id' => $workerLead->id],
                    ['step' => 4]
                );

           } else {
                $workerLead = WorkerLeads::find($workerLead->id);
                $workerLead->status = "not-hired";
                $workerLead->save();

               $resp = $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::FINAL_MESSAGE_IF_NO_TO_LEAD);

               WhatsAppBotWorkerState::updateOrCreate(
                   ['worker_lead_id' => $workerLead->id],
                   ['step' => 4]
               );

           }

       }


    public function detectLanguage($text)
    {
        // Regex for Russian (Cyrillic)
        if (preg_match('/[\x{0400}-\x{04FF}]/u', $text)) {
            return 'ru';
        } else {
            return 'en';
        }

        // else if (preg_match('/[a-zA-Z]/', $text)) {
        //     return 'en';
        // } else {
        //     return 'heb';
        // }
    }

    protected function sendWhatsAppMessage($workerLead, $enum)
    {
       event(new WhatsappNotificationEvent([
            "type" => $enum,
            "notificationData" => [
                'worker' => $workerLead->toArray(),
            ]
        ]));
    }


    public function isWeekend($date)
    {
        $weekDay = date('w', strtotime($date));
        return ($weekDay == 5 || $weekDay == 6);
    }

    public function createUser($workerLead){
        $role = $workerLead->role ?? 'cleaner';
        $lng = $workerLead->lng;

        if ($role == 'cleaner') {
            $role = match ($lng) {
                'heb' => "מנקה",
                'en' => "Cleaner",
                'ru' => "уборщик",
                default => "limpiador"
            };
        } elseif ($role == 'general_worker') {
            $role = match ($lng) {
                'heb' => "עובד כללי",
                'en' => "General worker",
                'ru' => "Общий рабочий",
                default => "Trabajador general"
            };
        }

        // Create new user
        $worker = User::create([
            'firstname' => $workerLead->firstname ?? '',
            'lastname' => $workerLead->lastname ?? '',
            'phone' => $workerLead->phone ?? null,
            'email' => $workerLead->email ?? null,
            'gender' => $workerLead->gender ?? null,
            'first_date' => $workerLead->first_date ?? null,
            'role' => $role ?? null,
            'lng' => $lng ?? "en",
            'passcode' => $workerLead->phone ?? null,
            'password' => Hash::make($workerLead->phone),
            'company_type' => $workerLead->company_type ?? "my-company",
            'visa' => $workerLead->visa ?? NULL,
            'passport' => $workerLead->passport ?? NULL,
            'passport_card' => $workerLead->passport_card ?? NULL,
            'id_number' => $workerLead->id_number ?? NULL,
            'status' => 1,
            'is_afraid_by_cat' => $workerLead->is_afraid_by_cat == 1 ? 1 : 0,
            'is_afraid_by_dog' => $workerLead->is_afraid_by_dog == 1 ? 1 : 0,
            'renewal_visa' => $workerLead->renewal_visa ?? NULL,
            'address' => $workerLead->address ?? NULL,
            'latitude' => $workerLead->latitude ?? NULL,
            'longitude' => $workerLead->longitude ?? NULL,
            'manpower_company_id' => $workerLead->company_type == "manpower" ? $workerLead->manpower_company_id : NULL,
            'two_factor_enabled' => 1,
            'step' => $workerLead->step ?? 0
        ]);

        $i = 1;
        $j = 0;
        $check_friday = 1;
        while ($i == 1) {
            $current = Carbon::now();
            $day = $current->addDays($j);
            if ($this->isWeekend($day->toDateString())) {
                $check_friday++;
            } else {
                $w_a = new WorkerAvailability;
                $w_a->user_id = $worker->id;
                $w_a->date = $day->toDateString();
                $w_a->start_time = '08:00:00';
                $w_a->end_time = '17:00:00';
                $w_a->status = 1;
                $w_a->save();
            }
            $j++;
            if ($check_friday == 6) {
                $i = 2;
            }
        }


        $forms = $workerLead->forms()->get();
            foreach ($forms as $form) {
                $form->update([
                    'user_type' => User::class,
                    'user_id' => $worker->id
                ]);
            }

        $workerLead->delete();

        return $worker;
    }


}
