<?php

namespace App\Http\Controllers\User;

use App\Enums\JobStatusEnum;
use App\Events\JobNotificationToWorker;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\JobReviewRequest;
use App\Events\WorkerCommented;
use App\Events\WorkerUpdatedJobStatus;
use App\Models\Job;
use App\Models\Admin;
use App\Models\SkippedComment;
use App\Models\JobComments;
use App\Models\Notification;
use App\Http\Controllers\Controller;
use App\Jobs\CreateJobOrder;
use App\Jobs\ScheduleNextJobOccurring;
use App\Models\User;
use App\Traits\JobSchedule;
use App\Traits\PaymentAPI;
use App\Traits\PriceOffered;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Google\Cloud\Translate\V2\TranslateClient;
use Illuminate\Support\Facades\Log;

class JobCommentController extends Controller
{
    use PaymentAPI, JobSchedule, PriceOffered;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    private $translateClient;

    public function __construct()
    {
        $this->translateClient = new TranslateClient([
            'key' => config('services.google.translate_key'),
        ]);
    }

    public function index(request $request)
    {
        $comments = JobComments::query()
            ->with(['attachments'])
            ->where('job_id', $request->id)
            ->where(function ($q) {
                $q
                    ->where('comment_for', 'worker')
                    ->orWhereHasMorph(
                        'commenter',
                        [User::class],
                        function (Builder $query) {
                            $query->where('commenter_id', Auth::id());
                        }
                    );
            })
            ->latest()
            ->get();

        // Get the target language and comment ID
        $targetLanguage = $request->input('target_language', 'en');
        $commentId = $request->input('comment_id', null);

        // Translate the specific comment if a target language is provided
        if ($targetLanguage !== 'en' && $commentId) {
            foreach ($comments as $comment) {
                if ($comment->id == $commentId) {
                    $textToTranslate = $comment->comment;
                    if (!empty($textToTranslate)) {
                        try {
                            $translation = $this->translateClient->translate($textToTranslate, [
                                'target' => $targetLanguage,
                            ]);

                            $comment->comment = $translation['text'];
                            $comment->translated_text = $translation['text'];
                        } catch (\Exception $e) {
                            \Log::error('Translation API Error: ' . $e->getMessage());
                            $comment->translated_text = $textToTranslate;
                        }
                    }
                }
            }
        }

        return response()->json([
            'comments' => $comments
        ]);
    }





    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $job = Job::with(['client', 'worker', 'jobservice'])
            ->where('worker_id', Auth::id())
            ->find($request->job_id);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'job_id' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()]);
        }

        $commentIds = $request->input('comment_ids', []);

        // Ensure it is an array even if a single ID is passed
        if (!is_array($commentIds)) {
            $commentIds = [$commentIds];
        }

        // Update the "done" column for each of the checked comments
        JobComments::whereIn('id', $commentIds)->update(['status' => 'complete']);


        $comment = '';
        $filesArr = $request->file('files');
        $isFiles = ($request->hasFile('files') && count($filesArr) > 0);
        if ($isFiles || $request->comment) {
            $comment = JobComments::create([
                'name' => $request->name,
                'job_id' => $job->id,
                'comment_for' => 'admin',
                'comment' => $request->comment ? $request->comment : NULL,
            ]);
            if ($isFiles) {
                if (!Storage::disk('public')->exists('uploads/attachments')) {
                    Storage::disk('public')->makeDirectory('uploads/attachments');
                }
                $resultArr = [];
                foreach ($filesArr as $key => $file) {
                    $original_name = $file->getClientOriginalName();
                    $file_name = $comment->job_id . "_" . date('s') . "_" . $original_name;
                    if (Storage::disk('public')->putFileAs("uploads/attachments", $file, $file_name)) {
                        array_push($resultArr, [
                            'file_name' => $file_name,
                            'original_name' => $original_name
                        ]);
                    }
                }
                $comment->attachments()->createMany($resultArr);
            }
        }

        if (isset($request->status) && $request->status != '') {
            $jobData = [
                'status' => $request->status
            ];

            if ($request->status == JobStatusEnum::COMPLETED) {
                $end_time = $job->start_date."".$job->end_time;
                if ($end_time > now()->toDateTimeString()) {
                    $jobData['completed_at'] = $end_time;
                }else{
                    // $jobData['completed_at'] = now()->toDateTimeString();
                    event(new WhatsappNotificationEvent([
                        "type" => WhatsappMessageTemplateEnum::TEAM_ADJUST_WORKER_JOB_COMPLETED_TIME,
                        "notificationData" => [
                            'job' => $job->toArray(),
                            'complete_time' => now()->toDateTimeString(),
                        ]
                    ]));
                }

                if ($request->status == JobStatusEnum::COMPLETED) {
                    $jobArray = $job->load(['propertyAddress'])->toArray();
                    $worker = $jobArray['worker'];

                    $emailData = [
                        'emailSubject'  => __('mail.job_nxt_step.completed_nxt_step_email_subject'),
                        'emailTitle'  => __('mail.job_nxt_step.completed_nxt_step_email_title'),
                        'emailContent'  => __('mail.job_nxt_step.completed_nxt_step_email_content', ['jobId' => " <b>" . $jobArray['id'] . "</b>"]),
                        'emailContentWa'  => __('mail.job_nxt_step.completed_nxt_step_email_content', ['jobId' => " *" . $jobArray['id'] . "*"]),

                    ];

                    event(new JobNotificationToWorker($worker, $jobArray, $emailData));
                }
            }

            $job->update($jobData);

            event(new WorkerUpdatedJobStatus($job, $comment));

            if ($job->status == JobStatusEnum::COMPLETED) {
                $this->updateJobWorkerMinutes($job->id);
                $this->updateJobAmount($job->id);

                ScheduleNextJobOccurring::dispatch($job->id);
                CreateJobOrder::dispatch($job->id);
            }

            if (now()->hour >= 17 && now()->minute >= 1) {
                event(new JobReviewRequest($job));
            }
        }

        event(new WorkerCommented(Auth::user()->toArray(), $job->toArray()));

        return response()->json([
            'message' => 'Comment has been created successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $comment = JobComments::query()
            ->whereHasMorph(
                'commenter',
                [User::class],
                function (Builder $query) {
                    $query->where('commenter_id', Auth::id());
                }
            )
            ->find($id);

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found'
            ]);
        }

        foreach ($comment->attachments()->get() as $attachment) {
            if (Storage::drive('public')->exists('uploads/attachments/' . $attachment->file_name)) {
                Storage::drive('public')->delete('uploads/attachments/' . $attachment->file_name);
            }
            $attachment->delete();
        }
        $comment->delete();

        return response()->json([
            'message' => 'Comment has been deleted successfully'
        ]);
    }
    public function markComplete(Request $request)
    {
        // Ensure you're receiving the JSON data properly
        $commentId = $request->input('comment_id');
        // Check if the comment ID is received properly
        if (!$commentId) {
            return response()->json([
                'success' => false,
                'message' => 'Comment ID is required',
            ], 400);
        }

        // Find the comment by ID
        $comment = JobComments::find($commentId);

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found',
            ], 404);
        }
        // Toggle the comment status
        if ($comment->status === 'complete') {
            $comment->status = null;  // Set to null if it was complete
        } else {
            $comment->status = 'complete';  // Set to complete if it was null
        }

        $comment->save();

        return response()->json([
            'success' => true,
            'message' => 'Comment status toggled successfully!',
            'new_status' => $comment->status, // Return the new status for feedback
        ]);
    }
    public function adjustJobCompleteTime(Request $request, $id)
    {
        // Validate the input
        $request->validate([
            'action' => 'required|string|in:keep,adjust',
        ]);

        // Fetch the job by ID
        $job = Job::find($id);
        if (!$job) {
            return response()->json(['message' => 'Job not found.'], 404);
        }

        // Check the action and update the completed_at field accordingly
        if ($request->action === 'adjust') {
            // Adjust to the scheduled time
            $job->completed_at = $job->start_date . ' ' . $job->end_time;
        } else if ($request->action === 'keep') {
            // Keep the actual time (set to current time)
            $job->completed_at = Carbon::now()->toDateTimeString();
        }

        // Save the job with the updated time
        $job->save();

        return response()->json(['message' => 'Job time adjusted successfully.'], 200);
    }
}
