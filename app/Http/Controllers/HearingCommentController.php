<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HearingProtocol;

class HearingCommentController extends Controller
{
    public function store(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'worker_id' => 'required|exists:users,id',
            'comment' => 'required|string|max:500',
        ]);

        $protocol = HearingProtocol::where('worker_id', $request->worker_id)
            ->latest()
            ->first();

        if (!$protocol) {
            return response()->json(['message' => 'Protocol not found.'], 404);
        }

        $protocol->comment = $request->comment;
        $protocol->save();

        return response()->json(['message' => 'Comment submitted successfully!'], 200);
    }

    public function getComments(Request $request)
    {
        $request->validate([
            'worker_id' => 'required|exists:users,id',
        ]);

        $protocol = HearingProtocol::where('worker_id', $request->worker_id)
            ->latest()
            ->first();

        if (!$protocol) {
            return response()->json(['message' => 'Protocol not found.'], 404);
        }

        return response()->json(['comments' => [$protocol->comment]], 200);
    }
}
