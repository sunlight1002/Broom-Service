<?php

namespace App\Http\Controllers\Admin;

use App\Models\JobComments;
use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;
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
                $file_name = $data['job_id'] . "_" . date('s') . "_" . $file->getClientOriginalName();
                if (Storage::disk('public')->putFileAs("uploads/attachments", $file, $file_name)) {
                    array_push($resultArr, ['file' => $file_name]);
                }
            }
            $comment->attachments()->createMany($resultArr);
        }
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
            if (Storage::drive('public')->exists('uploads/attachments/' . $attachment->file)) {
                Storage::drive('public')->delete('uploads/attachments/' . $attachment->file);
            }
            $attachment->delete();
        }
        $commentObj->delete();

        return response()->json([
            'message' => 'Comments has been deleted successfully'
        ]);
    }
}
