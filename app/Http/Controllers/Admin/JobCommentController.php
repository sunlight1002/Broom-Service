<?php

namespace App\Http\Controllers\Admin;

use App\Models\JobComments;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Comment;

class JobCommentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(request $request)
    {
        $client_comments = JobComments::with(['comments'])->where('job_id', $request->id)->where('role', 'client')->orderBy('id', 'desc')->get();
        $worker_comments = JobComments::with(['comments'])->where('job_id', $request->id)->where('role', 'worker')->orderBy('id', 'desc')->get();

        return response()->json([
            'client_comments' => $client_comments,
            'worker_comments' => $worker_comments
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
            'role' => ['required'],
            'comment' => ['required']
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()]);
        }

        $data = $request->all();

        $comment = JobComments::create([
            'name' => $data['name'],
            'role' => $data['role'],
            'job_id' => $data['job_id'],
            'comment' => $data['comment'],
        ]);
        $filesArr = $request->file('files');
        if($request->hasFile('files') && count($filesArr) > 0){
            if (!Storage::disk('public')->exists('uploads/comments')) {
                Storage::disk('public')->makeDirectory('uploads/comments');
            }
            $resultArr = [];
            foreach ($filesArr as $key => $file) {
                $file_name = $file->getClientOriginalName();
                if (Storage::disk('public')->putFileAs("uploads/comments", $file, $file_name)) {
                    array_push($resultArr,['file' => $file_name]);
                }
            }
            $comment->comments()->createMany($resultArr);
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
        foreach($commentObj->comments as $comment)
        {
            if (Storage::drive('public')->exists('uploads/comments/' . $comment->file)) {
                Storage::drive('public')->delete('uploads/comments/' . $comment->file);
            }
            $comment->delete();
        }
        $commentObj->delete();

        return response()->json([
            'message' => 'Comments has been deleted successfully'
        ]);
    }
}
