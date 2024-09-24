<?php

namespace App\Http\Controllers\Client;

use App\Enums\ContractStatusEnum;
use App\Enums\LeadStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Events\ClientLeadStatusChanged;
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
use App\Events\OfferAccepted;
// use App\Events\ClientOfferAccepted;
use App\Events\ReScheduleMeetingJob;
use App\Events\SendClientLogin;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\App;
use App\Jobs\SendUninterestedClientEmail;
use Illuminate\Mail\Mailable;
use App\Jobs\SendMeetingMailJob;

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
        'propertyAddress:id,address_name,latitude,longitude,geo_address'
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

      $client = $offer->client;
      $ofr = $offer->toArray();

      $hash = md5($ofr['client']['email'] . $ofr['id']);

      $contract = Contract::create([
          'offer_id'   => $offer->id,
          'client_id'  => $ofr['client']['id'],
          'unique_hash' => $hash,
          'status'     => ContractStatusEnum::NOT_SIGNED
      ]);

      $newLeadStatus = LeadStatusEnum::PENDING_CLIENT;

      if ($client->lead_status->lead_status != $newLeadStatus) {
          $client->lead_status()->updateOrCreate(
              [],
              ['lead_status' => $newLeadStatus]
          );

          event(new ClientLeadStatusChanged($client, $newLeadStatus));

          $emailData = [
              'client' => $client->toArray(),
              'status' => $newLeadStatus,
          ];

          if($newLeadStatus === 'freeze client'){
              // Trigger WhatsApp Notification
              event(new WhatsappNotificationEvent([
                  "type" => WhatsappMessageTemplateEnum::CLIENT_IN_FREEZE_STATUS,
                  "notificationData" => [
                      'client' => $client->toArray(),
                  ]
              ]));
          }

          if ($client->notification_type === "both") {

            if ($newLeadStatus === 'uninterested') {

              event(new WhatsappNotificationEvent([
                  "type" => WhatsappMessageTemplateEnum::FOLLOW_UP_ON_OUR_CONVERSATION,
                  "notificationData" => [
                      'client' => $client->toArray(),
                  ]
              ]));

              SendUninterestedClientEmail::dispatch($client, $emailData);
          }

              if ($newLeadStatus === 'unanswered') {

                  event(new WhatsappNotificationEvent([
                      "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
                      "notificationData" => [
                          'client' => $client->toArray(),
                      ]
                  ]));
                  // App::setLocale($client['lng']);
                  // Mail::send('Mails.UnansweredLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                  //     $messages->to($emailData['client']['email']);
                  //     $sub = __('mail.unanswered_lead.header');
                  //     $messages->subject($sub);
                  // });
              }

              if ($newLeadStatus === 'irrelevant') {

                  event(new WhatsappNotificationEvent([
                      "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
                      "notificationData" => [
                          'client' => $client->toArray(),
                      ]
                  ]));
                  // App::setLocale($client['lng']);
                  // Mail::send('Mails.IrrelevantLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                  //     $messages->to($emailData['client']['email']);
                  //     $sub = __('mail.irrelevant_lead.header');
                  //     $messages->subject($sub);
                  // });
              }

              // event(new WhatsappNotificationEvent([
              //     "type" => WhatsappMessageTemplateEnum::USER_STATUS_CHANGED,
              //     "notificationData" => [
              //         'client' => $client->toArray(),
              //         'status' => $newLeadStatus,
              //     ]
              // ]));

          } elseif ($client->notification_type === "email") {

            if ($newLeadStatus === 'uninterested') {
              SendUninterestedClientEmail::dispatch($client, $emailData);
          }

              if ($newLeadStatus === 'unanswered') {
                // App::setLocale($client['lng']);
                //   Mail::send('Mails.UnansweredLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                //       $messages->to($emailData['client']['email']);
                //       $sub = __('mail.unanswered_lead.header');
                //       $messages->subject($sub);
                //   });
              }
              if ($newLeadStatus === 'irrelevant') {
                // App::setLocale($client['lng']);
                //   Mail::send('Mails.IrrelevantLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                //       $messages->to($emailData['client']['email']);
                //       $sub = __('mail.irrelevant_lead.header');
                //       $messages->subject($sub);
                //   });
              }

            //   event(new WhatsappNotificationEvent([
            //     "type" => WhatsappMessageTemplateEnum::USER_STATUS_CHANGED,
            //     "notificationData" => [
            //         'client' => $client->toArray(),
            //         'status' => $newLeadStatus,
            //     ]
            // ]));

          } else {

            if ($newLeadStatus === 'uninterested') {

              event(new WhatsappNotificationEvent([
                  "type" => WhatsappMessageTemplateEnum::FOLLOW_UP_ON_OUR_CONVERSATION,
                  "notificationData" => [
                      'client' => $client->toArray(),
                  ]
              ]));

          }
              if ($newLeadStatus === 'unanswered') {

                  event(new WhatsappNotificationEvent([
                      "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
                      "notificationData" => [
                          'client' => $client->toArray(),
                      ]
                  ]));
              }
              if ($newLeadStatus === 'irrelevant') {
                  event(new WhatsappNotificationEvent([
                      "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
                      "notificationData" => [
                          'client' => $client->toArray(),
                      ]
                  ]));
              }
              // event(new WhatsappNotificationEvent([
              //     "type" => WhatsappMessageTemplateEnum::USER_STATUS_CHANGED,
              //     "notificationData" => [
              //         'client' => $client->toArray(),
              //         'status' => $newLeadStatus,
              //     ]
              // ]));
          }
      }
      Notification::create([
          'user_id' => $ofr['client']['id'],
          'user_type' => Client::class,
          'type' => NotificationTypeEnum::LEAD_ACCEPTED_PRICE_OFFER,
          'offer_id' => $offer->id,
          'status' => 'accepted'
      ]);

      event(new WhatsappNotificationEvent([
          "type" => WhatsappMessageTemplateEnum::LEAD_ACCEPTED_PRICE_OFFER,
          "notificationData" => [
              'client' => $client->toArray(),
          ]
      ]));

      // Mail::send('Mails.ReminderLeadPriceOffer', ['client' => $emailData['client']], function ($messages) use ($emailData) {
      //     $messages->to($emailData['client']['email']);
      //     $sub = __('mail.price_offer_reminder.header');
      //     $messages->subject($sub);
      // });

      $ofr['contract_id'] = $hash;

      event(new OfferAccepted($ofr));

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
            'user_type' => get_class($client),
            'type' => NotificationTypeEnum::LEAD_DECLINED_PRICE_OFFER,
            'offer_id' => $offer->id,
            'status' => 'declined'
        ]);

        // Trigger WhatsApp Notification
        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::LEAD_DECLINED_PRICE_OFFER,
            "notificationData" => [
                'client' => $client->toArray(),
            ]
        ]));

      //   $emailData = [
      //     'client' => $client->toArray(),
      //     'status' => $newLeadStatus,
      // ];

      //   Mail::send('Mails.LeadDeclinePriceOffer', ['client' => $emailData['client']], function ($messages) use ($emailData) {
      //     $messages->to($emailData['client']['email']);
      //     $sub = __('mail.price_offer_decline.header');
      //     $messages->subject($sub);
      // });

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

    if ($schedule->is_calendar_event_created) {
      // Initializes Google Client object
      $googleClient = $this->getClient();

      $this->saveGoogleCalendarEvent($schedule);
    }

    Notification::create([
      'user_id' => $schedule->client_id,
      'user_type' => get_class($client),
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

    $client->update(['status' => 0]);

    $schedule->load(['client', 'team', 'propertyAddress']);

    if ($schedule->is_calendar_event_created) {
      // Initializes Google Client object
      $googleClient = $this->getClient();

      $this->deleteGoogleCalendarEvent($schedule);
    }

    Notification::create([
      'user_id' => $schedule->client_id,
      'user_type' => get_class($client),
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

    // Initializes Google Client object
    $googleClient = $this->getClient();

    $this->saveGoogleCalendarEvent($schedule);

    $schedule->load(['client', 'team', 'propertyAddress']);
    event(new ReScheduleMeetingJob($schedule));

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


      $args = [
        'client_id'   => $client->id,
        'card_type'   => $request->input('card_type'),
        'card_number' => $request->input('card_number'),
        'cvv'         => $request->input('cvv'),
        'card_holder_id' => $client->id,
        'card_holder_name' => $request->input('card_holder_name')
      ];

      $card = ClientCard::create($args);

      $contract->update(['card_id' => $card->id]);

      // $card = ClientCard::query()->find($request->id);

      // if (!$card) {
      //   return response()->json([
      //     'message' => "No card found"
      //   ], 404);
      // }

      $input = $request->input();
      $input['signed_at'] = now()->toDateTimeString();

      $contract->update($input);

      if ($client->status != 2) {
        $client->update([
          'status' => 2
        ]);

        Notification::create([
          'user_id' => $contract->client_id,
          'user_type' => get_class($client),
          'type' => NotificationTypeEnum::CONVERTED_TO_CLIENT,
          'status' => 'converted'
        ]);
      }

      $newLeadStatus = LeadStatusEnum::PENDING_CLIENT;

      if (!$client->lead_status || $client->lead_status->lead_status != $newLeadStatus) {
        $client->lead_status()->updateOrCreate(
          [],
          ['lead_status' => $newLeadStatus]
        );

        event(new ClientLeadStatusChanged($client, $newLeadStatus));

        $emailData = [
          'client' => $client->toArray(),
          'status' => $newLeadStatus,
      ];

      if($newLeadStatus === 'freeze client'){
        // Trigger WhatsApp Notification
        event(new WhatsappNotificationEvent([
           "type" => WhatsappMessageTemplateEnum::CLIENT_IN_FREEZE_STATUS,
           "notificationData" => [
               'client' => $client->toArray(),
           ]
       ]));
   }

   if ($client->notification_type === "both") {

    if ($newLeadStatus === 'uninterested') {

      event(new WhatsappNotificationEvent([
          "type" => WhatsappMessageTemplateEnum::FOLLOW_UP_ON_OUR_CONVERSATION,
          "notificationData" => [
              'client' => $client->toArray(),
          ]
      ]));

      SendUninterestedClientEmail::dispatch($client, $emailData);
  }

    if ($newLeadStatus === 'unanswered') {

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
            "notificationData" => [
                'client' => $client->toArray(),
            ]
        ]));
        // App::setLocale($client['lng']);
        // Mail::send('Mails.UnansweredLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
        //     $messages->to($emailData['client']['email']);
        //     $sub = __('mail.unanswered_lead.header');
        //     $messages->subject($sub);
        // });
    }

    if ($newLeadStatus === 'irrelevant') {
        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
            "notificationData" => [
                'client' => $client->toArray(),
            ]
        ]));
        // App::setLocale($client['lng']);
        // Mail::send('Mails.IrrelevantLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
        //     $messages->to($emailData['client']['email']);
        //     $sub = __('mail.irrelevant_lead.header');
        //     $messages->subject($sub);
        // });
    };
        // event(new WhatsappNotificationEvent([
        //     "type" => WhatsappMessageTemplateEnum::USER_STATUS_CHANGED,
        //     "notificationData" => [
        //         'client' => $client->toArray(),
        //         'status' => $newLeadStatus,
        //     ]
        // ]));

    } elseif ($client->notification_type === "email") {

      if ($newLeadStatus === 'uninterested') {
        SendUninterestedClientEmail::dispatch($client, $emailData);
    }

    if ($newLeadStatus === 'unanswered') {
      // App::setLocale($client['lng']);
      //   Mail::send('Mails.UnansweredLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
      //       $messages->to($emailData['client']['email']);
      //       $sub = __('mail.unanswered_lead.header');
      //       $messages->subject($sub);
      //   });
    }
    if ($newLeadStatus === 'irrelevant') {
      // App::setLocale($client['lng']);
      //   Mail::send('Mails.IrrelevantLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
      //       $messages->to($emailData['client']['email']);
      //       $sub = __('mail.irrelevant_lead.header');
      //       $messages->subject($sub);
      //   });
    }

  //   event(new WhatsappNotificationEvent([
  //     "type" => WhatsappMessageTemplateEnum::USER_STATUS_CHANGED,
  //     "notificationData" => [
  //         'client' => $client->toArray(),
  //         'status' => $newLeadStatus,
  //     ]
  // ]));

} else {

  if ($newLeadStatus === 'uninterested') {

    event(new WhatsappNotificationEvent([
        "type" => WhatsappMessageTemplateEnum::FOLLOW_UP_ON_OUR_CONVERSATION,
        "notificationData" => [
            'client' => $client->toArray(),
        ]
    ]));
}

    if ($newLeadStatus === 'unanswered') {

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
            "notificationData" => [
                'client' => $client->toArray(),
            ]
        ]));
    }
    if ($newLeadStatus === 'irrelevant') {

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
            "notificationData" => [
                'client' => $client->toArray(),
            ]
        ]));
    }

        // event(new WhatsappNotificationEvent([
        //     "type" => WhatsappMessageTemplateEnum::USER_STATUS_CHANGED,
        //     "notificationData" => [
        //         'client' => $client->toArray(),
        //         'status' => $newLeadStatus,
        //     ]
        // ]));
    }
      }

      event(new WhatsappNotificationEvent([
        "type" => WhatsappMessageTemplateEnum::BOOK_CLIENT_AFTER_SIGNED_CONTRACT,
        "notificationData" => [
            'client' => $client->toArray(),
        ]
       ]));

      $client->makeVisible('passcode');

      event(new SendClientLogin($client->toArray()));

      return response()->json([
        'message' => "Thanks, for accepting contract"
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'error' => $e->getMessage()
      ], 500);
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
    ], 200);
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

      $client = Client::find($contract->client_id);
      $client->update(['status' => 1]);
      Notification::create([
        'user_id' => $contract->client_id,
        'user_type' => get_class($client),
        'type' => NotificationTypeEnum::CONTRACT_REJECT,
        'contract_id' => $contract->id,
        'status' => 'declined'
      ]);

      event(new WhatsappNotificationEvent([
        "type" => WhatsappMessageTemplateEnum::LEAD_DECLINED_CONTRACT,
        "notificationData" => [
            'client' => $client->toArray(),
        ]
       ]));

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

    $cards = ClientCard::query()
        ->where('client_id', $client->id) // Filter by client_id
        ->when(
            $contract->status != ContractStatusEnum::NOT_SIGNED,
            function ($q) use ($contract) {
                return $q->where('id', $contract->card_id);
            }
        )
        ->get(['id', 'card_number', 'valid', 'card_type', 'card_holder_name', 'card_holder_id']);

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

  public function getClientInfo($id)
  {
    $client = Client::find($id);

    return response()->json([
      'client' => $client
    ]);
  }

  public function addMeet(Request $request)
  {
    $client = Client::find($request['data']['client']['id']);
    if (!$client) {
      return response()->json([
        'message' => 'Client not found'
      ], 404);
    }

    $start_time_standard_format = Carbon::createFromFormat('Y-m-d h:i A', date('Y-m-d') . ' ' . $request['data']['startDate'])->toTimeString();

    $schedule = Schedule::create([
      'booking_status' => 'pending',
      'start_date'     => $request['data']['startDate'],
      'start_time'     => $request['data']['startTime'],
      'end_time'       => $request['data']['endTime'],
      'start_time_standard_format'       => $start_time_standard_format,
      'client_id'      => $request['data']['client']['id'],
    ]);

    $newLeadStatus = LeadStatusEnum::POTENTIAL;

    if (!$client->lead_status || $client->lead_status->lead_status != $newLeadStatus) {
      $client->lead_status()->updateOrCreate(
        [],
        ['lead_status' => $newLeadStatus]
      );

      event(new ClientLeadStatusChanged($client, $newLeadStatus));

      $emailData = [
        'client' => $client->toArray(),
        'status' => $newLeadStatus,
    ];

    if($newLeadStatus === 'freeze client'){
      // Trigger WhatsApp Notification
      event(new WhatsappNotificationEvent([
         "type" => WhatsappMessageTemplateEnum::CLIENT_IN_FREEZE_STATUS,
         "notificationData" => [
             'client' => $client->toArray(),
         ]
     ]));
 }

    if ($client->notification_type === "both") {

      if ($newLeadStatus === 'uninterested') {

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::FOLLOW_UP_ON_OUR_CONVERSATION,
            "notificationData" => [
                'client' => $client->toArray(),
            ]
        ]));

        SendUninterestedClientEmail::dispatch($client, $emailData);
    }

      if ($newLeadStatus === 'unanswered') {


          event(new WhatsappNotificationEvent([
              "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
              "notificationData" => [
                  'client' => $client->toArray(),
              ]
          ]));
          // App::setLocale($client['lng']);
          // Mail::send('Mails.UnansweredLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
          //     $messages->to($emailData['client']['email']);
          //     $sub = __('mail.unanswered_lead.header');
          //     $messages->subject($sub);
          // });
      }

      if ($newLeadStatus === 'irrelevant') {

          event(new WhatsappNotificationEvent([
              "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
              "notificationData" => [
                  'client' => $client->toArray(),
              ]
          ]));
          // App::setLocale($client['lng']);
          // Mail::send('Mails.IrrelevantLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
          //     $messages->to($emailData['client']['email']);
          //     $sub = __('mail.irrelevant_lead.header');
          //     $messages->subject($sub);
          // });
      };
          // event(new WhatsappNotificationEvent([
          //     "type" => WhatsappMessageTemplateEnum::USER_STATUS_CHANGED,
          //     "notificationData" => [
          //         'client' => $client->toArray(),
          //         'status' => $newLeadStatus,
          //     ]
          // ]));

    } elseif ($client->notification_type === "email") {

      if ($newLeadStatus === 'uninterested') {

        SendUninterestedClientEmail::dispatch($client, $emailData);
    }

      if ($newLeadStatus === 'unanswered') {
        // App::setLocale($client['lng']);
        //   Mail::send('Mails.UnansweredLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
        //       $messages->to($emailData['client']['email']);
        //       $sub = __('mail.unanswered_lead.header');
        //       $messages->subject($sub);
        //   });
      }
      if ($newLeadStatus === 'irrelevant') {
        // App::setLocale($client['lng']);
        //   Mail::send('Mails.IrrelevantLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
        //       $messages->to($emailData['client']['email']);
        //       $sub = __('mail.irrelevant_lead.header');
        //       $messages->subject($sub);
        //   });
      }
    //   event(new WhatsappNotificationEvent([
    //     "type" => WhatsappMessageTemplateEnum::USER_STATUS_CHANGED,
    //     "notificationData" => [
    //         'client' => $client->toArray(),
    //         'status' => $newLeadStatus,
    //     ]
    // ]));

    } else {

      if ($newLeadStatus === 'uninterested') {

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::FOLLOW_UP_ON_OUR_CONVERSATION,
            "notificationData" => [
                'client' => $client->toArray(),
            ]
        ]));

    }

      if ($newLeadStatus === 'unanswered') {
          event(new WhatsappNotificationEvent([
              "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
              "notificationData" => [
                  'client' => $client->toArray(),
              ]
          ]));
      }
      if ($newLeadStatus === 'irrelevant') {

          event(new WhatsappNotificationEvent([
              "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
              "notificationData" => [
                  'client' => $client->toArray(),
              ]
          ]));
      }
          // event(new WhatsappNotificationEvent([
          //     "type" => WhatsappMessageTemplateEnum::USER_STATUS_CHANGED,
          //     "notificationData" => [
          //         'client' => $client->toArray(),
          //         'status' => $newLeadStatus,
          //     ]
          // ]));
      }
    }

    $schedule->load(['client', 'team', 'propertyAddress']);

    // Initializes Google Client object
    $googleClient = $this->getClient();

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
    $startTimeStandardFormat = Carbon::createFromFormat('Y-m-d H:i', date('Y-m-d') . ' ' . $data['start_time'])->toTimeString();

    $schedule->update([
      'start_time' => $startTime,
      'end_time' => $endTime,
      'start_time_standard_format' => $startTimeStandardFormat,
      'booking_status' => 'confirmed'
    ]);

    $schedule->load(['client', 'team', 'propertyAddress']);

    // Initializes Google Client object
    $googleClient = $this->getClient();

    $this->saveGoogleCalendarEvent($schedule);
    // $this->sendMeetingMail($schedule);
    SendMeetingMailJob::dispatch($schedule);

    return response()->json([
      'message' => 'Meeting is confirmed successfully'
    ]);
  }
}
