<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\SettingKeyEnum;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\LeadStatus;
use App\Models\Schedule;
use App\Models\Notification;
use App\Models\Setting;
use App\Traits\GoogleAPI;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Traits\ScheduleMeeting;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

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
        $q = $request->q;
        $result = Schedule::query()->with('client', 'team', 'propertyAddress');
        $result->orWhere('booking_status', 'like', '%' . $q . '%');
        $result->orWhere('end_time',       'like', '%' . $q . '%');
        $result->orWhere('start_date',     'like', '%' . $q . '%');
        $result->orWhere('start_time', 'like', '%' . $q . '%');

        $result = $result->orWhereHas('client', function ($qr) use ($q) {
            $qr->where(function ($qr) use ($q) {
                $qr->where(DB::raw('firstname'), 'like', '%' . $q . '%');
                $qr->orWhere(DB::raw('lastname'), 'like', '%' . $q . '%');
                $qr->orWhere(DB::raw('city'), 'like', '%' . $q . '%');
                $qr->orWhere(DB::raw('street_n_no'), 'like', '%' . $q . '%');
                $qr->orWhere(DB::raw('zipcode'), 'like', '%' . $q . '%');
                $qr->orWhere(DB::raw('phone'), 'like', '%' . $q . '%');
            });
        });

        $result = $result->orWhereHas('team', function ($qr) use ($q) {
            $qr->where(function ($qr) use ($q) {
                $qr->where(DB::raw('name'), 'like', '%' . $q . '%');
            });
        });

        $result = $result->orderBy('created_at', 'desc')->paginate(20);

        if (!empty($result)) {
            foreach ($result as $i => $res) {
                if ($res->client->lastname == null) {
                    $result[$i]->client->lastname = '';
                }
            }
        }

        return response()->json([
            'schedules' => $result
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

        $schedule->load(['client', 'propertyAddress']);

        if (!$schedule->start_date) {
            $this->sendMeetingMail($schedule);

            return response()->json([
                'data' => $schedule,
                'message' => 'Meeting scheduled successfully',
            ]);
        }

        $googleAccessToken = Setting::query()
            ->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)
            ->value('value');

        // Initializes Google Client object
        $googleClient = $this->getClient();
        if (!$googleAccessToken) {
            /**
             * Generate the url at google we redirect to
             */
            $authUrl = $googleClient->createAuthUrl(null, ['state' => 'SCH-' . $schedule->id]);

            return response()->json([
                'action' => 'redirect',
                'url' => $authUrl,
            ]);
        } else {
            $schedule->load(['client', 'team', 'propertyAddress']);

            $this->saveGoogleCalendarEvent($schedule);

            $this->sendMeetingMail($schedule);

            if (!empty($schedule->start_time) && !empty($schedule->end_time)) {
                Notification::create([
                    'user_id' => $schedule->client_id,
                    'type' => NotificationTypeEnum::SENT_MEETING,
                    'meet_id' => $schedule->id,
                    'status' => $schedule->booking_status
                ]);
            }

            return response()->json([
                'data' => $schedule,
                'message' => 'Meeting scheduled successfully',
            ]);
        }
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
                    'type' => NotificationTypeEnum::SENT_MEETING,
                    'meet_id' => $schedule->id,
                    'status' => $schedule->booking_status
                ]);
            }

            $this->sendMeetingMail($schedule);

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

        App::setLocale($scheduleArr['client']['lng']);
        if (isset($scheduleArr['client']) && !empty($scheduleArr['client']['phone'])) {
            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::DELETE_MEETING,
                "notificationData" => $scheduleArr
            ]));
        }
        Mail::send('/Mails/DeleteMeetingMail', $scheduleArr, function ($messages) use ($scheduleArr) {
            $messages->to($scheduleArr['client']['email']);
            $sub = __('mail.cancel_meeting.subject') . " " . __('mail.cancel_meeting.from') . " " . __('mail.cancel_meeting.company') . " #" . $scheduleArr['id'];
            $messages->subject($sub);
        });

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
