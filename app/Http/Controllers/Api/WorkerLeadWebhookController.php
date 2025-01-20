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
            $user = User::where('phone', $from)->first();

            if ($user) {
                $m = null;
                if ($user->status == 1) {
                $request = ScheduleChange::where('user_id', $user->id)
                        ->where('user_type', get_class($user))
                        ->latest()->first();
            
                    // Check if ScheduleChange is older than 1 week
                    $isOlderThanWeek = $request && $request->created_at->lt(now()->subWeek());
            
                        if ($input == 1 && now()->isMonday() && (!$request || $isOlderThanWeek)) {
                            if($user->lng == 'heb') {
                                $m =  "מהו השינוי שאתה מבקש לשבוע הבא? תשובתך תועבר לצוות.";
                            }else if($user->lng == 'ru') {
                                $m = "Какие у вас изменения на следующую неделю? Ваш ответ будет отправлен команде.";
                            }else if($user->lng == 'en') {
                                $m = "What is your change for next week? Your response will be forwarded to the team.";
                            }else{
                                $m = "¿Cuál es tu cambio para la próxima semana? Tu respuesta será enviada al equipo.";
                            }
                
                            $result = sendWorkerWhatsappMessage($from, array('name' => '', 'message' => $m));
                
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
                            
                        } else if ($input != 1 && $input != 2 && now()->isMonday() && (!$request || $isOlderThanWeek)) {
                            $scheduleChange = new ScheduleChange();
                            $scheduleChange->user_type = get_class($user);  
                            $scheduleChange->user_id = $user->id;      
                            $scheduleChange->comments = $input;  
                            $scheduleChange->save();
                        }  
                }                   

                die("User is already Worker");
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
                if (preg_match('/^\+?\d+\s*[-–]\s*(hire|no|unanswered|ללא מענה|לִשְׂכּוֹר|לֹא)$/i', $messageInput, $matches) && ($message_data[0]['chat_id'] == config('services.whatsapp_groups.relevant_with_workers'))) {
                    $phoneNumber = trim(explode('-', $matches[0])[0]); // Extracts the number
                    $statusInput = strtolower($matches[1]); // Extracts the status

                    // Find the workerLead based on the phone number
                    $workerLead = WorkerLeads::where('phone', $phoneNumber)->first();

                    if ($workerLead) {
                        // Determine the status
                        if (in_array($statusInput, ['hire', 'לִשְׂכּוֹר'])) {
                            $workerLead->status = "hiring";
                        } elseif (in_array($statusInput, ['unanswered', 'ללא מענה'])) {
                            $workerLead->status = "unanswered";
                        } else {
                            $workerLead->status = "not-hired";
                        }

                        $workerLead->save();

                        // Send appropriate WhatsApp message
                        if ($workerLead->status == "hiring") {
                            $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::NEW_LEAD_HIRIED_TO_TEAM);
                        } elseif ($workerLead->status == "not-hired") {
                            $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::FINAL_MESSAGE_IF_NO_TO_LEAD);
                        } else {
                            $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::NEW_LEAD_HIRING_ALEX_REPLY_UNANSWERED);
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
