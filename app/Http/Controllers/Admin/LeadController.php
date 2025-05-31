<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadStatusEnum;
use App\Enums\SettingKeyEnum;
use App\Http\Controllers\Controller;
use App\Models\Fblead;
use App\Models\Client;
use App\Models\LeadComment;
use App\Models\FacebookInsights;
use App\Models\Offer;
use App\Models\ClientPropertyAddress;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\WebhookResponse;
use App\Models\WhatsAppBotClientState;
use App\Models\WhatsappLastReply;
use App\Traits\JobSchedule;
use App\Traits\PaymentAPI;
use App\Traits\ICountDocument;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Notification;
use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\WhatsappNotificationEvent;
use App\Rules\ValidPhoneNumber;
use App\Models\LeadActivity;
use App\Jobs\AddGoogleContactJob;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Twilio\Rest\Client as TwilioClient;


class LeadController extends Controller
{
    use JobSchedule, PaymentAPI, ICountDocument;

    protected $botMessages = [
        'main-menu' => [
            'heb' => 'היי, אני בר, הנציגה הדיגיטלית של ברום סרוויס. איך אוכל לעזור לך היום? 😊' . "\n\n" . 'בכל שלב תוכלו לחזור לתפריט הראשי ע"י שליחת המס 9 או לחזור תפריט אחד אחורה ע"י שליחת הספרה 0' . "\n\n" . '1. פרטים על השירות' . "\n" . '2. אזורי שירות' . "\n" . '3. קביעת פגישה לקבלת הצעת מחיר' . "\n" . '4. שירות ללקוחות קיימים' . "\n" . '5. מעבר לנציג אנושי (בשעות הפעילות)' . "\n" . '6. English menu'
        ]
    ];

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
        $filter = $request->get('filter');
        $source = $request->get('source');

        \Log::info($source);

        $query = Client::with('property_addresses')
            ->leftJoin('leadstatus', 'leadstatus.client_id', '=', 'clients.id')
            // ->leftJoin('client_property_addresses', 'client_property_addresses.client_id', '=', 'clients.id')
            ->leftJoinSub(
                LeadActivity::select('lead_activities.client_id', 'lead_activities.reason', 'lead_activities.reschedule_date', 'lead_activities.reschedule_time')
                    ->whereNotNull('reschedule_date')
                    ->whereRaw('lead_activities.id IN (
                        SELECT MAX(id)
                        FROM lead_activities AS sub
                        WHERE sub.client_id = lead_activities.client_id
                    )'),
                'latest_lead_activity',
                'latest_lead_activity.client_id',
                '=',
                'clients.id'
            )
            ->where('clients.status', '!=', 2)
            ->select(
                'clients.id',
                'clients.firstname',
                'clients.lastname',
                'clients.email',
                'clients.phone',
                'leadstatus.lead_status',
                'clients.created_at',
                'clients.source',
                // 'client_property_addresses.address_name',
                // 'client_property_addresses.geo_address',
                'latest_lead_activity.reason',
                'latest_lead_activity.reschedule_date',
                'latest_lead_activity.reschedule_time'
            )
            ->groupBy('clients.id');

        if ($filter != 'All') {
            $query->where('leadstatus.lead_status', strtolower($filter));
        }

