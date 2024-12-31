<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkerLeads;
use App\Models\WhatsAppBotWorkerState;
use App\Models\WorkerWebhookResponse;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            'name',
            'email',
            'phone',
            'status',
            'ready_to_get_best_job',
            'ready_to_work_in_house_cleaning',
            'areas_aviv_herzliya_ramat_gan_kiryat_ono_good',
            'none_id_visa',
            'you_have_valid_work_visa',
            'work_sunday_to_thursday_fit_schedule_8_10am_12_2pm',
            'full_or_part_time'
        ];

        $length = $request->get('length', 10);
        $start = $request->get('start', 0);
        $order = $request->get('order', []);
        $columnIndex = $order[0]['column'] ?? 0;
        $dir = $order[0]['dir'] ?? 'desc';

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

        // Filter by status if provided
        if ($request->has('status') && $request->get('status') !== null) {
            $query->where('status', $request->get('status'));
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
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'status' => $lead->status,
                'ready_to_get_best_job' => $lead->ready_to_get_best_job ? 'Yes' : 'No',
                'ready_to_work_in_house_cleaning' => $lead->ready_to_work_in_house_cleaning ? 'Yes' : 'No',
                'areas_aviv_herzliya_ramat_gan_kiryat_ono_good' => $lead->areas_aviv_herzliya_ramat_gan_kiryat_ono_good ? 'Yes' : 'No',
                'none_id_visa' => $lead->none_id_visa ? 'Yes' : 'No',
                'you_have_valid_work_visa' => $lead->you_have_valid_work_visa ? 'Yes' : 'No',
                'work_sunday_to_thursday_fit_schedule_8_10am_12_2pm' => $lead->work_sunday_to_thursday_fit_schedule_8_10am_12_2pm ? 'Yes' : 'No',
                'full_or_part_time' => $lead->full_or_part_time ? 'Yes' : 'No',
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
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:worker_leads,email',
                'phone' => 'required|string|max:15', // Adjust max length as needed
                'status' => 'required|string',
                // 'ready_to_get_best_job' => 'boolean',
                // 'ready_to_work_in_house_cleaning' => 'boolean',
                // 'areas_aviv_herzliya_ramat_gan_kiryat_ono_good' => 'boolean',
                // 'none_id_visa' => 'required|string',
                // 'you_have_valid_work_visa' => 'boolean',
                // 'work_sunday_to_thursday_fit_schedule_8_10am_12_2pm' => 'boolean',
                // 'full_or_part_time' => 'required|string',
            ]);
    
            // Create a new worker lead
            $workerLead = WorkerLeads::create($request->all());

            if($request->send_bot_message) {
                try {
                    $m = $this->botMessages['step0']['heb'];
    
                    $result = sendWorkerWhatsappMessage($workerLead->phone, array('name' => ucfirst($workerLead->name), 'message' => $m));

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


    public function edit($id)
    {
        $workerLead = WorkerLeads::find($id);
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

        // Validate the request
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'status' => 'required|string',
            'phone' => 'required|string|max:15', // Adjust max length as needed
        ]);

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
        if (!$workerLead) {
            return response()->json(['message' => 'Worker Lead not found'], 404);
        }

        // Change the status
        $workerLead->status = $request->status;
        $workerLead->save();

        if ($workerLead->status === 'irrelevant') {

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::WORKER_LEAD_WEBHOOK_IRRELEVANT,
                "notificationData" => [
                    'client' => $workerLead->toArray(),
                ]
            ]));
        }

        return response()->json(['message' => 'Worker Lead status changed successfully']);
    }
}
