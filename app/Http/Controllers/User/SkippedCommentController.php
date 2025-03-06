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
        $comment = JobComments::with(['job.client', 'job.worker', 'job.propertyAddress'])->find($validatedData['comment_id']);

        // Prepare the data for the event, correcting the client and worker mapping


        // Set the comment's status to 'pending'
        $comment->status = 'pending';
        $comment->save();

        // Create a record in the skipped_comments table
        SkippedComment::create([
            'comment_id' => $comment->id,
            'request_text' => $validatedData['request_text'],
            'status' => 'pending',
        ]);

        $job = $comment->job;
        $job->load(['jobservice', 'propertyAddress']);

        // Fire the event with the correct data
        event(new WhatsappNotificationEvent([
            'type' => WhatsappMessageTemplateEnum::NOTIFY_TEAM_FOR_SKIPPED_COMMENTS,
            'notificationData' => [
                'job' => $job->toArray(), // Send the comment, worker, and client
                'worker' => $comment->job->worker->toArray(),
                'client' => $comment->job->client->toArray(),
                'comment' => $comment->toArray(),
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

        $jobComment = JobComments::with(["job.jobservice","job.worker"])
                    ->where('id', $skippedComment->comment_id)->first();

        // Update the corresponding JobComments status if skipped comment is 'approved'
        if ($skippedComment->status === 'approved') {
            // Find the JobComment by the same comment_id
            if ($jobComment) {
                // Update the JobComment status to 'approved'
                $jobComment->status = 'approved';
                $jobComment->save();
            }
        }

        $pending_comments = JobComments::where('job_id', $jobComment->job_id)
            ->where(function ($query) {
                $query->whereNotIn('status', ['complete'])
                    ->orWhereNull('status');
            })
            ->whereDoesntHave('skipComment', function ($q) {
                $q->where(function ($query) {
                    $query->where('status', '!=', 'approved')
                        ->orWhereNull('status');
                });
            })
            ->count();

        if ($pending_comments < 1) {
            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::WORKER_NOTIFY_AFTER_ALL_COMMENTS_COMPLETED,
                "notificationData" => [
                    'job' => $jobComment->job->toArray(),
                    'worker' => $jobComment->job->worker->toArray(),
                    'client' => $jobComment->job->client->toArray(),
                ]
            ]));
        }

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::UPDATE_ON_COMMENT_RESOLUTION,
            "notificationData" => [
                'job' => $jobComment->job->toArray(),
                'property' => $jobComment->job->propertyAddress->toArray(),
                'worker' => $jobComment->job->worker->toArray(),
                'client' => $jobComment->job->client->toArray(),
            ]
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Skipped comment status updated successfully.',
        ]);
    }


    public function getSkippedCommentByUuid($cid)
    {
        // Retrieve all skipped comments, optionally you can paginate or filter
        $skippedComments = SkippedComment::with('comment')->get(); // Eager load the related comments

        // Return the data as JSON if this is an API
        return response()->json($skippedComments);

        // Or return a view if this is a web route
        // return view('skipped_comments.index', compact('skippedComments'));
    }

    public function skipCommentStore(Request $request)
    {
        \Log::info($request->all());
        // Validate the request
        $validatedData = $request->validate([
            'comment_id' => 'required|exists:job_comments,id',
            'request_text' => 'required|string',
        ]);

        // Find the comment and load related job, client, and worker
        $comment = JobComments::with(['job.client', 'job.worker', 'job.propertyAddress'])->find($validatedData['comment_id']);

        // Prepare the data for the event, correcting the client and worker mapping


        // Set the comment's status to 'pending'
        $comment->status = 'pending';
        $comment->save();

        // Create a record in the skipped_comments table
        SkippedComment::create([
            'comment_id' => $comment->id,
            'request_text' => $validatedData['request_text'],
            'status' => 'pending',
        ]);

        $job = $comment->job;
        $job->load(['jobservice', 'propertyAddress']);

        // Fire the event with the correct data
        event(new WhatsappNotificationEvent([
            'type' => WhatsappMessageTemplateEnum::NOTIFY_TEAM_FOR_SKIPPED_COMMENTS,
            'notificationData' => [
                'job' => $job->toArray(), // Send the comment, worker, and client
                'worker' => $comment->job->worker->toArray(),
                'client' => $comment->job->client->toArray(),
                'comment' => $comment->toArray(),
            ],
        ]));
        // Return a successful response
        return response()->json([
            'success' => true,
            'message' => 'Comment skip request submitted successfully',
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
