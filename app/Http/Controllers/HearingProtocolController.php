<?php

namespace App\Http\Controllers;

use App\Models\HearingProtocol;
use Illuminate\Http\Request;

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
            $path = $request->file('file')->store('hearing_protocols');
            $validatedData['file'] = $path; // Save the path to the file
        }

        $protocol = HearingProtocol::create($validatedData);

        return response()->json(['message' => 'Protocol saved successfully!'], 201);
    }

    public function show($id)
    {
        $protocol = HearingProtocol::find($id);

        if (!$protocol) {
            return response()->json(['message' => 'Protocol not found.'], 404);
        }

        return response()->json(['file' => $protocol->file], 200);
    }
}
