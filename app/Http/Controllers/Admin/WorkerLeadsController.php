<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkerLeads;
use App\Models\WorkerAvailability;
use App\Models\Job;
use App\Models\User;
use App\Models\Client;
use App\Models\Admin;
use Illuminate\Support\Facades\Mail;
use App\Models\WorkerMetas;
use App\Models\Offer;
use App\Models\WhatsAppBotWorkerState;
use App\Models\WorkerWebhookResponse;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;




class WorkerLeadsController extends Controller
{

    protected $botMessages = [
        'step0' => [
            'en' => "🌟 Thank you for contacting Job4Service! 🌟\n\nWe are hiring house cleaning professionals for part-time and full-time positions in the Tel Aviv area.\n\n✅ To apply, you must have one of the following:\n- Israeli ID\n- B1 Work Visa\n- Refugee (blue) visa\n\nPlease answer these questions to proceed:\n1. Do you have experience in house cleaning?\n(Please reply with 'Yes' or 'No')\n\n if you want change language then for עיתונות עברית 4 for русская пресса 2 and for prensa española 3",
            'heb' => "🌟 תודה שפנית ל- Job4Service! 🌟\n\nאנחנו מגייסים אנשי מקצוע לניקיון בתים למשרה חלקית ומלאה באזור תל אביב.\n\n✅ להגשת מועמדות יש להצטייד באחד מהבאים:\n- תעודת זהות ישראלית\n- עבודת ויזה (B1)\n- אשרת פליט (כחול)\n\nענה על השאלות הבאות כדי להמשיך:\n1. האם יש לך ניסיון בניקיון בתים?\n(ענה 'כן' או 'לא')\n\nאם אתה רוצה לשנות שפה, עבור English Press 1 עבור русская пресса 2 ועבור prensa española 3",
            'spa' => "🌟 ¡Gracias por contactar a Job4Service! 🌟\n\nEstamos contratando profesionales de limpieza de casas para puestos de tiempo parcial y completo en el área de Tel Aviv.\n\n✅ Para postularte, debes tener uno de los siguientes:\n- Identificación israelí\n- Visa de trabajo B1\n- Visa de refugiado (azul)\n\nResponde estas preguntas para continuar:\n1. ¿Tienes experiencia en limpieza de casas?\n(Responde 'Sí' o 'No')\n\nsi desea cambiar el idioma, entonces para עיתונות עברית 4 para русская пресса 3 y para English press 1",
            'rus' => "🌟 Спасибо, что обратились в Job4Service! 🌟\n\nМы ищем уборщиков домов на полный и неполный рабочий день в районе Тель-Авива.\n✅ Для подачи заявки вам необходимо иметь один из следующих документов:\n- Израильское удостоверение личности\n- Рабочая виза B1\n- Статус беженца (синяя виза)\n\nПожалуйста, ответьте на два вопроса:\n1. Есть ли у вас опыт уборки домов?\n(Пожалуйста, ответьте «Да» или «Нет»)\n\nЕсли вы хотите изменить язык, для עיתונות עברית 4 для English press 1 и для prensa española 3",
        ],
    ];

