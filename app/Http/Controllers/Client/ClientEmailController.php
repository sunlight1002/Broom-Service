<?php

namespace App\Http\Controllers\Client;

use App\Enums\ContractStatusEnum;
use App\Enums\LeadStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Schedule;
use App\Models\Offer;
use App\Models\Services;
use App\Models\Contract;
use App\Models\ClientCard;
use App\Models\LeadStatus;
use App\Models\Notification;
use App\Traits\ClientCardTrait;
use App\Traits\PriceOffered;
use App\Traits\ScheduleMeeting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\ReScheduleMettingJob;
use App\Events\WhatsappNotificationEvent;

class ClientEmailController extends Controller
{
  use PriceOffered, ClientCardTrait, ScheduleMeeting;

  public function ShowMeeting(Request $request)
  {
    $id = $request->id;
    $schedule = Schedule::query()
      ->with([
        'client:id,lng,firstname,lastname',
        'team:id,name,heb_name',
        'team.availabilities:team_member_id,date,start_time,end_time',
        'propertyAddress:id,address_name,latitude,longitude'
      ])
      ->find($id);

    if (!$schedule) {
      return response()->json([
        'message' => 'Meeting not found'
      ], 404);
    }

    $scheduleArr = $schedule;
    $startDate = Carbon::parse($scheduleArr['start_date'])->toDateString();

    $bookedSlots = Schedule::query()
      ->whereDate('start_date', $startDate)
      ->where('team_id', $schedule->team_id)
      ->whereNotNull('start_time')
      ->where('start_time', '!=', '')
      ->whereNotNull('end_time')
      ->where('end_time', '!=', '')
      // ->selectRaw("DATE_FORMAT(start_date, '%Y-%m-%d') as start_date")
      ->selectRaw("DATE_FORMAT(STR_TO_DATE(start_time, '%h:%i %p'), '%H:%i') as start_time")
      ->selectRaw("DATE_FORMAT(STR_TO_DATE(end_time, '%h:%i %p'), '%H:%i') as end_time")
      ->get();

    return response()->json([
      'schedule' => $scheduleArr,
      'booked_slots' => $bookedSlots,
    ]);
  }

  public function GetOffer($id)
  {
    $offer = Offer::query()->with('client')->find($id);

    $offer->services = $this->formatServices($offer);

    return response()->json([
      'data' => $offer
    ]);
  }

  public function AcceptOffer(Request $request)
  {
    $offer = Offer::query()
      ->with('client')
      ->find($request->id);

    $offer->update([
      'status' => 'accepted'
    ]);

    $ofr = $offer->toArray();

    Notification::create([
      'user_id' => $ofr['client']['id'],
      'type' => NotificationTypeEnum::ACCEPT_OFFER,
      'offer_id' => $offer->id,
      'status' => 'accepted'
    ]);

    LeadStatus::UpdateOrCreate(
      [
        'client_id' => $ofr['client']['id']
      ],
      [
        'lead_status' => LeadStatusEnum::POTENTIAL_CLIENT
      ]
    );

    $hash = md5($ofr['client']['email'] . $ofr['id']);

    $contract = Contract::create([
      'offer_id'   => $offer->id,
      'client_id'  => $ofr['client']['id'],
      'unique_hash' => $hash,
      'status'     => ContractStatusEnum::NOT_SIGNED
    ]);

    $ofr['contract_id'] = $hash;

    App::setLocale($ofr['client']['lng']);

    if (isset($ofr['client']) && !empty($ofr['client']['phone'])) {
      event(new WhatsappNotificationEvent([
        "type" => WhatsappMessageTemplateEnum::CONTRACT,
        "notificationData" => $ofr
      ]));
    }
    Mail::send('/Mails/ContractMail', $ofr, function ($messages) use ($ofr) {
      $messages->to($ofr['client']['email']);
      $ofr['client']['lng'] ?
        $sub = __('mail.contract.subject') . "  " . __('mail.contract.company') . " for offer #" . $ofr['id']
        :  $sub = $ofr['id'] . "# " . __('mail.contract.subject') . "  " . __('mail.contract.company');

      $messages->subject($sub);
    });

    return response()->json([
      'message' => 'Offer is accepted'
    ]);
  }

  public function RejectOffer(Request $request)
  {
    $offer = Offer::with('client')->find($request->id);
    if (!$offer) {
      return response()->json([
        'message' => 'Offer not found'
      ], 404);
    }

    $client = $offer->client;
    if (!$client) {
      return response()->json([
        'message' => 'Client not found'
      ], 404);
    }

    $offer->update([
      'status' => 'declined'
    ]);

    $offerArr = $offer->toArray();

    Notification::create([
      'user_id' => $offerArr['client']['id'],
      'type' => NotificationTypeEnum::REJECT_OFFER,
      'offer_id' => $offer->id,
      'status' => 'declined'
    ]);

    $client->lead_status()->updateOrCreate(
      [],
      ['lead_status' => LeadStatusEnum::UNINTERESTED]
    );

    return response()->json([
      'message' => 'Thanks, your offer has been rejected'
    ]);
  }

