<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\WorkerAvailability;
use App\Models\Job;
use App\Models\Contract;
use App\Models\WorkerFreezeDate;
use App\Models\WorkerNotAvailableDate;
use App\Traits\JobSchedule;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\WorkerCreated;
use Illuminate\Validation\Rule;

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
        $keyword = $request->get('q');
        $statusCode = $request->get('status');
        $manpowerCompanyID = $request->get('manpower_company_id');

        $status = NULL;
        if (strtolower($keyword) === "active") {
            $status = 1;
        }
        if (strtolower($keyword) === "inactive") {
            $status = 0;
        }

        $data = User::query()
            ->when($keyword, function ($q) use ($keyword) {
                return $q
                    ->where(function ($q) use ($keyword) {
                        $q
                            ->where('firstname', 'like', '%' . $keyword . '%')
                            ->orWhere('lastname', 'like', '%' . $keyword . '%')
                            ->orWhere('phone', 'like', '%' . $keyword . '%')
                            ->orWhere('address', 'like', '%' . $keyword . '%');
                    });
            })
            ->when(!is_null($status), function ($q) use ($status) {
                return $q->where('status', $status);
            })
            ->when($statusCode == "active", function ($q) {
                return $q
                    ->where(function ($q) {
                        $q
                            ->whereNull('last_work_date')
                            ->orWhereDate('last_work_date', '>=', today()->toDateString());
                    });
            })
            ->when($statusCode == "past", function ($q) {
                return $q
                    ->whereNotNull('last_work_date')
                    ->whereDate('last_work_date', '<', today()->toDateString());
            })
            ->when($manpowerCompanyID, function ($q) use ($manpowerCompanyID) {
                return $q->where('manpower_company_id', $manpowerCompanyID);
            })
            ->latest()
            ->paginate(20);

        return response()->json([
            'workers' => $data,
        ]);
    }

    public function AllWorkers(Request $request)
    {
        $service = '';
        $onlyWorkerIDArr = $request->only_worker_ids ? explode(',', $request->only_worker_ids) : [];
        $ignoreWorkerIDArr = $request->ignore_worker_ids ? explode(',', $request->ignore_worker_ids) : [];
        if ($request->service_id) {
            // $contract=Contract::with('offer','client')->find($request->contract_id);
            // if($contract->offer){
            //     $services=json_decode($contract->offer['services']);
            //     $service=$services[0]->service;
            // }
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

        $workers = User::query()
            ->with([
                'availabilities:user_id,day,date,start_time,end_time',
                'defaultAvailabilities:user_id,weekday,start_time,end_time,until_date',
                'jobs:worker_id,start_date,shifts,client_id,id',
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
                            $query->where('until_date', '>=', Carbon::now()->toDateString());
                            $query->orWhereNull('until_date');
                        });
                    })
                    // ->whereRelation('jobs', function ($query) {
                    //     $query->where('start_date', '>=', Carbon::now()->toDateString());
                    // })
                    ->where('skill',  'like', '%' . $service . '%');
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
            ->where('status', 1)
            ->get();

        if (isset($request->filter)) {
            $workers = $workers->map(function ($worker, $key) {
                $workerArr = $worker->toArray();

                $defaultAvailabilities = $worker->defaultAvailabilities
                    ->where('until_date', '>=', date('Y-m-d'))
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
            'phone'     => ['required', 'unique:users'],
            'worker_id' => ['required'],
            'status'    => ['required'],
            'password'  => ['required'],
            'email'     => ['nullable', 'unique:users'],
            'gender'    => ['required'],
            'role'      => ['required', 'max:50'],
            'company_type'    => [
                'required',
                Rule::in(['my-company', 'manpower']),
            ],
            'manpower_company_id' => ['required_if:company_type,manpower']
        ], [], [
            'manpower_company_id' => 'Manpower'
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
            'is_afraid_by_cat'          => $request->is_afraid_by_cat,
            'is_afraid_by_dog'          => $request->is_afraid_by_dog,
            'manpower_company_id'       => $request->company_type == "manpower" ? $request->manpower_company_id : NULL,
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
            'phone'     => ['required', 'unique:users,phone,' . $id],
            //'worker_id' => ['required','unique:users,worker_id,'.$id],
            'status'    => ['required'],
            'email'     => ['nullable',  'unique:users,email,' . $id],
            'role'      => ['required', 'max:50'],
            'company_type'    => [
                'required',
                Rule::in(['my-company', 'manpower']),
            ],
            'manpower_company_id' => ['required_if:company_type,manpower']
        ], [], [
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
            'is_afraid_by_cat'          => $request->is_afraid_by_cat,
            'is_afraid_by_dog'          => $request->is_afraid_by_dog,
            'manpower_company_id'       => $request->company_type == "manpower" ? $request->manpower_company_id : NULL,
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

            $form_insurance = $request->file('form_insurance');
            if ($form_insurance) {
                $filename = 'safety_gear_' . $worker->id . '_' . date('s') . "_." . $form_insurance->getClientOriginalExtension();
                if (!Storage::disk('public')->exists('uploads/worker/safetygear')) {
                    Storage::disk('public')->makeDirectory('uploads/worker/safetygear');
                }
                if (!empty($worker->form_insurance) && Storage::drive('public')->exists('uploads/worker/safetygear/' . $worker->form_insurance)) {
                    Storage::drive('public')->delete('uploads/worker/safetygear/' . $worker->form_insurance);
                }
                if (Storage::disk('public')->putFileAs("uploads/worker/safetygear", $form_insurance, $filename)) {
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
        $worker->update([
            'last_work_date' => $date,
        ]);

        return response()->json([
            'message' => 'Leave job date updated successfully'
        ]);
    }

    public function workingHoursReport(Request $request)
    {
        $keyword = $request->get('keyword');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $manpowerCompanyID = $request->get('manpower_company_id');

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

        $data = User::query()
            ->leftJoinSub($jobHours, 'job_hours', function ($join) {
                $join->on('users.id', '=', 'job_hours.worker_id');
            })
            ->when($keyword, function ($query, $keyword) {
                $query
                    ->where('users.firstname',  'like', '%' . $keyword . '%')
                    ->orWhere('users.lastname', 'like', '%' . $keyword . '%')
                    ->orWhere('users.phone',    'like', '%' . $keyword . '%')
                    ->orWhere('users.address',  'like', '%' . $keyword . '%')
                    ->orWhere('users.email',  'like', '%' . $keyword . '%');
            })
            ->when($manpowerCompanyID, function ($q) use ($manpowerCompanyID) {
                return $q->where('manpower_company_id', $manpowerCompanyID);
            })
            ->where(function ($q) {
                $q
                    ->whereNull('last_work_date')
                    ->orWhereDate('last_work_date', '>=', today()->toDateString());
            })
            ->select('users.id', 'users.firstname', 'users.lastname', 'users.email', 'users.phone', 'job_hours.minutes', 'users.created_at')
            ->orderBy('users.id', 'desc')
            ->paginate(20);

        return response()->json([
            'workers' => $data,
        ]);
    }

    public function exportWorkingHoursReport(Request $request)
    {
        $keyword = $request->get('keyword');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $manpowerCompanyID = $request->get('manpower_company_id');

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

        $data = User::query()
            ->leftJoinSub($jobHours, 'job_hours', function ($join) {
                $join->on('users.id', '=', 'job_hours.worker_id');
            })
            ->when($keyword, function ($query, $keyword) {
                $query
                    ->where('users.firstname',  'like', '%' . $keyword . '%')
                    ->orWhere('users.lastname', 'like', '%' . $keyword . '%')
                    ->orWhere('users.phone',    'like', '%' . $keyword . '%')
                    ->orWhere('users.address',  'like', '%' . $keyword . '%')
                    ->orWhere('users.email',  'like', '%' . $keyword . '%');
            })
            ->when($manpowerCompanyID, function ($q) use ($manpowerCompanyID) {
                return $q->where('manpower_company_id', $manpowerCompanyID);
            })
            ->where(function ($q) {
                $q
                    ->whereNull('last_work_date')
                    ->orWhereDate('last_work_date', '>=', today()->toDateString());
            })
            ->select('users.worker_id', 'job_hours.minutes')
            ->selectRaw('CONCAT(users.firstname, " ", COALESCE(users.lastname, "")) as worker_name')
            ->latest()
            ->get();

        $data = $data->map(function ($item, $key) {
            $item->hours = (float) number_format((float)($item->minutes / 60), 2, '.', '');

            return $item;
        });

        return response()->json([
            'workers' => $data,
        ]);
    }
}
