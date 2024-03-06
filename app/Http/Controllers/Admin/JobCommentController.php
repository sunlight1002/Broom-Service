<?php

namespace App\Http\Controllers\Admin;

use App\Models\JobComments;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobCommentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(request $request)
    {
        $client_comments = JobComments::where('job_id', $request->id)->where('role', 'client')->orderBy('id', 'desc')->get();
        $worker_comments = JobComments::where('job_id', $request->id)->where('role', 'worker')->orderBy('id', 'desc')->get();

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

        JobComments::create([
            'name' => $request->name,
            'role' => $request->role,
            'job_id' => $request->job_id,
            'comment' => $request->comment,
        ]);

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
        $comment = JobComments::find($id);
        $comment->delete();

        return response()->json([
            'message' => 'Comments has been deleted successfully'
        ]);
    }
}