  public function acceptMeeting(Request $request)
  {
    $schedule = Schedule::find($request->id);
    if (!$schedule) {
      return response()->json([
        'message' => 'Meeting not found'
      ], 404);
    }

    $client = $schedule->client;
    if (!$client) {
      return response()->json([
        'message' => 'Client not found'
      ], 404);
    }

    $schedule->update([
      'booking_status' => 'confirmed'
    ]);

    $client->update(['status' => 1]);

    $schedule->load(['client', 'team', 'propertyAddress']);

    $this->saveGoogleCalendarEvent($schedule);

    Notification::create([
      'user_id' => $schedule->client_id,
      'type' => NotificationTypeEnum::ACCEPT_MEETING,
      'meet_id' => $request->id,
      'status' => 'confirmed'
    ]);

    return response()->json([
      'message' => 'Thanks, your meeting is confirmed'
    ]);
  }

  public function rejectMeeting(Request $request)
  {
    $schedule = Schedule::find($request->id);
    if (!$schedule) {
      return response()->json([
        'message' => 'Meeting not found'
      ], 404);
    }

    $client = $schedule->client;
    if (!$client) {
      return response()->json([
        'message' => 'Client not found'
      ], 404);
    }

    $schedule->update([
      'booking_status' => 'declined'
    ]);

    $client->lead_status()->updateOrCreate(
      [],
      ['lead_status' => LeadStatusEnum::IRRELEVANT]
    );

    $client->update(['status' => 0]);

    $schedule->load(['client', 'team', 'propertyAddress']);

    if ($schedule->is_calendar_event_created) {
      $this->deleteGoogleCalendarEvent($schedule);
    }

    Notification::create([
      'user_id' => $schedule->client_id,
      'type' => NotificationTypeEnum::REJECT_MEETING,
      'meet_id' => $request->id,
      'status' => 'declined'
    ]);

    return response()->json([
      'message' => 'Thanks, your meeting is declined'
    ]);
  }

  public function rescheduleMeeting(Request $request, $id)
  {
    $data = $request->all();

    $schedule = Schedule::find($id);
    if (!$schedule) {
      return response()->json([
        'message' => 'Meeting not found'
      ], 404);
    }

    $client = $schedule->client;
    if (!$client) {
      return response()->json([
        'message' => 'Client not found'
      ], 404);
    }

    $data['end_time'] = Carbon::createFromFormat('Y-m-d h:i A', date('Y-m-d') . ' ' . $data['start_time'])->addMinutes(30)->format('h:i A');
    $data['start_time_standard_format'] = Carbon::createFromFormat('Y-m-d h:i A', date('Y-m-d') . ' ' . $data['start_time'])->toTimeString();

    $schedule->update([
      'start_date' => $data['start_date'],
      'start_time' => $data['start_time'],
      'end_time' => $data['end_time'],
      'start_time_standard_format' => $data['start_time_standard_format'],
      'booking_status' => 'rescheduled'
    ]);


    $this->saveGoogleCalendarEvent($schedule);

    $schedule->load(['client', 'team', 'propertyAddress']);
    event(new ReScheduleMettingJob($schedule));

    return response()->json([
      'message' => 'Thanks, your meeting is rescheduled'
    ]);
  }

  public function AcceptContract(Request $request)
  {
    try {
      $contract = Contract::query()
        ->with('client')
        ->where('unique_hash', $request->unique_hash)
        ->first();

      if (!$contract) {
        return response()->json([
          'message' => "Contract not found"
        ], 404);
      }

      $client = $contract->client;
      if (!$client) {
        return response()->json([
          'message' => "Client not found"
        ], 404);
      }

      $card = ClientCard::query()->find($request->card_id);

      if (!$card) {
        return response()->json([
          'message' => "No card found"
        ], 404);
      }

      Contract::where('unique_hash', $request->unique_hash)->update($request->input());

      Notification::create([
        'user_id' => $contract->client_id,
        'type' => NotificationTypeEnum::CONTRACT_ACCEPT,
        'contract_id' => $contract->id,
        'status' => 'accepted'
      ]);

      $client->lead_status()->updateOrCreate(
        [],
        ['lead_status' => LeadStatusEnum::PENDING_CLIENT]
      );

      return response()->json([
        'message' => "Thanks, for accepting contract"
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'error' => $e->getMessage()
      ]);
    }
  }

