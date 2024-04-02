<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadStatusEnum;
use App\Enums\SettingKeyEnum;
use App\Http\Controllers\Controller;
use App\Models\LeadStatus;
use App\Models\Schedule;
use App\Models\Offer;
use App\Models\Notification;
use App\Models\Setting;
use App\Traits\GoogleAPI;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Event\WhatsappNotificationEvent;

class ScheduleController extends Controller
{
    use GoogleAPI;

    protected $googleCalendarID;

    public function __construct()
    {
        $this->googleCalendarID = config('services.google.calendar_id');
    }

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
            'start_date'     => ['required'],
            'start_time'     => ['required'],
            'end_time'       => ['required'],
            'booking_status' => ['required'],
            'address_id'     => ['required']
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $input = $request->input();
        $schedule = Schedule::create($input);

        LeadStatus::updateOrCreate(
            ['client_id' => $schedule->client_id],
            ['lead_status' => LeadStatusEnum::MEETING_PENDING]
        );

        $googleAccessToken = Setting::query()
            ->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)
            ->value('value');

        // Initializes Google Client object
        $client = $this->getClient();
        if (!$googleAccessToken) {
            /**
             * Generate the url at google we redirect to
             */
            $authUrl = $client->createAuthUrl(null, ['state' => 'SCH-' . $schedule->id]);

            return response()->json([
                'action' => 'redirect',
                'url' => $authUrl,
            ]);
        } else {
            $schedule->load(['client', 'team', 'propertyAddress']);
            $this->pushEvent($schedule);

            Notification::create([
                'user_id' => $schedule->client_id,
                'type' => 'sent-meeting',
                'meet_id' => $schedule->id,
                'status' => $schedule->booking_status
            ]);

            $this->sendMeetingMail($schedule);

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
                $client = $this->getClient();

                $this->pushEvent($schedule);

                Notification::create([
                    'user_id' => $schedule->client_id,
                    'type' => 'sent-meeting',
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

    public function pushEvent($schedule)
    {
        $googleAccessToken = Setting::query()
            ->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)
            ->value('value');

        $userTimezone = $this->getUserCalendarTimezone($googleAccessToken);

        $eventTitle = "Meeting with " . $schedule->client->firstname . " " . $schedule->client->lastname;
        $clientPhone = (!empty($schedule->client->phone)) ? $schedule->client->phone : 'phone N/A';

        $description = "Between " . $schedule->start_time . " - " . $schedule->end_time . " <br>" . $schedule->client->email . " <br> " . $clientPhone;

        $eventDate = Carbon::parse($schedule->start_date)->toDateString();
        if ($schedule->start_time) {
            $fullDayEvent = false;

            $eventTime = [
                'event_date' => $eventDate,
                'event_start_at' => Carbon::createFromFormat('Y-m-d H:i A', $eventDate . ' ' . $schedule->start_time)->toRfc3339String(),
                'event_end_at' => Carbon::createFromFormat('Y-m-d H:i A', $eventDate . ' ' . $schedule->end_time)->toRfc3339String(),
            ];
        } else {
            $fullDayEvent = true;

            $eventTime = [
                'event_date' => $eventDate,
            ];
        }

        $event_id = $this->createCalendarEvent(
            $eventTitle,
            $fullDayEvent,
            $eventTime,
            $userTimezone,
            $googleAccessToken,
            $description,
            $schedule->propertyAddress->geo_address
        );

        $schedule->update([
            'is_calendar_event_created' => true,
            'google_calendar_event_id' => $event_id
        ]);
    }

    public function sendMeetingMail($schedule)
    {
        $services = Offer::where('client_id', $schedule->client_id)->get()->last();
        $service_names = '';

        if (!empty($services->services)) {
            $allServices = json_decode($services->services);
            foreach ($allServices as $k => $serv) {

                if ($k != count($allServices) - 1 && $serv->service != 10) {
                    $service_names .= $serv->name . ", ";
                } else if ($serv->service == 10) {
                    if ($k != count($allServices) - 1) {
                        $service_names .= $serv->other_title . ", ";
                    } else {
                        $service_names .= $serv->other_title;
                    }
                } else {
                    $service_names .= $serv->name;
                }
            }
        }

        $scheduleArr = $schedule->toArray();
        $scheduleArr['service_names'] = $service_names;
        App::setLocale($scheduleArr['client']['lng']);
        if (isset($scheduleArr['client']) && !empty($scheduleArr['client']['phone'])) {
            event(new WhatsappNotificationEvent(["type" => 'client_meeting_schedule', "notificationData" => $scheduleArr]));
        }
        Mail::send('/Mails/MeetingMail', $scheduleArr, function ($messages) use ($scheduleArr) {
            $messages->to($scheduleArr['client']['email']);

            if ($scheduleArr['client']['lng'] == 'en') {
                $subject = __('mail.meeting.subject') . " " . __('mail.meeting.from') . " " . __('mail.meeting.company') . " #" . $scheduleArr['id'];
            } else {
                $subject = $scheduleArr['id'] . "# " . __('mail.meeting.subject') . " " . __('mail.meeting.from') . " " . __('mail.meeting.company');
            }

            $messages->subject($subject);
        });

        $schedule->update(['meeting_mail_sent_at' => now()]);
    }

    public function createCalendarEvent(
        $summary,
        $isAllDayEvent,
        $event_time,
        $event_timezone,
        $access_token,
        $description,
        $location
    ) {
        $postData = array('summary' => $summary);

        if ($isAllDayEvent) {
            $postData = [
                'summary' => $summary,
                'description' => $description,
                'start' => array(
                    'date' => $event_time['event_date'],
                    'timeZone' => $event_timezone,
                ),
                'end' => array(
                    'date' => $event_time['event_date'],
                    'timeZone' => $event_timezone,
                ),
                // 'attendees' => array(
                //     array('email' => 'demo01@example.com'),
                //     array('email' => 'demo02@example.com'),
                // ),
                'location' => $location,
                'reminders' => array(
                    'useDefault' => FALSE,
                    'overrides' => array(
                        array('method' => 'email', 'minutes' => 24 * 60),
                        array('method' => 'popup', 'minutes' => 10),
                    ),
                ),
            ];
        } else {
            $postData = [
                'summary' => $summary,
                'description' => $description,
                'start' => array(
                    'dateTime' => $event_time['event_start_at'],
                    'timeZone' => $event_timezone,
                ),
                'end' => array(
                    'dateTime' => $event_time['event_end_at'],
                    'timeZone' => $event_timezone,
                ),
                // 'attendees' => array(
                //     array('email' => 'demo01@example.com'),
                //     array('email' => 'demo02@example.com'),
                // ),
                'location' => $location,
                'reminders' => array(
                    'useDefault' => FALSE,
                    'overrides' => array(
                        array('method' => 'email', 'minutes' => 24 * 60),
                        array('method' => 'popup', 'minutes' => 10),
                    ),
                ),
            ];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json',
        ])->post(
            'https://www.googleapis.com/calendar/v3/calendars/' . $this->googleCalendarID . '/events',
            $postData
        );

        $data = $response->json();
        $http_code = $response->status();

        if ($http_code != 200) {
            $this->notifyError($http_code);

            throw new Exception('Error : Failed to create event');
        }

        return $data['id'];
    }

    public function getUserCalendarTimezone($access_token)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
        ])->get('https://www.googleapis.com/calendar/v3/users/me/settings/timezone');

        $data = $response->json();
        $http_code = $response->status();

        if ($http_code != 200) {
            $this->notifyError($http_code);

            throw new Exception('Error : Failed to get timezone');
        }

        return $data['value'];
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

    public function getEvents(Request $request)
    {
        $schedules = Schedule::query()
            ->where('team_id', $request->tid)
            ->where('booking_status', '!=', 'declined')
            ->get();

        $events = [];
        if ($schedules->count()) {
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

                array_push($events, $eventArr);
            }
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
        $change = '';
        if ($request->name == 'start_date') {
            Schedule::where('id', $id)->update([
                'booking_status' => 'pending',
                'start_date'     => $request->value,
                'start_time'     => '',
                'end_time'       => ''
            ]);
            $change = 'date';
        } else {
            Schedule::where('id', $id)->update([
                $request->name => $request->value
            ]);
            $change = "other";
        }

        return response()->json([
            'message' => str_replace('_', ' ', $request->name) . " has been updated",
            'change'  => $change
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
        $schedule = Schedule::with('client')->find($id)->first()->toArray();

        if ($schedule) {
            return response()->json([
                'error' => [
                    'message' => 'Schedule not found!',
                    'code' => 404
                ]
            ], 404);
        }

        $scheduleArr = $schedule->toArray();

        App::setLocale($scheduleArr['client']['lng']);
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
