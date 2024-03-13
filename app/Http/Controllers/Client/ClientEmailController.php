<?php

namespace App\Http\Controllers\Client;

use App\Enums\ContractStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Schedule;
use App\Models\Offer;
use App\Models\Services;
use App\Models\Contract;
use App\Models\ClientCard;
use App\Models\LeadStatus;
use App\Models\Notification;
use App\Models\ClientPropertyAddress;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class ClientEmailController extends Controller
{
  public function ShowMeeting(Request $request)
  {
    $id = $request->id;
    $schedule = Schedule::query()->with('client', 'team')->find($id);
    $services = Offer::where('client_id', $schedule->client->id)->get()->last();
    $str = '';
    if (!empty($services->services)) {

      $allServices = json_decode($services->services);
      foreach ($allServices as $k => $serv) {
        $s = Services::where('id', $serv->service)->get('name')->first()->toArray();
        if ($k != count($s)) {
          $str .= $s['name'] . ", ";
        } else {
          $str .= $s['name'];
        }
      }
    }

    $schedule->service_names = $str;
    return response()->json([
      'schedule' => $schedule
    ]);
  }

  public function GetOffer(Request $request)
  {
    $id = $request->id;
    $offer = Offer::where('id', $id)->with('client')->get();
    $services = ($offer[0]->services != '') ? json_decode($offer[0]->services) : [];

    //map serice property address
    if (isset($services)) {
        foreach ($services as $service) {
            if(!empty($service->address)){
                $service->address = ClientPropertyAddress::find($service->address)->toArray();
            }
        }
    }
    $offer[0]->services = json_encode($services, true);

    return response()->json([
      'offer' => $offer
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
        'client_id' => $ofr['client']['id'],
        'lead_status' => 'Offer Accepted'
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
    $offer = Offer::find($request->id);

    $offer->update([
      'status' => 'declined'
    ]);

    $offer->load('client');
    $offerArr = $offer->toArray();

    Notification::create([
      'user_id' => $offerArr['client']['id'],
      'type' => 'reject-offer',
      'offer_id' => $offer->id,
      'status' => 'declined'
    ]);

    LeadStatus::UpdateOrCreate(
      [
        'client_id' => $offerArr['client']['id']
      ],
      [
        'client_id' => $offerArr['client']['id'],
        'lead_status' => 'Offer Rejected'
      ]
    );
  }

  public function AcceptMeeting(Request $request)
  {
    try {
      Schedule::where('id', $request->id)->update([
        'booking_status' => $request->response
      ]);

      $sch = Schedule::where('id', $request->id)->get('client_id')->first();

      $ls =  LeadStatus::UpdateOrCreate(
        [
          'client_id' => $sch->client_id
        ],
        [
          'client_id' => $sch->client_id,
          'lead_status' => ($request->response == 'confirmed') ? 'Meeting Set' : ($request->response == 'rescheduled' ? 'Meeting Rescheduled' : 'Meeting Rejected')
        ]
      );

      if ($request->response == 'confirmed') {
        Client::where('id', $sch->client_id)->update(['status' => 1]);

        Notification::create([
          'user_id' => $sch->client_id,
          'type' => 'accept-meeting',
          'meet_id' => $request->id,
          'status' => $request->response
        ]);
      } else {
        Client::where('id', $sch->client_id)->update(['status' => 0]);

        Notification::create([
          'user_id' => $sch->client_id,
          'type' => 'reject-meeting',
          'meet_id' => $request->id,
          'status' => $request->response
        ]);
      }

      return response()->json([
        'message' => 'Thanks, your meeting is ' . $request->response
      ]);
    } catch (\Exception $e) {
      return $e->getMessage();
    }
  }

  public function AcceptContract(Request $request)
  {
    try {
      $contract = Contract::query()
        ->with('client')
        ->where('unique_hash', $request->unique_hash)
        ->first();
      $card = ClientCard::query()
        ->where('client_id', $contract->client->id)
        ->first();

      if (
        config('services.app.old_contract') == true ||
        (config('services.app.old_contract') == false && !empty($card))
      ) {
        Contract::where('unique_hash', $request->unique_hash)->update($request->input());
        Client::where('id', $contract->client_id)->update(['status' => 2]);

        Notification::create([
          'user_id' => $contract->client_id,
          'type' => 'contract-accept',
          'contract_id' => $contract->id,
          'status' => 'accepted'
        ]);

        LeadStatus::UpdateOrCreate(
          [
            'client_id' => $contract->client->id
          ],
          [
            'client_id' => $contract->client->id,
            'lead_status' => 'Contract Accepted'
          ]
        );

        return response()->json([
          'message' => "Thanks, for accepting contract"
        ]);
      } else {
        return response()->json([
          'message' => 0
        ]);
      }
    } catch (\Exception $e) {
      return response()->json([
        'error' => $e->getMessage()
      ]);
    }
  }

  public function saveCard(Request $request)
  {
    $args = [
      'client_id'   => $request->cdata['cid'],
      'card_type'   => $request->cdata['card_type'],
      'card_number' => $request->cdata['card_number'],
      'valid'       => $request->cdata['valid'],
      'cvv'         => $request->cdata['cvv'],
      'cc_charge'   => $request->cdata['cc_charge'],
      'card_token'  => $request->cdata['card_token'],
    ];

    ClientCard::create($args);
    return response()->json([
      'message' => "Card validated successfully"
    ]);
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

      LeadStatus::UpdateOrCreate(
        [
          'client_id' => $contract->client->id
        ],
        [
          'client_id' => $contract->client->id,
          'lead_status' => 'Contract Rejected'
        ]
      );

      return response()->json([
        'message' => "Contract has been rejected"
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'error' => $e->getMessage()
      ]);
    }
  }

  public function GetOfferFromHash(Request $request)
  {
    $offer = Contract::where('unique_hash', $request->token)->get()->last();
    $goffer = Offer::where('id', $offer->offer_id)->with('client')->get();
    $cid = $goffer[0]->client_id;

    $exist_card = ClientCard::where('client_id', $cid)->where('card_token', '!=', null)->first();

    for ($i=0; $i < count($goffer) ; $i++) { 
      $services = json_decode($goffer[$i]['services']);
      if (isset($services)) {
          foreach ($services as $service) {
              if(!empty($service->address)){
                  $service->address = ClientPropertyAddress::find($service->address)->toArray();
              }
          }            
      }
      $goffer[$i]['services'] = json_encode($services, true);
    }


    if (isset($exist_card->card_token)) {
      $offer->add_card = 0;
    } else {
      $offer->add_card = 1;
    }

    return response()->json([
      'old_contract' => config('services.app.old_contract'),
      'offer' => $goffer,
      'contract' => $offer,
      'card' => $exist_card,
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
}
