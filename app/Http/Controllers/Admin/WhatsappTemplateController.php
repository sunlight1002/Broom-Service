<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;

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
            'message_rus' => 'string',
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
}
