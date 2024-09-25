<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadStatusEnum;
use App\Events\ClientLeadStatusChanged;
use App\Events\OfferSaved;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\LeadStatus;
use App\Models\Offer;
use App\Traits\PriceOffered;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use Illuminate\Support\Facades\Mail;
use App\Models\Notification;
use App\Enums\NotificationTypeEnum;
use App\Jobs\SendUninterestedClientEmail;
use Illuminate\Mail\Mailable;
use App\Models\LeadActivity;

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
        $query = Offer::query()
            ->leftJoin('clients', 'offers.client_id', '=', 'clients.id')
            ->select('offers.id', 'clients.id as client_id', 'clients.firstname', 'clients.lastname', 'clients.email', 'clients.phone', 'offers.status', 'offers.subtotal', 'offers.total', 'offers.created_at');

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                if (request()->has('search')) {
                    $keyword = request()->get('search')['value'];

                    if (!empty($keyword)) {
                        $query->where(function ($sq) use ($keyword) {
                            $sq->whereRaw("CONCAT_WS(' ', clients.firstname, clients.lastname) like ?", ["%{$keyword}%"])
                                ->orWhere('clients.email', 'like', "%" . $keyword . "%")
                                ->orWhere('clients.phone', 'like', "%" . $keyword . "%");
                        });
                    }
                }
            })
            ->editColumn('created_at', function ($data) {
                return $data->created_at ? Carbon::parse($data->created_at)->format('d/m/Y') : '-';
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
            'client_id'    => ['required'],
            'comment'      => ['nullable'],
            'status'       => ['required'],
            'services'     => ['required']
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        if (!$request->get('client_id')) {
            return response()->json([
                'message' => 'Client not selected'
            ], 404);
        }

        $client = Client::find($request->get('client_id'));
        if (!$client) {
            return response()->json([
                'message' => 'Client not found'
            ], 404);
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

        $newLeadStatus = LeadStatusEnum::POTENTIAL_CLIENT;

        if (!$client->lead_status || $client->lead_status->lead_status != $newLeadStatus) {
            $client->lead_status()->updateOrCreate(
                [],
                ['lead_status' => $newLeadStatus]
            );

            LeadActivity::create([
                'client_id' => $client->id,
                'created_date' => " ",
                'status_changed_date' => now(),
                'changes_status' => $newLeadStatus,
                'reason' => "New price offered",
            ]);

            event(new ClientLeadStatusChanged($client, $newLeadStatus));

        }

        if ($request->action == 'Save and Send') {
            event(new OfferSaved($offer->toArray()));   
        }

        return response()->json([
            'message' => 'Offer created successfully',
            'offer' => $offer->toArray(),
        ]);
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
            'comment'      => ['nullable'],
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
            event(new OfferSaved($offer->toArray()));
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

    public function ClientOffers(Request $request, $id)
    {
        $query = Offer::query()
            ->where('offers.client_id', $id)
            ->select('offers.id', 'offers.status', 'offers.subtotal');

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                if (request()->has('search')) {
                    $keyword = request()->get('search')['value'];

                    if (!empty($keyword)) {
                        $query->where(function ($sq) use ($keyword) {
                            $sq->where('offers.subtotal', 'like', "%" . $keyword . "%");
                        });
                    }
                }
            })
            ->addColumn('action', function ($data) {
                return '';
            })
            ->rawColumns(['action'])
            ->toJson();
    }

    public function getLatestClientOffer(Request $request)
    {
        $latestOffer = Offer::where('client_id', $request->id)->get()->last();

        return response()->json([
            'latestOffer' => $latestOffer
        ]);
    }
}
