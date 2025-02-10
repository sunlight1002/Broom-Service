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


    public function fbWebhookCurrentLive(Request $request)
    {
        $get_data = $request->getContent();
        $data_returned = json_decode($get_data, true);
        \Log::info($data_returned);
        $messageId = $data_returned['messages'][0]['id'] ?? null;
        $lng = "en";

        if (!$messageId) {
            return response()->json(['status' => 'Invalid message data'], 400);
        }

        // Check if the messageId exists in cache and matches
        if (Cache::get('worker_processed_message_' . $messageId) === $messageId) {
            \Log::info('Already processed');
            return response()->json(['status' => 'Already processed'], 200);
        }

        // Store the messageId in the cache for 1 hour
        Cache::put('worker_processed_message_' . $messageId, $messageId, now()->addHours(1));

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

            if (Str::endsWith($message_data[0]['chat_id'], '@g.us')) {
                $messageInput = strtolower(trim($input));
                \Log::info($messageInput);

                $pattern1 = '/^(\+?\d{1,4}[\s\-]?\d{1,4}[\s\-]?\d{1,4}[\s\-]?\d{1,4})\s*([hnut])\s*(\d+)$/i';
                // '/^(\+?\d{1,4}[\s\-]?\d{1,4}[\s\-]?\d{1,4}[\s\-]?\d{1,4})\s*([hnut])\s*(\d+)?$/i'
                $pattern2 = '/^(new|חדש)\s+([\s\S]+?)\s+(ours|mp)\s+(\+?\d{1,4}[\s\-]?\d{1,4}[\s\-]?\d{1,4}[\s\-]?\d{1,4})$/is';
                $input = implode(' ', array_map('trim', explode("\n", $messageInput)));  

                $last_input = Cache::get('manpower');
                \Log::info($last_input);
            
                if (preg_match($pattern1, $messageInput, $matches) 
                    && ($message_data[0]['chat_id'] == config('services.whatsapp_groups.relevant_with_workers'))) {

                    $phoneNumber = trim($matches[1]); // Extracts the phone number
                    $statusInput = strtolower($matches[2]); // Extracts the status (h/n/u/t)
                    $numericValue = intval($matches[3]); // Extracts the numeric value (e.g., 55)
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
                                $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::WORKER_LEAD_FORMS_AFTER_HIRING)
                            ],
                            "not-hired" => $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::WORKER_LEAD_NOT_RELEVANT_BY_TEAM),
                            "unanswered" => $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::NEW_LEAD_HIRING_ALEX_REPLY_UNANSWERED),
                            "will-think" => $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::TEAM_WILL_THINK_SEND_TO_WORKER_LEAD),
                            default => null
                        };
            
                        return response()->json(['status' => 'Worker status updated', 'value' => $numericValue], 200);
                    }
            
                    return response()->json(['status' => 'Worker not found'], 404);
                } else if((preg_match($pattern2, $input, $matches)) 
                    && ($message_data[0]['chat_id'] == config('services.whatsapp_groups.relevant_with_workers'))) {
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
                        $workerLead->save();

                        if($workerLead->company_type == 'manpower'){
                            $message = "select manpower company\n";
                            $companies = ManpowerCompany::all();
                            foreach($companies as $key => $company){
                                $message .= $company->id . ". " . $company->name . "\n";
                            }
                            sendTeamWhatsappMessage(config('services.whatsapp_groups.relevant_with_workers'), ['name' => '', 'message' => $message]);
                            Cache::put('manpower', $workerLead->id, now()->addDays(1));
                        }else if($workerLead->company_type == 'my-company'){
                            $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::WORKER_LEAD_FORMS_AFTER_HIRING);
                        }
                
                        return response()->json([
                            'status' => 'New worker added',
                            'name' => $workerName,
                            'language' => $language,
                            'company_type' => $companyType
                        ], 201);
                    }
                }

                if($last_input){
                        $selectedCompanyId = intval($messageInput);
                            // Update the worker's lead with the selected company ID
                            $workerLead = WorkerLeads::where('id', $last_input)->first();
                            if ($workerLead) {
                                $workerLead->manpower_company_id = $selectedCompanyId;
                                $workerLead->save();

                                // Send confirmation message to the user
                                $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::WORKER_LEAD_FORMS_AFTER_HIRING);
                                Cache::forget('manpower', $last_input);
                            }
                }
            

                return response()->json(['status' => 'Message format invalid or already processed'], 400);
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


    public function fbActiveWorkersWebhookCurrentLive(Request $request)
    {
        $get_data = $request->getContent();
        $data_returned = json_decode($get_data, true);
        $messageId = $data_returned['messages'][0]['id'] ?? null;
        $lng = "en";

        if (!$messageId) {
            return response()->json(['status' => 'Invalid message data'], 400);
        }

        // Check if the messageId exists in cache and matches
        if (Cache::get('active_worker_processed_message_' . $messageId) === $messageId) {
            \Log::info('Already processed');
            return response()->json(['status' => 'Already processed'], 200);
        }

        // Store the messageId in the cache for 1 hour
        Cache::put('active_worker_processed_message_' . $messageId, $messageId, now()->addHours(1));

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
            $input = trim($data_returned['messages'][0]['text']['body'] ?? '');
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

            $user = User::where('phone', $from)
                    ->where('status', 1)
                    ->first();
            if ($user) {
                \Log::info('User found activeWorker: ' . $user);
            }

            if ($user && $user->stop_last_message == 1) {
                $lng = $user->lng;
                $last_menu = null;
                $send_menu = null;
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

                if(empty($last_menu) || in_array(strtolower($input), ["menu", "меню", "תפריט", "menú"])) {
                    $send_menu = 'main_menu';
                } else if ($last_menu == 'main_menu' && $input == '1') {
                    $send_menu = 'talk_to_manager';
                } else if ($last_menu == 'talk_to_manager' && !empty($input)) {
                    $send_menu = 'comment';
                } else if ($last_menu == 'main_menu' && $input == '2') {
                    $send_menu = 'change_schedule';
                } else if ($last_menu == 'change_schedule' && !empty($input)) {
                    $send_menu = 'change_schedule_comment';
                } else if ($last_menu == 'main_menu' && $input == '3') {
                    $send_menu = 'today_and_tomorrow_schedule';
                } else if ($last_menu == 'main_menu' && $input == '4') {
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
                        sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

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
                        sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

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
                        $nextMessage = $this->activeWorkersbotMessages['comment'][$lng];
                        $workerName = (($user->firstname ?? ''). ' ' . ($user->lastname ?? ''));
                        $personalizedMessage = str_replace([':worker_name', ':message'], [$workerName, $input], $nextMessage);
                        sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

                        $nextMessage = $this->activeWorkersbotMessages['team_comment']["en"];
                        $personalizedMessage = str_replace([':worker_name', ':message'], [$workerName, $input], $nextMessage);
                        sendTeamWhatsappMessage(config('services.whatsapp_groups.problem_with_workers'), ['name' => '', 'message' => $personalizedMessage]);
                        $activeWorkerBot->delete();
                        break;

                    case 'change_schedule':
                        $nextMessage = $this->activeWorkersbotMessages['change_schedule'][$lng];
                        sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);

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
                        sendClientWhatsappMessage($from, array('message' => $message));
                        $activeWorkerBot->delete();
                        break;

                    case 'access_employee_portal':
                        $nextMessage = $this->activeWorkersbotMessages['access_employee_portal'][$lng];
                        $personalizedMessage = str_replace(':link', generateShortUrl(url("worker/login"), 'worker'), $nextMessage);
                        sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);
                        $activeWorkerBot->delete();
                        break;

                    case 'sorry':
                        $message = $this->activeWorkersbotMessages['sorry'][$lng];
                        sendClientWhatsappMessage($from, array('message' => $message));
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
                        sendClientWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);
                        $activeWorkerBot->delete();
                        break;

                    case 'attempts_exceeded':
                        // Handle attempts exceeded logic
                        $message = $this->activeWorkersbotMessages['attempts'][$lng];
                        sendClientWhatsappMessage($from, array('message' => $message));

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
            }
        }
    }

    public function activeWorkersMonday(Request $request)
    {
        $get_data = $request->getContent();
        $data_returned = json_decode($get_data, true);
        $messageId = $data_returned['messages'][0]['id'] ?? null;
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
                    $messageBody = trim($data_returned['messages'][0]['text']['body'] ?? '');
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

                        sendClientWhatsappMessage($from, ['name' => '', 'message' => $m]);
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

                        sendClientWhatsappMessage($from, array('message' => $message));
                        Cache::forget('worker_monday_msg_status_' . $user->id);
                        WorkerMetas::where('worker_id', $user->id)->where('key', 'monday_msg_sent')->delete();
                        $user->stop_last_message = 1;
                        $user->save();
                    } else if ($last_menu == 'next_week_change' && !empty($messageBody)) {
                        $scheduleChange = new ScheduleChange();
                        $scheduleChange->user_type = get_class($user);
                        $scheduleChange->user_id = $user->id;
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

                        sendClientWhatsappMessage($from, array('message' => $message));
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

                        sendClientWhatsappMessage($from, array('message' => $follow_up_msg));
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
