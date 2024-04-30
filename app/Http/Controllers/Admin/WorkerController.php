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
        $q = $request->q;
        $result = User::query();

        $status = '';
        if (strtolower($q) === "active") {
            $status = 1;
        }
        if (strtolower($q) === "inactive") {
            $status = 0;
        }

        $result->where('firstname',  'like', '%' . $q . '%');
        $result->orWhere('lastname', 'like', '%' . $q . '%');
        $result->orWhere('phone',    'like', '%' . $q . '%');
        $result->orWhere('address',  'like', '%' . $q . '%');

        // $result->orWhere('email',    'like','%'.$q.'%');
        if ($status != '') {
            $result->orWhere('status',   'like', '%' . $status . '%');
        }

        $result = $result->orderBy('id', 'desc')->paginate(20);

        return response()->json([
            'workers' => $result,
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
                'jobs:worker_id,start_date,shifts,client_id',
                'jobs.client:id,firstname,lastname',
                'notAvailableDates:user_id,date,start_time,end_time'
            ])
            ->when(count($onlyWorkerIDArr), function ($q) use ($onlyWorkerIDArr) {
                return $q->whereIn('id', $onlyWorkerIDArr);
            })
            ->when(count($ignoreWorkerIDArr), function ($q) use ($ignoreWorkerIDArr) {
                return $q->whereNotIn('id', $ignoreWorkerIDArr);
            })
            ->when($service != '', function ($q) use ($service) {
                return $q
                    ->whereHas('availabilities', function ($query) {
                        $query->where('date', '>=', Carbon::now()->toDateString());
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

                $availabilities = [];
                foreach ($worker->availabilities->groupBy('date') as $date => $times) {
                    $availabilities[$date] = $times->map(function ($item, $key) {
                        return $item->only(['start_time', 'end_time']);
                    });
                }

                $workerArr['availabilities'] = $availabilities;

                $dates = array();
                foreach ($worker->jobs as $job) {
                    $slotInfo = [
                        'client_name' => $job->client->firstname . ' ' . $job->client->lastname,
                        'slot' => $job->shifts
                    ];

                    $dates[$job->start_date][] = $slotInfo;
                }

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
            'company_type'    => [
                'required',
                Rule::in(['my-company', 'manpower']),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $worker                = new User;
        $worker->firstname     = $request->firstname;
        $worker->lastname      = ($request->lastname) ? $request->lastname : '';
        $worker->phone         = $request->phone;
        $worker->email         = $request->email;
        $worker->address       = $request->address;
        $worker->latitude      = $request->latitude;
        $worker->longitude     = $request->longitude;
        $worker->renewal_visa  = $request->renewal_visa;
        $worker->gender        = $request->gender;
        $worker->payment_per_hour  = $request->payment_hour;
        $worker->worker_id     = $request->worker_id;
        $worker->lng           = $request->lng;
        $worker->passcode      = $request->password;
        $worker->password      = Hash::make($request->password);
        $worker->skill         = $request->skill;
        $worker->company_type  = $request->company_type;
        $worker->status        = $request->status;
        $worker->country       = $request->country;
        $worker->is_afraid_by_cat       = $request->is_afraid_by_cat;
        $worker->is_afraid_by_dog       = $request->is_afraid_by_dog;
        $worker->save();

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
                $w_a->end_time = '16:00:00';
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
            'message' => 'Worker updated successfully',
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
            'company_type'    => [
                'required',
                Rule::in(['my-company', 'manpower']),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $worker                = User::find($id);
        $worker->firstname     = $request->firstname;
        $worker->lastname      = ($request->lastname) ? $request->lastname : '';
        $worker->phone         = $request->phone;
        // $worker->email         = $request->email;
        $worker->address       = $request->address;
        $worker->latitude      = $request->latitude;
        $worker->longitude     = $request->longitude;
        $worker->renewal_visa  = $request->renewal_visa;
        $worker->gender        = $request->gender;
        $worker->payment_per_hour  = $request->payment_hour;
        $worker->worker_id     = $request->worker_id;
        $worker->lng           = $request->lng;
        $worker->passcode     = $request->password;
        $worker->password      = Hash::make($request->password);
        $worker->skill         = $request->skill;
        $worker->company_type  = $request->company_type;
        $worker->status        = $request->status;
        $worker->country       = $request->country;
        $worker->is_afraid_by_cat       = $request->is_afraid_by_cat;
        $worker->is_afraid_by_dog       = $request->is_afraid_by_dog;
        $worker->save();

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
        User::find($id)->delete();
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

    // public function upload(Request $request, $id)
    // {
    //     $worker = User::find($id);

    //     $pdf = $request->file('pdf');
    //     $filename = 'form101_' . $worker->id . '_' . date('s') . "_." . $pdf->getClientOriginalExtension();

    //     if (!Storage::disk('public')->exists('uploads/worker/form101')) {
    //         Storage::disk('public')->makeDirectory('uploads/worker/form101');
    //     }

    //     if (Storage::disk('public')->putFileAs("uploads/worker/form101", $pdf, $filename)) {
    //         $worker->update([
    //             'form_101' => $filename
    //         ]);
    //     }

    //     return response()->json(['success' => true]);
    // }

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
}
