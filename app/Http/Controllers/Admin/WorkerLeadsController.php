<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkerLeads;
use App\Models\WhatsAppBotWorkerState;
use Illuminate\Http\Request;

class WorkerLeadsController extends Controller
{

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
        // Validate the request
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:worker_leads,email',
            'phone' => 'required|string|max:15', // Adjust max length as needed
            'status' => 'required|string',
            'ready_to_get_best_job' => 'boolean',
            'ready_to_work_in_house_cleaning' => 'boolean',
            'areas_aviv_herzliya_ramat_gan_kiryat_ono_good' => 'boolean',
            'none_id_visa' => 'required|string',
            'you_have_valid_work_visa' => 'boolean',
            'work_sunday_to_thursday_fit_schedule_8_10am_12_2pm' => 'boolean',
            'full_or_part_time' => 'required|string',
        ]);

        // Create a new worker lead
        $workerLead = WorkerLeads::create($request->all());

        return response()->json([
            'message' => 'Worker Lead created successfully',
            'data' => $workerLead,
        ], 201); // 201 status code for created resource
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
            // Add other fields as necessary
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

        return response()->json(['message' => 'Worker Lead status changed successfully']);
    }
}
