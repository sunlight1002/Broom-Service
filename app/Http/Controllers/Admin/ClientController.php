<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContractStatusEnum;
use App\Exports\ClientSampleFileExport;
use App\Http\Controllers\Controller;
use App\Jobs\ImportClientJob;
use App\Models\Client;
use App\Models\ClientCard;
use App\Models\Files;
use App\Models\Note;
use App\Models\Offer;
use App\Models\ServiceSchedule;
use App\Models\Services;
use App\Models\Contract;
use App\Models\Job;
use App\Models\JobService;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use App\Models\ClientPropertyAddress;
use App\Traits\JobSchedule;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Facades\Excel;

class ClientController extends Controller
{
    use JobSchedule;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $q = $request->q;

        $result = Client::where('status', 2);

        if (!is_null($q)) {
            // $result->where('email',      'like', '%' . $q . '%');
            // $result->orwhere('firstname',    'like', '%' . $ex[0] . '%');
            // $result->orWhere('lastname',   'like', '%' . $q2 . '%');
            // $result->orWhere('geo_address',   'like', '%' . $q . '%');
            // $result->orWhere('phone',   'like', '%' . $q . '%');

            // $result->orWhere('phone',      'like', '%' . $q . '%');
            // $result->orWhere('city',       'like', '%' . $q . '%');
            // $result->orWhere('street_n_no', 'like', '%' . $q . '%');
            // $result->orWhere('zipcode',    'like', '%' . $q . '%');
            // $result->orWhere('email',      'like', '%' . $q . '%');
            // $result->where('status','2');

            $result->where(function ($query) use ($q) {
                $ex = explode(' ', $q);
                $q2 = isset($ex[1]) ? $ex[1] : $q;
                $query->where('email', 'like', '%' . $q . '%')
                    ->orWhere('firstname', 'like', '%' . $ex[0] . '%')
                    ->orWhere('lastname', 'like', '%' . $q2 . '%')
                    ->orWhere('phone', 'like', '%' . $q . '%')
                    ->orWhere('geo_address', 'like', '%' . $q . '%');
            });
        }

        if (isset($request->action)) {
            $result = '';
            $ac = $request->action;

            if ($ac == 'booked') {
                $result = Client::with('jobs')->has('jobs');
            }

            if ($ac == 'notbooked') {
                $result = Client::whereDoesntHave('jobs');
            }
        }

        $result = $result->where('status', '2')->orderBy('id', 'desc')->paginate(20);

        if (isset($result)) {
            foreach ($result as $k => $res) {
                $contract = Contract::query()
                    ->where('client_id', $res->id)
                    ->where('status', ContractStatusEnum::VERIFIED)
                    ->get()
                    ->last();

                if ($contract != null) {
                    $result[$k]->latest_contract = $contract->id;
                } else {
                    $result[$k]->latest_contract = 0;
                }
            }
        }

        return response()->json([
            'clients' => $result,
        ]);
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
            'phone'     => ['required', 'unique:clients'],
            'status'    => ['required'],
            'passcode'  => ['required', 'string', 'min:6',],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:clients'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $input = $request->data;
        $input['password'] = Hash::make($input['passcode']);
        $client = Client::create($input);

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
                'services' => json_encode($allServices, true),
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
        $client = Client::with('property_addresses')->find($id);

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

        if (!$client) {
            return response()->json([
                'error' => [
                    'message' => 'Client not found!',
                    'code' => 404
                ]
            ], 404);
        }

