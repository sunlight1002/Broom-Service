<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContractStatusEnum;
use App\Enums\LeadStatusEnum;
use App\Exports\ClientSampleFileExport;
use App\Http\Controllers\Controller;
use App\Jobs\ImportClientJob;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Files;
use App\Models\Note;
use App\Models\Offer;
use App\Models\ServiceSchedule;
use App\Models\Services;
use App\Models\Contract;
use App\Models\Job;
use App\Models\JobService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use App\Models\ClientPropertyAddress;
use App\Models\Comment;
use App\Models\User;
use App\Traits\JobSchedule;
use App\Traits\PaymentAPI;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class ClientController extends Controller
{
    use JobSchedule, PaymentAPI;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $action = $request->get('action');

        $query = Client::query()
            ->leftJoin('leadstatus', 'leadstatus.client_id', '=', 'clients.id')
            ->leftJoin('contracts', 'contracts.client_id', '=', 'clients.id')
            ->where('clients.status', '>', 0)
            ->when($action == 'booked', function ($q) {
                return $q->has('jobs');
            })
            ->when($action == 'notbooked', function ($q) {
                return $q->whereDoesntHave('jobs');
            })
            ->select('clients.id', 'clients.firstname', 'clients.lastname', 'clients.email', 'clients.phone', 'leadstatus.lead_status', 'clients.created_at')
            ->selectRaw('IF(contracts.status = "' . ContractStatusEnum::VERIFIED . '", 1, 0) AS has_contract')
            ->groupBy('clients.id');

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                if (request()->has('search')) {
                    $keyword = request()->get('search')['value'];

                    if (!empty($keyword)) {
                        $query->where(function ($sq) use ($keyword) {
                            $sq->whereRaw("CONCAT_WS(' ', clients.firstname, clients.lastname) like ?", ["%{$keyword}%"])
                                ->orWhere('clients.email', 'like', "%" . $keyword . "%")
                                ->orWhere('clients.phone', 'like', "%" . $keyword . "%")
                                ->orWhere('leadstatus.lead_status', 'like', "%" . $keyword . "%");
                        });
                    }
                }
            })
            ->editColumn('created_at', function ($data) {
                return $data->created_at ? Carbon::parse($data->created_at)->format('d/m/Y') : '-';
            })
            ->editColumn('name', function ($data) {
                return $data->firstname . ' ' . $data->lastname;
            })
            ->filterColumn('name', function ($query, $keyword) {
                $sql = "CONCAT_WS(' ', clients.firstname, clients.lastname) like ?";
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->orderColumn('name', function ($query, $order) {
                $query->orderBy('firstname', $order);
            })
            ->addColumn('action', function ($data) {
                return '';
            })
            ->filterColumn('lead_status', function ($query, $keyword) {
                $sql = "leadstatus.lead_status like ?";
                $query->whereRaw($sql, ["{$keyword}"]);
            })
            ->rawColumns(['action'])
            ->toJson();
    }

    public function AllClients()
    {
        $clients = Client::all();

        if (!empty($clients)) {
            foreach ($clients as $i => $res) {
                if ($res->lastname == null) {
                    $clients[$i]->lastname = '';
                }
            }
        }

        return response()->json([
            'clients' => $clients,
        ]);
    }

    public function latestClients()
    {
        $clients = Client::latest()->paginate(5);

        if (!empty($clients)) {
            foreach ($clients as $i => $res) {
                if ($res->lastname == null) {
                    $clients[$i]->lastname = '';
                }
            }
        }

        return response()->json([
            'clients' => $clients,
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
        $validator = Validator::make($request->data, [
            'firstname' => ['required', 'string', 'max:255'],
            'invoicename' => ['required', 'string', 'max:255'],
            'phone'     => ['required', 'unique:clients'],
            'status'    => ['required'],
            'passcode'  => ['required', 'string', 'min:6',],
            'email'     => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:clients'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $input = $request->data;
        $input['password'] = Hash::make($input['passcode']);
        $client = Client::create($input);

        $client->lead_status()->updateOrCreate(
            [],
            ['lead_status' => LeadStatusEnum::PENDING]
        );

        $addressIds = [];
        $property_address_data = $request->propertyAddress;
        if (count($property_address_data) > 0) {
            foreach ($property_address_data as $key => $address) {
                $address['client_id'] = $client->id;
                $createdClient = ClientPropertyAddress::create($address);
                $addressIds[$key] = $createdClient->id;
            }
        }

        if (!empty($request->jobdata)) {
            $allServices = json_decode($request->jobdata['services'], true);
            for ($i = 0; $i < count($allServices); $i++) {
                $allServices[$i]['address'] =  $addressIds[$allServices[$i]['address']];
            }

            $offer = Offer::create([
                'client_id' => $client->id,
                'services' => json_encode($allServices, JSON_UNESCAPED_UNICODE),
                'subtotal' => $request->jobdata['subtotal'],
                'total' => $request->jobdata['total'],
                'status' => 'accepted'
            ]);

            $contract = Contract::create([
                'offer_id' => $offer->id,
                'client_id' => $client->id,
                'unique_hash' => md5($client->email . $offer->id),
                'status' => 'verified',
            ]);

            /* Create job */

            $jds = [];
            foreach ($allServices as $service) {
                $service_schedules = ServiceSchedule::find($service['frequency']);
                $ser = Services::find($service['service']);

                $repeat_value = $service_schedules->period;
                if ($service['service'] == 10) {
                    $s_name = $service['other_title'];
                    $s_heb_name = $service['other_title'];
                } else {
                    $s_name = $ser->name;
                    $s_heb_name = $ser->heb_name;
                }
                $s_hour = $service['jobHours'];
                $s_freq = $service['freq_name'];
                $s_cycle = $service['cycle'];
                $s_period = $service['period'];
                $s_total = $service['totalamount'];
                $s_id = $service['service'];
                $address_id = $service['address'];

                $worker = $service['worker'];
                $shift =  $service['shift'];

                $jobsArr = $this->scheduleJob(Arr::only($service, [
                    'period',
                    'cycle',
                    'start_date',
                    'weekday_occurrence',
                    'weekday',
                    'weekdays',
                    'month_occurrence',
                    'monthday_selection_type',
                    'month_date',
                ]));

                foreach ($jobsArr as $key => $job) {
                    $status = 'scheduled';
                    if (Job::where('start_date', $job['job_date'])->where('worker_id', $worker)->exists()) {
                        $status = 'unscheduled';
                    }

                    $jds[] = [
                        'job' => [
                            'worker'      => $worker,
                            'client_id'   => $client->id,
                            'offer_id'    => $offer->id,
                            'contract_id' => $contract->id,
                            'schedule_id' => $s_id,
                            'start_date'  => $job['job_date'],
                            'next_start_date'  => $job['next_job_date'],
                            'shifts'      => $shift,
                            'schedule'    => $repeat_value,
                            'status'      => $status,
                            'address_id'  => $address_id,
                        ],

                        'service' => [
                            'service_id' => $s_id,
                            'name'       => $s_name,
                            'heb_name'   => $s_heb_name,
                            'job_hour'   => $s_hour,
                            'freq_name'  => $s_freq,
                            'cycle'      => $s_cycle,
                            'period'     => $s_period,
                            'total'      => $s_total,
                            'config'      => $job['configuration'],
                        ]
                    ];
                }
            }

            foreach ($jds as $jd) {
                $jdata = $jd['job'];
                $sdata = $jd['service'];

                $job = Job::create([
                    'worker_id'   => $jdata['worker'],
                    'address_id'  => $jdata['address_id'],
                    'client_id'   => $jdata['client_id'],
                    'offer_id'    => $jdata['offer_id'],
                    'contract_id' => $jdata['contract_id'],
                    'schedule_id' => $jdata['schedule_id'],
                    'start_date'  => $jdata['start_date'],
                    'next_start_date'  => $jdata['next_start_date'],
                    'shifts'      => $jdata['shifts'],
                    'schedule'    => $jdata['schedule'],
                    'status'      => $jdata['status'],
                ]);

                JobService::create([
                    'job_id'     => $job->id,
                    'service_id' => $sdata['service_id'],
                    'name'       => $sdata['name'],
                    'heb_name'   => $sdata['heb_name'],
                    'job_hour'   => $sdata['job_hour'],
                    'freq_name'  => $sdata['freq_name'],
                    'cycle'      => $sdata['cycle'],
                    'period'     => $sdata['period'],
                    'total'      => $sdata['total'],
                    'config'     => $sdata['config'],
                ]);
            }

            $client->lead_status()->updateOrCreate(
                [],
                ['lead_status' => LeadStatusEnum::ACTIVE_CLIENT]
            );
        }
        /*End create job */

        return response()->json([
            'message' => 'Client created successfully',
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $client = Client::with(['property_addresses', 'latestLog'])->find($id);

        if (!$client) {
            return response()->json([
                'error' => [
                    'message' => 'Client not found!',
                    'code' => 404
                ]
            ], 404);
        }

        $contract = Contract::query()
            ->where('client_id', $client->id)
            ->where('status', 'verified')
            ->latest()
            ->first();

        if ($contract != null) {
            $client->latest_contract = $contract->id;
        } else {
            $client->latest_contract = 0;
        }

        return response()->json([
            'client' => $client,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $client = Client::with('property_addresses')->find($id);

        if (!$client) {
            return response()->json([
                'error' => [
                    'message' => 'Client not found!',
                    'code' => 404
                ]
            ], 404);
        }

        return response()->json([
            'client' => $client,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->data, [
            'firstname' => ['required', 'string', 'max:255'],
            // 'passcode'  => ['required', 'string', 'min:6'],
            'phone'     => ['required', 'unique:clients,phone,' . $id],
            'status'    => ['required'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:clients,email,' . $id],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $client = Client::find($id);

        $hasVerifiedContract = Contract::query()
            ->where('client_id', $client->id)
            ->where('status', ContractStatusEnum::VERIFIED)
            ->exists();

        if (!$client) {
            return response()->json([
                'message' => 'Client not found!',
            ], 404);
        }

        $input = $request->data;
        if ((isset($input['passcode']) && $input['passcode'] != null)) {
            $input['password'] = Hash::make($input['passcode']);
        } else {
            $input['password'] = $client->password;
        }

        if ($hasVerifiedContract && $input['status'] != 2) {
            return response()->json([
                'message' => "Can't convert client to lead",
            ], 422);
        }
        $client->update($input);

        if (!empty($request->jobdata)) {
            $offer = Offer::create([
                'client_id' => $client->id,
                'services' => $request->jobdata['services'],
                'subtotal' => $request->jobdata['subtotal'],
                'total' => $request->jobdata['total'],
                'status' => 'accepted'
            ]);

            $contract = Contract::create([
                'offer_id' => $offer->id,
                'client_id' => $client->id,
                'unique_hash' => md5($client->email . $offer->id),
                'status' => 'verified',
            ]);

            /* Create job */
            $allServices = json_decode($request->jobdata['services'], true);

            $jds = [];
            foreach ($allServices as $service) {
                $service_schedules = ServiceSchedule::find($service['frequency']);
                $ser = Services::find($service['service']);

                $repeat_value = $service_schedules->period;
                if ($service['service'] == 10) {
                    $s_name = $service['other_title'];
                    $s_heb_name = $service['other_title'];
                } else {
                    $s_name = $ser->name;
                    $s_heb_name = $ser->heb_name;
                }
                $s_hour = $service['jobHours'];
                $s_freq = $service['freq_name'];
                $s_cycle = $service['cycle'];
                $s_period = $service['period'];
                $s_total = $service['totalamount'];
                $s_id = $service['service'];
                $address_id = $service['address'];

                $worker = $service['worker'];
                $shift = $service['shift'];

                $jobsArr = $this->scheduleJob(Arr::only($service, [
                    'period',
                    'cycle',
                    'start_date',
                    'weekday_occurrence',
                    'weekday',
                    'weekdays',
                    'month_occurrence',
                    'monthday_selection_type',
                    'month_date',
                ]));

                foreach ($jobsArr as $key => $job) {
                    $status = 'scheduled';
                    if (Job::where('start_date', $job['job_date'])->where('worker_id', $worker)->exists()) {
                        $status = 'unscheduled';
                    }

                    $jds[] = [
                        'job' => [
                            'worker'      => $worker,
                            'client_id'   => $client->id,
                            'offer_id'    => $offer->id,
                            'contract_id' => $contract->id,
                            'schedule_id' => $s_id,
                            'start_date'  => $job['job_date'],
                            'next_start_date'  => $job['next_job_date'],
                            'shifts'      => $shift,
                            'schedule'    => $repeat_value,
                            'status'      => $status,
                            'address_id'  => $address_id,
                        ],

                        'service' => [
                            'service_id' => $s_id,
                            'name'       => $s_name,
                            'heb_name'   => $s_heb_name,
                            'job_hour'   => $s_hour,
                            'freq_name'  => $s_freq,
                            'cycle'      => $s_cycle,
                            'period'     => $s_period,
                            'total'      => $s_total,
                            'config'      => $job['configuration'],
                        ]
                    ];
                }
            }

            foreach ($jds as $jd) {
                $jdata = $jd['job'];
                $sdata = $jd['service'];

                $job = Job::create([
                    'worker_id'   => $jdata['worker'],
                    'address_id'  => $jdata['address_id'],
                    'client_id'   => $jdata['client_id'],
                    'offer_id'    => $jdata['offer_id'],
                    'contract_id' => $jdata['contract_id'],
                    'schedule_id' => $jdata['schedule_id'],
                    'start_date'  => $jdata['start_date'],
                    'next_start_date'  => $jdata['next_start_date'],
                    'shifts'      => $jdata['shifts'],
                    'schedule'    => $jdata['schedule'],
                    'status'      => $jdata['status'],
                ]);

                JobService::create([
                    'job_id'     => $job->id,
                    'service_id' => $sdata['service_id'],
                    'name'       => $sdata['name'],
                    'heb_name'   => $sdata['heb_name'],
                    'job_hour'   => $sdata['job_hour'],
                    'freq_name'  => $sdata['freq_name'],
                    'cycle'      => $sdata['cycle'],
                    'period'     => $sdata['period'],
                    'total'      => $sdata['total'],
                    'config'     => $sdata['config'],
                ]);
            }

            $client->lead_status()->updateOrCreate(
                [],
                ['lead_status' => LeadStatusEnum::ACTIVE_CLIENT]
            );
        }
        /*End create job */

        return response()->json([
            'message'  => 'Client updated successfully',
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
        Client::find($id)->delete();

        return response()->json([
            'message' => "Client has been deleted"
        ]);
    }

    public function addfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required',
            'user_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()]);
        }

        $file_nm = '';
        if ($request->type == 'video') {

            $video = $request->file('file');
            $vname = $request->user_id . "_" . date('s') . "_" . $video->getClientOriginalName();
            if (!Storage::disk('public')->exists('uploads/ClientFiles')) {
                Storage::disk('public')->makeDirectory('uploads/ClientFiles');
            }

            if (Storage::disk('public')->putFileAs("uploads/ClientFiles", $video, $vname)) {
                $file_nm = $vname;
            }
        } else {
            if ($request->hasfile('file')) {

                $image = $request->file('file');
                $name = $image->getClientOriginalName();
                $img = Image::make($image)->resize(350, 227);
                $fname = 'file_' . $request->user_id . '_' . date('s') . '_' . $name;
                $path = Storage::disk('public')->path('uploads/ClientFiles/' . $fname);

                if (!Storage::disk('public')->exists('uploads/ClientFiles')) {
                    Storage::disk('public')->makeDirectory('uploads/ClientFiles');
                }

                $img->save($path, 90);
                $file_nm = $fname;
            }
        }

        Files::create([
            'user_id'   => $request->user_id,
            'meeting'   => $request->meeting,
            'note'      => $request->note,
            'role'      => 'client',
            'type'      => $request->type,
            'file'      => $file_nm
        ]);

        return response()->json([
            'message' => 'File uploaded',
        ]);
    }

    public function files($id)
    {
        $files = Files::where('user_id', $id)->get();

        foreach ($files as $k => $file) {
            $files[$k]->path = asset('storage/uploads/ClientFiles') . "/" . $file->file;
        }

        return response()->json([
            'files' => $files
        ]);
    }

    public function deletefile(Request $request)
    {
        $file = Files::find($request->id);
        $file->delete();

        return response()->json([
            'message' => 'File deleted',
        ]);
    }

    public function addNote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'note'     => 'required',
            'team_id'  => 'required',
            'user_id'  => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        Note::create([
            'note'   => $request->note,
            'user_id' => $request->user_id,
            'team_id' => $request->team_id,
            'important' => $request->important
        ]);

        return response()->json(['message' => 'Note added']);
    }

    public function getNotes(Request $request)
    {
        $notes = Note::query()
            ->with('team')
            ->where(['user_id' => $request->id, 'role' => 'client'])
            ->get();

        return response()->json(['notes' => $notes]);
    }

    public function deleteNote(Request $request)
    {
        Note::where(['id' => $request->id])->delete();
        return response()->json(['message' => 'Note deleted']);
    }

    public function export(Request $request)
    {
        if (isset($request->f) &&  $request->f != "null") {
            $clients = Client::where('status', $request->f)->get();
        }

        if (!is_null($request->action)) {
            $ac = $request->action;

            if ($ac == 'booked') {
                $clients = Client::with('jobs')->has('jobs')->get();
            }

            if ($ac == 'notbooked') {
                $clients = Client::with('jobs')->whereDoesntHave('jobs')->get();
            }
        }

        if ($request->f == 'null') {
            $clients = Client::get();
        }

        foreach ($clients as $i => $c) {
            if ($c->status == 0) {
                $clients[$i]['status'] = 'Lead';
            } else if ($c->status == 1) {
                $clients[$i]['status'] = 'Potential Customer';
            } else if ($c->status == 2) {
                $clients[$i]['status'] = 'Customer';
            }
        }

        return response()->json([
            'clients' => $clients
        ]);
    }

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()]);
        }

        $file = $request->file('file');
        $fileName = 'file_' . $request->user()->id . '_' . date('YmdHis') . '_' . $file->getClientOriginalName();

        if (!Storage::disk('public')->exists('uploads/imports')) {
            Storage::disk('public')->makeDirectory('uploads/imports');
        }

        if (!Storage::disk('public')->putFileAs("uploads/imports", $file, $fileName)) {
            return response()->json(['error' => 'File not uploaded']);
        }

        ImportClientJob::dispatch($fileName);

        return response()->json([
            'message' => 'File has been submitted, it will be imported soon',
        ]);
    }

    public function sampleFileExport(Request $request)
    {
        return Excel::download(new ClientSampleFileExport, 'client-import-sheet.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    public function getComments($id)
    {
        $comments = Comment::query()
            ->with('commenter', 'attachments')
            ->where('relation_type', Client::class)
            ->where('relation_id', $id)
            ->latest()
            ->get();

        $comments = $comments->map(function ($item, $key) {
            $commenter_name = NULL;
            if (get_class($item->commenter) == Admin::class) {
                $commenter_name = $item->commenter->name;
            } else if (get_class($item->commenter) == User::class) {
                $commenter_name = $item->commenter->firstname . ' ' . $item->commenter->lastname;
            } else if (get_class($item->commenter) == Client::class) {
                $commenter_name = $item->commenter->firstname . ' ' . $item->commenter->lastname;
            }
            $item->commenter_name = $commenter_name;
            return $item;
        });

        return response()->json([
            'comments' => $comments
        ]);
    }

    public function saveComment(Request $request, $id)
    {
        $client = Client::query()->find($id);

        if (!$client) {
            return response()->json([
                'message' => 'Client not found!',
            ], 404);
        }

        if (!$request->get('comment')) {
            return response()->json([
                'message' => 'Comment is required!',
            ], 404);
        }

        $comment = $client->comments()->create([
            'comment' => $request->get('comment'),
            'valid_till' => $request->get('valid_till')
        ]);

        $filesArr = $request->file('files');
        if ($request->hasFile('files') && count($filesArr) > 0) {
            if (!Storage::disk('public')->exists('uploads/attachments')) {
                Storage::disk('public')->makeDirectory('uploads/attachments');
            }
            $resultArr = [];
            foreach ($filesArr as $key => $file) {
                $original_name = $file->getClientOriginalName();
                $file_name = Str::uuid()->toString();
                $file_extension = $file->getClientOriginalExtension();
                $file_name = $file_name . '.' . $file_extension;

                if (Storage::disk('public')->putFileAs("uploads/attachments", $file, $file_name)) {
                    array_push($resultArr, [
                        'file_name' => $file_name,
                        'original_name' => $original_name
                    ]);
                }
            }
            $comment->attachments()->createMany($resultArr);
        }

        return response()->json([
            'message' => 'Comment is added successfully!',
        ]);
    }

    public function deleteComment($serviceID, $id)
    {
        $comment = Comment::query()
            ->whereHasMorph(
                'commenter',
                [Admin::class],
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

        $comment->delete();

        return response()->json([
            'message' => 'Comment has been deleted successfully'
        ]);
    }
    public function clienStatusLog(Request $request)
    {
        $data = $request->all();
        $statusArr = [
            LeadStatusEnum::PENDING => 0,
            LeadStatusEnum::POTENTIAL => 0,
            LeadStatusEnum::IRRELEVANT => 0,
            LeadStatusEnum::UNINTERESTED => 0,
            LeadStatusEnum::UNANSWERED => 0,
            LeadStatusEnum::FREEZE_CLIENT => 2,
            LeadStatusEnum::POTENTIAL_CLIENT => 1,
            LeadStatusEnum::PENDING_CLIENT => 2,
            LeadStatusEnum::ACTIVE_CLIENT => 2,
        ];
        $client = Client::find($data['id']);
        if (!$client) {
            return response()->json([
                'message' => 'Client not found!'
            ]);
        }
        $client->status = $statusArr[$data['status']];
        $client->save();
        $client->lead_status()->updateOrCreate(
            [],
            ['lead_status' => $data['status']]
        );
        $client->logs()->create([
            'status' =>  $statusArr[$data['status']],
            'reason' =>  $data['reason']
        ]);
        return response()->json([
            'message' => 'Status has been changes successfully!'
        ]);
    }
}
