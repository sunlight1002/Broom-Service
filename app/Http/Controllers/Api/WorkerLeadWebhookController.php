<?php

namespace App\Http\Controllers\Api;

use App\Enums\LeadStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\SettingKeyEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\WhatsappNotificationEvent;
use App\Http\Controllers\Controller;
use App\Models\Fblead;
use App\Models\User;
use App\Models\Contract;
use App\Models\Job;
use App\Jobs\SendMeetingMailJob;
use App\Models\Offer;
use App\Models\WorkerWebhookResponse;
use App\Models\WhatsAppBotWorkerState;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\WorkerLeads;
use App\Models\ScheduleChange;
use App\Models\WhatsAppBotActiveWorkerState;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;


class WorkerLeadWebhookController extends Controller
{

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
        ]
    ];


    public function fbWebhookCurrentLive(Request $request)
    {
        $get_data = $request->getContent();
        $data_returned = json_decode($get_data, true);
        $messageId = $data_returned['messages'][0]['id'] ?? null;
        $lng = "en";

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
            $lng = $this->detectLanguage($input);
            $currentStep = 0;

            WorkerWebhookResponse::create([
                'status' => 1,
                'name' => 'whatsapp',
                'entry_id' => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                'message' => $data_returned['messages'][0]['text']['body'],
                'number' => $from,
                'read' => 0,
                'flex' => 'W',
                'data' => json_encode($get_data)
            ]);

            $workerLead = WorkerLeads::where('phone', $from)->first();
            $user = User::where('phone', $from)
                    ->where('status', 1)
                    ->first();

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
                // Send the step0 message
                $initialMessage = $this->botMessages['step0'][$lng];
                $result = sendWorkerWhatsappMessage($from, ['name' => '', 'message' => $initialMessage]);
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

            if (Str::endsWith($message_data[0]['chat_id'], '@g.us')) {
                $messageInput = strtolower(trim($input));
                // Check if the message follows the format "phone – status"
                if (preg_match('/^\+?\d+\s*[-–]\s*(h|n|u|t)$/i', $messageInput, $matches) && ($message_data[0]['chat_id'] == config('services.whatsapp_groups.relevant_with_workers'))) {
                    $phoneNumber = trim(explode('-', $matches[0])[0]); // Extracts the number
                    $statusInput = strtolower($matches[1]); // Extracts the status
                    \Log::info('phone: ' . $phoneNumber . ' status: ' . $statusInput);

                    // Find the workerLead based on the phone number
                    $workerLead = WorkerLeads::where('phone', $phoneNumber)->first();

                    if ($workerLead) {
                        // Determine the status
                        if (in_array($statusInput, ['h'])) {
                            $workerLead->status = "hiring";
                        } elseif (in_array($statusInput, ['u'])) {
                            $workerLead->status = "unanswered";
                        } else if(in_array($statusInput, ['t'])){
                            $workerLead->status = "will-think";
                        }else if(in_array($statusInput, ['n'])) {
                            $workerLead->status = "not-hired";
                        }

                        $workerLead->save();

                        // Send appropriate WhatsApp message
                        if ($workerLead->status == "hiring") {
                            $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::NEW_LEAD_HIRIED_TO_TEAM);
                        } elseif ($workerLead->status == "not-hired") {
                            $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::WORKER_LEAD_NOT_RELEVANT_BY_TEAM);
                        } else if($workerLead->status == "unanswered"){
                            $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::NEW_LEAD_HIRING_ALEX_REPLY_UNANSWERED);
                        } else if($workerLead->status == "will-think"){
                            $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::TEAM_WILL_THINK_SEND_TO_WORKER_LEAD);
                        }
                        return response()->json(['status' => 'Worker status updated'], 200);
                    }

                    return response()->json(['status' => 'Worker not found'], 404);
                }

                return response()->json(['status' => 'Message format invalid or already processed'], 400);
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

                $switchMessage = $this->botMessages['step0'][$lng];
                $result = sendWorkerWhatsappMessage($from, ['name' => '', 'message' => $switchMessage]);

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
                $nextMessage = $this->processWorkerResponse($workerLead, $input, $currentStep, $workerState);

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


    public function fbActiveWorkersWebhookCurrentLive(Request $request)
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

            WorkerWebhookResponse::create([
                'status' => 1,
                'name' => 'whatsapp',
                'entry_id' => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                'message' => $data_returned['messages'][0]['text']['body'],
                'number' => $from,
                'read' => 0,
                'flex' => 'W',
                'data' => json_encode($get_data)
            ]);

            $workerLead = WorkerLeads::where('phone', $from)->first();
            $user = User::where('phone', $from)
                    ->where('status', 1)
                    ->first();
                    \Log::info($user);

            if ($user && !$workerLead) {
                $lng = $user->lng;
                $last_menu = '';
                $activeWorkerBot = WhatsAppBotActiveWorkerState::where('worker_id', $user->id)->first();
                
                if($activeWorkerBot){
                    $menu_option = explode('->', $activeWorkerBot->menu_option);
                    $last_menu = end($menu_option);
                    \Log::info($last_menu);
                }

                if (!$activeWorkerBot || $input == in_array(strtolower($input), ["menu", "меню", "תפריט", "menú"])) {
                    // Fetch the initial message based on the selected language
                    $initialMessage = $this->activeWorkersbotMessages['main_menu'][$lng];
                
                    // Replace :worker_name with the user's firstname and lastname
                    $workerName = $user->firstname ?? ''. ' ' . $user->lastname ?? '';
                    $personalizedMessage = str_replace(':worker_name', $workerName, $initialMessage);
                    $result = sendWorkerWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

                    WhatsAppBotActiveWorkerState::updateOrCreate(
                        ['worker_id' => $user->id],
                        ['menu_option' => 'main_menu', 'lng' => $lng]
                    );

                    WorkerWebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $personalizedMessage,
                        'number' => $from,
                        'read' => 1,
                        'flex' => 'A',
                    ]);
                }

                if($input == '1' || $last_menu == 'comment'){
                    if($input == '1'){
                        $nextMessage = $this->activeWorkersbotMessages['talk_to_manager'][$lng];
                        $result = sendWorkerWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

                        WhatsAppBotActiveWorkerState::updateOrCreate(
                            ['worker_id' => $user->id],
                            ['menu_option' => 'talk_to_manager->comment']
                        );

                        WorkerWebhookResponse::create([
                            'status' => 1,
                            'name' => 'whatsapp',
                            'message' => $nextMessage,
                            'number' => $from,
                            'read' => 1,
                            'flex' => 'A',
                        ]);
                    }

                    if($last_menu == 'comment'){
                       $workerComment = WhatsAppBotActiveWorkerState::updateOrCreate(
                            ['worker_id' => $user->id],
                            ['menu_option' => 'talk_to_manager->comment', 
                            'comment' => trim($input),
                            'final' => true
                            ]
                        );

                        $nextMessage = $this->activeWorkersbotMessages['comment'][$lng];

                        $workerName = $user->firstname ?? ''. ' ' . $user->lastname ?? '';
                        $personalizedMessage = str_replace([':worker_name', ':message'], [$workerName, $workerComment->comment], $nextMessage);
                        $result = sendWorkerWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

                        WorkerWebhookResponse::create([
                            'status' => 1,
                            'name' => 'whatsapp',
                            'message' => $personalizedMessage,
                            'number' => $from,
                            'read' => 1,
                            'flex' => 'A',
                        ]);

                        $nextMessage = $this->activeWorkersbotMessages['team_comment']["en"];
                        $personalizedMessage = str_replace([':worker_name', ':message'], [$workerName, $workerComment->comment], $nextMessage);
                        $result = sendTeamWhatsappMessage(config('services.whatsapp_groups.relevant_with_workers'), ['name' => '', 'message' => $personalizedMessage]);

                    }
                }

                if($input == '2' || $last_menu == 'change_schedule'){
                    if($input == '2'){
                        $nextMessage = $this->activeWorkersbotMessages['change_schedule'][$lng];
                        $result = sendWorkerWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

                        WhatsAppBotActiveWorkerState::updateOrCreate(
                            ['worker_id' => $user->id],
                            ['menu_option' => 'main_menu->change_schedule']
                        );

                        WorkerWebhookResponse::create([
                            'status' => 1,
                            'name' => 'whatsapp',
                            'message' => $nextMessage,
                            'number' => $from,
                            'read' => 1,
                            'flex' => 'A',
                        ]);
                    }

                    if($last_menu == 'change_schedule'){
                        if($input == '1'){

                        }else{
                            $workerComment = WhatsAppBotActiveWorkerState::updateOrCreate(
                                ['worker_id' => $user->id],
                                ['menu_option' => 'main_menu->change_schedule', 
                                'comment' => trim($input),
                                'final' => true
                                ]
                            );
    
                            $nextMessage = $this->activeWorkersbotMessages['team_schedule_change']["en"];
                            $workerName = $user->firstname ?? ''. ' ' . $user->lastname ?? '';
                            $personalizedMessage = str_replace([':worker_name', ':message'], [$workerName, $workerComment->comment], $nextMessage);
                            $result = sendTeamWhatsappMessage(config('services.whatsapp_groups.relevant_with_workers'), ['name' => '', 'message' => $personalizedMessage]);
                        }
                    }
                }
            }
                    
        }
    }

    public function activeWorkersMonday(Request $request)
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

            $user = User::where('phone', $from)
                    ->where('status', 1)
                    ->first();

            $client = Client::where('phone', $from)->first();
            $workerLead = WorkerLead::where('phone', $from)->first();
            if ($client || $workerLead) {
                die('exist');
            }

            if ($user) {
                $m = null;
            
                if ($user->status == 1) {
                    $request = ScheduleChange::where('user_id', $user->id)
                        ->where('user_type', get_class($user))
                        ->latest()
                        ->first();
            
                    $isOlderThanWeek = $request && $request->created_at->lt(now()->subWeek());
            
                    // If the input is 1
                    if ($input == 1 && now()->isMonday() && (!$request || $isOlderThanWeek)) {
                        // Set the flag to true
                        $user->has_input_one = true;
                        $user->save();
            
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
            
                        sendWorkerWhatsappMessage($from, ['name' => '', 'message' => $m]);
            
                        WorkerWebhookResponse::create([
                            'status' => 1,
                            'name' => 'whatsapp',
                            'message' => $m,
                            'number' => $from,
                            'read' => 1,
                            'flex' => 'A',
                        ]);
            
                        $user->stop_last_message = 1;
                        $user->save();
                    }


                    if (now()->isMonday() && $input != '1' && $input != '2' && $user->stop_last_message != 1) {
                        $follow_up_msg = null;
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
    
                        WorkerWebhookResponse::create([
                            'status' => 1,
                            'name' => 'whatsapp',
                            'entry_id' => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                            'message' => $data_returned['messages'][0]['text']['body'],
                            'number' => $from,
                            'read' => 1,
                            'flex' => 'A',
                            'data' => json_encode($get_data)
                        ]);
    
                        $result = sendWorkerWhatsappMessage($from, array('message' => $follow_up_msg));
                        
                    } else if ($input != 1 && $input != 2 && now()->isMonday() && (!$request || $isOlderThanWeek) && $user->has_input_one) {
                        $scheduleChange = new ScheduleChange();
                        $scheduleChange->user_type = get_class($user);  
                        $scheduleChange->user_id = $user->id;      
                        $scheduleChange->comments = $input;  
                        $scheduleChange->save();

                        $user->has_input_one = false;
                        $user->stop_last_message = 1;
                        $user->save();

                        $message = null;

                        if($user->lng == 'heb'){
                            $message = 'שלום ' . $user->firstname . " " . $user->lastname . ',  
קיבלנו את תגובתך. בקשתך לשינויים בסידור העבודה התקבלה והועברה לצוות שלנו לבדיקה וטיפול.  

להלן הבקשה שלך:  
"' . $scheduleChange->comments . '"  

בברכה,  
צוות ברום סרוויס 🌹';
                        } else if($user->lng == 'ru'){
                            $message = 'Здравствуйте, '  . $user->firstname . " " . $user->lastname .',  
Мы получили ваш ответ. Ваш запрос на изменения в графике получен и передан нашей команде для проверки и обработки.  

Вот ваш запрос:  
"' . $scheduleChange->comments . '"  

С уважением,  
Команда Broom Service 🌹';
                        } else{
                            $message = 'Hello '  . $user->firstname . " " . $user->lastname . ',  
We received your response. Your request for changes to your schedule has been received and forwarded to our team for review and action.  

Here’s your request:  
"' . $scheduleChange->comments . '"  

Best Regards,  
Broom Service Team 🌹 ';
                        }

                        sendWorkerWhatsappMessage($from, array('message' => $message));
                    }  else if($input == 2 && now()->isMonday() && (!$request || $isOlderThanWeek) && !$user->has_input_one) {

                        $user->has_input_one = false;
                        $user->stop_last_message = 1;
                        $user->save();

                        $message = null;

                        if($user->lng == 'heb'){
                            $message = 'שלום ' . $user->firstname . " " . $user->lastname . ',  
קיבלנו את תגובתך. אין שינויים בסידור העבודה שלך לשבוע הבא.  

בברכה,  
צוות ברום סרוויס 🌹';
                        } else if($user->lng == 'ru'){
                            $message = 'Здравствуйте, '  . $user->firstname . " " . $user->lastname .',  
Мы получили ваш ответ. Ваш график на следующую неделю остается без изменений.  

С уважением,  
Команда Broom Service 🌹';
                        } else{
                            $message = 'Hello '  . $user->firstname . " " . $user->lastname . ',  
We received your response. There are no changes to your schedule for next week.  

Best Regards,  
Broom Service Team 🌹 ';
                        }

                        sendWorkerWhatsappMessage($from, array('message' => $message));
                    }
                }                   

                die("User is already Worker");
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
                if (in_array($response, ['yes', 'sí', 'Да', 'כֵּן'])) {
                    $workerLead->experience_in_house_cleaning = true;
                    $workerState->step = 1;
                    $workerState->save();
                    $workerLead->save();
                    return $messages['step2'][$lng];
                } elseif (in_array($response, ['no', 'No', 'Нет', 'לא'])) {
                    $workerLead->experience_in_house_cleaning = false;
                    $workerState->step = 1;
                    $workerState->save();
                    $workerLead->save();
                    return $messages['step2'][$lng];
                } else {
                    $workerState->step = 0;
                    $workerState->save();
                    return $messages['step1'][$lng];
                }

            case 1:
                if (in_array($response, ['yes', 'sí', 'Да','כֵּן'])) {
                    $workerLead->you_have_valid_work_visa = true;
                    $workerLead->save();
                    return $this->sendMessageToTeamOrLead($workerLead, $input);
                } elseif (in_array($response, ['no', 'No', 'Нет', 'לא'])) {
                    $workerLead->you_have_valid_work_visa = false;
                    $workerLead->save();
                    return $this->sendMessageToTeamOrLead($workerLead, $input);
                } else {
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

}
