<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContractStatusEnum;
use App\Enums\LeadStatusEnum;
use App\Exports\ClientSampleFileExport;
use App\Http\Controllers\Controller;
use App\Jobs\ImportClientJob;
use App\Events\ClientLeadStatusChanged;
use App\Models\Admin;
use App\Models\Holiday;
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
use App\Traits\ICountDocument;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Enums\ClientMetaEnum;
use App\Models\ClientMetas;
use Illuminate\Support\Facades\Mail;
use App\Models\Notification;
use App\Enums\NotificationTypeEnum;
use Illuminate\Support\Facades\App;
use Illuminate\Mail\Mailable;
use App\Rules\ValidPhoneNumber;
use App\Models\LeadActivity;
use App\Models\WebhookResponse;
use App\Models\WhatsAppBotClientState;
use App\Jobs\AddGoogleContactJob;
use App\Jobs\SaveGoogleCalendarCallJob;
use App\Jobs\NotifyClientForCallAfterHoliday;


class ClientController extends Controller
{
    use JobSchedule, PaymentAPI, ICountDocument;

    protected $botMessages = [
        'main-menu' => [
            'heb' => 'היי, אני בר, הנציגה הדיגיטלית של ברום סרוויס. איך אוכל לעזור לך היום? 😊' . "\n\n" . 'בכל שלב תוכלו לחזור לתפריט הראשי ע"י שליחת המס 9 או לחזור תפריט אחד אחורה ע"י שליחת הספרה 0' . "\n\n" . '1. פרטים על השירות' . "\n" . '2. אזורי שירות' . "\n" . '3. קביעת פגישה לקבלת הצעת מחיר' . "\n" . '4. שירות ללקוחות קיימים' . "\n" . '5. מעבר לנציג אנושי (בשעות הפעילות)' . "\n" . '6. English menu'
        ]
    ];

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
            ->whereNotIn('leadstatus.lead_status', ['potential client', 'potential', 'uninterested'])
            ->when($action == 'notbooked', function ($q) {
                return $q->whereDoesntHave('jobs');
            })
            ->select('clients.id', 'clients.firstname', 'clients.lastname', 'clients.email', 'clients.phone', 'leadstatus.lead_status', 'clients.created_at')
            ->selectRaw('IF(contracts.status = "' . ContractStatusEnum::VERIFIED . '", 1, 0) AS has_contract')
            ->groupBy('clients.id');
    
        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                if ($request->has('search')) {
                    $keyword = $request->get('search')['value'];
    
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
            ->editColumn('lead_status', function ($data) {
                return $data->lead_status === 'pending client' ? 'waiting' : $data->lead_status;
            })
            ->filterColumn('lead_status', function ($query, $keyword) {
                $sql = "leadstatus.lead_status like ?";
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->addColumn('action', function ($data) {
                return '';
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
            'vat_number' => ['nullable', 'string', 'max:50'],
            'phone'     => ['required', 'string', 'max:20', new ValidPhoneNumber(),'unique:clients'],
            'status' => ['required'],
            'passcode' => ['required', 'string', 'min:6'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:clients'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $input = $request->data;
        $input['password'] = Hash::make($input['passcode']);
        $client = Client::create($input);

        // Create user in iCount
        $iCountResponse = $this->createOrUpdateUser($request);

        // Handle iCount response
        if ($iCountResponse->status() != 200) {
            return response()->json(['error' => 'Failed to create user in iCount'], 500);
        }

        $iCountData = $iCountResponse->json();

        // Extract Client_id from iCount response and update the Client model
        if (isset($iCountData['client_id'])) {
            $client->update(['icount_client_id' => $iCountData['client_id']]);
        }

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

        if($request->send_bot_message) {
            try {
                $m = $this->botMessages['main-menu']['heb'];

                $result = sendWhatsappMessage($client->phone, array('name' => ucfirst($client->firstname), 'message' => $m));

                WhatsAppBotClientState::updateOrCreate([
                    'client_id' => $client->id,
                ], [
                    'menu_option' => 'main_menu',
                    'language' => 'he',
                ]);

                $response = WebhookResponse::create([
                    'status'        => 1,
                    'name'          => 'whatsapp',
                    'message'       => $m,
                    'number'        => $client->phone,
                    'read'          => 1,
                    'flex'          => 'A',
                ]);
            } catch (\Throwable $th) {
                logger($th);
            }
        }

        if (!empty($request->jobdata)) {
            $allServices = json_decode($request->jobdata['services'], true);
            for ($i = 0; $i < count($allServices); $i++) {
                $allServices[$i]['address'] = $addressIds[$allServices[$i]['address']];
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
                            'worker' => $worker,
                            'client_id' => $client->id,
                            'offer_id' => $offer->id,
                            'contract_id' => $contract->id,
                            'schedule_id' => $s_id,
                            'start_date' => $job['job_date'],
                            'next_start_date' => $job['next_job_date'],
                            'shifts' => $shift,
                            'schedule' => $repeat_value,
                            'status' => $status,
                            'address_id' => $address_id,
                        ],

                        'service' => [
                            'service_id' => $s_id,
                            'name' => $s_name,
                            'heb_name' => $s_heb_name,
                            'job_hour' => $s_hour,
                            'freq_name' => $s_freq,
                            'cycle' => $s_cycle,
                            'period' => $s_period,
                            'total' => $s_total,
                            'config' => $job['configuration'],
                        ]
                    ];
                }
            }

            foreach ($jds as $jd) {
                $jdata = $jd['job'];
                $sdata = $jd['service'];

                $job = Job::create([
                    'worker_id' => $jdata['worker'],
                    'address_id' => $jdata['address_id'],
                    'client_id' => $jdata['client_id'],
                    'offer_id' => $jdata['offer_id'],
                    'contract_id' => $jdata['contract_id'],
                    'schedule_id' => $jdata['schedule_id'],
                    'start_date' => $jdata['start_date'],
                    'next_start_date' => $jdata['next_start_date'],
                    'shifts' => $jdata['shifts'],
                    'schedule' => $jdata['schedule'],
                    'status' => $jdata['status'],
                ]);

                JobService::create([
                    'job_id' => $job->id,
                    'service_id' => $sdata['service_id'],
                    'name' => $sdata['name'],
                    'heb_name' => $sdata['heb_name'],
                    'job_hour' => $sdata['job_hour'],
                    'freq_name' => $sdata['freq_name'],
                    'cycle' => $sdata['cycle'],
                    'period' => $sdata['period'],
                    'total' => $sdata['total'],
                    'config' => $sdata['config'],
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

        $client->makeVisible('passcode');

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

        $client->makeVisible('passcode');

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
            'vat_number' => ['nullable', 'string', 'max:50'],
            // 'passcode'  => ['required', 'string', 'min:6'],
            'phone'     => ['required', 'string', new ValidPhoneNumber(), 'unique:clients,phone,' . $id],
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

         // Create user in iCount
         $iCountResponse = $this->createOrUpdateUser($request);

         // Handle iCount response
         $iCountData = $iCountResponse->json();

        // Handle iCount response
        if ($iCountResponse->status() != 200) {
            return response()->json(['error' => 'Failed to create user in iCount'], 500);
        }


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

        AddGoogleContactJob::dispatch($client);

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


        $iCountResponse =  $this->deleteUser($id);

        // Handle iCount response
        $iCountData = $iCountResponse->json();

       // Handle iCount response
       if ($iCountResponse->status() != 200) {
           return response()->json(['error' => 'Failed to delete user in iCount'], 500);
       }


        return response()->json([
            'message' => "Client has been deleted",
            $iCountData
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
            LeadStatusEnum::UNANSWERED_FINAL => 2,
            LeadStatusEnum::FREEZE_CLIENT => 2,
            LeadStatusEnum::POTENTIAL_CLIENT => 1,
            LeadStatusEnum::PENDING_CLIENT => 2,
            LeadStatusEnum::ACTIVE_CLIENT => 2,
            LeadStatusEnum::UNHAPPY => 2,
            LeadStatusEnum::PRICE_ISSUE => 2,
            LeadStatusEnum::MOVED => 2,
            LeadStatusEnum::ONE_TIME => 2,
            LeadStatusEnum::PAST => 2,
            LeadStatusEnum::RESCHEDULE_CALL => 0,
        ];
    
        $client = Client::find($data['id']);
        if (!$client) {
            return response()->json([
                'message' => 'Client not found!',
            ]);
        }
    
        $client->status = $statusArr[$data['status']];
        $client->save();
    
        $newLeadStatus = $data['status'];
    
        if (!$client->lead_status || $client->lead_status->lead_status != $newLeadStatus) {
            $client->lead_status()->updateOrCreate(
                [],
                ['lead_status' => $newLeadStatus]
            );
    
            event(new ClientLeadStatusChanged($client, $newLeadStatus));
        }
    
        $client->logs()->create([
            'status' => $statusArr[$data['status']],
            'reason' => $data['reason'],
            'reschedule_date' => $data['reschedule_date'] ?? null,
            'reschedule_time' => $data['reschedule_time'] ?? null,
        ]);
    
        // Log the status change in LeadActivity
        $activity = LeadActivity::create([
            'client_id' => $data['id'],
            'created_date' => now(),
            'status_changed_date' => now(),
            'changes_status' => $newLeadStatus,
            'reason' => $data['reason'],
            'reschedule_date' => $data['reschedule_date'] ?? null,
            'reschedule_time' => $data['reschedule_time'] ?? null,
        ]);
    
        if ($data['status'] == LeadStatusEnum::RESCHEDULE_CALL) {
            // Check if reschedule date is set or use today's date
            $rescheduleDate = isset($data['reschedule_date']) ? Carbon::parse($data['reschedule_date']) : Carbon::today();
            $rescheduleTime = Carbon::createFromFormat('H:i', $data['reschedule_time']);
            $endTime = $rescheduleTime->copy()->addMinutes(30);
        
            $notificationData = [
                "schedule" => [
                    "id" => $activity->id,
                    "start_date" => $rescheduleDate->format('Y-m-d'), // Start date for scheduling
                    "start_time" => $data['reschedule_time'] ?? null,
                    "end_time" => $endTime->format('H:i'),
                    "client" => [
                        "id" => $data['id'],
                        "firstname" => $client->firstname ?? '',
                        "lastname" => $client->lastname ?? '',
                        "email" => $client->email,
                        "phone" => $client->phone,
                    ],
                ]
            ];
        
            // Check if today is a holiday or Saturday
            $today = Carbon::today();
            $holidays = Holiday::whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->get();
        
            // Ensure $today is a Carbon instance
            $today = Carbon::today();
        
            // Check if today is a holiday or Saturday
            if ($today->isSaturday() || $holidays->contains(fn($holiday) => $today->between($holiday->start_date, $holiday->end_date))) {
                // Move notification to the next day (Saturday or holiday)
                $notificationDate = $today->addDay();
            } elseif ($rescheduleDate->isSaturday() || $holidays->contains(fn($holiday) => $rescheduleDate->between($holiday->start_date, $holiday->end_date))) {
                // If reschedule date is a holiday, do not dispatch any job
                Log::info('Both today and reschedule date are holidays. Skipping job dispatch.');
                return; // Skip dispatching the job if both dates are holidays
            } else {
                $notificationDate = Carbon::now();
            }
        
            // Dispatch the jobs to save Google calendar event and send notification
            SaveGoogleCalendarCallJob::dispatch($notificationData);
        
            NotifyClientForCallAfterHoliday::dispatch($client, $activity)
                ->delay($notificationDate->diffInSeconds(now()));
        }
        
    
        return response()->json([
            'message' => 'Status has been changed successfully!',
        ]);
    }
    

    public function deleteClientMetaIfExists($clientId)
    {
        // Fetch all client metas with matching keys
        $metaKeys = [
            ClientMetaEnum::NOTIFICATION_SENT_24_HOURS,
            ClientMetaEnum::NOTIFICATION_SENT_3_DAY,
            ClientMetaEnum::NOTIFICATION_SENT_7_DAY,
        ];

        // Check if records exist for the given client_id and any of the keys
        $clientMetas = ClientMetas::where('client_id', $clientId)
            ->whereIn('key', $metaKeys)
            ->get();

        if ($clientMetas->isNotEmpty()) {
            // Delete the matched records
            ClientMetas::where('client_id', $clientId)
                ->whereIn('key', $metaKeys)
                ->delete();

            // Log the deletion
            \Log::info("Deleted meta records for client_id: $clientId with keys: " . implode(', ', $metaKeys));

            return response()->json([
                'message' => 'Client meta records deleted successfully.',
            ]);
        } else {
            // If no records were found, return a message
            return response()->json([
                'message' => 'No matching client meta records found.',
            ]);
        }
    }

}
