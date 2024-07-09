<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Fblead;
use App\Models\Client;
use App\Models\LeadComment;
use App\Models\Offer;
use App\Models\ClientPropertyAddress;
use App\Models\Schedule;
use App\Models\WebhookResponse;
use App\Models\WhatsappLastReply;
use App\Traits\ICountDocument;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class LeadController extends Controller
{
    use ICountDocument;
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
        $query = Client::query()
            ->leftJoin('leadstatus', 'leadstatus.client_id', '=', 'clients.id')
            ->where('clients.status', '!=', 2)
            ->select('clients.id', 'clients.firstname', 'clients.lastname', 'clients.email', 'clients.phone', 'leadstatus.lead_status', 'clients.created_at');

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                if (request()->has('search')) {
                    $keyword = request()->get('search')['value'];

                    if (!empty($keyword)) {
                        $query->where(function ($sq) use ($keyword) {
                            $sq->whereRaw("CONCAT_WS(' ', clients.firstname, clients.lastname) like ?", ["%{$keyword}%"])
                                ->orWhere('clients.email', 'like', "%" . $keyword . "%")
                                ->orWhere('clients.phone', 'like', "%" . $keyword . "%")
                                ->orWhere('leadstatus.lead_status', 'like', $keyword);
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
            ->filterColumn('lead_status', function ($query, $keyword) {
                $sql = "leadstatus.lead_status like ?";
                $query->whereRaw($sql, ["{$keyword}"]);
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
        $data = $request->data;

        $validator = Validator::make($data, [
            'firstname' => ['required', 'string', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:50'],
            'email'     => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:clients'],
            'phone'     => ['nullable', 'unique:clients'],
                        
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $input = $data;
        $password =  isset($input['phone']) && !empty($input['phone'])
            ? $input['phone']
            : 'password';
        $input['password'] = Hash::make($password);
        $input['passcode'] = $password;

        $client = Client::create($input);

        // Create user in iCount
        $iCountResponse = $this->createOrUpdateUser($request);
    
        // Handle iCount response
        if ($iCountResponse->status() != 200) {
            return response()->json(['error' => 'Failed to create user in iCount'], 500);
        }
    
        $iCountData = $iCountResponse->json();
        
        // Extract Client_id from iCount response and update the Client model
        if (isset($iCountData['client_id'])) {
            $client->update(['icount_client_id' => $iCountData['client_id']]);
        }
    

        $property_address_data = $request->propertyAddress;
        if (count($property_address_data) > 0) {
            foreach ($property_address_data as $key => $address) {
                $address['client_id'] = $client->id;
                ClientPropertyAddress::create($address);
            }
        }

        $client->lead_status()->updateOrCreate(
            [],
            ['lead_status' => LeadStatusEnum::PENDING]
        );

        return response()->json([
            'message' => 'Lead created successfully',
            'data' => $client,
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
            ->with(['offers', 'meetings', 'lead_status', 'property_addresses', 'latestLog'])
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
            'vat_number' => ['nullable', 'string', 'max:50'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:clients,email,' . $id],
            'phone'     => ['nullable', 'unique:clients,phone,' . $id],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $client = Client::find($id);

        $input = $request->data;

        if (isset($input['status'])) {
            unset($input['status']);
        }

        if ((isset($input['passcode']) && $input['passcode'] != null)) {
            $input['password'] = Hash::make($input['passcode']);
        } else {
            $input['password'] = $client->password;
        }

        // Create user in iCount
        $iCountResponse = $this->createOrUpdateUser($request);
    
        // Handle iCount response
        if ($iCountResponse->status() != 200) {
            return response()->json(['error' => 'Failed to create user in iCount'], 500);
        }
    
        $iCountData = $iCountResponse->json();
        
        // Extract Client_id from iCount response and update the Client model
        if (isset($iCountData['client_id'])) {
            $client->update(['icount_client_id' => $iCountData['client_id']]);
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

        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }

        // Call deleteUser method with the iCount client ID
        $iCountResponse = $this->deleteUser($client->icount_client_id);

        // Handle iCount response
        if ($iCountResponse->status() != 200) {
            return response()->json(['error' => 'Failed to delete user in iCount'], 500);
        }

        // Delete the client from the local database
        $client->delete();

        return response()->json([
            'message' => "Client has been deleted",
            'client' => $client,
            'iCountResponse' => $iCountResponse->json()
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
                ]);
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
                'message' => "Lead property address has been deleted"
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Something went wrong!',
            ], 500);
        }
    }

    public function facebookWebhook(Request $request)
    {
        $challenge = $request->hub_challenge;
        if (!empty($challenge)) {
            $verify_token = $request->hub_verify_token;
            if ($verify_token === config('services.facebook.webhook_token')) {
                Fblead::create(["challenge" => $challenge]);
                return $challenge;
            }
        } else {
            $pageAccessToken = $this->pageAccessToken();
            $request_data = $request->getContent();
            Log::info("webhook_request_data");
            Log::info("type - " . gettype($request_data));
            Log::info($request_data);
            $request_data = json_decode($request_data, true);

            if (
                isset($request_data['object']) &&
                $request_data['object'] == "page" &&
                isset($request_data['entry']) &&
                isset($request_data['entry'][0]) &&
                count($request_data['entry'][0]) > 0 &&
                !empty($pageAccessToken)
            ) {
                $entry_data = $request_data['entry'][0];
                $changes_data = $entry_data['changes'];
                foreach ($changes_data as $key => $changes) {
                    $response = $this->getLeadData($changes['value']['leadgen_id'], $pageAccessToken);
                    if ($response['http_code'] == 200) {
                        $lead_data = $response['lead_data'];
                        $name_keys = ['full_name', 'phone_number', 'email'];
                        $field_data = $lead_data['field_data'];
                        $mapped_field_data = [];
                        foreach ($field_data as $key => $field) {
                            if (
                                isset($field['name']) &&
                                in_array($field['name'], $name_keys) &&
                                $field['values'] &&
                                count($field['values']) > 0
                            ) {
                                $mapped_field_data[$field['name']] = $field['values'][0];
                            }
                        }

                        $email = isset($mapped_field_data['email']) && !empty($mapped_field_data['email']) ? $mapped_field_data['email'] : 'lead' . $lead_data['id'] . '@lead.com';

                        $name = isset($mapped_field_data['full_name']) && !empty($mapped_field_data['full_name']) ? explode(' ', $mapped_field_data['full_name']) : explode(' ', 'lead ' . $lead_data['id']);

                        $phone = isset($mapped_field_data['phone_number']) && !empty($mapped_field_data['phone_number']) ? str_replace('+', '', $mapped_field_data['phone_number']) : '';
                        $lng = 'heb';
                        if (isset($phone) && strlen($phone) > 10 && substr($phone, 0, 3) != 972) {
                            $lng = 'en';
                        }
                        // Fblead::create(["challenge" => json_encode($lead_data)]);
                        $client = Client::updateOrCreate([
                            'email'             => $email,
                        ], [
                            'payment_method'    => 'cc',
                            'password'          => Hash::make($lead_data['id']),
                            'passcode'          => $lead_data['id'],
                            'status'            => 0,
                            'lng'               => $lng,
                            'firstname'         => $name[0],
                            'lastname'          => $name[1],
                            'phone'             => $phone,
                        ]);
                        if (!empty($phone)) {
                            $m = "Hi, I'm Bar, the digital representative of Broom Service. How can I help you today? 😊\n\nAt any stage, you can return to the main menu by sending the number 9 or return one menu back by sending the number 0.\n\n1. About the Service\n2. Service Areas\n3. Set an appointment for a quote\n4. Customer Service\n5. Switch to a human representative (during business hours)\n7. שפה עברית";
                            if($lng == 'heb') {
                                $m = 'היי, אני בר, הנציגה הדיגיטלית של ברום סרוויס. איך אוכל לעזור לך היום? 😊' . "\n\n" . 'בכל שלב תוכלו לחזור לתפריט הראשי ע"י שליחת המס 9 או לחזור תפריט אחד אחורה ע"י שליחת הספרה 0' . "\n\n" . '1. פרטים על השירות' . "\n" . '2. אזורי שירות' . "\n" . '3. קביעת פגישה לקבלת הצעת מחיר' . "\n" . '4. שירות ללקוחות קיימים' . "\n" . '5. מעבר לנציג אנושי (בשעות הפעילות)' . "\n" . '6. English menu';
                            }
                            $result = sendWhatsappMessage($phone, array('name' => '', 'message' => $m), $lng == 'heb' ? 'he' : 'en');
                        }
                        $client->lead_status()->updateOrCreate(
                            [],
                            ['lead_status' => LeadStatusEnum::PENDING]
                        );
                    } else {
                        Log::info('Error : Failed to create lead of lead id - ' . $changes['value']['leadgen_id']);
                    }
                }
                $webhook_response = WebhookResponse::create([
                    'entry_id'  => $entry_data['id'],
                    'read'      => 1,
                    'flex'      => 'A',
                    'name'      => 'facebook-callback-lead',
                    'data'      => json_encode($request_data)
                ]);
            }
            die('received');
        }
    }

    public function getLeadData($leadgen_id, $pageAccessToken)
    {
        $url = "https://graph.facebook.com/v20.0/" . $leadgen_id . "/";
        $lead_response = Http::get($url, [
            'access_token' => $pageAccessToken,
        ]);
        $lead_data = $lead_response->json();
        $http_code = $lead_response->status();
        Log::info("lead_data_get");
        Log::info($lead_data);
        return ['lead_data' => $lead_data, 'http_code' => $http_code];
    }
}
