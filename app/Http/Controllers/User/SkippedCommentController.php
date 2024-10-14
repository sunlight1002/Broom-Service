<?php

namespace App\Http\Controllers\User;

use App\Models\SkippedComment;
use App\Models\JobComments;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

class SkippedCommentController extends Controller
{

    public function index()
    {
        // Retrieve all skipped comments, optionally you can paginate or filter
        $skippedComments = SkippedComment::with('comment')->get(); // Eager load the related comments

        // Return the data as JSON if this is an API
        return response()->json($skippedComments);

        // Or return a view if this is a web route
        // return view('skipped_comments.index', compact('skippedComments'));
    }

    public function store(Request $request)
    {
        // Validate the request
        $validatedData = $request->validate([
            'comment_id' => 'required|exists:job_comments,id',
            'request_text' => 'required|string',
        ]);

        // Find the comment and load related job, client, and worker
        $comment = JobComments::with(['job.client', 'job.worker'])->find($validatedData['comment_id']);

        // Prepare the data for the event, correcting the client and worker mapping


        // Set the comment's status to 'pending'
        $comment->status = 'pending';
        $comment->save();

        // Create a record in the skipped_comments table
        $skipcomment = SkippedComment::create([
            'comment_id' => $comment->id,
            'request_text' => $validatedData['request_text'],
            'status' => 'pending',
        ]);

        $data = [
            'comment_id' => $comment->job->id,
            'comment' => $comment->comment,
            // 'request' => $skipcomment->request_text,
            'skipcomment' => $skipcomment,               // The comment itself
            'worker' => $comment->job->worker,   // The worker assigned to the job
            'client' => $comment->job->client,   // The client for the job
        ];

        // Fire the event with the correct data
        event(new WhatsappNotificationEvent([
            'type' => WhatsappMessageTemplateEnum::NOTIFY_TEAM_FOR_SKIPPED_COMMENTS,
            'notificationData' => [
                'job' => $data, // Send the comment, worker, and client
            ],
        ]));

        // Return a successful response
        return response()->json([
            'success' => true,
            'message' => 'Comment skip request submitted successfully',
        ]);
    }

    public function updateStatus(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'comment_id' => 'required|exists:skipped_comments,comment_id',
            'status' => 'required|in:Approved,Rejected', // Validate that status is either 'Approved' or 'Rejected'
            'response_text' => 'nullable|string|max:255', // Add validation for the response text
        ]);

        // Find the skipped comment by its comment_id
        $skippedComment = SkippedComment::where('comment_id', $validatedData['comment_id'])->first();

        if (!$skippedComment) {
            return response()->json([
                'success' => false,
                'message' => 'Skipped comment not found.',
            ], 404);
        }

        // Update the skipped comment's status to lowercase ('approved' or 'rejected')
        $skippedComment->status = strtolower($validatedData['status']); // 'approved' or 'rejected'

        // If status is 'rejected', store the rejection text
        if ($skippedComment->status === 'rejected' && !empty($validatedData['response_text'])) {
            $skippedComment->response_text = $validatedData['response_text'];
        } else {
            // Clear the response_text if the status is not rejected
            $skippedComment->response_text = null;
        }

        $skippedComment->save();

        // Update the corresponding JobComments status if skipped comment is 'approved'
        if ($skippedComment->status === 'approved') {
            // Find the JobComment by the same comment_id
            $jobComment = JobComments::where('id', $skippedComment->comment_id)->first();

            if ($jobComment) {
                // Update the JobComment status to 'approved'
                $jobComment->status = 'approved';
                $jobComment->save();
            }
        }

        // Optionally, fire an event if a notification is needed
        // event(new SkippedCommentStatusUpdated($skippedComment)); // Example event

        return response()->json([
            'success' => true,
            'message' => 'Skipped comment status updated successfully.',
        ]);
    }


}

// $comment = Job::with('comments')
// ->where('id', $request->input("job_id"))
// ->whereHas('comments', function ($q) {
//     // Only include clients created on or after the static date
//     $q->where('id', $validatedData[comment_id]);
// })->get();

// // Set the comment's status to 'pending'
// $comment->status = 'pending';
// $comment->save();