        if (!empty($source)) {
            $query->where('clients.source', $source);
        }

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                if (request()->has('search')) {
                    $keyword = request()->get('search')['value'];

                    if (!empty($keyword)) {
                        $query->where(function ($sq) use ($keyword) {
                            $sq->whereRaw("CONCAT_WS(' ', clients.firstname, clients.lastname) like ?", ["%{$keyword}%"])
                                ->orWhere('clients.email', 'like', "%" . $keyword . "%")
                                ->orWhere('clients.phone', 'like', "%" . $keyword . "%")
                                ->orWhere('clients.invoicename', 'like', "%" . $keyword . "%")
                                ->orWhere('leadstatus.lead_status', 'like', "%" . $keyword . "%")
                                ->orWhereHas('property_addresses', function ($query) use ($keyword) {
                                    $query->where('address_name', 'like', "%" . $keyword . "%")
                                        ->orWhere('geo_address', 'like', "%" . $keyword . "%");
                                });
                            // ->orWhere('client_property_addresses.address_name', 'like', "%" . $keyword . "%")
                            // ->orWhere('client_property_addresses.geo_address', 'like', "%" . $keyword . "%");
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
            ->addColumn('reschedule_date', function ($data) {
                return $data->reschedule_date ? Carbon::parse($data->reschedule_date)->format('d/m/Y') : '-';
            })
            ->addColumn('reschedule_time', function ($data) {
                return $data->reschedule_time ?? '-';
            })
            ->addColumn('reason', function ($data) {
                return $data->reason ?? null;
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
        $twilioAccountSid = config('services.twilio.twilio_id');
        $twilioAuthToken = config('services.twilio.twilio_token');
        $twilioWhatsappNumber = config('services.twilio.twilio_whatsapp_number');

        // Initialize the Twilio client
        $twilio = new TwilioClient($twilioAccountSid, $twilioAuthToken);

        $validator = Validator::make($data, [
            'firstname' => ['required', 'string', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:50'],
            'email'     => ['string', 'email:rfc,dns', 'max:255', 'unique:clients'],
            'phone'     => ['required', 'string', 'max:20', new ValidPhoneNumber(), 'unique:clients'],
        ]);

        $validator->sometimes(['contact_person_name', 'contact_person_phone'], ['required'], function ($input) {
            return !empty($input->contact_person_name) || !empty($input->contact_person_phone);
        });


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $input = $data;
        $password = isset($input['phone']) && !empty($input['phone'])
            ? $input['phone']
            : 'password';
        $input['password'] = Hash::make($password);
        $input['passcode'] = $password;
        $input['two_factor_enabled'] = 1;
        $input['source'] = 'CRM';

        // Create the client
        $client = Client::create($input);

        AddGoogleContactJob::dispatch($client);

        // Process property addresses
        $property_address_data = $request->propertyAddress;
        if (count($property_address_data) > 0) {
            foreach ($property_address_data as $key => $address) {
                $address['client_id'] = $client->id;
                ClientPropertyAddress::create($address);
            }
        }

        // Update or create lead status
        $client->lead_status()->updateOrCreate(
            [],
            ['lead_status' => LeadStatusEnum::PENDING]
        );

        // Create a notification
        Notification::create([
            'user_id' => $client->id,
            'user_type' => get_class($client),
            'type' => NotificationTypeEnum::NEW_LEAD_ARRIVED,
            'status' => 'created'
        ]);

        $client->load('property_addresses');

        // Trigger WhatsApp notification
        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::NEW_LEAD_ARRIVED,
            "notificationData" => [
                'client' => $client->toArray(),
                // 'type' => "website"
            ]
        ]));

        LeadActivity::create([
            'client_id' => $client->id,
            'created_date' => $client->created_at,
            'status_changed_date' => " ",
            'changes_status' => "pending",
            'reason' => " ",
        ]);


        if ($data["send_bot_message"] == 1) {
            try {
                \Log::info("send_bot_message");

                $sid = $client->lng == "heb" ? "HX46b1587bfcaa3e6b29869edb538f45e0" : "HXccd789be06e2fd60dd0708266ae7007f";

                $message = $twilio->messages->create(
                    "whatsapp:+$client->phone",
                    [
                        "from" => "$twilioWhatsappNumber",
                        "contentSid" => $sid,
                    ]
                );
                \Log::info($message->sid);

                $m = $this->botMessages['main-menu']['heb'];

                // $result = sendWhatsappMessage($client->phone, array('name' => ucfirst($client->firstname), 'message' => $m));

                WhatsAppBotClientState::updateOrCreate([
                    'client_id' => $client->id,
                ], [
                    'menu_option' => 'main_menu',
                    'language' => 'he',
                ]);

                $response = WebhookResponse::create([
                    'status'        => 1,
                    'name'          => 'whatsapp',
                    'message'       => $message->body ?? '',
                    'from'          => str_replace("whatsapp:+", "", $twilioWhatsappNumber),
                    'number'        => $client->phone,
                    'read'          => 1,
                    'flex'          => 'A',
                    'data'          => json_encode($message->toArray()),
                ]);
            } catch (\Throwable $th) {
                \Log::error($th);
            }
        }


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
            'phone'     => ['required', new ValidPhoneNumber(), 'unique:clients,phone,' . $id],
        ]);

        $validator->sometimes(['contact_person_name', 'contact_person_phone'], ['required'], function ($input) {
            return !empty($input->contact_person_name) || !empty($input->contact_person_phone);
        });

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
        // $iCountResponse = $this->createOrUpdateUser($request);

        // Handle iCount response
        // if ($iCountResponse->status() != 200) {
        //     return response()->json(['error' => 'Failed to create user in iCount'], 500);
        // }

        // $iCountData = $iCountResponse->json();

        // // Extract Client_id from iCount response and update the Client model
        // if (isset($iCountData['client_id'])) {
        //     $client->update(['icount_client_id' => $iCountData['client_id']]);
        // }


        $client->update($input);

        AddGoogleContactJob::dispatch($client);

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

    public function addSomeFields(Request $request)
    {
        $client = Client::find($request->id);

        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        };

        $client->update($request->all());

        return response()->json(['message' => 'Client updated successfully']);
    }

    public function jobstarttime(Request $request)
    {
        \Log::info("Request received");
        \Log::info($request->all());
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
            \Log::info($property_address);
            if (count($property_address) > 0) {
                $savedAddress = ClientPropertyAddress::UpdateOrCreate(
                    [
                        'id' => $property_address['id']
                    ],
                    $property_address
                );

                $address = ClientPropertyAddress::where('client_id', $property_address['client_id'])->first();
                $client = Client::find($address->client_id);
                if ($address->id == $savedAddress->id && $client->status == 2) {
                    \Log::info("Address updated");
                    if ($client->icount_client_id) {
                        $data = [
                            'icount_client_id' => $client->icount_client_id,
                            'bus_street' => $address->geo_address,
                            'bus_city' => $address->city ?? null,
                            'bus_zip' => $address->zipcode ?? null,
                        ];
                    }

                    $this->updateClientIcount($data);
                }

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

    private function updateClientIcount($data)
    {

        $iCountCompanyID = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_COMPANY_ID)
            ->value('value');

        $iCountUsername = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_USERNAME)
            ->value('value');

        $iCountPassword = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_PASSWORD)
            ->value('value');

        $url = 'https://api.icount.co.il/api/v3.php/client/update';

        $requestData = [
            'cid' => $iCountCompanyID,
            'user' => $iCountUsername,
            'pass' => $iCountPassword,
            'client_id' => $data['icount_client_id'] ?? 0,
            'bus_street' => $data['bus_street'] ?? null,
            'bus_city' => $data['bus_city'] ?? null,
            'bus_zip' => $data['bus_zip'] ?? null,
        ];
        \Log::info($requestData);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $requestData);

