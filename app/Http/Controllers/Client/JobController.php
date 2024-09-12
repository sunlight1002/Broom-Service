<?php

namespace App\Http\Controllers\Client;

use App\Enums\CancellationActionEnum;
use App\Enums\JobStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\ClientLeadStatusChanged;
use App\Events\ClientReviewed;
use App\Events\WhatsappNotificationEvent;
use App\Http\Controllers\Controller;
use App\Jobs\CreateJobOrder;
use App\Jobs\GenerateJobInvoice;
use App\Jobs\ScheduleNextJobOccurring;
use App\Models\Admin;
use App\Models\User;
use App\Models\Problems;
use App\Models\Job;
use App\Models\JobCancellationFee;
use App\Models\Notification;
use App\Traits\JobSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Events\JobNotificationToAdmin;
use App\Events\JobWorkerChanged;
use App\Models\ManageTime;
use App\Events\JobNotificationToWorker;
use App\Enums\OrderPaidStatusEnum;
use App\Models\Client;
use Illuminate\Support\Facades\Http;
use Yajra\DataTables\Facades\DataTables;



class JobController extends Controller
{
    use JobSchedule;
    protected $whapiApiEndpoint;
    protected $whapiApiToken;

    public function __construct()
    {
        $this->whapiApiEndpoint = config('services.whapi.url');
        $this->whapiApiToken = config('services.whapi.token');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $service_column = Auth::user()->lng == 'en' ? 'job_services.name' : 'job_services.heb_name';

        $query = Job::query()
            ->leftJoin('client_property_addresses', 'jobs.address_id', '=', 'client_property_addresses.id')
            ->leftJoin('job_services', 'job_services.job_id', '=', 'jobs.id')
            ->leftJoin('services', 'job_services.service_id', '=', 'services.id')
            ->where('jobs.client_id', Auth::user()->id)
            ->select('jobs.id', 'jobs.start_date', 'jobs.shifts', 'jobs.status', 'jobs.total_amount', 'jobs.is_order_generated', 'jobs.job_group_id', 'client_property_addresses.address_name', 'client_property_addresses.latitude', 'client_property_addresses.longitude', 'jobs.start_time', 'client_property_addresses.geo_address')
            ->selectRaw("$service_column AS service_name")
            ->groupBy('jobs.id');

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                if (request()->has('search')) {
                    $keyword = request()->get('search')['value'];

                    if (!empty($keyword)) {
                        $query->where(function ($sq) use ($keyword) {
                            $sq
                                ->where('job_services.name', 'like', "%" . $keyword . "%")
                                ->orWhere('job_services.heb_name', 'like', "%" . $keyword . "%")
                                ->orWhere('jobs.shifts', 'like', "%" . $keyword . "%");
                        });
                    }
                }
            })
            ->editColumn('start_date', function ($data) {
                return $data->start_date ? Carbon::parse($data->start_date)->format('d M, Y') : '-';
            })
            ->editColumn('start_time', function ($data) {
                if (!$data->start_time) {
                    return '-';
                } else {
                    $hour1 = Carbon::now()->setTimeFromTimeString($data->start_time)->format('H:i');
                    $hour2 = Carbon::now()->setTimeFromTimeString($data->start_time)->addHours(2)->format('H:i');

                    return $hour1 . ' - ' . $hour2;
                }
            })
            ->editColumn('comment', function ($data) {
                return $data->comment ? $data->comment : '-';
            })
            ->addColumn('action', function ($data) {
                return '';
            })
            ->rawColumns(['action'])
            ->toJson();
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
                'propertyAddress'
            ])
            ->where('client_id', Auth::user()->id)
            ->find($id);

        return response()->json([
            'job' => $job,
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $job = Job::with('client')
            ->where('client_id', Auth::user()->id)
            ->find($id);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found'
            ], 404);
        }

        $requestedJob = $job;

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

        $repeatancy = $request->get('repeatancy');
        $until_date = $request->get('until_date');

        $jobs = Job::query()
            ->with(['client', 'offer', 'worker', 'jobservice'])
            ->whereIn('status', [
                JobStatusEnum::SCHEDULED,
                JobStatusEnum::UNSCHEDULED,
            ])
            ->when($repeatancy == 'until_date', function ($q) use ($until_date) {
                return $q->whereDate('start_date', '<=', $until_date);
            })
            ->when($repeatancy == 'one_time', function ($q) use ($id) {
                return $q->where('id', $id);
            })
            ->where('job_group_id', $job->job_group_id)
            ->get();

        $admin = Admin::where('role', 'admin')->first();

        foreach ($jobs as $key => $job) {
            $feePercentage = Carbon::parse($job->start_date)->diffInDays(today(), false) <= -1 ? 50 : 100;
            $feeAmount = ($feePercentage / 100) * $job->total_amount;

            \Log::info("JobCancellationFee Save for Job : ". $job->id);

            JobCancellationFee::create([
                'job_id' => $job->id,
                'job_group_id' => $job->job_group_id,
                'cancellation_fee_percentage' => $feePercentage,
                'cancellation_fee_amount' => $feeAmount,
                'cancelled_user_role' => 'client',
                'cancelled_by' => Auth::user()->id,
                'action' => CancellationActionEnum::CANCELLATION,
                'duration' => $repeatancy,
                'until_date' => $until_date,
            ]);

            $job->update([
                'status' => JobStatusEnum::CANCEL,
                'cancellation_fee_percentage' => $feePercentage,
                'cancellation_fee_amount' => $feeAmount,
                'cancelled_by_role' => 'client',
                'cancelled_by' => Auth::user()->id,
                'cancelled_at' => now(),
                'cancelled_for' => $repeatancy,
                'cancel_until_date' => $until_date,
            ]);
            $job->load(['client', 'worker', 'jobservice', 'propertyAddress']);

            \Log::info("CreateJobOrder dispatch from cancel job");

            CreateJobOrder::dispatch($job->id);

            \Log::info("CreateJobOrder dispatch from cancel job end here");

            ScheduleNextJobOccurring::dispatch($job->id);

            Notification::create([
                'user_id' => $job->client->id,
                'user_type' => get_class($job->client),
                'type' => NotificationTypeEnum::CLIENT_CANCEL_JOB,
                'job_id' => $job->id,
                'status' => 'declined'
            ]);

            App::setLocale('en');
            $data = array(
                'by'         => 'client',
                'email'      => $admin->email,
                'admin'      => $admin->toArray(),
                'job'        => $job->toArray(),
            );

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::ADMIN_JOB_STATUS_NOTIFICATION,
                "notificationData" => array(
                    'by'         => 'client',
                    'job'        => $job->toArray(),
                )
            ]));
            // Mail::send('/ClientPanelMail/JobStatusNotification', $data, function ($messages) use ($data) {
            //     $messages->to($data['email']);
            //     $sub = __('mail.client_job_status.subject');
            //     $messages->subject($sub);
            // });

            //send notification to admin
            $emailContent = '';
            if ($data['by'] == 'client') {
                $emailContent .=  __('mail.client_job_status.content') . ' ' . ucfirst($job->status) . '.';
                if ($job->cancellation_fee_amount) {
                    $emailContent .= __('mail.client_job_status.cancellation_fee') . ' ' . $job->cancellation_fee_amount . 'ILS.';
                }
            } else {
                $emailContent .= 'Job is marked as' . ucfirst($job->status) . 'by admin/team.';
            }

            $emailSubject = ($data['by'] == 'admin') ?
                ('Job has been cancelled') . " #" . $job->id :
                __('mail.client_job_status.subject') . " #" . $job->id;

            $adminEmailData = [
                'emailData' => [
                    'job' => $job->toArray(),
                ],
                'emailSubject'  => $emailSubject,
                'emailTitle'    => 'Job Status',
                'emailContent'  => $emailContent
            ];
            event(new JobNotificationToAdmin($adminEmailData));

            //send notification to worker
            $job = $job->toArray();
            $worker = $job['worker'];
            if($worker) {
                $emailData = [
                    'emailSubject'  => $emailSubject,
                    'emailTitle'  => __('mail.job_common.job_status'),
                    'emailContent'  => $emailContent
                ];
                event(new JobNotificationToWorker($worker, $job, $emailData));
            }
        }

        $monthEndDate = Carbon::parse($requestedJob->start_date)->endOfMonth()->toDateString();

        $upcomingJobCountInCurrentMonth = Job::query()
            ->where('client_id', $client->id)
            ->whereDate('start_date', '>=', $requestedJob->start_date)
            ->where(function ($q) use ($monthEndDate) {
                $q->whereDate('start_date', '<=', $monthEndDate)
                    ->orWhereDate('next_start_date', '<=', $monthEndDate);
            })
            ->whereIn('status', [
                JobStatusEnum::PROGRESS,
                JobStatusEnum::SCHEDULED,
                JobStatusEnum::UNSCHEDULED
            ])
            ->where('is_paid', false)
            ->count();

        if($upcomingJobCountInCurrentMonth <= 0) {
            $completedJobs = Job::query()
                ->where('status', JobStatusEnum::COMPLETED)
                ->where('job_group_id', $requestedJob->job_group_id)
                ->where('is_paid', false)
                ->get();

            foreach ($completedJobs as $key => $completedJob) {
                if($completedJob->order->paid_status != OrderPaidStatusEnum::PAID) {
                    GenerateJobInvoice::dispatch($completedJob->order->id);
                }
            }
        }

        return response()->json([
            'job' => $job,
        ]);
    }

    public function changeWorker(Request $request, $id)
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

        if (Carbon::parse($data['worker']['date'])->isPast()) {
            return response()->json([
                'message' => 'New date should be in future'
            ], 403);
        }

        $oldWorker = $job->worker;

        $old_job_data = [
            'start_date' => $job->start_date,
            'start_time' => $job->start_time,
            'shifts' => $job->shifts,
        ];

        $manageTime = ManageTime::first();
        $workingWeekDays = json_decode($manageTime->days);

        $repeat_value = $job->jobservice->period;

        $job_date = Carbon::parse($data['worker']['date']);
        $preferredWeekDay = strtolower($job_date->format('l'));
        $next_job_date = $this->scheduleNextJobDate($job_date, $repeat_value, $preferredWeekDay, $workingWeekDays);

        $job_date = $job_date->toDateString();

        $slots = explode(',', $data['worker']['shifts']);
        // sort slots in ascending order of time before merging for continuous time
        sort($slots);

        $shiftFormattedArr = [];
        foreach ($slots as $key => $shift) {
            $timing = explode('-', $shift);

            $start_time = Carbon::createFromFormat('H:i', $timing[0])->toTimeString();
            $end_time = Carbon::createFromFormat('H:i', $timing[1])->toTimeString();

            $shiftFormattedArr[$key] = [
                'starting_at' => Carbon::parse($job_date . ' ' . $start_time)->toDateTimeString(),
                'ending_at' => Carbon::parse($job_date . ' ' . $end_time)->toDateTimeString()
            ];
        }

        $mergedContinuousTime = $this->mergeContinuousTimes($shiftFormattedArr);

        $slotsInString = '';
        foreach ($mergedContinuousTime as $key => $slot) {
            if (!empty($slotsInString)) {
                $slotsInString .= ',';
            }
            $slotsInString .= Carbon::parse($slot['starting_at'])->format('H:i') . '-' . Carbon::parse($slot['ending_at'])->format('H:i');
        }

        $minutes = 0;
        foreach ($mergedContinuousTime as $key => $value) {
            $minutes += Carbon::parse($value['ending_at'])->diffInMinutes(Carbon::parse($value['starting_at']));
        }

        $status = JobStatusEnum::SCHEDULED;

        if ($this->isJobTimeConflicting($mergedContinuousTime, $job_date, $data['worker']['worker_id'])) {
            $status = JobStatusEnum::UNSCHEDULED;
        }

        $start_time = Carbon::parse($mergedContinuousTime[0]['starting_at'])->toTimeString();
        $end_time = Carbon::parse($mergedContinuousTime[count($mergedContinuousTime) - 1]['ending_at'])->toTimeString();

        $jobData = [
            'worker_id'     => $data['worker']['worker_id'],
            'start_date'    => $job_date,
            'start_time'    => $start_time,
            'end_time'      => $end_time,
            'shifts'        => $slotsInString,
            'status'        => $status,
            'next_start_date'   => $next_job_date,
        ];

        if ($data['repeatancy'] == 'one_time') {
            $jobData['previous_worker_id'] = $job->worker_id;
            $jobData['previous_worker_after'] = NULL;
            $jobData['previous_shifts'] = $job->shifts;
            $jobData['previous_shifts_after'] = NULL;
        } else if ($data['repeatancy'] == 'until_date') {
            $jobData['previous_worker_id'] = $job->worker_id;
            $jobData['previous_worker_after'] = $data['until_date'];
            $jobData['previous_shifts'] = $job->shifts;
            $jobData['previous_shifts_after'] = $data['until_date'];
        } else if ($data['repeatancy'] == 'forever') {
            $jobData['previous_worker_id'] = NULL;
            $jobData['previous_worker_after'] = NULL;
            $jobData['previous_shifts'] = NULL;
            $jobData['previous_shifts_after'] = NULL;
        }

        $job->update($jobData);

        $job->jobservice()->update([
            'duration_minutes'  => $minutes,
            'config'            => [
                'cycle'             => $job->jobservice->cycle,
                'period'            => $job->jobservice->period,
                'preferred_weekday' => $preferredWeekDay
            ]
        ]);

        $job->workerShifts()->delete();
        foreach ($mergedContinuousTime as $key => $shift) {
            $job->workerShifts()->create($shift);
        }

        $feePercentage = Carbon::parse($job->start_date)->diffInDays(today(), false) <= -1 ? 50 : 100;
        $feeAmount = ($feePercentage / 100) * $job->total_amount;

        JobCancellationFee::create([
            'job_id' => $job->id,
            'job_group_id' => $job->job_group_id,
            'cancellation_fee_percentage' => $feePercentage,
            'cancellation_fee_amount' => $feeAmount,
            'cancelled_user_role' => 'client',
            'cancelled_by' => Auth::user()->id,
            'action' => CancellationActionEnum::CHANGE_WORKER,
            'duration' => $data['repeatancy'],
            'until_date' => $data['until_date'],
        ]);

        $newLeadStatus = $this->getClientLeadStatusBasedOnJobs($client);

        if (!$client->lead_status || $client->lead_status->lead_status != $newLeadStatus) {
            $client->lead_status()->updateOrCreate(
                [],
                ['lead_status' => $newLeadStatus]
            );

            event(new ClientLeadStatusChanged($client, $newLeadStatus));

            $emailData = [
                'client' => $client->toArray(),
                'status' => $newLeadStatus,
            ];

            if($newLeadStatus === 'freeze client'){
                // Trigger WhatsApp Notification
                event(new WhatsappNotificationEvent([
                   "type" => WhatsappMessageTemplateEnum::CLIENT_IN_FREEZE_STATUS,
                   "notificationData" => [
                       'client' => $client->toArray(),
                   ]
               ]));
           }
            
           if ($client->notification_type === "both") {
            if ($newLeadStatus === 'unanswered') {

                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));
                App::setLocale($client['lng']);
                Mail::send('Mails.UnansweredLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                    $messages->to($emailData['client']['email']);
                    $sub = __('mail.unanswered_lead.header');
                    $messages->subject($sub);
                });
            }
            
            if ($newLeadStatus === 'irrelevant') {

                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));
                App::setLocale($client['lng']);
                Mail::send('Mails.IrrelevantLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                    $messages->to($emailData['client']['email']);
                    $sub = __('mail.irrelevant_lead.header');
                    $messages->subject($sub);
                });
            }; 
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::USER_STATUS_CHANGED,
                    "notificationData" => [
                        'client' => $client->toArray(),
                        'status' => $newLeadStatus,
                    ]
                ]));
            
        } elseif ($client->notification_type === "email") {
            if ($newLeadStatus === 'unanswered') {
                App::setLocale($client['lng']);
                Mail::send('Mails.UnansweredLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                    $messages->to($emailData['client']['email']);
                    $sub = __('mail.unanswered_lead.header');
                    $messages->subject($sub);
                });
            }
            if ($newLeadStatus === 'irrelevant') {
                App::setLocale($client['lng']);
                Mail::send('Mails.IrrelevantLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                    $messages->to($emailData['client']['email']);
                    $sub = __('mail.irrelevant_lead.header');
                    $messages->subject($sub);
                });
            }

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::USER_STATUS_CHANGED,
                "notificationData" => [
                    'client' => $client->toArray(),
                    'status' => $newLeadStatus,
                ]
            ]));
            
        } else {
            if ($newLeadStatus === 'unanswered') {

                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));
            }
            if ($newLeadStatus === 'irrelevant') {

                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));
            }
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::USER_STATUS_CHANGED,
                    "notificationData" => [
                        'client' => $client->toArray(),
                        'status' => $newLeadStatus,
                    ]
                ]));
            }
        }

        Notification::create([
            'user_id' => $job->client->id,
            'user_type' => get_class($job->client),
            'type' => NotificationTypeEnum::JOB_SCHEDULE_CHANGE,
            'job_id' => $job->id,
            'status' => 'changed'
        ]);

        $job->load(['client', 'worker', 'jobservice', 'propertyAddress']);

        event(new JobWorkerChanged(
            $job,
            $mergedContinuousTime[0]['starting_at'],
            $old_job_data,
            $oldWorker,
            true
        ));

        return response()->json([
            'message' => 'Worker changed successfully'
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
            'review' => $data['review'],
            'client_reviewed_at' => now()->toDateTimeString()
        ]);

        event(new ClientReviewed($job->client, $job));

        return response()->json([
            'message' => 'Job rating submitted successfully',
        ]);
    }

    public function getOpenJobAmountByGroup(Request $request, $id)
    {
        $groupID = $request->get('group_id');
        $repeatancy = $request->get('repeatancy');
        $until_date = $request->get('until_date');

        $jobs = Job::query()
            ->where('client_id', Auth::user()->id)
            ->whereIn('status', [
                JobStatusEnum::SCHEDULED,
                JobStatusEnum::UNSCHEDULED,
            ])
            ->when($repeatancy == 'until_date' && $until_date, function ($q) use ($until_date) {
                return $q->whereDate('start_date', '<=', $until_date);
            })
            ->when($repeatancy == 'one_time', function ($q) use ($id) {
                return $q->where('id', $id);
            })
            ->where('job_group_id', $groupID)
            ->selectRaw("SUM(total_amount) as total_amount")
            ->first();

        return response()->json([
            'total_amount' => $jobs->total_amount
        ]);
    }

    public function addProblems(Request $request)
    {
        $validated = $request->validate([
            'problem' => 'required|string|max:1000',
        ]);
    
        $client = Client::with('property_addresses')->find($request->input('client_id'));
        $worker = User::find($request->input('worker_id'));

        $problem = new Problems();
        $problem->client_id = $client->id;
        $problem->job_id = $request->input('job_id');
        $problem->worker_id = $request->input('worker_id');
        $problem->problem = $validated['problem'];
        $problem->save();
    
        $receiverNumber = config('services.whatsapp_groups.problem_with_workers');
        $text = '*Worker Speak To Manager | Broom Service*';
    
        $text .= "\n\nHi, everyone\n\n";
        
        $text .= 'The Worker Need to Speak with Manager.' . "\n\n";
        
        $text .= sprintf(
            "Date/Time: %s\nClient: %s\nWorker: %s\nProperty: %s",
            Carbon::now()->format('M d Y H:i'),
            $client->firstname . ' ' . $client->lastname,
            $worker->firstname . ' ' . $worker->lastname ?? 'NA',
            $client->property_addresses->first()->address_name ?? 'NA'
        );
    
        $response = Http::withToken($this->whapiApiToken)
            ->post($this->whapiApiEndpoint . 'messages/text', [
                'to' => $receiverNumber,
                'body' => $text  
            ]);
    
        if ($response->successful()) {
            return response()->json(['message' => 'Problem saved successfully'], 201);
        } else {
            return response()->json(['error' => 'Failed to send WhatsApp message'], $response->status());
        }
    }
    
    public function getProblems(Request $request)
    {
        // Query the Problems table with related client and worker details
        $query = Problems::with(['client', 'client.property_addresses', 'worker']); // Assuming 'worker' is the relationship name
        
        // Apply filters if present
        if (!empty($request->input('client_id'))) {
            $query->where('client_id', $request->input('client_id'));
        }
        
        if (!empty($request->input('job_id'))) {
            $query->where('job_id', $request->input('job_id'));
        }
        
        // Execute the query and retrieve the results
        $problems = $query->get();
        
        // Transform the result if needed
        $problems = $problems->map(function ($problem) {
            return [
                'id' => $problem->id,
                'problem' => $problem->problem,
                'created_at' => $problem->created_at->format('M d Y H:i'),
                'client' => [
                    'id' => $problem->client->id,
                    'name' => $problem->client->firstname . ' ' . $problem->client->lastname,
                    'address' => $problem->client->property_addresses->first()->address_name ?? 'NA',
                ],
                'worker' => [
                    'id' => $problem->worker->id ?? 'NA',
                    'firstname' => $problem->worker->firstname ?? 'NA',
                    'lastname' => $problem->worker->lastname ?? 'NA',
                ],
                'job_id' => $problem->job_id,
            ];
        });
        
        return response()->json(['problems' => $problems], 200);
    }

    // public function deleteProblem($id)
    // {
    //     \Log::info($id); // Log the id
    
    //     // Find the problem entry
    //     $problem = Problems::find($id);
    
    //     if (!$problem) {
    //         return response()->json(['error' => 'Problem not found'], 404);
    //     }
    
    //     // Delete the problem entry
    //     try {
    //         $problem->delete();
    //         return response()->json(['message' => 'Problem deleted successfully'], 200);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => 'Failed to delete problem'], 500);
    //     }
    // }
    
}