    public function index(Request $request)
    {
        $columns = [
            'id',
            'created_at',
            'firstname',
            'lastname',
            'email',
            'phone',
            'status',
            'source',
            'sub_status',
            'experience_in_house_cleaning',
            'you_have_valid_work_visa',
        ];

        $length = $request->get('length', 10);
        $start = $request->get('start', 0);
        $order = $request->get('order', []);
        $columnIndex = $order[0]['column'] ?? 0;
        $dir = $order[0]['dir'] ?? 'desc';

        $source = $request->get('source');

        // Remove user-based filtering
        $query = WorkerLeads::query();

        // Search functionality
        if ($search = $request->get('search')['value'] ?? null) {
            $query->where(function ($query) use ($search, $columns) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', "%{$search}%");
                }
            });
        }
        \Log::info($request->get('status'));

        // Filter by status if provided
        if ($request->has('status') && $request->get('status') !== null) {
            $query->where('status', $request->get('status'));
        }

        // Filter by sub_status if provided
        if ($request->has('sub_status') && $request->get('sub_status') !== null && $request->get('status') == "not-hired") {
            $query->where('sub_status', $request->get('sub_status'));
        }

        // Filter by source if provided
        if (!empty($source)) {
            $query->where('source', $source);
        }

        // Select specified columns
        $query->select($columns);

        // Ordering
        $query->orderBy($columns[$columnIndex] ?? 'id', $dir);

        // Pagination
        $totalRecords = $query->count();
        $workerLeads = $query->skip($start)->take($length)->get();

        // Transform boolean values
        $workerLeads = $workerLeads->map(function ($lead) {
            return [
                'id' => $lead->id,
                'created_at' => $lead->created_at->format('d/m/Y'),
                'name' => $lead->firstname . ' ' . $lead->lastname,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'source' => $lead->source,
                'status' => $lead->sub_status && $lead->status == "not-hired" ? $lead->sub_status : $lead->status,
                'experience_in_house_cleaning' => $lead->experience_in_house_cleaning ? 'Yes' : 'No',
                'you_have_valid_work_visa' => $lead->you_have_valid_work_visa ? 'Yes' : 'No',
            ];
        });

        return response()->json([
            'filter' => $request->filter,
            'draw' => intval($request->get('draw')),
            'data' => $workerLeads,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
        ]);
    }



    public function store(Request $request)
    {
        try {

            // Validate the request
            $request->validate([
                'firstname' => 'nullable|string|max:255',
                'lastname' => 'nullable|string|min:2|max:255',
                'email' => 'nullable|email|max:255|unique:worker_leads,email',
                'phone' => 'required|string|max:15|unique:worker_leads,phone', // Adjust max length as needed
                'status' => 'required|string',
                'role' => 'required|string',
                // 'ready_to_get_best_job' => 'boolean',
                // 'ready_to_work_in_house_cleaning' => 'boolean',
                // 'areas_aviv_herzliya_ramat_gan_kiryat_ono_good' => 'boolean',
                // 'none_id_visa' => 'required|string',
                // 'you_have_valid_work_visa' => 'boolean',
                // 'work_sunday_to_thursday_fit_schedule_8_10am_12_2pm' => 'boolean',
                // 'full_or_part_time' => 'required|string',
            ]);

            $role = $request->role ?? 'cleaner';
            $lng = $request->lng;

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

            // Create a new worker lead
            $workerLead = WorkerLeads::create([
                'firstname' => $request->firstname ?? '',
                'lastname' => $request->lastname ?? '',
                'email' => $request->email ?? '',
                'phone' => $request->phone ?? '',
                'status' => $request->status ?? '',
                'role' => $role ?? '',
                'lng' => $request->lng ?? "en",
                'latitude' => $request->latitude ?? '',
                'longitude' => $request->longitude ?? '',
                'address' => $request->address ?? '',
                'renewal_visa' => $request->renewal_visa ?? '',
                'gender' => $request->gender ?? '',
                'country' => $request->country ?? '',
                'manpower_company_id' => $request->manpower_company_id ?? '',
                'company_type' => $request->company_type ?? '',
                'experience_in_house_cleaning' => $request->experience_in_house_cleaning ?? '',
                'you_have_valid_work_visa' => $request->you_have_valid_work_visa ?? '',
            ]);

            if ($request->send_bot_message) {
                try {
                    $m = $this->botMessages['step0']['heb'];

                    $result = sendWorkerWhatsappMessage($workerLead->phone, array('name' => ucfirst($workerLead->firstname) . ' ' . ucfirst($workerLead->lastname), 'message' => $m));

                    WhatsAppBotWorkerState::updateOrCreate(
                        ['worker_lead_id' => $workerLead->id],
                        ['step' => 0, 'language' => 'heb']
                    );

                    WorkerWebhookResponse::create([
                        'status' => 1,
                        'name' => 'whatsapp',
                        'message' => $m,
                        'number' => $workerLead->phone,
                        'read' => 1,
                        'flex' => 'A',
                    ]);
                } catch (\Throwable $th) {
                    logger($th);
                }
            }

            return response()->json([
                'message' => 'Worker Lead created successfully',
                'data' => $workerLead,
            ], 201); // 201 status code for created resource

        } catch (ValidationException $e) {
            // Log validation errors
            Log::error('Validation Error:', $e->errors());

            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function getAllWorkerLeads()
    {
        $workerLeads = WorkerLeads::all();
        return response()->json([
            'workerLeads' => $workerLeads
        ]);
    }

    public function edit($id)
    {
        $workerLead = WorkerLeads::find($id);
        \Log::info($workerLead);
        if (!$workerLead) {
            return response()->json(['message' => 'Worker Lead not found'], 404);
        }

        return response()->json($workerLead);
    }

    public function update(Request $request, $id)
    {
        $workerLead = WorkerLeads::find($id);
        if (!$workerLead) {
            return response()->json(['message' => 'Worker Lead not found'], 404);
        }

        // // Validate the request
        // $request->validate([
        //     'firstname' => 'required|string|max:255',
        //     'email' => 'required|email|max:255',
        //     'status' => 'required|string',
        //     'phone' => 'required|string|max:15', // Adjust max length as needed
        // ]);

        // Update the worker lead
        $workerLead->update($request->all());

        return response()->json(['message' => 'Worker Lead updated successfully']);
    }

    public function destroy($id)
    {
        $workerLead = WorkerLeads::find($id);
        if (!$workerLead) {
            return response()->json(['message' => 'Worker Lead not found'], 404);
        }

        $workerState = WhatsAppBotWorkerState::where('worker_lead_id', $id)->first();

        $workerLead->delete();
        if ($workerState) {
            $workerState->delete();
        }
        return response()->json(['message' => 'Worker Lead deleted successfully']);
    }

    public function changeStatus(Request $request, $id)
    {
        $workerLead = WorkerLeads::find($id);
        $admin = Admin::where('role', 'hr')->first();
        if (!$workerLead) {
            return response()->json(['message' => 'Worker Lead not found'], 404);
        }

        // Change the status
        $workerLead->status = $request->status;
        $workerLead->sub_status = $request->status == "not-hired" ? $request->sub_status : null;
        $workerLead->save();

        if ($workerLead->status === 'irrelevant') {
            $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::WORKER_LEAD_WEBHOOK_IRRELEVANT);
        } else if ($workerLead->status === 'will-think') {
            $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::TEAM_WILL_THINK_SEND_TO_WORKER_LEAD);
        } else if ($workerLead->status === 'unanswered') {
            $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::NEW_LEAD_HIRING_ALEX_REPLY_UNANSWERED);
        } else if ($workerLead->status === 'not-hired') {
            $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::WORKER_LEAD_NOT_RELEVANT_BY_TEAM);
        } else if ($workerLead->status === 'hiring') {
            $this->sendWhatsAppMessage($workerLead, WhatsappMessageTemplateEnum::NEW_LEAD_HIRIED_TO_TEAM);
            $worker = $this->createUser($workerLead);
            $this->sendWhatsAppMessage($worker, WhatsappMessageTemplateEnum::WORKER_FORMS);
            $workerArr = $worker->toArray();
            if ($admin) {
                Mail::send('/Mails/WorkerForms', $workerArr, function ($messages) use ($workerArr, $admin) {
                    $messages->to($admin->email);
                    $messages->bcc(config('services.mail.default'));
                    ($workerArr['lng'] == 'heb') ?
                        $sub = $workerArr['id'] . "# " . __('mail.forms.worker_forms') :
                        $sub = __('mail.forms.worker_forms') . " #" . $workerArr['id'];
                    $messages->subject($sub);
                });
            }
        }

        return response()->json(['message' => 'Worker Lead status changed successfully']);
    }

    public function sendWhatsAppMessage($workerLead, $enum)
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

    public function createUser($workerLead)
    {
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


    public function getUniqueSource()
    {
        $workerLeads = WorkerLeads::all();
        $sources = $workerLeads->pluck('source')->filter()->unique()->values();

        return response()->json([
            'sources' => $sources->isEmpty() ? [] : $sources
        ]);
    }
}
