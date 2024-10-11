<?php

namespace App\Http\Controllers\Admin;

use Mpdf\Mpdf;
use App\Models\User;
use App\Models\ManpowerCompany;
use App\Models\Job;
use App\Models\WorkerAvailability;
use App\Models\ClientPropertyAddress;
use App\Models\WorkerFreezeDate;
use App\Models\WorkerNotAvailableDate;
use App\Http\Controllers\Controller;
use App\Enums\Form101FieldEnum;
use App\Enums\WorkerFormTypeEnum;
use App\Events\WorkerCreated;
use App\Events\WorkerForm101Requested;
use App\Events\WorkerLeaveJob;
use App\Exports\WorkerHoursExport;
use App\Exports\WorkerSampleExport;
use App\Jobs\ImportWorker;
use App\Traits\JobSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use App\Rules\ValidPhoneNumber;
use PDF;
class WorkerController extends Controller
{
    use JobSchedule;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $status = $request->get('status');
        $manpowerCompanyID = $request->get('manpower_company_id');
        $isMyCompany = $request->get('is_my_company');

        $query = User::query()
            ->when($status == "active", function ($q) {
                return $q
                    ->where(function ($q) {
                        $q
                            ->whereNull('last_work_date')
                            ->orWhereDate('last_work_date', '>=', today()->toDateString());
                    });
            })
            ->when($status == "past", function ($q) {
                return $q
                    ->whereNotNull('last_work_date')
                    ->whereDate('last_work_date', '<', today()->toDateString());
            })
            ->when($manpowerCompanyID, function ($q) use ($manpowerCompanyID) {
                return $q->where('manpower_company_id', $manpowerCompanyID);
            })
            ->when($isMyCompany == 'true', function ($q) {
                return $q->where('company_type', 'my-company');
            })
            ->when($status && !$manpowerCompanyID, function ($q) {
                return $q->where('company_type', 'my-company');
            })
            ->select('users.id', 'users.firstname', 'users.lastname', 'users.email', 'users.phone', 'users.status', 'users.address', 'users.latitude', 'users.longitude');

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                if (request()->has('search')) {
                    $keyword = request()->get('search')['value'];

                    if (!empty($keyword)) {
                        $query->where(function ($sq) use ($keyword) {
                            $sq->whereRaw("CONCAT_WS(' ', users.firstname, users.lastname) like ?", ["%{$keyword}%"])
                                ->orWhere('users.email', 'like', "%" . $keyword . "%")
                                ->orWhere('users.phone', 'like', "%" . $keyword . "%");
                        });
                    }
                }
            })
            ->editColumn('name', function ($data) {
                return $data->firstname . ' ' . $data->lastname;
            })
            ->filterColumn('name', function ($query, $keyword) {
                $sql = "CONCAT_WS(' ', users.firstname, users.lastname) like ?";
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->orderColumn('name', function ($query, $order) {
                $query->orderBy('firstname', $order);
            })
            ->addColumn('action', function ($data) {
                return '';
            })
            ->rawColumns(['action'])
            ->toJson();
    }

    public function AllWorkers(Request $request)
    {
        $service = '';
        $onlyWorkerIDArr = $request->only_worker_ids ? explode(',', $request->only_worker_ids) : [];
        $ignoreWorkerIDArr = $request->ignore_worker_ids ? explode(',', $request->ignore_worker_ids) : [];
        if ($request->service_id) {
            $service = $request->service_id;
        }
        if ($request->job_id) {
            $job = Job::with('offer')->find($request->job_id);
            if ($job->offer) {
                $services = json_decode($job->offer['services']);
                $service = $services[0]->service;
            }
        }
        $available_date = $request->get('available_date');
        $has_cat = $request->get('has_cat');
        $has_dog = $request->get('has_dog');
        $prefer_type = $request->get('prefer_type');
    
        $client_latitude = null;
        $client_longitude = null;
        if ($request->client_property_id) {
            $client_property = ClientPropertyAddress::find($request->client_property_id);
            if ($client_property) {
                $client_latitude = $client_property->latitude;
                $client_longitude = $client_property->longitude;
            }
        }
    
        $workers = User::query()
            ->with([
                'availabilities:user_id,day,date,start_time,end_time',
                'defaultAvailabilities:user_id,weekday,start_time,end_time,until_date',
                'jobs' => function ($q) {
                    $q->where('id', '!=', request()->job_id)
                        ->select('worker_id', 'start_date', 'shifts', 'client_id');
                },
                'jobs.client:id,firstname,lastname',
                'notAvailableDates:user_id,date,start_time,end_time'
            ])
            ->where(function ($query) {
                $query->whereNull('last_work_date')->orWhereDate('last_work_date', '>=', now());
            })
            ->when(count($onlyWorkerIDArr), function ($q) use ($onlyWorkerIDArr) {
                return $q->whereIn('id', $onlyWorkerIDArr);
            })
            ->when(count($ignoreWorkerIDArr), function ($q) use ($ignoreWorkerIDArr) {
                return $q->whereNotIn('id', $ignoreWorkerIDArr);
            })
            ->when($service != '', function ($q) use ($service) {
                return $q
                    ->where(function ($qu) {
                        $qu->whereHas('availabilities', function ($query) {
                            $query->where('date', '>=', Carbon::now()->toDateString());
                        });
                        $qu->orWhereHas('defaultAvailabilities', function ($query) {
                            $query->where('until_date', '>=', Carbon::now()->toDateString())
                                ->orWhereNull('until_date');
                        });
                    })
                    ->where('skill', 'like', '%' . $service . '%');
            })
            ->when($has_dog == '1', function ($q) {
                return $q->where('is_afraid_by_dog', false);
            })
            ->when($has_cat == '1', function ($q) {
                return $q->where('is_afraid_by_cat', false);
            })
            ->when(in_array($prefer_type, ['male', 'female']), function ($q) use ($prefer_type) {
                return $q->where('gender', $prefer_type);
            })
            ->whereDoesntHave('notAvailableDates', function ($q) use ($available_date) {
                $q->where('date', $available_date);
            })
            ->when($request->distance === 'nearest' && $client_latitude && $client_longitude, function ($q) use ($client_latitude, $client_longitude) {
                $haversine = "(6371 * acos(cos(radians($client_latitude)) * cos(radians(latitude)) * cos(radians(longitude) - radians($client_longitude)) + sin(radians($client_latitude)) * sin(radians(latitude))))";
                return $q->selectRaw("* , {$haversine} AS distance")
                    ->orderBy('distance');
            })
            ->when($request->distance === 'farthest' && $client_latitude && $client_longitude, function ($q) use ($client_latitude, $client_longitude) {
                $haversine = "(6371 * acos(cos(radians($client_latitude)) * cos(radians(latitude)) * cos(radians(longitude) - radians($client_longitude)) + sin(radians($client_latitude)) * sin(radians(latitude))))";
                return $q->selectRaw("* , {$haversine} AS distance")
                    ->orderBy('distance', 'desc'); 
            })
            ->where('status', 1)
            ->get();

        if (isset($request->filter)) {
            $workers = $workers->map(function ($worker, $key) {
                $workerArr = $worker->toArray();

                $defaultAvailabilities = $worker->defaultAvailabilities()
                    ->where(function ($q) {
                        $q
                            ->whereNull('until_date')
                            ->orWhereDate('until_date', '>=', date('Y-m-d'));
                    })
                    ->get()
                    ->groupBy('weekday');

                $workerAvailabilitiesByDate = $worker
                    ->availabilities
                    ->sortBy([
                        ['date', 'asc'],
                        ['start_time', 'asc'],
                    ])
                    ->groupBy('date');

                $availabilities = [];
                foreach ($workerAvailabilitiesByDate as $date => $times) {
                    if (is_null($worker->last_work_date) || Carbon::parse($worker->last_work_date)->gte(Carbon::parse($date))) {
                        $availabilities[$date] = $times->map(function ($item, $key) {
                            return $item->only(['start_time', 'end_time']);
                        });
                    }
                }

                $available_dates = array_keys($availabilities);
                $dates = [];

                $currentDate = Carbon::now();

                // Loop through the next 4 weeks (28 days)
                for ($i = 0; $i < 28; $i++) {
                    // Add the current date to the array
                    $date_ = $currentDate->toDateString();

                    if (!in_array($date_, $available_dates)) {
                        $weekDay = $currentDate->weekday();
                        if (isset($defaultAvailabilities[$weekDay])) {
                            if (is_null($worker->last_work_date) || Carbon::parse($worker->last_work_date)->gte(Carbon::parse($date_))) {
                                $availabilities[$date_] = $defaultAvailabilities[$weekDay]->map(function ($item, $key) {
                                    return $item->only(['start_time', 'end_time']);
                                });
                            }
                        }
                    }

                    // Move to the next day
                    $currentDate->addDay();
                }

                $workerArr['availabilities'] = $availabilities;

                $dates = array();
                foreach ($worker->jobs as $job) {
                    $slotInfo = [
                        'job_id' => $job->id,
                        'client_name' => $job->client->firstname . ' ' . $job->client->lastname,
                        'slot' => $job->shifts
                    ];

                    $dates[$job->start_date][] = $slotInfo;
                }

                $freezeDates = $worker->freezeDates()->whereDate('date', '>=', Carbon::now())->get();
                $workerArr['freeze_dates'] = $freezeDates;
                $workerArr['booked_slots'] = $dates;
                $workerArr['not_available_on'] = $worker
                    ->notAvailableDates
                    ->map(function ($item) {
                        return $item->only([
                            'date',
                            'start_time',
                            'end_time'
                        ]);
                    })
                    ->toArray();
                $workerArr['not_available_dates'] = $worker->notAvailableDates->pluck('date')->toArray();

                return $workerArr;
            });

            return response()->json([
                'workers' => $workers,
            ]);
        }

        return response()->json([
            'workers' => $workers,
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
            'firstname' => ['required', 'string', 'max:255'],
            'address'   => ['required', 'string'],
            'phone'     => ['required', 'string', 'max:20', new ValidPhoneNumber(), 'unique:users'],
            'worker_id' => ['required'],
            'status'    => ['required'],
            'password'  => ['required'],
            'email'     => ['nullable', 'unique:users'],
            'gender'    => ['required'],
            'payment_type' => ['required', 'string'],
            'full_name' => ['required_if:payment_type,money_transfer'],
            'bank_name' => ['required_if:payment_type,money_transfer'],
            'bank_number' => ['required_if:payment_type,money_transfer'],
            'branch_number' => ['required_if:payment_type,money_transfer'],
            'account_number' => ['required_if:payment_type,money_transfer'],
            'role'      => ['required', 'max:50'],
            'company_type'    => [
                'required',
                Rule::in(['my-company', 'manpower']),
            ],
            'manpower_company_id' => ['required_if:company_type,manpower']
        ], [
            'payment_type.required' => 'The payment type is required.',
            'full_name.required_if' => 'The full name is required.',
            'bank_name.required_if' => 'The bank name is required .',
            'bank_number.required_if' => 'The bank number is required.',
            'branch_number.required_if' => 'The branch number is required.',
            'account_number.required_if' => 'The account number is required.',
            'manpower_company_id.required_if' => 'Manpower Company ID is required.',
            'employment_type.required' => 'The employment type is required.'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $worker = User::create([
            'firstname'     => $request->firstname,
            'lastname'      => ($request->lastname) ? $request->lastname : '',
            'phone'         => $request->phone,
            'email'         => $request->email,
            'address'       => $request->address,
            'latitude'      => $request->latitude,
            'longitude'     => $request->longitude,
            'renewal_visa'  => $request->renewal_visa,
            'gender'        => $request->gender,
            'role'          => $request->role,
            'payment_per_hour'  => $request->payment_hour,
            'worker_id'     => $request->worker_id,
            'lng'           => $request->lng,
            'passcode'      => $request->password,
            'password'      => Hash::make($request->password),
            'skill'         => $request->skill,
            'company_type'  => $request->company_type,
            'status'        => $request->status,
            'country'       => $request->country,
            'payment_type'  =>  $request->payment_type,
            'full_name'     => $request->full_name,
            'bank_name'     => $request->bank_name,
            'bank_number'   => $request->bank_number,
            'branch_number' => $request->branch_number,
            'account_number'    => $request->account_number,
            'is_afraid_by_cat'          => $request->is_afraid_by_cat,
            'is_afraid_by_dog'          => $request->is_afraid_by_dog,
            'manpower_company_id'       => $request->company_type == "manpower"
                ? $request->manpower_company_id
                : NULL,
            'driving_fees' =>$request->driving_fees,
            'employment_type' => $request->employment_type,
            'salary' =>$request->salary
        ]);

        $i = 1;
        $j = 0;
        $check_friday = 1;
        while ($i == 1) {
            $current = Carbon::now();
            $day = $current->addDays($j);
            if ($this->isWeekend($day->toDateString())) {
                $check_friday++;
            } else {
                $w_a = new WorkerAvailability;
                $w_a->user_id = $worker->id;
                $w_a->date = $day->toDateString();
                $w_a->start_time = '08:00:00';
                $w_a->end_time = '17:00:00';
                $w_a->status = 1;
                $w_a->save();
            }
            $j++;
            if ($check_friday == 6) {
                $i = 2;
            }
        }

        if($worker->company_type == 'manpower' && $worker->country != 'Israel') {
            return response()->json([
                'message' => 'Worker created successfully',
            ]);
        }

        $formEnum = new Form101FieldEnum;

        $defaultFields = $formEnum->getDefaultFields();
        $defaultFields['employeeFirstName'] = $worker->firstname;
        $defaultFields['employeeLastName'] = $worker->lastname;
        $defaultFields['employeeMobileNo'] = $worker->phone;
        $defaultFields['employeeEmail'] = $worker->email;
        $defaultFields['sender']['employeeEmail'] = $worker->email;
        $defaultFields['employeeSex'] = Str::ucfirst($worker->gender);
        $formData = app('App\Http\Controllers\User\Auth\AuthController')->transformFormDataForBoolean($defaultFields);

        $worker->forms()->create([
            'type' => WorkerFormTypeEnum::FORM101,
            'data' => $formData,
            'submitted_at' => NULL
        ]);

        event(new WorkerCreated($worker));

        return response()->json([
            'message' => 'Worker created successfully',
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
        $worker = User::find($id);

        return response()->json([
            'worker' => $worker,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function isWeekend($date)
    {
        $weekDay = date('w', strtotime($date));
        return ($weekDay == 5 || $weekDay == 6);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => ['required', 'string', 'max:255'],
            'address'   => ['required', 'string'],
            'phone'     => ['required', new ValidPhoneNumber(), 'unique:users,phone,' . $id],
            //'worker_id' => ['required','unique:users,worker_id,'.$id],
            'status'    => ['required'],
            'email'     => ['nullable',  'unique:users,email,' . $id],
            'payment_type' => ['required', 'string'],
            'full_name' => ['required_if:payment_type,money_transfer'],
            'bank_name' => ['required_if:payment_type,money_transfer'],
            'bank_number' => ['required_if:payment_type,money_transfer'],
            'branch_number' => ['required_if:payment_type,money_transfer'],
            'account_number' => ['required_if:payment_type,money_transfer'],
            'role'      => ['required', 'max:50'],
            'company_type'    => [
                'required',
                Rule::in(['my-company', 'manpower']),
            ],
           'manpower_company_id' => ['required_if:company_type,manpower'],
        ], [
            'payment_type.required' => 'The payment type is required.',
            'full_name.required_if' => 'The full name is required.',
            'bank_name.required_if' => 'The bank name is required .',
            'bank_number.required_if' => 'The bank number is required.',
            'branch_number.required_if' => 'The branch number is required.',
            'account_number.required_if' => 'The account number is required.',
            'manpower_company_id.required_if' => 'Manpower Company ID is required.'
        ], [
            'manpower_company_id' => 'Manpower'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $worker = User::find($id);

        if (!$worker) {
            return response()->json([
                'message' => 'Worker not found',
            ], 404);
        }

        $worker->update([
            'firstname'     => $request->firstname,
            'lastname'      => ($request->lastname) ? $request->lastname : '',
            'phone'         => $request->phone,
            'address'       => $request->address,
            'latitude'      => $request->latitude,
            'longitude'     => $request->longitude,
            'renewal_visa'  => $request->renewal_visa,
            'gender'        => $request->gender,
            'role'          => $request->role,
            'payment_per_hour'  => $request->payment_hour,
            'worker_id'     => $request->worker_id,
            'lng'           => $request->lng,
            'passcode'      => $request->password,
            'password'      => Hash::make($request->password),
            'skill'         => $request->skill,
            'company_type'  => $request->company_type,
            'status'        => $request->status,
            'country'       => $request->country,
            'payment_type'  => $request->payment_type,
            'full_name'  => $request->full_name,
            'bank_name'  => $request->bank_name,
            'bank_number'  => $request->bank_number,
            'branch_number'  => $request->branch_number,
            'account_number'  => $request->account_number,
            'is_afraid_by_cat'          => $request->is_afraid_by_cat,
            'is_afraid_by_dog'          => $request->is_afraid_by_dog,
            'manpower_company_id'       => $request->company_type == "manpower" ? $request->manpower_company_id : NULL,
            'driving_fees' =>$request->driving_fees,
        ]);

        return response()->json([
            'message' => 'Worker updated successfully',
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
        $worker = User::find($id);
        if (!$worker) {
            return response()->json([
                'message' => "Worker not found"
            ], 404);
        }

        $worker->delete();
        return response()->json([
            'message' => "Worker has been deleted"
        ]);
    }

    public function updateAvailability(Request $request, $id)
    {
        $worker = User::find($id);

        $data = $request->all();

        $worker->availabilities()->delete();

        foreach ($data['time_slots'] as $key => $availabilties) {
            $date = trim($key);

            foreach ($availabilties as $key => $availabilty) {
                WorkerAvailability::create([
                    'user_id' => $id,
                    'date' => $date,
                    'start_time' => $availabilty['start_time'],
                    'end_time' => $availabilty['end_time'],
                    'status' => '1',
                ]);
            }
        }

        $worker->defaultAvailabilities()->delete();

        if (isset($data['default']['time_slots'])) {
            foreach ($data['default']['time_slots'] as $weekday => $availabilties) {
                foreach ($availabilties as $key => $timeSlot) {
                    $worker->defaultAvailabilities()->create([
                        'weekday' => $weekday,
                        'start_time' => $timeSlot['start_time'],
                        'end_time' => $timeSlot['end_time'],
                        'until_date' => $data['default']['until_date'],
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Updated Successfully',
        ]);
    }

    public function getWorkerAvailability($id)
    {
        $worker = User::find($id);

        $worker_availabilities = $worker->availabilities()
            ->orderBy('date', 'asc')
            ->get(['date', 'start_time', 'end_time']);

        $availabilities = [];
        foreach ($worker_availabilities->groupBy('date') as $date => $times) {
            $availabilities[$date] = $times->map(function ($item, $key) {
                return $item->only(['start_time', 'end_time']);
            });
        }

        $default_availabilities = $worker->defaultAvailabilities()
            ->orderBy('id', 'asc')
            ->get(['weekday', 'start_time', 'end_time', 'until_date'])
            ->groupBy('weekday');

        return response()->json([
            'data' => [
                'regular' => $availabilities,
                'default' => $default_availabilities
            ],
        ]);
    }

    public function formSave(Request $request)
    {
        try {
            $workerId = $request->id;
            $worker = User::find($workerId);
            $worker_contract = $request->file('worker_contract');
            if ($worker_contract) {
                $filename = 'contract_' . $worker->id . '_' . date('s') . "_." . $worker_contract->getClientOriginalExtension();
                if (!Storage::disk('public')->exists('uploads/worker/contract')) {
                    Storage::disk('public')->makeDirectory('uploads/worker/contract');
                }
                if (!empty($worker->worker_contract) && Storage::drive('public')->exists('uploads/worker/contract/' . $worker->worker_contract)) {
                    Storage::drive('public')->delete('uploads/worker/contract/' . $worker->worker_contract);
                }
                if (Storage::disk('public')->putFileAs("uploads/worker/contract", $worker_contract, $filename)) {
                    $worker->update([
                        'worker_contract' => $filename
                    ]);
                }
            }

            $form_101 = $request->file('form_101');
            if ($form_101) {
                $filename = 'form101_' . $worker->id . '_' . date('s') . "_." . $form_101->getClientOriginalExtension();
                if (!Storage::disk('public')->exists('uploads/worker/form101')) {
                    Storage::disk('public')->makeDirectory('uploads/worker/form101');
                }
                if (!empty($worker->form_101) && Storage::drive('public')->exists('uploads/worker/form101/' . $worker->form_101)) {
                    Storage::drive('public')->delete('uploads/worker/form101/' . $worker->form_101);
                }
                if (Storage::disk('public')->putFileAs("uploads/worker/form101", $form_101, $filename)) {
                    $worker->update([
                        'form_101' => $filename
                    ]);
                }
            }

            $safety_and_gear_form = $request->file('safety_and_gear_form');
            if ($safety_and_gear_form) {
                $filename = 'safety_gear_' . $worker->id . '_' . date('s') . "_." . $safety_and_gear_form->getClientOriginalExtension();
                if (!Storage::disk('public')->exists('uploads/worker/safetygear')) {
                    Storage::disk('public')->makeDirectory('uploads/worker/safetygear');
                }
                if (!empty($worker->safety_and_gear_form) && Storage::drive('public')->exists('uploads/worker/safetygear/' . $worker->safety_and_gear_form)) {
                    Storage::drive('public')->delete('uploads/worker/safetygear/' . $worker->safety_and_gear_form);
                }
                if (Storage::disk('public')->putFileAs("uploads/worker/safetygear", $safety_and_gear_form, $filename)) {
                    $worker->update([
                        'safety_and_gear_form' => $filename
                    ]);
                }
            }

            $form_insurance = $request->file('form_insurance');
            if ($form_insurance) {
                $filename = 'insurance_' . $worker->id . '_' . date('s') . "_." . $form_insurance->getClientOriginalExtension();
                if (!Storage::disk('public')->exists('uploads/worker/insurance')) {
                    Storage::disk('public')->makeDirectory('uploads/worker/insurance');
                }
                if (!empty($worker->form_insurance) && Storage::drive('public')->exists('uploads/worker/insurance/' . $worker->form_insurance)) {
                    Storage::drive('public')->delete('uploads/worker/insurance/' . $worker->form_insurance);
                }
                if (Storage::disk('public')->putFileAs("uploads/worker/insurance", $form_insurance, $filename)) {
                    $worker->update([
                        'form_insurance' => $filename
                    ]);
                }
            }
            return response()->json(['success' => true]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function addNotAvailableDates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date'     => 'required',
            'worker_id'  => 'required',
            'start_time' => 'required_with:end_time',
            'end_time' => 'required_with:start_time',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        WorkerNotAvailableDate::create([
            'user_id' => $request->worker_id,
            'date'    => $request->date,
            'status'  => $request->status,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time
        ]);

        return response()->json(['message' => 'Date added']);
    }

    public function getNotAvailableDates(Request $request)
    {
        $dates = WorkerNotAvailableDate::where(['user_id' => $request->id])->get();
        return response()->json(['dates' => $dates]);
    }

    public function deleteNotAvailableDates(Request $request)
    {
        WorkerNotAvailableDate::find($request->id)->delete();
        return response()->json(['message' => 'date deleted']);
    }

    public function presentWorkersForJob(Request $request)
    {
        $data = $request->all();
        $property = $data['property'];

        $jobsArr = $this->scheduleJob(Arr::only($data['job'], [
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

        $dates = data_get($jobsArr, '*.job_date');

        $workers = User::query()
            ->when($property['has_dog'], function ($q) {
                return $q->where('is_afraid_by_dog', false);
            })
            ->when($property['has_cat'], function ($q) {
                return $q->where('is_afraid_by_cat', false);
            })
            ->when(in_array($property['prefer_type'], ['male', 'female']), function ($q) use ($property) {
                return $q->where('gender', $property['prefer_type']);
            })
            ->whereDoesntHave('notAvailableDates', function ($q) use ($dates) {
                $q->whereIn('date', $dates);
            })
            ->where('status', 1)
            ->select(['id', 'firstname', 'lastname'])
            ->selectRaw('( 6371 * acos( cos( radians(' . $property['lat'] . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $property['lng'] . ') ) + sin( radians(' . $property['lat'] . ') ) * sin( radians( latitude ) ) ) ) AS distance')
            ->orderBy('distance')
            ->get();

        return response()->json([
            'data' => $workers,
            'message' => 'Workers fetched successfully'
        ]);
    }

    public function updateFreezeShift(Request $request, $id)
    {
        $worker = User::find($id);

        if (!$worker) {
            return response()->json([
                'message' => 'Worker not found'
            ], 404);
        }

        $data = $request->all();

        $worker->update([
            'freeze_shift_start_time' => $data['start_time'],
            'freeze_shift_end_time' => $data['end_time'],
        ]);

        return response()->json([
            'message' => 'Freeze shift updated successfully'
        ]);
    }

    public function updateFreezeShiftWorkers(Request $request)
    {
        if ($request->has('removedSlots')) {
            $removedSlots = $request->get('removedSlots');
            foreach ($removedSlots as $removedSlot) {
                WorkerFreezeDate::where('user_id', $removedSlot['workerId'])->where('id', $removedSlot['id'])->delete();
            }
        }

        if ($request->has('workers')) {
            $workers = $request->get('workers');

            foreach ($workers as $key => $worker) {
                $times = explode(' - ', $worker['shifts']);
                WorkerFreezeDate::updateOrCreate([
                    'user_id' => $worker['workerId'],
                    'date' => Carbon::parse($worker['date']),
                    'start_time' => $times[0] . '.00',
                    'end_time' => $times[1] . '.00',
                ]);
            }
        }

        return response()->json([
            'message' => 'Freeze shifts updated successfully'
        ]);
    }

    public function getFreezeShiftWorkers(Request $request, $id)
    {
        $worker = User::find($id);

        if (!$worker) {
            return response()->json([
                'message' => 'Worker not found'
            ], 404);
        }

        $workerFreezeDates = $worker->freezeDates()->whereDate('date', '>=', Carbon::now())->get();

        return response()->json([
            'data' => $workerFreezeDates,
            'message' => 'Freeze shifts fetched successfully'
        ]);
    }

    public function updateLeaveJob(Request $request, $id)
    {
        $worker = User::find($id);

        if (!$worker) {
            return response()->json([
                'message' => 'Worker not found'
            ], 404);
        }

        $data = $request->all();
        $date = null;
        if (isset($data['date']) && !empty($data['date'])) {
            $date = $data['date'];
        }

        if ($date != $worker->last_work_date) {
            $worker->update([
                'last_work_date' => $date,
            ]);

            event(new WorkerLeaveJob($worker));
        }

        return response()->json([
            'message' => 'Leave job date updated successfully'
        ]);
    }

    public function workingHoursReport(Request $request)
    {
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $manpowerCompanyID = $request->get('manpower_company_id');
        $isMyCompany = $request->get('is_my_company');

        $jobHours = Job::query()
            ->when($start_date, function ($q) use ($start_date) {
                return $q->whereDate('start_date', '>=', $start_date);
            })
            ->when($end_date, function ($q) use ($end_date) {
                return $q->whereDate('start_date', '<=', $end_date);
            })
            ->select('jobs.worker_id')
            ->selectRaw('SUM(jobs.actual_time_taken_minutes) AS minutes')
            ->groupBy('jobs.worker_id');

        $query = User::query()
            ->leftJoinSub($jobHours, 'job_hours', function ($join) {
                $join->on('users.id', '=', 'job_hours.worker_id');
            })
            ->when($manpowerCompanyID, function ($q) use ($manpowerCompanyID) {
                return $q->where('manpower_company_id', $manpowerCompanyID);
            })
            ->when($isMyCompany == 'true', function ($q) {
                return $q->where('company_type', 'my-company');
            })
            ->where(function ($q) {
                $q
                    ->whereNull('last_work_date')
                    ->orWhereDate('last_work_date', '>=', today()->toDateString());
            })
            ->select('users.id', 'users.firstname', 'users.lastname', 'users.email', 'users.phone', 'job_hours.minutes', 'users.created_at');

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                if (request()->has('search')) {
                    $keyword = request()->get('search')['value'];

                    if (!empty($keyword)) {
                        $query->where(function ($sq) use ($keyword) {
                            $sq->whereRaw("CONCAT_WS(' ', users.firstname, users.lastname) like ?", ["%{$keyword}%"])
                                ->orWhere('users.email', 'like', "%" . $keyword . "%")
                                ->orWhere('users.phone', 'like', "%" . $keyword . "%");
                        });
                    }
                }
            })
            ->editColumn('name', function ($data) {
                return $data->firstname . ' ' . $data->lastname;
            })
            ->filterColumn('name', function ($query, $keyword) {
                $sql = "CONCAT_WS(' ', users.firstname, users.lastname) like ?";
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->orderColumn('name', function ($query, $order) {
                $query->orderBy('firstname', $order);
            })
            ->toJson();
    }

    public function exportWorkingHoursReport(Request $request)
    {
        $worker_ids = $request->get('worker_ids') ?? [];
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $manpowerCompanyID = $request->get('manpower_company_id');
        $isMyCompany = $request->get('is_my_company');

        return Excel::download(new WorkerHoursExport(
            $worker_ids,
            $start_date,
            $end_date,
            $manpowerCompanyID,
            $isMyCompany
        ), 'Worker Hours.csv');
    }

    public function generateWorkerHoursPDF(Request $request)
    {
        
        $worker_ids = $request->get('worker_ids', []);
        $start_date = $request->get('start_date', null);
        $end_date = $request->get('end_date', null);
        $manpowerCompanyID = $request->get('manpower_company_id', '');
        $isMyCompany = $request->get('is_my_company', '');
    
        $startDate = Carbon::parse($start_date);
        $endDate = Carbon::parse($end_date);
    
        // Generate the date range
        $dates = [];
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $dates[] = $date->format('Y-m-d');
        }
    
        $allPdfData = []; // Initialize an array to store data for all workers
    
        foreach ($worker_ids as $worker_id) {
            $worker = User::find($worker_id);
           
            if ($worker) {
                //Determine the company name based on the worker's manpower_company_id and isMyCompany flag
                if ($worker->manpower_company_id) {
                    $company = ManpowerCompany::find($worker->manpower_company_id);
                    $companyName = $company ? $company->name : 'Unknown Company';
                } elseif ($isMyCompany == 'true') {
                    $companyName = 'My Company';
                } else {
                    $companyName = 'Creative Development';
                }

                $workerName = $worker->firstname . ' ' . $worker->lastname;
                $department = $worker->role; // Fetch department name based on role
                $pdfData = []; // Reset the pdfData array for each worker
            
            foreach ($dates as $date) {
                // Fetch all records for the current worker and date
                $data = Job::where('jobs.worker_id', $worker_id)
                    ->whereDate('jobs.start_date', $date)
                    ->when($worker->manpower_company_id, function ($q) use ($worker) {
                        return $q->where('users.manpower_company_id',$worker->manpower_company_id);
                    })
                    ->where(function ($q) {
                        $q->whereNull('users.last_work_date')
                            ->orWhereDate('users.last_work_date', '>=', today()->toDateString());
                    })
                    ->when($isMyCompany == 'true' && !$worker->manpower_company_id, function ($q) {
                        return $q->where('company_type', 'my-company');
                    })
                    ->join('users', 'jobs.worker_id', '=', 'users.id')
                    ->select(
                        DB::raw('MIN(jobs.start_time) as entry_time'), 
                        DB::raw('MAX(jobs.end_time) as exit_time'),    
                        DB::raw('SUM(jobs.actual_time_taken_minutes) as total_minutes') // Sum of working hours
                    )
                    ->groupBy('jobs.worker_id', 'jobs.start_date')
                    ->get();
    
                    // Initialize daily data with null and 0 values
                    $dailyData = [
                        'entry_time' => null,
                        'exit_time' => null,
                        'total_hours' => 0,
                    ];
    
                    // Process each record to aggregate data
                    foreach ($data as $record) {
                        $dailyData['entry_time'] = $record->entry_time ?: $dailyData['entry_time'];
                        $dailyData['exit_time'] = $record->exit_time ?: $dailyData['exit_time'];
                        $dailyData['total_hours'] += $record->total_minutes / 60; // Convert minutes to hours
                    }
    
                // Store daily data for the specific date
                $pdfData[$date] = $dailyData;
            }
    
            // Store the data for this worker
                $allPdfData[$workerName] = [
                    'dates' => $dates,
                    'pdfData' => $pdfData,
                    'department' => $department,
                    'companyName' => $companyName,
                ];
            }
        }
    
        if (empty($allPdfData)) {
            return response()->json(['message' => 'No data found for the given criteria'], 404);
        }
    
        $pdf = new Mpdf(['mode' => 'rtl']);
        $pdf->WriteHTML(view('pdf.workers_hours_report', [
            'allPdfData' => $allPdfData,
        ])->render());
        
        $pdfFileName = 'worker_hours_report_' . time() . '.pdf';
        $pdfFilePath = 'pdfs/' . $pdfFileName;
        $pdfOutput = $pdf->Output('', 'S');

        // Save the PDF to the specified path
        Storage::put($pdfFilePath, $pdfOutput);

        // Generate the full URL for the PDF
        $pdfUrl = url(Storage::url($pdfFilePath));

        // Serve the PDF directly if preferred
        return response($pdfOutput, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $pdfFileName . '"');

    }
    


    public function formSend(Request $request, Form101FieldEnum $formEnum)
    {
        $formId = null;
        $data = $request->all();
        $formType = $data['type'];
        $worker = User::find($data['workerId']);
        if (!$worker) {
            return response()->json([
                'message' => 'Worker not found',
            ], 404);
        }
        $isExistForm101 = $worker->forms()
            ->where('type', WorkerFormTypeEnum::FORM101)
            ->whereNull('submitted_at')
            ->first();

        if (!$isExistForm101) {
            $defaultFields = $formEnum->getDefaultFields();
            $defaultFields['employeeFirstName'] = $worker->firstname;
            $defaultFields['employeeLastName'] = $worker->lastname;
            $defaultFields['employeeMobileNo'] = $worker->phone;
            $defaultFields['employeeEmail'] = $worker->email;
            $defaultFields['sender']['employeeEmail'] = $worker->email;
            $defaultFields['employeeSex'] = Str::ucfirst($worker->gender);
            $formData = app('App\Http\Controllers\User\Auth\AuthController')->transformFormDataForBoolean($defaultFields);
            $form = $worker->forms()->create([
                'type' => WorkerFormTypeEnum::FORM101,
                'data' => $formData,
                'submitted_at' => NULL
            ]);
            $formId = $form->id;
        } else {
            $formId = $isExistForm101->id;
        }

        event(new WorkerForm101Requested($worker, $formType, $formId));

        return response()->json([
            'message' => 'Worker created successfully',
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

        ImportWorker::dispatch($fileName);

        return response()->json([
            'message' => 'File has been submitted, it will be imported soon',
        ]);
    }

    public function sampleFileExport(Request $request)
    {
        return Excel::download(new WorkerSampleExport, 'worker-import-sheet.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
}