        $input = $request->data;
        if ((isset($input['passcode']) && $input['passcode'] != null)) {
            $input['password'] = Hash::make($input['passcode']);
        } else {
            $input['password'] = $client->password;
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
            $files[$k]->path = Storage::disk('public')->url('uploads/ClientFiles/' . $file->file);
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

    public function cardToken($id)
    {
        $card = ClientCard::where('client_id', $id)->first();

        return response()->json([
            'status_code'  => (!empty($card)) ? 200 : 0,
            'card'         => (!empty($card)) ? $card->card_number : 0,
            'expiry'       => (!empty($card)) ? $card->valid : 0,
            'ctype'        => (!empty($card)) ? $card->card_type : 0,
            'holder'       => (!empty($card)) ? $card->card_holder_name : 0,
        ]);
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

    public function updateShift(Request $request)
    {
        $req = (object)$request->cshift;

        if ($req->repetency == 'one_time') {
            if ($req->worker != '') {
                Job::where('id', $req->job)->update([
                    'worker_id' => $req->worker,
                    'start_date' => $req->shift_date,
                    'shifts' => $req->shift_time,
                    'status' => 'scheduled'
                ]);
            } else {
                Job::where('id', $req->job)->update([
                    'start_date' => $req->shift_date,
                    'shifts' => $req->shift_time
                ]);
            }
        } else {
            if ($req->repetency == 'forever') {
                $jobs =  Job::query()
                    ->where([
                        'client_id' => $req->client,
                        'contract_id' => $req->contract,
                        //'schedule_id' => $req->service,
                    ])
                    ->whereIn('status', ['scheduled', 'unscheduled'])
                    ->get();
            }

            if ($req->repetency == 'untill_date') {
                $jobs =  Job::query()
                    ->where([
                        'client_id' => $req->client,
                        'contract_id' => $req->contract,
                        //'schedule_id' => $req->service,
                    ])
                    ->whereIn('status', ['scheduled', 'unscheduled'])
                    ->whereBetween('start_date', [$req->from, $req->to])
                    ->get();
            }

            if (isset($jobs)) {
                Shift::create([
                    'contract_id' =>  $req->contract,
                    'repetency'   =>  $req->repetency,
                    'old_freq'    =>  $jobs[0]->schedule,
                    'new_freq'    =>  $req->period,
                    'shift_date'  =>  $req->shift_date,
                    'shift_time'  =>  $req->shift_time,
                    'from'        =>  $req->from,
                    'to'          =>  $req->to
                ]);

                $firstDate = true;
                foreach ($jobs as $k => $job) {
                    // if (Carbon::now()->format('Y-m-d') <= $job->start_date) {

                    if ($req->period == 'w') {
                        $date = Carbon::parse($job->start_date);
                        $newDate = $date->addDays(7);
                    } else if ($req->period == '2w') {
                        $date = Carbon::parse($job->start_date);
                        $newDate = $date->addDays(14);
                    } else if ($req->period == '3w') {
                        $date = Carbon::parse($job->start_date);
                        $newDate = $date->addDays(21);
                    } else if ($req->period == 'm') {
                        $date = Carbon::parse($job->start_date);
                        $newDate = $date->addMonths(1);
                    } else if ($req->period == '2m') {
                        $date = Carbon::parse($job->start_date);
                        $newDate = $date->addMonths(2);
                    } else if ($req->period == '3m') {
                        $date = Carbon::parse($job->start_date);
                        $newDate = $date->addMonths(3);
                    }

                    //if ($job->start_date >= $req->shift_date && $firstDate == true) {
                    if ($k == 0) {
                        Job::where('id', $job->id)->update([
                            'start_date'    => ($req->shift_date != '') ? $req->shift_date : $job->start_date,
                            'shifts'        => ($req->shift_time != '') ? $req->shift_time : $job->shifts,
                            'schedule'      => $req->period,
                            'schedule_id'   => $req->frequency,
                            'worker_id'     => ($req->worker != '') ? $req->worker : $job->worker
                        ]);

                        // $firstDate = false;
                    } else {
                        Job::where('id', $job->id)->update([
                            'start_date'    => $newDate,
                            'shifts'        => ($req->shift_time != '') ? $req->shift_time : $job->shifts,
                            'schedule'      => $req->period,
                            'schedule_id'   => $req->frequency,
                            'worker_id'     => ($req->worker != '') ? $req->worker : $job->worker
                        ]);
                    }
                    // }
                }
            }
        }

        return response()->json([
            'success' => 'shift updated successfully'
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
        return Excel::download(new ClientSampleFileExport, 'client-import-sheet.xlsx');
    }
}
