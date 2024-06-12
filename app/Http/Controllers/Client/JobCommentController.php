<?php

namespace App\Http\Controllers\Client;

use App\Events\ClientCommented;
use App\Models\JobComments;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Job;
use Illuminate\Database\Eloquent\Builder;
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
    public function index($jobID)
    {
        $comments = JobComments::query()
            ->with(['attachments'])
            ->where('job_id', $jobID)
            ->where(function ($q) {
                $q
                    ->where('comment_for', 'client')
                    ->orWhereHasMorph(
                        'commenter',
                        [Client::class],
                        function (Builder $query) {
                            $query->where('commenter_id', Auth::id());
                        }
                    );
            })
            ->latest()
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
    public function store(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'comment' => ['required']
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()]);
        }

        $job = Job::query()
            ->with('client')
            ->where('client_id', Auth::id())
            ->find($id);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found'
            ], 404);
        }

        $comment = JobComments::create([
            'name' => $request->name,
            'job_id' => $job->id,
            'comment_for' => 'worker',
            'comment' => $request->comment,
        ]);

        $filesArr = $request->file('files');
        if ($request->hasFile('files') && count($filesArr) > 0) {
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

        event(new ClientCommented(Auth::user()->toArray(), $job->toArray()));

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
    public function destroy($jobID, $id)
    {
        $job = Job::query()
            ->where('client_id', Auth::id())
            ->find($jobID);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found'
            ], 404);
        }

        $comment = JobComments::query()
            ->whereHasMorph(
                'commenter',
                [Client::class],
                function (Builder $query) {
                    $query->where('commenter_id', Auth::id());
                }
            )
            ->find($id);

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found'
            ], 404);
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
}
