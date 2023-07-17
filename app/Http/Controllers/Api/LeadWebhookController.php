<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Lead;
use App\Models\Fblead;
use App\Models\Client;
use App\Models\WebhookResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Helper;

class LeadWebhookController extends Controller
{
    public function saveLead(Request $request){
        $challenge = $request->hub_challenge;
        if(!empty($challenge)){

            $verify_token = $request->hub_verify_token;
            if ( $verify_token === env('FB_WEBHOOK_TOKEN') ) {
                Fblead::create(["challenge"=>$challenge]);
                return $challenge;
            }
        }else{

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'phone'     => ['required'],
            'email'     => ['required'],
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $lead_exists = Client::where('phone', $request->phone)->orWhere('email', $request->email)->exists();
        if(!$lead_exists){
            $lead                = new Client;
        }else{
            $lead = Client::where('phone', $request->phone)->first();
            if(empty($lead)){
                $lead = Client::where('email', $request->email)->first();
            }
            $lead                = Client::find($lead->id);
        }
        $lead->firstname     = $request->name;
        $lead->phone         = $request->phone;
        $lead->email         = $request->email;
        $lead->status        = 0;
        $lead->password      = Hash::make($request->phone);
        $lead->geo_address       = $request->has('address') ? $request->address : '';
        $lead->save();

        $result = Helper::sendWhatsappMessage($lead->phone,'new_leads',array('name'=>ucfirst($lead->firstname)));
            
         }
        return response()->json([
            'message'       => $lead,            
        ], 200);

    }
    public function fbWebhook(Request $request){
    
    $challenge = $request->hub_challenge;
  
        if(!empty($challenge)){
          
              $verify_token = $request->hub_verify_token;
              
              if ( $verify_token === env('FB_WEBHOOK_TOKEN') ) {

                  Fblead::create(["challenge"=>$challenge]);
                  return $challenge;

              }
        }else{
              $get_data = $request->getContent();

              Log::info($get_data);
              $get_data = json_decode($get_data, true);

              $response = WebhookResponse::create([
                  'status'        => 1,
                  'name'    => 'whatsapp',
                  'data' => json_encode($get_data)
              ]);

               $data_returned = $get_data['entry'][0]['changes'][0]['value'];


                if ( isset($data_returned) && isset($data_returned['messages']) && is_array($data_returned['messages'])) {

                    $message_data =  $data_returned['messages'];
                    $from      = $message_data[0]['from'];
                    $to      = $data_returned['metadata']['display_phone_number'];
                    $to_name      = $data_returned['contacts'][0]['profile']['name'];
                    $message =  ($message_data[0]['type'] == 'text') ? $message_data[0]['text']['body'] : $message_data[0]['button']['text'];

                    if ($message == '' || $from == '') {
                        return 'Destination or Sender number and message value required';
                    }
                   if (in_array($message, [1,2,3,4,5])){
                          $text_message='message_'.$message;
                    }else{
                         $text_message='message_0';
                    }
                    $response = WebhookResponse::getWhatsappMessage($text_message,'en');
                    $result = Helper::sendWhatsappMessage($to,'',array('message'=>$response));
                }

                die('sent');


        }
    
  }
}