        $data = $response->json();
        $http_code = $response->status();

        if ($http_code != 200) {
            throw new Exception('Error: Failed to create or update user');
        }
        // return $data;
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

        $twilioAccountSid = config('services.twilio.twilio_id');
        $twilioAuthToken = config('services.twilio.twilio_token');
        $twilioWhatsappNumber = config('services.twilio.twilio_whatsapp_number');
        $twilio = new TwilioClient($twilioAccountSid, $twilioAuthToken);

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

                        $email = null;

                        $name = isset($mapped_field_data['full_name']) && !empty($mapped_field_data['full_name']) ? explode(' ', $mapped_field_data['full_name']) : explode(' ', 'lead ' . $lead_data['id']);

                        $phone = isset($mapped_field_data['phone_number']) && !empty($mapped_field_data['phone_number']) ? str_replace('+', '', $mapped_field_data['phone_number']) : '';
                        $lng = 'heb';
                        // if (isset($phone) && strlen($phone) > 10 && substr($phone, 0, 3) != 972) {
                        //     $lng = 'en';
                        // }
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
                            'source'            => 'fblead',
                        ]);
                        if (!empty($phone)) {
                            $m = "Hi, I'm Bar, the digital representative of Broom Service. How can I help you today? 😊\n\nAt any stage, you can return to the main menu by sending the number 9 or return one menu back by sending the number 0.\n\n1. About the Service\n2. Service Areas\n3. Set an appointment for a quote\n4. Customer Service\n5. Switch to a human representative (during business hours)\n7. שפה עברית";
                            if ($lng == 'heb') {
                                $m = 'היי, אני בר, הנציגה הדיגיטלית של ברום סרוויס. איך אוכל לעזור לך היום? 😊' . "\n\n" . 'בכל שלב תוכלו לחזור לתפריט הראשי ע"י שליחת המס 9 או לחזור תפריט אחד אחורה ע"י שליחת הספרה 0' . "\n\n" . '1. פרטים על השירות' . "\n" . '2. אזורי שירות' . "\n" . '3. קביעת פגישה לקבלת הצעת מחיר' . "\n" . '4. שירות ללקוחות קיימים' . "\n" . '5. מעבר לנציג אנושי (בשעות הפעילות)' . "\n" . '6. English menu';
                            }
                            $sid = $client->lng == "heb" ? "HX46b1587bfcaa3e6b29869edb538f45e0" : "HXccd789be06e2fd60dd0708266ae7007f";

                            $message = $twilio->messages->create(
                                "whatsapp:+$client->phone",
                                [
                                    "from" => "$twilioWhatsappNumber",
                                    "contentSid" => $sid,
                                ]
                            );
                            \Log::info($message->sid);
                            // $result = sendWhatsappMessage($phone, array('name' => '', 'message' => $m), $lng == 'heb' ? 'he' : 'en');
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
                    'message'       => $message->body ?? '',
                    'read'      => 1,
                    'flex'      => 'A',
                    'from'          => str_replace("whatsapp:+", "", $twilioWhatsappNumber),
                    'number'    => $client->phone,
                    'name'      => 'facebook-callback-lead',
                    'data'      => json_encode($message->toArray()),
                ]);
            }
            die('received');
        }
    }

    public function getFacebookInsights(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $filter = $request->input('filter');

        $clientCount = 0;
        $totalSpend = 0;
        $costPerLead = 0;
        $costPerClient = 0;

        // Get all Facebook Insights data
        $insights = FacebookInsights::all();

        // Base query for clients
        $clientQuery = Client::whereNotNull('campaign_id');

        // Apply date range filter if provided
        if ($startDate && $endDate) {
            $clientQuery->whereBetween('created_at', [$startDate, $endDate]);
            $clientCount = $clientQuery->count();
        } else {
            $clientCount = $clientQuery->count();
        }

        // Total count of all clients (ignoring date range)
        $totalClients = Client::whereNotNull('campaign_id')->count();

        // Calculations
        $totalSpend = $insights->sum('spend');
        $costPerLead = $totalClients > 0 ? $totalSpend / $totalClients : 0;
        $costPerClient = $insights->sum('client_count') > 0 ? $totalSpend / $insights->sum('client_count') : 0;

        return response()->json([
            'insights' => $insights,
            'clientCount' => $clientCount,
            'totalSpend' => $totalSpend,
            'costPerLead' => $costPerLead,
            'costPerClient' => $costPerClient,
        ]);
    }


    public function getLeadData($leadgen_id, $pageAccessToken)
    {
        Log::info("Received leadgen_id:", ['leadgen_id' => $leadgen_id]);

        if (is_array($leadgen_id)) {
            Log::info("leadgen_id is an array containing multiple IDs.", ['leadgen_ids' => $leadgen_id]);
        } else {
            Log::info("leadgen_id is a single ID.", ['leadgen_id' => $leadgen_id]);
        }

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

    public function getUniqueSource()
    {
        $leads = Client::all();
        $sources = $leads->pluck('source')->filter()->unique()->values();

        return response()->json([
            'sources' => $sources->isEmpty() ? [] : $sources
        ]);
    }
}
