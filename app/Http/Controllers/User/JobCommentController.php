<?php

namespace App\Http\Controllers\User;

use App\Enums\JobStatusEnum;
use App\Events\WorkerUpdatedJobStatus;
use App\Models\Job;
use App\Models\Admin;
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

class JobCommentController extends Controller
{
    use PaymentAPI, JobSchedule, PriceOffered;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
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
            'comment' => ['required']
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()]);
        }

        $comment = JobComments::create([
            'name' => $request->name,
            'job_id' => $job->id,
            'comment_for' => 'admin',
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

        if (isset($request->status) && $request->status != '') {
            $job->update([
                'status' => $request->status
            ]);

            event(new WorkerUpdatedJobStatus($job, $comment));

            if ($job->status == JobStatusEnum::COMPLETED) {
                $this->updateJobWorkerMinutes($job->id);
                $this->updateJobAmount($job->id);

                ScheduleNextJobOccurring::dispatch($job->id);
                CreateJobOrder::dispatch($job->id);
            }
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
}
