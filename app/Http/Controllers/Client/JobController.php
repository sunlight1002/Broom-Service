<?php

namespace App\Http\Controllers\Client;

use App\Enums\CancellationActionEnum;
use App\Enums\ChangeWorkerRequestStatusEnum;
use App\Enums\JobStatusEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\WhatsappNotificationEvent;
use App\Http\Controllers\Controller;
use App\Jobs\CreateJobOrder;
use App\Jobs\ScheduleNextJobOccurring;
use App\Models\Admin;
use App\Models\Job;
use App\Models\JobCancellationFee;
use App\Models\Notification;
use App\Traits\JobSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class JobController extends Controller
{
    use JobSchedule;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $jobs = Job::query()
            ->with(['offer', 'client', 'worker', 'jobservice', 'propertyAddress'])
            ->where('client_id', Auth::user()->id)
            ->orderBy('start_date')
            ->get();

        return response()->json([
            'jobs' => $jobs,
        ]);
    }

    public function show(Request $request, $id)
    {
        $job = Job::query()
            ->with([
                'client',
                'worker',
                'service',
                'offer',
                'jobservice',
                'propertyAddress',
                'changeWorkerRequests'
            ])
            ->where('client_id', Auth::user()->id)
            ->find($id);

        return response()->json([
            'job' => $job,
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $job = Job::with(['client', 'offer', 'worker', 'jobservice'])
            ->where('client_id', Auth::user()->id)
            ->find($id);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found'
            ], 404);
        }

        $client = $job->client;
        if (!$client) {
            return response()->json([
                'message' => 'Client not found'
            ], 404);
        }

        if (
            $job->status == JobStatusEnum::COMPLETED ||
            $job->is_job_done
        ) {
            return response()->json([
                'message' => 'Job already completed',
            ], 403);
        }

        if ($job->status == JobStatusEnum::PROGRESS) {
            return response()->json([
                'message' => 'Job is in progress',
            ], 403);
        }

        if ($job->status == JobStatusEnum::CANCEL) {
            return response()->json([
                'message' => 'Job already cancelled'
            ], 403);
        }

        $feePercentage = Carbon::parse($job->start_date)->diffInDays(today(), false) <= -1 ? 50 : 100;
        $feeAmount = ($feePercentage / 100) * $job->offer->total;

        JobCancellationFee::create([
            'job_id' => $job->id,
            'cancellation_fee_percentage' => $feePercentage,
            'cancellation_fee_amount' => $feeAmount,
            'cancelled_user_role' => 'client',
            'cancelled_by' => Auth::user()->id,
            'action' => CancellationActionEnum::CANCELLATION,
            'duration' => $request->repeatancy,
            'until_date' => $request->until_date,
        ]);

        $job->update([
            'status' => JobStatusEnum::CANCEL,
            'cancellation_fee_percentage' => $feePercentage,
            'cancellation_fee_amount' => $feeAmount,
            'cancelled_by_role' => 'client',
            'cancelled_by' => Auth::user()->id,
            'cancelled_at' => now(),
            'cancelled_for' => $request->repeatancy,
            'cancel_until_date' => $request->until_date,
        ]);

        CreateJobOrder::dispatch($job->id);
        ScheduleNextJobOccurring::dispatch($job->id);

        Notification::create([
            'user_id' => $job->client->id,
            'type' => 'client-cancel-job',
            'job_id' => $job->id,
            'status' => 'declined'
        ]);

        $admin = Admin::where('role', 'admin')->first();
        App::setLocale('en');
        $data = array(
            'by'         => 'client',
            'email'      => $admin->email,
            'admin'      => $admin->toArray(),
            'job'        => $job->toArray(),
        );
        if (isset($data['admin']) && !empty($data['admin']['phone'])) {
            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::CLIENT_JOB_STATUS_NOTIFICATION,
                "notificationData" => $data
            ]));
        }
        Mail::send('/ClientPanelMail/JobStatusNotification', $data, function ($messages) use ($data) {
            $messages->to($data['email']);
            $sub = __('mail.client_job_status.subject');
            $messages->subject($sub);
        });

        return response()->json([
            'job' => $job,
        ]);
    }

    public function changeWorkerRequest(Request $request, $id)
    {
        $data = $request->all();

        if (!in_array($data['repeatancy'], ['one_time', 'until_date', 'forever'])) {
            return response()->json([
                'message' => "Repeatancy is invalid",
            ], 422);
        }

        $job = Job::query()
            ->with([
                'client',
                'worker',
                'jobservice',
            ])
            ->where('client_id', Auth::user()->id)
            ->find($id);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found'
            ], 404);
        }

        if (
            $job->status == JobStatusEnum::COMPLETED ||
            $job->is_job_done
        ) {
            return response()->json([
                'message' => 'Job already completed',
            ], 403);
        }

        if ($job->status == JobStatusEnum::CANCEL) {
            return response()->json([
                'message' => 'Job already cancelled',
            ], 403);
        }

        if ($job->status == JobStatusEnum::PROGRESS) {
            return response()->json([
                'message' => 'Job is in progress',
            ], 403);
        }

        $client = $job->client;
        if (!$client) {
            return response()->json([
                'message' => 'Client not found'
            ], 404);
        }

        if ($job->changeWorkerRequests()->where('status', ChangeWorkerRequestStatusEnum::PENDING)->exists()) {
            return response()->json([
                'message' => 'Change worker request already pending'
            ], 403);
        }

        if (Carbon::parse($data['worker']['date'])->isPast()) {
            return response()->json([
                'message' => 'New date should be in future'
            ], 403);
        }

        $job->changeWorkerRequests()->create([
            'client_id' => $client->id,
            'worker_id' => $data['worker']['worker_id'],
            'date' => $data['worker']['date'],
            'shifts' => $data['worker']['shifts'],
            'repeatancy' => $data['repeatancy'],
            'repeat_until_date' => $data['until_date'],
            'status' => ChangeWorkerRequestStatusEnum::PENDING
        ]);

        $admins = Admin::where('role', 'admin')->get();

        $job->load(['client', 'worker', 'jobservice', 'propertyAddress']);
        foreach ($admins as $key => $admin) {
            // App::setLocale($admin->lng);

            $emailData = array(
                'email' => $admin->email,
                'admin' => $admin->toArray(),
                'job' => $job->toArray(),
            );
            if (isset($emailData['admin']) && !empty($emailData['admin']['phone'])) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::WORKER_CHANGE_REQUEST,
                    "notificationData" => $emailData
                ]));
            }
            Mail::send('/Mails/WorkerChangeRequestMail', $emailData, function ($messages) use ($emailData) {
                $messages->to($emailData['email']);
                $messages->subject(__('mail.change_worker_request.subject'));
            });
        }

        return response()->json([
            'message' => 'Change worker request sent successfully'
        ]);
    }

    public function saveReview(Request $request, $id)
    {
        $job = Job::query()
            ->where('client_id', Auth::user()->id)
            ->find($id);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found'
            ], 404);
        }

        if (
            $job->status != JobStatusEnum::COMPLETED ||
            !$job->is_job_done
        ) {
            return response()->json([
                'message' => 'Job not completed yet',
            ], 403);
        }

        if ($job->rating) {
            return response()->json([
                'message' => 'Job rating already submitted',
            ], 403);
        }

        $data = $request->all();

        $job->update([
            'rating' => $data['rating'],
            'review' => $data['review']
        ]);

        return response()->json([
            'message' => 'Job rating submitted successfully',
        ]);
    }
}
