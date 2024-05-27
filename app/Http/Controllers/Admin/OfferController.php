<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadStatusEnum;
use App\Models\Offer;
use App\Http\Controllers\Controller;
use App\Models\LeadStatus;
use App\Traits\PriceOffered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

class OfferController extends Controller
{
    use PriceOffered;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $q = $request->q;
        $result = Offer::query()->with('client');

        $result->orWhere('status', 'like', '%' . $q . '%');
        $result->orWhere('total', 'like', '%' . $q . '%');

        $result = $result->orWhereHas('client', function ($qr) use ($q) {
            $qr->where(function ($qr) use ($q) {
                $qr->where(DB::raw('firstname'), 'like', '%' . $q . '%');
                $qr->orWhere(DB::raw('lastname'), 'like', '%' . $q . '%');
                $qr->orWhere(DB::raw('email'), 'like', '%' . $q . '%');
                $qr->orWhere(DB::raw('city'), 'like', '%' . $q . '%');
                $qr->orWhere(DB::raw('street_n_no'), 'like', '%' . $q . '%');
                $qr->orWhere(DB::raw('zipcode'), 'like', '%' . $q . '%');
                $qr->orWhere(DB::raw('phone'), 'like', '%' . $q . '%');
            });
        });

        $result = $result->orderBy('created_at', 'desc')->paginate(20);

        if (!empty($result)) {
            foreach ($result as $i => $res) {
                if (!is_null($res->client) && $res->client->lastname == null) {
                    $result[$i]->client->lastname = '';
                }
            }
        }

        return response()->json([
            'offers' => $result
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
            'client_id'    => ['required'],
            'status'       => ['required'],
            'services'     => ['required']
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $services = json_decode($request->get('services'), true);

        $tax_percentage = config('services.app.tax_percentage');
        $subtotal = 0;
        foreach ($services as $skey => $service) {
            $serviceTotal = 0;
            if ($service['type'] == 'hourly') {
                foreach ($service['workers'] as $wkey => $worker) {
                    $serviceTotal += $service['rateperhour'] * $worker['jobHours'];
                }
                $subtotal += $serviceTotal;
            } else {
                $serviceTotal += $service['fixed_price'] * count($service['workers']);
                $subtotal += $serviceTotal;
            }
            $services[$skey]['totalamount'] = $serviceTotal;
        }

        $tax_amount = ($tax_percentage / 100) * $subtotal;

        $input = $request->except(['action']);
        $input['subtotal'] = $subtotal;
        $input['total'] = $subtotal + $tax_amount;
        $input['services'] = json_encode($services, JSON_UNESCAPED_UNICODE);

        $offer = Offer::create($input);
        $offer->load(['client', 'service']);

        LeadStatus::updateOrCreate(
            [
                'client_id' => $offer->client_id,
            ],
            [
                'lead_status' =>  LeadStatusEnum::UNANSWERED
            ]
        );

        if ($request->action == 'Save and Send') {
            $this->sendOfferMail($offer);
        }

        return response()->json([
            'message' => 'Offer created successfully'
        ]);
    }

    public function sendOfferMail($offer)
    {
        $offer = $offer->toArray();
        $services = ($offer['services'] != '') ? json_decode($offer['services']) : [];
        if (isset($services)) {
            $s_names  = '';
            foreach ($services as $k => $service) {

                if ($k != count($services) - 1 && $service->service != 10) {
                    $s_names .= $service->name . ", ";
                } else if ($service->service == 10) {
                    if ($k != count($services) - 1) {
                        $s_names .= $service->other_title . ", ";
                    } else {
                        $s_names .= $service->other_title;
                    }
                } else {
                    $s_names .= $service->name;
                }
            }
        }

        $offer['service_names'] = $s_names;

        App::setLocale($offer['client']['lng']);
        if (isset($offer['client']) && !empty($offer['client']['phone'])) {
            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::OFFER_PRICE,
                "notificationData" => $offer
            ]));
        }
        Mail::send('/Mails/OfferMail', $offer, function ($messages) use ($offer) {
            $messages->to($offer['client']['email']);
            ($offer['client']['lng'] == 'en') ?
                $sub = __('mail.offer.subject') . " " . __('mail.offer.from') . " " . __('mail.offer.company') . " #" . ($offer['id'])
                : $sub = $offer['id'] . "# " . __('mail.offer.subject') . " " . __('mail.offer.from') . " " . __('mail.offer.company');

            $messages->subject($sub);
        });
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Offer  $offer
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $offer = Offer::query()
            ->with('client')
            ->find($id);

        if (!$offer) {
            return response()->json([
                'error' => [
                    'message' => 'Offer not found!',
                    'code' => 404
                ]
            ], 404);
        }

        $perhour = false;
        $services = json_decode($offer->services);
        if (isset($services)) {
            foreach ($services as $service) {
                if ($service->type == 'hourly') {
                    $perhour = true;
                }
            }
        }

        $offer->services = $this->formatServices($offer);
        ($perhour == true) ? $offer->perhour = 1 : $offer->perhour = 0;

        if ($offer['client']['lastname'] == null) {
            $offer['client']['lastname'] = "";
        }

        return response()->json([
            'offer' => $offer
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Offer  $offer
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $offer = Offer::find($id);

        return response()->json([
            'offer' => $offer
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Offer  $offer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'client_id'    => ['required'],
            'status'       => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $services = json_decode($request->get('services'), true);

        $tax_percentage = config('services.app.tax_percentage');
        $subtotal = 0;
        foreach ($services as $skey => $service) {
            $serviceTotal = 0;
            if ($service['type'] == 'hourly') {
                foreach ($service['workers'] as $wkey => $worker) {
                    $serviceTotal += $service['rateperhour'] * $worker['jobHours'];
                }
                $subtotal += $serviceTotal;
            } else {
                $serviceTotal += $service['fixed_price'] * count($service['workers']);
                $subtotal += $serviceTotal;
            }
            $services[$skey]['totalamount'] = $serviceTotal;
        }

        $tax_amount = ($tax_percentage / 100) * $subtotal;

        $offer = Offer::find($id);
        $input = $request->except(['action']);
        $input['subtotal'] = $subtotal;
        $input['total'] = $subtotal + $tax_amount;
        $input['services'] = json_encode($services, JSON_UNESCAPED_UNICODE);

        $offer->update($input);
        $offer->load(['client', 'service']);

        if ($request->action == 'Save and Send') {
            $this->sendOfferMail($offer);
        }

        return response()->json([
            'message' => 'Offer updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Offer  $offer
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $offer = Offer::find($id);
        $offer->delete();

        return response()->json([
            'message' => 'Offer has been deleted successfully'
        ]);
    }

    public function ClientOffers(Request $request)
    {
        $offers = Offer::query()
            ->with('client')
            ->where('client_id', $request->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'offers' => $offers
        ]);
    }

    public function getLatestClientOffer(Request $request)
    {
        $latestOffer = Offer::where('client_id', $request->id)->get()->last();

        return response()->json([
            'latestOffer' => $latestOffer
        ]);
    }
}
