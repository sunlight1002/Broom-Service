<?php

namespace App\Http\Controllers\Client;

use App\Models\JobComments;
use App\Http\Controllers\Controller;
use App\Models\Client;
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
    public function index(request $request)
    {
        $jobID = base64_decode($request->id);

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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'job_id' => ['required'],
            'comment' => ['required']
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()]);
        }
        $comment = new JobComments();
        $comment->name = $request->name;
        $comment->job_id = base64_decode($request->job_id);
        $comment->comment_for = 'worker';
        $comment->comment = $request->comment;
        $comment->save();
        $filesArr = $request->file('files');
        if ($request->hasFile('files') && count($filesArr) > 0) {
            if (!Storage::disk('public')->exists('uploads/attachments')) {
                Storage::disk('public')->makeDirectory('uploads/attachments');
            }
            $resultArr = [];
            foreach ($filesArr as $key => $file) {
                $file_name = $comment->job_id . "_" . date('s') . "_" . $file->getClientOriginalName();
                if (Storage::disk('public')->putFileAs("uploads/attachments", $file, $file_name)) {
                    array_push($resultArr, ['file' => $file_name]);
                }
            }
            $comment->attachments()->createMany($resultArr);
        }

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
                [Client::class],
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
            if (Storage::drive('public')->exists('uploads/attachments/' . $attachment->file)) {
                Storage::drive('public')->delete('uploads/attachments/' . $attachment->file);
            }
            $attachment->delete();
        }
        $comment->delete();

        return response()->json([
            'message' => 'Comment has been deleted successfully'
        ]);
    }
}
