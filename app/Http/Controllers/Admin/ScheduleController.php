<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\SettingKeyEnum;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\ClientLeadStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Schedule;
use App\Models\Notification;
use App\Models\Setting;
use App\Traits\GoogleAPI;
use App\Traits\ScheduleMeeting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use App\Models\LeadActivity;
use App\Jobs\SendMeetingMailJob;
use App\Jobs\SendMeetingNotificationJob;

class ScheduleController extends Controller
{
    use GoogleAPI, ScheduleMeeting;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Schedule::query()
            ->leftJoin('admins', 'schedules.team_id', '=', 'admins.id')
            ->leftJoin('clients', 'schedules.client_id', '=', 'clients.id')
            ->leftJoin('client_property_addresses', 'schedules.address_id', '=', 'client_property_addresses.id')
            ->select('schedules.id', 'clients.id as client_id', 'clients.firstname', 'clients.lastname', 'clients.phone', 'schedules.booking_status', 'client_property_addresses.address_name', 'client_property_addresses.latitude', 'client_property_addresses.longitude', 'admins.name as attender_name', 'schedules.start_date', 'schedules.start_time', 'schedules.end_time', 'client_property_addresses.geo_address');

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                if (request()->has('search')) {
                    $keyword = request()->get('search')['value'];

                    if (!empty($keyword)) {
                        $query->where(function ($sq) use ($keyword) {
                            $sq->whereRaw("CONCAT_WS(' ', clients.firstname, clients.lastname) like ?", ["%{$keyword}%"])
                                ->orWhere('clients.email', 'like', "%" . $keyword . "%")
                                ->orWhere('clients.phone', 'like', "%" . $keyword . "%")
                                ->orWhere('admins.name', 'like', "%" . $keyword . "%");
                        });
                    }
                }
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
            ->orderColumn('start_date', function ($query, $order) {
                $query->orderBy('start_date', $order)
                    ->orderBy('start_time_standard_format', $order);
            })
            ->addColumn('action', function ($data) {
                return '';
            })
            ->rawColumns(['action'])
            ->toJson();
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
            'client_id'      => ['required'],
            'start_date'     => ['required_if:meet_via,on-site'],
            'start_time'     => ['required_if:meet_via,on-site'],
            'booking_status' => ['required'],
            'address_id'     => ['required'],
            'team_id'        => ['required']
        ], [], [
            'address_id'     => 'Property',
            'team_id'        => 'Attender'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $input = $request->input();

        \Log::info($input['meet_via']);
        if ($input['start_time']) {
            $input['end_time'] = Carbon::createFromFormat('Y-m-d h:i A', date('Y-m-d') . ' ' . $input['start_time'])->addMinutes(30)->format('h:i A');
            $input['start_time_standard_format'] = Carbon::createFromFormat('Y-m-d h:i A', date('Y-m-d') . ' ' . $input['start_time'])->toTimeString();
        }

        $client = Client::find($input['client_id']);
        if (!$client) {
            return response()->json([
                'message' => 'Client not found',
            ], 404);
        }

        if (empty($client->phone)) {
            return response()->json([
                'message' => "Client's phone is required",
            ], 403);
        }

        $schedule = Schedule::create($input);

        $client->lead_status()->updateOrCreate(
            [],
            ['lead_status' => LeadStatusEnum::POTENTIAL]
        );

        event(new ClientLeadStatusChanged($client, LeadStatusEnum::POTENTIAL));
      

        LeadActivity::create([
            'client_id' => $client->id,
            'created_date' => " ",
            'status_changed_date' => Carbon::now(),
            'changes_status' => LeadStatusEnum::POTENTIAL,
            'reason' => 'New schedule created',
        ]);

        if (!$schedule->start_date) {
            $schedule->load(['client', 'team', 'propertyAddress']);

            // $this->sendMeetingMail($schedule);
            SendMeetingMailJob::dispatch($schedule);
            if ($input['meet_via'] == 'off-site') {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::FILE_SUBMISSION_REQUEST,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));
            }

            return response()->json([
                'data' => $schedule,
                'message' => 'Meeting scheduled successfully',
            ]);
        }

        

        $googleAccessToken = Setting::query()
            ->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)
            ->value('value');

            $schedule->load(['client', 'team', 'propertyAddress']);

            $this->saveGoogleCalendarEvent($schedule);
            Notification::create([
                'user_id' => $schedule->client_id,
                'user_type' => Client::class,
                'type' => NotificationTypeEnum::SENT_MEETING,
                'meet_id' => $schedule->id,
                'status' => $schedule->booking_status
            ]);

            // $this->sendMeetingMail($schedule);
            SendMeetingMailJob::dispatch($schedule);

            if (!empty($schedule->start_time) && !empty($schedule->end_time)) {
                Notification::create([
                    'user_id' => $schedule->client_id,
                    'user_type' => get_class($client),
                    'type' => NotificationTypeEnum::SENT_MEETING,
                    'meet_id' => $schedule->id,
                    'status' => $schedule->booking_status
                ]);
            }

            return response()->json([
                'data' => $schedule,
                'message' => 'Meeting scheduled successfully',
            ]);
        // }
    }

    public function createScheduleCalendarEvent($scheduleID)
    {
        $schedule = Schedule::find($scheduleID);

        if (!$schedule) {
            return response()->json([
                'error' => [
                    'message' => 'Schedule not found!',
                    'code' => 404
                ]
            ], 404);
        }

        try {
            $schedule->load(['client', 'team', 'propertyAddress']);
            if (!$schedule->is_calendar_event_created) {
                // Initializes Google Client object
                $googleClient = $this->getClient();

                $this->saveGoogleCalendarEvent($schedule);

                Notification::create([
                    'user_id' => $schedule->client_id,
                    'user_type' => Client::class,
                    'type' => NotificationTypeEnum::SENT_MEETING,
                    'meet_id' => $schedule->id,
                    'status' => $schedule->booking_status
                ]);
            }

            // $this->sendMeetingMail($schedule);

            return response()->json([
                'data' => $schedule,
                'message' => 'Meeting scheduled successfully',
            ]);
        } catch (\Throwable $th) {
            // throw $th;

            return response()->json([
                'error' => [
                    'message' => $th->getMessage(),
                    'code' => $th->getCode()
                ]
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Schedule  $schedule
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $schedule = Schedule::query()
            ->with('client', 'team')
            ->find($id);

        if (!$schedule) {
            return response()->json([
                'error' => [
                    'message' => 'Schedule not found!',
                    'code' => 404
                ]
            ], 404);
        }

        if ($schedule->client->lastname == null) {
            $schedule->client->lastname = '';
        }

        return response()->json([
            'schedule' => $schedule
        ]);
    }

    public function getTeamEvents($id)
    {
        $statusColors = [
            'pending' => '#800080',     // purple
            'confirmed' => '#008000',
            'declined' => '#ff0000',
            'completed' => '#008000',
            'rescheduled' => '#ff0000',
        ];

        $schedules = Schedule::query()
            ->where('team_id', $id)
            ->where('start_time', '!=', '')
            ->where('end_time', '!=', '')
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->where('booking_status', '!=', 'declined')
            ->get();

        $events = [];
        foreach ($schedules as $schedule) {
            $eventArr = [];

            $date = Carbon::parse($schedule['start_date'])->format('Y-m-d');
            $startAt = $date . ' ' . $schedule['start_time'];
            $endAt = $date . ' ' . $schedule['end_time'];

            $eventArr["id"]         = $schedule['id'];
            $eventArr["title"]      = 'Busy';
            $eventArr["start"]      = Carbon::createFromFormat('Y-m-d H:i A', $startAt)->toDateTimeString();
            $eventArr["end"]        = Carbon::createFromFormat('Y-m-d H:i A', $endAt)->toDateTimeString();
            $eventArr["start_time"] = $schedule['start_time'];
            $eventArr["backgroundColor"] = $statusColors[$schedule['booking_status']];
            $eventArr["borderColor"] = $statusColors[$schedule['booking_status']];

            array_push($events, $eventArr);
        }

        return response()->json([
            'events' => $events
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Schedule  $schedule
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'start_date'     => ['required_if:meet_via,on-site'],
            'start_time'     => ['required_if:meet_via,on-site'],
            'booking_status' => ['required'],
            'address_id'     => ['required'],
            'team_id'        => ['required']
        ], [], [
            'address_id'     => 'Property',
            'team_id'        => 'Attender'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $schedule = Schedule::find($id);

        if (!$schedule) {
            return response()->json([
                'message' => 'Meeting not found',
            ], 404);
        }

        $input = $request->input();

        if ($input['start_time']) {
            $input['end_time'] = Carbon::createFromFormat('Y-m-d h:i A', date('Y-m-d') . ' ' . $input['start_time'])->addMinutes(30)->format('h:i A');
            $input['start_time_standard_format'] = Carbon::createFromFormat('Y-m-d h:i A', date('Y-m-d') . ' ' . $input['start_time'])->toTimeString();
        } else {
            $input['end_time'] = NULL;
            $input['start_time_standard_format'] = NULL;
        }

        $schedule->update([
            'team_id'   => $input['team_id'],
            'meet_via'  => $input['meet_via'],
            'meet_link' => $input['meet_link'],
            'purpose'   => $input['purpose'],
            'address_id'        => $input['address_id'],
            'booking_status'    => $input['booking_status'],
            'start_date' => $input['start_date'],
            'start_time' => $input['start_time'],
            'end_time'   => $input['end_time'],
            'start_time_standard_format'   => $input['start_time_standard_format']
        ]);

        $schedule->load(['client', 'team', 'propertyAddress']);

        if ($schedule->is_calendar_event_created) {
            // Initializes Google Client object
            $googleClient = $this->getClient();

            if ($schedule->booking_status == 'declined') {
                $this->deleteGoogleCalendarEvent($schedule);
            } else {
                $this->saveGoogleCalendarEvent($schedule);
            }
        }

        return response()->json([
            'message' => "Schedule has been updated",
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Schedule  $schedule
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $schedule = Schedule::with('client')->find($id);

        if (!$schedule) {
            return response()->json([
                'message' => 'Schedule not found',
            ], 404);
        }

        if ($schedule->is_calendar_event_created) {
            // Initializes Google Client object
            $googleClient = $this->getClient();

            $this->deleteGoogleCalendarEvent($schedule);
        }

        $scheduleArr = $schedule->toArray();

        SendMeetingNotificationJob::dispatch($scheduleArr);

        $schedule->delete();

        return response()->json([
            'message' => 'Meeting has been deleted'
        ]);
    }

    public function clientSchedules(Request $request)
    {
        $schedules = Schedule::query()
            ->with('team')
            ->where('client_id', $request->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'schedules' => $schedules
        ]);
    }

    public function latestClientSchedule(Request $request)
    {
        $latestSchedule = Schedule::query()
            ->where('client_id', $request->id)
            ->orderBy('created_at', 'desc')
            ->first();

        return response()->json([
            'latestSchedule' => $latestSchedule
        ]);
    }
}
