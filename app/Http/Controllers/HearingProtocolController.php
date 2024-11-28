<?php
namespace App\Http\Controllers;

use App\Models\HearingProtocol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class HearingProtocolController extends Controller
{
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'pdf_name' => 'nullable|string|max:255',
            'file' => 'required|file|mimes:pdf|max:2048',
            'worker_id' => 'nullable|exists:users,id',
            'team_id' => 'nullable|exists:admins,id',
            'hearing_invitation_id' => 'nullable|exists:hearing_invitations,id',
            'comment' => 'nullable|string',
        ]);

        if ($request->file) {
            $path = $request->file('file')->store('hearing_protocols', 'public');
            $validatedData['file'] = $path;
        }

        $protocol = HearingProtocol::create($validatedData);

        return response()->json(['message' => 'Protocol saved successfully!'], 201);
    }

    public function show(Request $request)
    {
        $workerId = $request->query('worker_id');
        if (!$workerId) {
            return response()->json(['message' => 'Worker ID is required.'], 400);
        }
    
        // Find the protocol by worker_id
        $protocol = HearingProtocol::where('worker_id', $workerId)->latest()->first();
    
        if (!$protocol) {
            return response()->json(['message' => 'Protocol not found.'], 404);
        }
    
        // Generate the correct URL using Storage::url()
        $fileUrl = Storage::url($protocol->file);

        return response()->json([
            'id' => $protocol->id,
            'file' => $fileUrl
        ]);
    }
}
