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
        $workerIDArr = $request->worker_ids ? explode(',', $request->worker_ids) : [];
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
            ->with(['availabilities', 'jobs:worker_id,start_date,shifts', 'notAvailableDates:user_id,date'])
            ->when(count($workerIDArr), function ($q) use ($workerIDArr) {
                return $q->whereNotIn('id', $workerIDArr);
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
                $workerArr['aval'] = $this->workerAvl($worker->availabilities);
                $workerArr['wjobs'] = $this->workerJobs($worker->jobs);
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

    public function workerJobs($jobs)
    {
        $data = array();
        foreach ($jobs as $job) {
            if (array_key_exists($job->start_date, $data)) {
                $data[$job->start_date] = $data[$job->start_date] . ',' . $job->shifts;
            } else {
                $data[$job->start_date] = $job->shifts;
            }
        }
        return $data;
    }

    public function workerAvl($availabilities)
    {
        $data = array();
        foreach ($availabilities as $avl) {
            $data[$avl->date] = $avl->working;
        }
        return $data;
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
                $w_a->working = array('8am-16pm');
                $w_a->status = 1;
                $w_a->save();
            }
            $j++;
            if ($check_friday == 6) {
                $i = 2;
            }
        }

        App::setLocale($worker->lng);
        $worker = $worker->toArray();
        if (!is_null($worker['email'])) {
            Mail::send('/Mails/Form101Mail', $worker, function ($messages) use ($worker) {
                $messages->to($worker['email']);
                ($worker['lng'] == 'heb') ?
                    $sub = $worker['id'] . "# " . __('mail.form_101.subject') . "  " . __('mail.form_101.company') :
                    $sub = __('mail.form_101.subject') . "  " . __('mail.form_101.company') . " #" . $worker['id'];
                $messages->subject($sub);
            });

            Mail::send('/Mails/WorkerContractMail', $worker, function ($messages) use ($worker) {
                $messages->to($worker['email']);
                ($worker['lng'] == 'heb') ?
                    $sub = $worker['id'] . "# " . __('mail.worker_contract.subject') . "  " . __('mail.worker_contract.company') :
                    $sub = __('mail.worker_contract.subject') . "  " . __('mail.worker_contract.company') . " #" . $worker['id'];
                $messages->subject($sub);
            });
        }

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
        $worker                = User::find($id);
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
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $worker                = User::find($id);
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
        $worker->passcode     = $request->password;
        $worker->password      = Hash::make($request->password);
        $worker->skill         = $request->skill;
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
        $data = $request->all();

        WorkerAvailability::where('user_id', $id)->delete();

        foreach ($data as $key => $availabilty) {
            $avl = new WorkerAvailability;
            $avl->user_id = $id;
            $avl->date = trim($key);
            $avl->working = $availabilty;
            $avl->status = '1';
            $avl->save();
        }

        return response()->json([
            'message' => 'Updated Successfully',
        ]);
    }

    public function getWorkerAvailability($id)
    {
        $worker_availabilities = WorkerAvailability::where('user_id', $id)
            ->orderBy('id', 'asc')
            ->get();
        $new_array = array();
        foreach ($worker_availabilities as $w_a) {
            $new_array[$w_a->date] = $w_a->working;
        }

        return response()->json([
            'data' => $new_array,
        ]);
    }

    public function getALLWorkerAvailability()
    {
        $allslot =  [
            '8am-16pm' => array('08:00', '16:00'),
            '8am-10am' => array('08:00', '10:00'),
            '10am-12pm' => array('10:00', '12:00'),
            '8am-12pm' => array('08:00', '12:00'),
            '12pm-14pm' => array('12:00', '14:00'),
            '14pm-16pm' => array('14:00', '16:00'),
            '12pm-16pm' => array('12:00', '16:00'),
            '16pm-18pm' => array('16:00', '18:00'),
            '18pm-20pm' => array('18:00', '20:00'),
            '16pm-20pm' => array('16:00', '20:00'),
            '20pm-22pm' => array('20:00', '22:00'),
            '22pm-24am' => array('22:00', '00:00'),
            '20pm-24am' => array('20:00', '00:00'),
        ];

        $sunday = Carbon::now()->startOfWeek()->subDays(1);
        $worker_availabilities = WorkerAvailability::with('worker')->where('date', '>=', $sunday)->orderBy('id', 'asc')->get();
        $new_array = array();
        foreach ($worker_availabilities as $w_a) {
            $working = $this->Slot($w_a->user_id, $w_a->date, $w_a->working[0]);
            foreach ($working as $key => $slot) {
                if ($allslot[$w_a->working[0]][1] != $slot) {
                    $new_array[] = array(
                        'id' => $w_a->id . '_' . $key,
                        'worker_id' => $w_a->user_id,
                        'date' => $w_a->date,
                        'start_time' => $slot,
                        'end_time' => $this->covertTime($slot),
                        'name' => $w_a->worker['firstname'] . ' ' . $w_a->worker['lastname']
                    );
                }
            }
        }

        return response()->json([
            'availability' => $new_array,
        ]);
    }

    public function covertTime($slot)
    {
        $time = str_replace(".", ":", ((float)str_replace(":", ".", $slot) + 0.30)) . '0';
        $time1 = explode(':', $time);
        if ($time1[1] == '60') {
            $time = ((int)$time1[0] + 1) . ':00';
        }
        return $time;
    }

    public function Slot($w_id, $w_date, $slot)
    {
        $allslot =  [
            '8am-16pm' => array('08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00'),
            '8am-10am' => array('08:00', '08:30', '09:00', '09:30', '10:00'),
            '10am-12pm' => array('10:00', '10:30', '11:00', '11:30', '12:00'),
            '8am-12pm' => array('08:00', '08:30', '09:00', '09:30', '10:00', '10:00', '10:30', '11:00', '11:30', '12:00'),
            '12pm-14pm' => array('12:00', '12:30', '13:00', '13:30', '14:00'),
            '14pm-16pm' => array('14:00', '14:30', '15:00', '15:30', '16:00'),
            '12pm-16pm' => array('12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00'),
            '16pm-18pm' => array('16:00', '16:30', '17:00', '17:30', '18:00'),
            '18pm-20pm' => array('18:00', '18:30', '19:00', '19:30', '20:00'),
            '16pm-20pm' => array('16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30', '20:00'),
            '20pm-22pm' => array('20:00', '20:30', '21:00', '21:30', '22:00'),
            '22pm-24am' => array('22:00', '22:30', '23:00', '23:30', '00:00'),
            '20pm-24am' => array('20:00', '20:30', '21:00', '21:30', '22:00', '22:30', '23:00', '23:30', '00:00'),
        ];
        $jobs = Job::Where('worker_id', $w_id)
            ->where('start_date', $w_date)
            ->get();
        if (count($jobs)) {
            $data = $allslot[$slot];
            foreach ($jobs as $job) {
                $unset = false;
                foreach ($allslot[$slot] as $key => $item) {
                    if ($job->start_time == $item) {
                        $unset = true;
                    }
                    if ($job->end_time == $item) {
                        $unset = false;
                    }
                    if ($unset) {
                        unset($data[$key]);
                    }
                }
            }
            return $data;
        } else {
            return $allslot[$slot];
        }
    }

    public function upload(Request $request, $id)
    {
        $worker = User::find($id);

        $pdf = $request->file('pdf');
        $filename = 'form101_' . $worker->id . '.' . $pdf->getClientOriginalExtension();
        $path = storage_path() . '/app/public/uploads/worker/form101/' . $worker->id;
        $pdf->move($path, $filename);
        $worker->form_101 = $filename;
        $worker->save();
        return response()->json(['success' => true]);
    }

    public function addNotAvailableDates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date'     => 'required',
            'worker_id'  => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $date          = new WorkerNotAvailableDate;
        $date->user_id = $request->worker_id;
        $date->date    = $request->date;
        $date->status  = $request->status;
        $date->save();

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
}
