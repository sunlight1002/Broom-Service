<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\LeadComment;
use App\Models\LeadStatus;
use App\Models\Offer;
use App\Models\ClientPropertyAddress;
use App\Models\Schedule;
use App\Models\WebhookResponse;
use App\Models\WhatsappLastReply;
use Illuminate\Support\Facades\Hash;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $fburl = 'https://graph.facebook.com/v17.0/';
    public $fbleads = [];
    public $pa_token;

    public function index(Request $request)
    {
        $q = $request->q;
        $c = $request->condition;

        $result = Client::with(['meetings', 'offers', 'lead_status']);

        if (!is_null($q) &&  ($q !== 1 && $q !== 0 && $q != 'all') && $c != 'filter') {

            $result->where(function ($query) use ($q) {
                $ex = explode(' ', $q);
                $q2 = isset($ex[1]) ? $ex[1] : $q;

                $query->where('email', 'like', '%' . $q . '%')
                    ->orWhere('firstname', 'like', '%' . $ex[0] . '%')
                    ->orWhere('lastname', 'like', '%' . $q2 . '%')
                    ->orWhere('phone', 'like', '%' . $q . '%');
            });
        }

        if (!is_null($q) &&  ($q == 1 || $q == 0)) {
            $result->where('status', $q);
        } else if (is_null($q)) {
            $result->where('status', 0)->orWhere('status', 1);
        }

        if ($q == 'pending') {
            $result = $result->WhereHas('lead_status', function ($q) {
                $q->where(function ($q) {
                    $q->where('lead_status', 'Pending');
                });
            })->orWhereDoesntHave('lead_status');
        } else if ($q == 'set') {
            $result = $result->WhereHas('lead_status', function ($q) {
                $q->where(function ($q) {
                    $q->where('lead_status', 'Meeting Set');
                });
            });
        } else if ($q == 'offersend') {
            $result = $result->WhereHas('lead_status', function ($q) {
                $q->where(function ($q) {
                    $q->where('lead_status', 'Offer Sent');
                });
            });
        } else if ($q == 'offerdecline') {
            $result = $result->WhereHas('lead_status', function ($q) {
                $q->where(function ($q) {
                    $q->where('lead_status', 'Offer Rejected');
                });
            });
        } else if ($q == 'uninterested') {
            $result = $result->WhereHas('lead_status', function ($q) {
                $q->where(function ($q) {
                    $q->where('lead_status', 'Uninterested');
                });
            });
        }

        $result = $result->where('status', '!=', 2);
        $result = $result->orderBy('id', 'desc')->paginate(20);

        return response()->json([
            'leads' => $result,
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
        $data = $request->data;

        $validator = Validator::make($data, [
            'firstname' => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:clients'],
            'phone'     => ['nullable', 'unique:clients'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $input = $data;
        $input['password'] = isset($input['phone']) && !empty($input['phone']) ?
            Hash::make($input['phone']) :
            Hash::make('password');

        $client = Client::create($input);

        $property_address_data = $request->propertyAddress;
        if (count($property_address_data) > 0) {
            foreach ($property_address_data as $key => $address) {
                $address['client_id'] = $client->id;
                ClientPropertyAddress::create($address);
            }
        }

        LeadStatus::UpdateOrCreate(
            [
                'client_id' => $client->id
            ],
            [
                'client_id' => $client->id,
                'lead_status' => 'Pending'
            ]
        );

        return response()->json([
            'message' => 'Lead created successfully',
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $lead                = Client::find($id);
        $lead->lead_status   = $request->lead_status;
        $lead->save();

        return response()->json([
            'message' => 'status updated',
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
        $lead = Client::query()
            ->with(['offers', 'meetings', 'lead_status', 'property_addresses'])
            ->find($id);

        if (!empty($lead)) {
            $offer = Offer::where('client_id', $id)->get()->last();
            $lead->latest_offer = $offer;

            $meeting = Schedule::where('client_id', $id)->get()->last();
            $lead->latest_meeting = $meeting;

            $reply = ($lead->phone != NULL && $lead->phone != '' && $lead->phone != 0) ?
                WhatsappLastReply::where('phone', 'like', '%' . $lead->phone . '%')
                ->first() : null;

            $_first_contact = ($lead->phone != NULL && $lead->phone != '' && $lead->phone != 0) ?
                WebhookResponse::where('number', 'like', '%' . $lead->phone . '%')
                ->where('flex', 'C')
                ->first() : null;

            if (!empty($reply)) {
                if ($reply->message < 2) {
                    $reply->msg = WebhookResponse::getWhatsappMessage('message_' . $reply->message, 'heb', $lead);
                } else {
                    $reply->msg = $reply->message;
                }
            }

            $lead->reply = $reply;
            $lead->first_contact = $_first_contact;
        }
        return response()->json([
            'lead' => $lead,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->data, [
            'firstname' => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:clients,email,' . $id],
            'phone'     => ['nullable', 'unique:clients,phone,' . $id],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $client = Client::find($id);

        $input = $request->data;
        if ((isset($input['passcode']) && $input['passcode'] != null)) {
            $input['password'] = Hash::make($input['passcode']);
        } else {
            $input['password'] = $client->password;
        }

        $client->update($input);
        return response()->json([
            'message' => 'Lead updated successfully',
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
        $client = Client::find($id);
        $client->delete();

        return response()->json([
            'message' => "Lead has been deleted"
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        return response()->json([
            'message' => 'status updated',
        ]);
    }

    public function addComment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comment'  => 'required',
            'lead_id'  => 'required',
            'team_id'  => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        LeadComment::create([
            'comment'   => $request->comment,
            'lead_id' => $request->lead_id,
            'team_id' => $request->team_id
        ]);

        return response()->json(['message' => 'comment added']);
    }

    public function getComments(Request $request)
    {
        $comments = LeadComment::where('lead_id', $request->id)->with('team')->get();

        return response()->json(['comments' => $comments]);
    }

    public function deleteComment(Request $request)
    {
        $leadComment = LeadComment::find($request->id);
        $leadComment->delete();

        return response()->json(['message' => 'comment deleted']);
    }

    public function uninterested($id)
    {
        LeadStatus::UpdateOrCreate(
            [
                'client_id' => $id
            ],
            [
                'client_id'   => $id,
                'lead_status' => 'Uninterested'
            ]
        );

        return response()->json(['message' => 'Marked Uninterested']);
    }

    /* FB ADS LEADS */
    public function longLivedToken()
    {
        $url = $this->fburl . 'oauth/access_token?grant_type=fb_exchange_token&client_id=' . config('services.facebook.app_id') . '&client_secret=' . config('services.facebook.app_secret') . '&fb_exchange_token=' . config('services.facebook.access_token');
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return  'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $result = json_decode($result);
        if (isset($result->error)) {
            return $result->error->message;
        }
        return $result->access_token;
    }

    public function pageAccessToken()
    {
        $url = $this->fburl . config('services.facebook.app_scope_id') . '/accounts?access_token=' .  config('services.facebook.access_token');

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result);
        if (isset($result->error)) {
            return $result->error->message;
        }
        if (count($result->data) > 0) {
            foreach ($result->data as $r) {
                if ($r->id == config('services.facebook.account_id')) {
                    return $r->access_token;
                }
            }
        }
    }

    public function leadGenForms()
    {
        $pa_token =  $this->pageAccessToken();
        $this->pa_token =  $pa_token;
        $url = $this->fburl . config('services.facebook.account_id') . '/leadgen_forms?access_token=' . $pa_token . '&pretty=0&limit=2500';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return  'Error:' . curl_error($ch);
        }

        $result = json_decode($result);

        if (isset($result->error)) {
            return $result->error->message;
        }

        return $result;
    }

    public function leadData($id)
    {
        $ch = curl_init();
        $url = $this->fburl . $id . '/leads?access_token=' . $this->pa_token;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        $result = json_decode($result);

        if (isset($result->error)) {
            return $result->error->message;
        }
        if (isset($result->data) && count($result->data) > 0) {

            $_fd = $result->data[0]->field_data;
            foreach ($_fd as $fd) {
                echo "<pre>";
                print_r($fd);
            }

            dd(1);
        }
        return $result;
    }

    public function fbAdsLead()
    {
        $leadForms = $this->leadGenForms();

        if (count($leadForms->data) > 0) {
            foreach ($leadForms->data as $lf) {
                dd($this->leadData($lf->id));
            }
        }
    }

    public function savePropertyAddress(Request $request)
    {
        try {
            $property_address = $request->data;
            if (count($property_address) > 0) {
                $savedAddress = ClientPropertyAddress::UpdateOrCreate(
                    [
                        'id' => $property_address['id']
                    ],
                    $property_address
                );
                return response()->json([
                    'data' => $savedAddress,
                    'message'   => 'Lead property address saved successfully',
                ], 200);
            } else {
                return response()->json([
                    'message'   => 'Data is empty!',
                ], 500);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'message'   => 'Something went wrong!',
            ], 500);
        }
    }
    public function removePropertyAddress($id)
    {
        try {
            ClientPropertyAddress::find($id)->delete();
            return response()->json([
                'message'     => "Lead property address has been deleted"
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message'   => 'Something went wrong!',
            ], 500);
        }
    }
}
