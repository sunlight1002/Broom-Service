<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Lead;
use App\Models\Fblead;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Job;
use App\Models\Offer;
use App\Models\TextResponse;
use App\Models\WebhookResponse;
use App\Models\WhatsappLastReply;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Helper;
use Illuminate\Support\Facades\Auth;
use Monolog\Processor\WebProcessor;

class LeadWebhookController extends Controller
{
    public function saveLead(Request $request)
    {
        $challenge = $request->hub_challenge;
        if (!empty($challenge)) {

            $verify_token = $request->hub_verify_token;
            if ($verify_token === env('FB_WEBHOOK_TOKEN')) {
                Fblead::create(["challenge" => $challenge]);
                return $challenge;
            }
        } else {

            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
                'phone'     => ['required'],
                'email'     => ['required'],
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->messages()]);
            }

            $lead_exists = Client::where('phone', $request->phone)->orWhere('email', $request->email)->exists();
            if (!$lead_exists) {
                $lead                = new Client;
            } else {
                $lead = Client::where('phone', 'like', '%' . $request->phone . '%')->first();
                if (empty($lead)) {
                    $lead = Client::where('email', $request->email)->first();
                }
                $lead                = Client::find($lead->id);
            }
            $nm = explode(' ', $request->name);

            $lead->firstname     = $nm[0];
            $lead->lastname     = (isset($nm[1])) ? $nm[1] : '';
            $lead->phone         = $request->phone;
            $lead->email         = $request->email;
            $lead->status        = 0;
            $lead->password      = Hash::make($request->phone);
            $lead->geo_address   = $request->has('address') ? $request->address : '';
            $lead->save();

            $result = Helper::sendWhatsappMessage($lead->phone, 'leads', array('name' => ucfirst($lead->firstname)));

            $response = WebhookResponse::create([
                'status'        => 1,
                'name'          => 'whatsapp',
                'message'       => 'main_menu',
                'number'        =>  $request->phone,
                'flex'          => 'A',
            ]);
        }
        return response()->json([
            'message'       => $lead,
        ], 200);
    }
    public function fbWebhook(Request $request)
    {

        $challenge = $request->hub_challenge;

        if (!empty($challenge)) {

            $verify_token = $request->hub_verify_token;

            if ($verify_token === env('FB_WEBHOOK_TOKEN')) {

                Fblead::create(["challenge" => $challenge]);
                return $challenge;
            }
        } else {
            $get_data = $request->getContent();

            Log::info($get_data);
            $get_data = json_decode($get_data, true);

            $data_returned = $get_data['entry'][0]['changes'][0]['value'];

            $response = WebhookResponse::create([
                'status'        => 1,
                'name'          => 'whatsapp',
                'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                'message'       => $data_returned['messages'][0]['text']['body'],
                'number'        => $data_returned['contacts'][0]['wa_id'],
                'flex'          => 'C',
                'data'          => json_encode($get_data)
            ]);


            if (isset($data_returned) && isset($data_returned['messages']) && is_array($data_returned['messages'])) {

                $message_data =  $data_returned['messages'];
                $from      = $message_data[0]['from'];
                $to      = $data_returned['metadata']['display_phone_number'];

                $to_name      = $data_returned['contacts'][0]['profile']['name'];
                $message =  ($message_data[0]['type'] == 'text') ? $message_data[0]['text']['body'] : $message_data[0]['button']['text'];

                $result = DB::table('whatsapp_last_replies')->where('phone', '=', $from)->whereRaw('updated_at >= now() - interval 15 minute')->first();


                if ($message == '0' && !is_null($result)) {

                    $last = WebhookResponse::where('number', $from)->orderBy('created_at', 'desc')->where('message', '!=', '0')->skip(1)->take(1)->get()->first();

                    $message = $last->message;
                }

                if ($message == 'yes') {

                    $last = WebhookResponse::where('number', $from)->orderBy('created_at', 'desc')->where('message', '!=', '0')->skip(1)->take(1)->get()->first();
                    if ($last == '3') {
                        $message = 'yes_כן';
                    } else {
                        $message = $last->message;
                    }
                }

                if ($message == '6') {

                    if (strlen($from) > 10) {
                        Client::where('phone', 'like', '%' . substr($from, 2) . '%')->update(['lng' => 'en']);
                    } else {
                        Client::where('phone', 'like', '%' . $from . '%')->update(['lng' => 'en']);
                    }
                }

                if (strlen($from) > 10)
                    $client  = Client::where('phone', 'like', '%' . substr($from, 2) . '%')->get()->first();
                else
                    $client  = Client::where('phone', 'like', '%' . $from . '%')->get()->first();



                if ($message == '' || $from == '') {
                    return 'Destination or Sender number and message value required';
                }

                $auth_id = null;
                $auth_check = false;
                if (str_contains($message, '@')) {

                    $auth = Client::where('email', $message)->get()->first();
                    $auth_id = (!is_null($auth)) ? $auth->id : '';
                    $auth_check = true;
                }
                if (is_numeric(str_replace('-', '', $message)) && strlen($message) > 5) {

                    $auth = Client::where('phone', 'like', '%' . $message . '%')->get()->first();
                    $auth_id = (!is_null($auth)) ? $auth->id : '';
                    $auth_check = true;
                }


                $link_for = '';
                $link_data = [];
                if (!empty($result)) {
                    $last_reply = $result->message;

                    if ((is_numeric(str_replace('-', '', $last_reply)) && strlen($last_reply) > 5) || str_contains($last_reply, '@')) {
                        $last_reply = 4;
                    }

                    if ($last_reply == 4 && $message == '1') {
                        $message = $last_reply . '_1';
                        $link_for = 'offer';
                        $ofrs = Offer::where('client_id', $client->id)->get();
                        if (count($ofrs) > 0) {
                            foreach ($ofrs as $ofr) {
                                $link_data[] = base64_encode($ofr->id);
                            }
                        }
                    }
                    if ($last_reply == 4 && $message == '2') {
                        $message = $last_reply . '_2';
                        $link_for = 'contract';
                        $cncs = Contract::where('client_id', $client->id)->get();
                        if (count($cncs) > 0) {
                            foreach ($cncs as $cn) {
                                $link_data[] = ($cn->unique_hash);
                            }
                        }
                    }
                    if ($last_reply == 4 && $message == '3') {
                        $message = $last_reply . '_3';
                        $link_for = 'jobs';
                        $jobs = Job::where('client_id', $client->id)->where('start_date', '>', Carbon::now())->get();
                        if (count($jobs) > 0) {
                            foreach ($jobs as $j) {
                                $link_data[] = base64_encode($j->id);
                            }
                        }
                    }

                    if ($last_reply == 4 && $message == '4') {
                        $message = $last_reply . '_4';
                    }
                    if ($last_reply == 4 && $message == '5') {
                        $message = $last_reply . '_5';
                    }
                    if ($last_reply == 4 && $message == '6') {
                        $message = $last_reply . '_6';
                    }
                    $reply = WhatsappLastReply::find($result->id);
                    $reply->phone = $from;
                    $reply->message = $message;
                    $reply->updated_at = now();
                    $reply->save();
                    $message = $reply->message;
                } else {
                    DB::table('whatsapp_last_replies')->where('phone', '=', $from)->delete();
                    $reply = new WhatsappLastReply;
                    $reply->phone = $from;
                    $reply->message = $message;
                    $reply->save();
                }
                // if (in_array($message, [1, 2, 3, 4, 5])) {

                //     $text_message = 'message_' . $message;
                // } else if (str_contains($message, '_')) {
                //     if ($message == '2_yes') {
                //         $text_message = 'message_3';
                //     } else {
                //         $text_message = 'message_' . $message;
                //     }
                // } else if (strlen($message) < 2) {

                //     $text_message = 'message_0';
                // } else {

                //     $text_message = $message;
                // }

                // if (strlen($message) > 2) {
                //       $response = $text_message;
                // } else {
                // $response = WebhookResponse::getWhatsappMessage($text_message, 'heb', $client);


                if (count($link_data) > 0) {
                    $message = '';
                    $prefix = ($link_for == 'offer') ? url('/') . '/price-offer/' : (($link_for == 'contract') ? url('/') . '/work-contract/' : url('/') . '/client/view-job/');
                    foreach ($link_data as $ld) {
                        $message .= $prefix . $ld . "\n";
                    }

                    $_merge = TextResponse::where('status', '1')->where('keyword', 'anything')->get()->first();
                    if (!is_null($_merge)) {

                        if ($client->lng == 'en') {
                            $merge =  $_merge->eng;
                        } else {
                            $merge =  $_merge->heb;
                        }
                    }
                    $message .= $merge;
                }


                if ($auth_check == true && ($auth_id) != '') {


                    $_response = TextResponse::where('status', '1')->where('keyword', '4_r')->get()->first();
                    if (!is_null($_response)) {

                        if ($client->lng == 'en') {
                            $response =  $_response->eng;
                        } else {
                            $response =  $_response->heb;
                        }

                        WebhookResponse::create([
                            'status'        => 1,
                            'name'          => 'whatsapp',
                            'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                            'message'       => $response,
                            'number'        => $data_returned['contacts'][0]['wa_id'],
                            'flex'          => 'A',
                            'data'          => json_encode($get_data)
                        ]);


                        $result = Helper::sendWhatsappMessage($from, '', array('message' => $response));
                    }
                } else if ($auth_check == true && ($auth_id) == '') {

                    $_response = TextResponse::where('status', '1')->where('keyword', 'no_auth')->get()->first();

                    if (!is_null($_response)) {

                        if ($client->lng == 'en') {
                            $response =  $_response->eng;
                        } else {
                            $response =  $_response->heb;
                        }
                    }

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                        'message'       => $response,
                        'number'        => $data_returned['contacts'][0]['wa_id'],
                        'flex'          => 'A',
                        'data'          => json_encode($get_data)
                    ]);


                    $result = Helper::sendWhatsappMessage($from, '', array('message' => $response));
                } else {


                    $_response = TextResponse::where('status', '1')->where('keyword', 'like', '%' . $message . '%')->get()->first();

                    if (!is_null($_response)) {

                        if ($client->lng == 'en') {
                            $response =  $_response->eng;
                        } else {
                            $response =  $_response->heb;
                        }
                    } else {
                        $response = $message;
                    }

                    WebhookResponse::create([
                        'status'        => 1,
                        'name'          => 'whatsapp',
                        'entry_id'      => (isset($get_data['entry'][0])) ? $get_data['entry'][0]['id'] : '',
                        'message'       => $response,
                        'number'        => $data_returned['contacts'][0]['wa_id'],
                        'flex'          => 'A',
                        'data'          => json_encode($get_data)
                    ]);


                    $result = Helper::sendWhatsappMessage($from, '', array('message' => $response));
                }
            }



            die('sent');
        }
    }
}