  public function RejectContract(Request $request)
  {
    try {
      $contract = Contract::query()
        ->with('client')
        ->find($request->id);

      if (!$contract) {
        return response()->json([
          'error' => 'Contract not found'
        ]);
      }

      $contract->update(['status' => ContractStatusEnum::DECLINED]);

      Client::where('id', $contract->client_id)->update(['status' => 1]);
      Notification::create([
        'user_id' => $contract->client_id,
        'type' => NotificationTypeEnum::CONTRACT_REJECT,
        'contract_id' => $contract->id,
        'status' => 'declined'
      ]);

      return response()->json([
        'message' => "Contract has been rejected"
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'error' => $e->getMessage()
      ]);
    }
  }

  public function contractByHash($hash)
  {
    $contract = Contract::with('card')->where('unique_hash', $hash)->latest()->first();
    if (!$contract) {
      return response()->json([
        'message' => 'Contract not found',
      ], 404);
    }

    $client = Client::find($contract->client_id);
    if (!$client) {
      return response()->json([
        'message' => 'Client not found',
      ], 404);
    }

    $offer = Offer::query()->with('client')->find($contract->offer_id);
    if (!$offer) {
      return response()->json([
        'message' => 'Offer not found',
      ], 404);
    }

    $cards = $client->cards()
      ->when(
        $contract->status != ContractStatusEnum::NOT_SIGNED,
        function ($q) use ($contract) {
          return $q->where('id', $contract->card_id);
        }
      )
      ->get(['id', 'card_number', 'valid', 'card_type']);

    $offer['services'] = $this->formatServices($offer);

    return response()->json([
      'offer' => $offer,
      'contract' => $contract,
      'cards' => $cards,
    ]);
  }

  public function serviceTemplate(Request $request)
  {
    $template = Services::query()
      ->select('template')
      ->find($request->id);

    return response()->json(['template' => $template]);
  }

  public function getClient(Request $request)
  {
    $client = Client::find($request->id);

    return response()->json([
      'client' => $client
    ]);
  }

  public function addMeet(Request $request)
  {
    $start_time_standard_format = Carbon::createFromFormat('Y-m-d h:i A', date('Y-m-d') . ' ' . $request['data']['startDate'])->toTimeString();

    $schedule = Schedule::create([
      'booking_status' => 'pending',
      'start_date'     => $request['data']['startDate'],
      'start_time'     => $request['data']['startTime'],
      'end_time'       => $request['data']['endTime'],
      'start_time_standard_format'       => $start_time_standard_format,
      'client_id'      => $request['data']['client']['id'],
    ]);

    $schedule->load(['client', 'team', 'propertyAddress']);

    $this->saveGoogleCalendarEvent($schedule);

    return response()->json([
      'schedule' => $schedule
    ]);
  }

  public function getSchedule($id)
  {
    $sch = Schedule::where('client_id', $id)
      ->where('booking_status', '!=', 'declined')
      ->where('start_date', '>=', Carbon::now())
      ->get();

    if (count($sch) > 0) {
      return response()->json([
        'status_code' => 200,
        'schedule' => $sch[0]
      ]);
    } else {
      return response()->json([
        'status_code' => 400
      ]);
    }
  }

  public function saveMeetingSlot(Request $request, $id)
  {
    $schedule = Schedule::find($id);
    if (!$schedule) {
      return response()->json([
        'message' => 'Meeting not found'
      ], 404);
    }

    if ($schedule->booking_status == 'completed') {
      return response()->json([
        'message' => 'Meeting is already completed'
      ], 403);
    }

    if ($schedule->start_time && $schedule->end_time) {
      return response()->json([
        'message' => 'Meeting slot is already selected'
      ], 403);
    }

    if ($schedule->booking_status == 'declined') {
      return response()->json([
        'message' => 'Meeting is already declined'
      ], 403);
    }

    if ($schedule->booking_status == 'rescheduled') {
      return response()->json([
        'message' => 'Meeting is already rescheduled'
      ], 403);
    }

    $data = $request->all();

    $startTime = Carbon::createFromFormat('Y-m-d H:i', date('Y-m-d') . ' ' . $data['start_time'])->format('h:i A');
    $endTime = Carbon::createFromFormat('Y-m-d H:i', date('Y-m-d') . ' ' . $data['end_time'])->format('h:i A');

    $schedule->update([
      'start_time' => $startTime,
      'end_time' => $endTime,
      'booking_status' => 'confirmed'
    ]);

    $schedule->load(['client', 'team', 'propertyAddress']);

    $this->saveGoogleCalendarEvent($schedule);
    $this->sendMeetingMail($schedule);

    return response()->json([
      'message' => 'Meeting is confirmed successfully'
    ]);
  }
}
