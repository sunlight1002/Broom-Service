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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class WorkerLeadWebhookController extends Controller
{

    protected $botMessages = [
        'step0' => [
            'en' => "🌟 Thank you for contacting Job4Service! 🌟\n\nWe are hiring house cleaning professionals for part-time and full-time positions in the Tel Aviv area.\n\n✅ To apply, you must have one of the following:\n- Israeli ID\n- B1 Work Visa\n- Refugee (blue) visa\n\nPlease answer these questions to proceed:\n1. Do you have experience in house cleaning?\n(Please reply with 'Yes' or 'No')\n\n if you want change language then for עיתונות עברית 4 for русская пресса 2 and for prensa española 3",
            'heb' => "🌟 תודה שפנית ל- Job4Service! 🌟\n\nאנחנו מגייסים אנשי מקצוע לניקיון בתים למשרה חלקית ומלאה באזור תל אביב.\n\n✅ להגשת מועמדות יש להצטייד באחד מהבאים:\n- תעודת זהות ישראלית\n- עבודת ויזה (B1)\n- אשרת פליט (כחול)\n\nענה על השאלות הבאות כדי להמשיך:\n1. האם יש לך ניסיון בניקיון בתים?\n(ענה 'כן' או 'לא')\n\nאם אתה רוצה לשנות שפה, עבור English Press 1 עבור русская пресса 2 ועבור prensa española 3",
            'spa' => "🌟 ¡Gracias por contactar a Job4Service! 🌟\n\nEstamos contratando profesionales de limpieza de casas para puestos de tiempo parcial y completo en el área de Tel Aviv.\n\n✅ Para postularte, debes tener uno de los siguientes:\n- Identificación israelí\n- Visa de trabajo B1\n- Visa de refugiado (azul)\n\nResponde estas preguntas para continuar:\n1. ¿Tienes experiencia en limpieza de casas?\n(Responde 'Sí' o 'No')\n\nsi desea cambiar el idioma, entonces para עיתונות עברית 4 para русская пресса 3 y para English press 1",
            'rus' => "🌟 Спасибо, что обратились в Job4Service! 🌟\n\nМы ищем уборщиков домов на полный и неполный рабочий день в районе Тель-Авива.\n✅ Для подачи заявки вам необходимо иметь один из следующих документов:\n- Израильское удостоверение личности\n- Рабочая виза B1\n- Статус беженца (синяя виза)\n\nПожалуйста, ответьте на два вопроса:\n1. Есть ли у вас опыт уборки домов?\n(Пожалуйста, ответьте «Да» или «Нет»)\n\nЕсли вы хотите изменить язык, для עיתונות עברית 4 для English press 1 и для prensa española 3",
       ],
        'step1' => [
            'en' => "We didn’t quite understand your answer.\n\n✅ Please respond clearly with:\n\nDo you have experience in house cleaning?\n**Yes / No**",
            'heb' => "לא כל כך הבנו את תשובתך.\n\n✅ נא להגיב ברור עם:\n\nהאם יש לך ניסיון בניקיון בתים?\n**כן / לא**",
            'spa' => "No entendimos bien tu respuesta.\n\n✅ Por favor, responde claramente con:\n\n¿Tienes experiencia en limpieza de casas?\n**Sí / No**",
            'rus' => "Мы не совсем поняли ваш ответ.\n\n✅ Пожалуйста, ответьте четко:\n\nЕсть ли у вас опыт в уборке дома?\n**Да / Нет**",
        ],
        'step2' => [
            'en' => "Do you have a valid work visa as mentioned above?\n**Yes / No**",
            'heb' => "האם יש לך ויזת עבודה תקפה כפי שהוזכר לעיל?\n**כן / לא**",
            'spa' => "¿Tiene una visa de trabajo válida como se mencionó anteriormente?\n**Sí / No**",
            'rus' => "У вас есть действующая рабочая виза, как указано выше?\n**Да/Нет**",
        ],
        'step3' => [
            'en' => "Hello there! Ready to get the best job?\n**Yes / No**",
            'heb' => 'שלום! מוכן לקבל את העבודה הטובה ביותר?\n**כן / לא**',
            'spa' => "¡Hola! ¿Listo para obtener el mejor trabajo?\n**Sí / No**",
            'rus' => "Привет! Готовы получить лучшую работу?\n**Да / Нет**",
        ],
        'step4' => [
            'en' => "We are hiring for house cleaning positions. Are you ready to work in house cleaning?\n**Yes / No**",
            'heb' => 'אנחנו מגייסים לתפקיד ניקיון בתים. האם אתה מוכן לעבוד בניקיון בתים?\n**כן / לא**',
            'spa' => "Estamos contratando para posiciones de limpieza de casas. ¿Estás listo para trabajar en limpieza de casas?\n**Sí / No**",
            'rus' => "Мы нанимаем на должности по уборке домов. Готовы работать в этой сфере?\n**Да / Нет**",
        ],

        'step5' => [
            'en' => "The job is around Tel Aviv, Herzliya, Ramat Gan, and Kiryat Ono area. Is this good for you?\n**Yes / No**",
            'heb' => "העבודה היא באזור תל אביב, הרצליה, רמת גן וקריית אונו. האם זה טוב עבורך?\n**כן / לא**",
            'spa' => "El trabajo está en las áreas de Tel Aviv, Herzliya, Ramat Gan y Kiryat Ono. ¿Te queda bien?\n**Sí / No**",
            'rus' => "Работа в районах Тель-Авив, Герцлия, Рамат-Ган и Кирьят Оно. Вам это подходит?\n**Да / Нет**",
        ],
        'step6' => [
            'en' => "To apply, you need to be in Israel with B1/Refugee (blue) / Israeli ID for official employment. Which do you have?\n**None / ID / Visa**",
            'heb' => "כדי להגיש בקשה, עליך להיות בישראל עם תעודת זהות / אשרת עבודה (B1/פליט). איזו יש לך?\n **לא / תעודת זהות / ויזה**",
            'spa' => "Para postularte, necesitas estar en Israel con B1/Refugiado (azul) / Identificación israelí para empleo oficial. ¿Cuál tienes?\n**Ninguno / ID / Visa**",
            'rus' => "Для подачи заявки вам нужно находиться в Израиле с B1/беженцем (синяя) / израильским удостоверением личности для официального трудоустройства. Что у вас есть?\n**Нет / ID / Виза**",
        ],

        'step7' => [
            'en' => "We have work from Sunday to Thursday, starting at 8-10am or 12-2pm. Can this fit your schedule?\n**Yes / No**",
            'heb' => "יש לנו עבודה מיום ראשון עד חמישי, מתחילה בשעות 8-10 בבוקר או 12-2 בצהריים. האם זה מתאים לך?\n**כן / לא**",
            'spa' => "Tenemos trabajo de domingo a jueves, comenzando a las 8-10am o 12-2pm. ¿Te queda bien este horario?\n**Sí / No**",
            'rus' => "У нас работа с воскресенья по четверг, начало в 8-10 утра или 12-2 дня. Вам подходит этот график?\n**Да / Нет**",
        ],
        'step8' => [
            'en' => "We offer full or part-time jobs for 1 or 2 shifts. Which do you prefer?\n**Full Time / Part Time**",
            'heb' => "אנו מציעים עבודה במשרה מלאה או חלקית למשמרות של 1 או 2. מה אתה מעדיף?**משרה מלאה / משרה חלקית**",
            'spa' => "Ofrecemos trabajos a tiempo completo o parcial para 1 o 2 turnos. ¿Cuál prefieres?\n**Tiempo Completo / Tiempo Parcial**",
            'rus' => "Мы предлагаем полную или частичную занятость на 1 или 2 смены. Что вы предпочитаете?\n**Полная занятость / Частичная занятость**",
        ],
        'step9' => [
            'en' => "Please leave your name, phone, and email, and we will call you right back with all the details.\n\n**Name**:\n**Phone**:\n**Email**:",
            'heb' => "אנא השאר את שמך, טלפון ואימייל ונחזור אליך מיד עם כל הפרטים.
                    \n**שם**:  
                    \n**טלפון**:  
                    \n**אימייל**: ",
            'spa' => "Por favor, deja tu nombre, teléfono y correo electrónico, y te llamaremos de inmediato con todos los detalles.
                    \n**Nombre**:  
                    \n**Teléfono**:  
                    \n**Correo Electrónico**:",
            'rus' => "Пожалуйста, оставьте свое имя, телефон и email, и мы свяжемся с вами для предоставления всех деталей.
                    \n**Имя**:  
                    \n**Телефон**:  
                    \n**Email**:",
        ],
        'end' => [
            'en' => "Feel free to make any adjustments or further refinements as needed.",
            'heb' => "אל תהסס לבצע כל התאמות או חידודים נוספים לפי הצורך.",
            'spa' => "Siéntase libre de realizar más ajustes o mejoras según sea necesario.",
            'rus' => "Не стесняйтесь вносить любые дополнительные корректировки или уточнения по мере необходимости.",
        ]
    ];

    public function fbWebhookCurrentLive(Request $request)
    {
        $get_data = $request->getContent();
        $data_returned = json_decode($get_data, true);
        $messageId = $data_returned['messages'][0]['id'] ?? null;

        if (
            isset($data_returned['messages']) &&
            isset($data_returned['messages'][0]['from_me']) &&
            $data_returned['messages'][0]['from_me'] == false
        ) {
            $message_data = $data_returned['messages'];
            $from = $message_data[0]['from'];
            $input = $data_returned['messages'][0]['text']['body'];
            $currentStep = 0;
    
            // Save the incoming message to the WorkerWebhookResponse
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
            // Check if user already exists
            $workerLead = WorkerLeads::where('phone', $from)->first();
            if (!$workerLead) {
                // If user doesn't exist, create a new record and send the first step message
                $workerLead = WorkerLeads::create(['phone' => $from]);
                WhatsAppBotWorkerState::updateOrCreate(
                    ['worker_lead_id' => $workerLead->id],
                    ['step' => 0, 'language' => 'heb']
                );
                // Send the step0 message
                $initialMessage = $this->botMessages['step0']['heb'];
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
            $lng = "heb";
            
            if (in_array($input, [1, 2, 3, 4])) {
                $languageMap = [1 => 'en', 2 => 'rus', 3 => 'spa', 4 => 'heb'];
                $lng = $languageMap[$input];
                
                WhatsAppBotWorkerState::updateOrCreate(
                    ['worker_lead_id' => $workerLead->id],
                    ['step' => 0, 'language' => $lng]
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
                \Log::info($currentStep);
                $nextMessage = $this->processWorkerResponse($workerLead, $input, $currentStep, $workerState->language);

                $lastMessageSent = WorkerWebhookResponse::where('number', $workerLead->phone)
                ->where('read',1)
                ->orderBy('created_at', 'desc')
                ->first()->message ?? '';

                if ($nextMessage) {
                    // Send the next step message
                    $result = sendWorkerWhatsappMessage($from, ['name' => '', 'message' => $nextMessage]);
                    $acceptedResponses = [
                        'yes', 'sí', 'да', 'לא', 'no', 'нет', 'כן',
                        'fulltime', 'משרה מלאה', 'tiempo completo', 'tiempo completo', 
                        'полная занятость', 'полнаязанятость', 'part time', 'parttime', 
                        'משרה חלקית', 'tiempo parcial', 'частичная занятость', 
                        'none', 'לֹא', 'ninguno', 'нет', 'id', 'תעודת זהות', 
                        'id', 'visa', 'ויזה', 'visa', 'виза'
                    ];
                    
                    // Normalize the user input
                    $normalizedInput = strtolower(trim($input));
                    
                    // Check if the input is valid and not the same as the last message
                    if (($nextMessage != $lastMessageSent) && in_array($normalizedInput, $acceptedResponses)) {
                        // Update the current step in the state
                        WhatsAppBotWorkerState::updateOrCreate(
                            ['worker_lead_id' => $workerLead->id],
                            ['step' => $currentStep + 1]
                        );
                    }
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
    protected function processWorkerResponse($workerLead, $input, $currentStep,$language)
    {
        $messages = $this->botMessages;
        $lng = $language;
        $response = strtolower(trim($input));
        switch ($currentStep) {
            case 0:
                if (in_array($response, ['yes', 'Sí', 'Да', 'לא'])) {
                    $workerLead->experience_in_house_cleaning = true;
                    $workerLead->save();
                    return $messages['step2'][$lng];   
                } elseif (in_array($response, ['no', 'No', 'Нет', 'כן'])) {
                    $workerLead->experience_in_house_cleaning = false;
                    $workerLead->save();
                    return $messages['step2'][$lng];   
                } else {
                    return $messages['step1'][$lng];   
                }
                
            case 1:
                if (in_array($response, ['yes', 'Sí', 'Да', 'לא'])) {
                    $workerLead->you_have_valid_work_visa = true;
                    $workerLead->save();
                    return $messages['step3'][$lng];   
                } elseif (in_array($response, ['no', 'No', 'Нет', 'כן'])) {
                    $workerLead->you_have_valid_work_visa = false;
                    $workerLead->save();
                    return $messages['step3'][$lng];   
                } else {
                    return $messages['step2'][$lng];   
                }
            case 2:
                if (in_array($response, ['yes', 'Sí', 'Да', 'לא'])) {
                    $workerLead->ready_to_get_best_job = true;
                    $workerLead->save();
                    return $messages['step4'][$lng];   
                } elseif (in_array($response, ['no', 'No', 'Нет', 'כן'])) {
                    $workerLead->ready_to_get_best_job = false;
                    $workerLead->save();
                    return $messages['step4'][$lng];   
                } else {
                    return $messages['step3'][$lng];   
                }
            case 3:
                if (in_array($response, ['yes', 'Sí', 'Да', 'לא'])) {
                    $workerLead->ready_to_work_in_house_cleaning = true;
                    $workerLead->save();
                    return $messages['step5'][$lng];   
                } elseif (in_array($response, ['no', 'No', 'Нет', 'כן'])) {
                    $workerLead->ready_to_work_in_house_cleaning = false;
                    $workerLead->save();
                    return $messages['step5'][$lng];   
                } else {
                    return $messages['step4'][$lng];   
                }
            case 4:
                if (in_array($response, ['yes', 'Sí', 'Да', 'לא'])) {
                    $workerLead->areas_aviv_herzliya_ramat_gan_kiryat_ono_good = true;
                    $workerLead->save();
                    return $messages['step6'][$lng];   
                } elseif (in_array($response, ['no', 'No', 'Нет', 'כן'])) {
                    $workerLead->areas_aviv_herzliya_ramat_gan_kiryat_ono_good = false;
                    $workerLead->save();
                    return $messages['step6'][$lng];   
                } else {
                    return $messages['step5'][$lng];   
                }
            case 5:
                if (in_array($response, ['none', 'לֹא', 'Ninguno', 'Нет' , 'id', 'תעודת זהות', 'ID', 'ID', 'visa','ויזה', 'Visa', 'Виза'])) {
                    $workerLead->none_id_visa = $response;
                    $workerLead->save();
                    return $messages['step7'][$lng];
                }else{
                    return $messages['step6'][$lng];
                }
            case 6:
                if (in_array($response, ['yes', 'Sí', 'Да', 'לא'])) {
                    $workerLead->work_sunday_to_thursday_fit_schedule_8_10am_12_2pm = true;
                    $workerLead->save();
                    return $messages['step8'][$lng];   
                } elseif (in_array($response, ['no', 'No', 'Нет', 'כן'])) {
                    $workerLead->work_sunday_to_thursday_fit_schedule_8_10am_12_2pm = false;
                    $workerLead->save();
                    return $messages['step8'][$lng];   
                } else {
                    return $messages['step7'][$lng];   
                }
        
            case 7:
                if (in_array($response, ['fulltime', "משרה מלאה", 'Tiempo Completo', 'Полная занятость', 'part time', "משרה חלקית", 'Tiempo Parcial', 'Частичная занятость'])) {
                    $workerLead->full_or_part_time = $response;
                    $workerLead->save();
                    return $messages['step9'][$lng];
                }else{
                    return $messages['step8'][$lng];
                }
            
            case 8:
                // The last step, collect contact details
                if ($this->saveContactDetails($workerLead, $input)) {
                    return $messages['end'][$lng];
                }
        }
    }
    protected function saveContactDetails($workerLead, $input)
    {
        // Normalize the input by removing any newline or carriage return characters
        $input = str_replace(["\n", "\r"], ',', $input);
    
        // Split the input by commas
        $details = array_map('trim', explode(',', $input));
    
        // Check if there are exactly 3 pieces of information
        if (count($details) == 3) {
            // Assign values to the workerLead object
            $workerLead->name = $details[0];
            $workerLead->phone = $details[1];
            $workerLead->email = $details[2];
            $workerLead->save();
            return true;
        }
    
        // If it's not in the comma-separated format, try the multiline format
        // Split input by new lines (to handle the multi-line case like pratik\n+912323232\na@mial.com)
        $details = array_map('trim', explode("\n", $input));
    
        // Check if we have exactly 3 pieces of information after splitting by new lines
        if (count($details) == 3) {
            // Assign values to the workerLead object
            $workerLead->name = $details[0];
            $workerLead->phone = $details[1];
            $workerLead->email = $details[2];
            $workerLead->save();
            return true;
        }
    
        return false; // If neither format is correct
    }    
}