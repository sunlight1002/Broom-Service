<?php

namespace App\Http\Controllers\Admin;

use App\Events\AdminCommented;
use App\Models\JobComments;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class JobCommentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(request $request)
    {
        $job = Job::find($request->id);

        $comments = $job
            ->comments()
            ->with(['attachments'])
            ->orderBy('id', 'desc')
            ->get();

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
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'job_id' => ['required'],
            'comment_for' => ['required'],
            'comment' => ['required']
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()]);
        }

        $data = $request->all();

        $job = Job::find($data['job_id']);

        $comment = JobComments::create([
            'name' => $data['name'],
            'comment_for' => $data['comment_for'],
            'job_id' => $data['job_id'],
            'comment' => $data['comment'],
        ]);
        $filesArr = $request->file('files');
        if ($request->hasFile('files') && count($filesArr) > 0) {
            if (!Storage::disk('public')->exists('uploads/attachments')) {
                Storage::disk('public')->makeDirectory('uploads/attachments');
            }
            $resultArr = [];
            foreach ($filesArr as $key => $file) {
                $original_name = $file->getClientOriginalName();
                $file_name = $data['job_id'] . "_" . date('s') . "_" . $original_name;

                if (Storage::disk('public')->putFileAs("uploads/attachments", $file, $file_name)) {
                    array_push($resultArr, [
                        'file_name' => $file_name,
                        'original_name' => $original_name
                    ]);
                }
            }
            $comment->attachments()->createMany($resultArr);
        }

        event(new AdminCommented(Auth::user()->toArray(), $job->toArray()));

        return response()->json([
            'message' => 'Comments has been created successfully'
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
        $commentObj = JobComments::find($id);
        foreach ($commentObj->attachments()->get() as $attachment) {
            if (Storage::drive('public')->exists('uploads/attachments/' . $attachment->file_name)) {
                Storage::drive('public')->delete('uploads/attachments/' . $attachment->file_name);
            }
            $attachment->delete();
        }
        $commentObj->delete();

        return response()->json([
            'message' => 'Comments has been deleted successfully'
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

        $end_time = $job->start_date." ".$job->end_time;

        // Check the action and update the completed_at field accordingly
        if ($request->action === 'adjust') {
            // Adjust to the scheduled time
            $job->is_extended = true;
        } else if ($request->action === 'keep') {
            // Keep the actual time (set to current time)
            $job->completed_at = $end_time;
            $job->is_extended = false;
        }

        // Save the job with the updated time
        $job->save();

        return response()->json(['message' => 'Job time adjusted successfully.'], 200);
    }
}
