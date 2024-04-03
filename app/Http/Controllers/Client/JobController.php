<?php

namespace App\Http\Controllers\Client;

use App\Enums\JobStatusEnum;
use App\Models\JobComments;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Job;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class JobController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $jobs = Job::query()
            ->with(['offer', 'client', 'worker', 'jobservice', 'propertyAddress'])
            ->where('client_id', $request->cid)
            ->orderBy('start_date')
            ->get();

        return response()->json([
            'jobs' => $jobs,
        ]);
    }

    public function show(Request $request)
    {
        $job = Job::query()
            ->with(['client', 'worker', 'service', 'offer', 'jobservice', 'propertyAddress'])
            ->find($request->id);

        return response()->json([
            'job' => $job,
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $job = Job::with(['client', 'offer', 'worker', 'jobservice'])
            ->find($id);

        $feePercentage = Carbon::parse($job->start_date)->diffInDays(today(), false) <= -1 ? 50 : 100;
        $feeAmount = ($feePercentage / 100) * $job->offer->total;

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

        Notification::create([
            'user_id' => $job->client->id,
            'type' => 'client-cancel-job',
            'job_id' => $job->id,
            'status' => 'declined'
        ]);

        $admin = Admin::find(1)->first();
        App::setLocale('en');
        $data = array(
            'by'         => 'client',
            'email'      => $admin->email,
            'admin'      => $admin->toArray(),
            'job'        => $job->toArray(),
        );

        Mail::send('/ClientPanelMail/JobStatusNotification', $data, function ($messages) use ($data) {
            $messages->to($data['email']);
            $sub = __('mail.client_job_status.subject');
            $messages->subject($sub);
        });

        return response()->json([
            'job' => $job,
        ]);
    }
}
