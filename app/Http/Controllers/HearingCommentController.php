<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HearingProtocol;
use App\Models\Comment;

class HearingCommentController extends Controller
{
    public function store(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'worker_id' => 'required|exists:users,id',
            'comment' => 'required|string|max:500',
        ]);

        Comment::create([
            'user_id' => $request->worker_id,
            'comment' => $request->comment,
        ]);

        return response()->json(['message' => 'Comment submitted successfully!'], 200);
    }

    public function getComments(Request $request)
    {
        $request->validate([
            'worker_id' => 'required|exists:users,id',
        ]);

        $comment = Comment::where('user_id', $request->worker_id)
        ->latest()
        ->first();

        if (!$comment) {
            return response()->json(['message' => 'No comment found.'], 404);
        }

        return response()->json(['comment' => $comment->comment], 200);
    }
}
