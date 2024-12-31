<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\User;
use App\Models\CustomMessages;
use App\Jobs\SendCustomMessage;
class WhatsappTemplateController extends Controller
{
    public function index()
    {
        $templates = WhatsappTemplate::all();
        return response()->json($templates, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|unique:whatsapp_templates,key',
            'message_en' => 'string',
            'message_heb' => 'string',
            'message_spa' => 'string',
            'message_ru' => 'string',
        ]);

        // Create the new template
        $template = WhatsappTemplate::create($validated);

        return response()->json([
            'message' => 'WhatsApp template created successfully!',
            'template' => $template
        ], 201);
    }

    public function show($id)
    {
        $template = WhatsappTemplate::find($id);

        if (!$template) {
            return response()->json(['message' => 'Template not found'], 404);
        }

        return response()->json($template, 200);
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();
        $template = WhatsappTemplate::find($id);

        if (!$template) {
            return response()->json(['message' => 'Template not found'], 404);
        }

        $template->update($data);

        return response()->json([
            'message' => 'WhatsApp template updated successfully!',
            'template' => $template
        ], 200);
    }

    public function destroy($id)
    {
        $template = WhatsappTemplate::find($id);

        if (!$template) {
            return response()->json(['message' => 'Template not found'], 404);
        }

        $template->delete();

        return response()->json(['message' => 'Template deleted successfully!'], 200);
    }

    public function customMessageSend(Request $request)
    {
        $type = $request->input('type'); // leads, clients, or workers
        $status = $request->input('status'); // status filter
        $workerInculdeIds = $request->input('worker_inculde_ids', []); 
        $workerExcludeIds = $request->input('worker_exclude_ids', []); 
        $clientIncludeIds = $request->input('client_include_ids', []); 
        $clientExcludeIds = $request->input('client_exclude_ids', []); 
        $templates = $request->input('templates', []); // Get templates (messages)
    
        $data = [];
    
        if ($type === 'leads' || $type === 'clients') {
            $query = Client::query()->with('lead_status');
    
            $query->where(function ($q) use ($status, $clientIncludeIds, $clientExcludeIds) {
                if ($status !== 'all') {
                    $q->whereHas('lead_status', function ($subQuery) use ($status) {
                        $subQuery->where('lead_status', $status);
                    });
                }
        
                if (!empty($clientIds)) {
                    $q->orWhereIn('id', $clientIncludeIds);
                }
        
                if (!empty($clientExcludeIds)) {
                    $q->whereNotIn('id', $clientExcludeIds);
                }
            });
        
            $data = $query->get();
    
        } elseif ($type === 'workers') {
            $query = User::query();
        
            $query->where(function($q) use ($status, $workerInculdeIds, $workerExcludeIds) {
                if ($status !== 'all') {
                    $statusValue = strtolower($status) === 'active' ? 1 : (strtolower($status) === 'inactive' ? 0 : null);
                    if ($statusValue !== null) {
                        $q->where('status', $statusValue);
                    }
                }
        
                if (!empty($workerIds)) {
                    $q->orWhereIn('id', $workerInculdeIds);
                }
        
                if (!empty($workerExcludeIds)) {
                    $q->whereNotIn('id', $workerExcludeIds);
                }
            });
            $data = $query->get();
        }
    
        if (!empty($templates)) {
            CustomMessages::create([
                'type' => $type,
                'status' => $status,
                'message_en' => $templates['message_en'] ?? '',
                'message_heb' => $templates['message_heb'] ?? '',
                'message_ru' => $templates['message_ru'] ?? '',
                'message_spa' => $templates['message_spa'] ?? ''
            ]);
        }
    
        foreach ($data as $item) {
            if($item->lng === 'heb'){
                $message = $templates['message_heb'];
            }elseif($item->lng === 'spa'){
                $message = $templates['message_spa'];
            }elseif($item->lng === 'ru'){
                $message = $templates['message_ru'];
            }else{
                $message = $templates['message_en'];
            }
            $phone = $item->phone; 
            SendCustomMessage::dispatch($phone, $message);
        }
    
        return response()->json(['data' => $data]);
    }
}
