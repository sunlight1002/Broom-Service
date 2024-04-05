<?php

namespace App\Http\Controllers\Client;

use App\Enums\ContractStatusEnum;
use App\Enums\LeadStatusEnum;
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
        'team.availability:team_member_id,time_slots',
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
      ->whereNotNull('start_time')
      ->where('start_time', '!=', '')
      ->whereNotNull('end_time')
      ->where('end_time', '!=', '')
      // ->selectRaw("DATE_FORMAT(start_date, '%Y-%m-%d') as start_date")
      ->selectRaw("DATE_FORMAT(STR_TO_DATE(start_time, '%h:%i %p'), '%H:%i') as start_time")
      ->selectRaw("DATE_FORMAT(STR_TO_DATE(end_time, '%h:%i %p'), '%H:%i') as end_time")
      ->get();

    $timeSlot = json_decode($schedule->team->availability->time_slots, true);
    $availableSlots = $timeSlot[$startDate];
    $availableSlots24Hrs = [];
    foreach ($availableSlots as $key => $value) {
      $availableSlots24Hrs[] = [
        'start' => Carbon::createFromFormat('Y-m-d H:i A', date('Y-m-d') . ' ' . $value[0])->format('H:i'),
        'end' => Carbon::createFromFormat('Y-m-d H:i A', date('Y-m-d') . ' ' . $value[1])->format('H:i'),
      ];
    }

    return response()->json([
      'schedule' => $scheduleArr,
      'booked_slots' => $bookedSlots,
      'available_slots' => $availableSlots24Hrs
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
      'type' => 'accept-offer',
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
      'type' => 'reject-offer',
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

    Notification::create([
      'user_id' => $schedule->client_id,
      'type' => 'accept-meeting',
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

    Notification::create([
      'user_id' => $schedule->client_id,
      'type' => 'reject-meeting',
      'meet_id' => $request->id,
      'status' => 'declined'
    ]);

    return response()->json([
      'message' => 'Thanks, your meeting is declined'
    ]);
  }

  public function rescheduleMeeting(Request $request)
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
      'booking_status' => 'rescheduled'
    ]);

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
        'type' => 'contract-accept',
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
        'type' => 'contract-reject',
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
    $offer = Offer::query()->with('client')->find($contract->offer_id);

    $card = $contract->card;
    if (!$card) {
      $card = $this->getClientCard($contract->client_id);
    }

    $offer['services'] = $this->formatServices($offer);

    return response()->json([
      'offer' => $offer,
      'contract' => $contract,
      'card' => $card,
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
    $sch = Schedule::create([
      'booking_status' => 'pending',
      'start_date'     => $request['data']['startDate'],
      'start_time'     => $request['data']['startTime'],
      'end_time'       => $request['data']['endTime'],
      'client_id'      => $request['data']['client']['id'],
    ]);

    return response()->json([
      'schedule' => $sch
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

    if ($schedule->booking_status == 'confirmed') {
      return response()->json([
        'message' => 'Meeting is already confirmed'
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
