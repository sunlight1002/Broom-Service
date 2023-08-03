<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Lead;
use App\Models\Fblead;
use App\Models\Client;
use App\Models\WebhookResponse;
use App\Models\WhatsappLastReply;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
        $nm = explode(' ',$request->name);
        
        $lead->firstname     = $nm[0];
        $lead->lastname     = (isset($nm[1])) ? $nm[1] : '';
        $lead->phone         = $request->phone;
        $lead->email         = $request->email;
        $lead->status        = 0;
        $lead->password      = Hash::make($request->phone);
        $lead->geo_address       = $request->has('address') ? $request->address : '';
        $lead->save();

        $result = Helper::sendWhatsappMessage($lead->phone,'',array('name'=>ucfirst($lead->firstname)));
            
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
                    $client  = Client::where('phone','like','%'.$from.'%')->get()->first();
                    $to_name      = $data_returned['contacts'][0]['profile']['name'];
                    $message =  ($message_data[0]['type'] == 'text') ? $message_data[0]['text']['body'] : $message_data[0]['button']['text'];

                    if ($message == '' || $from == '') {
                        return 'Destination or Sender number and message value required';
                    }
                    $result = DB::table('whatsapp_last_replies')->where('phone','=',$from)->whereRaw('updated_at >= now() - interval 15 minute')->first();
                    if(!empty($result)){
                        $last_reply = $result->message;
                        if($last_reply == 2 && $message=='yes'){
                           $message=$last_reply.'_yes';
                        }
                        if($last_reply == 2 && $message=='no'){
                           $message=$last_reply.'_no';
                        }
                        if($last_reply==4 && $message=='1'){
                            $message=$last_reply.'_1';
                        }
                        if($last_reply==4 && $message=='2'){
                            $message=$last_reply.'_2';
                        }
                        if($last_reply==4 && $message=='3'){
                            $message=$last_reply.'_3';
                        }
                        if($last_reply==4 && $message=='4'){
                            $message=$last_reply.'_4';
                        }
                        $reply = WhatsappLastReply::find($result->id);
                        $reply->phone=$from;
                        $reply->message=$message;
                        $reply->updated_at=now();
                        $reply->save();
                        $message=$reply->message;
                    }else{
                         DB::table('whatsapp_last_replies')->where('phone','=',$from)->delete();
                        $reply = new WhatsappLastReply;
                        $reply->phone=$from;
                        $reply->message=$message;
                        $reply->save();
                    }
                    if (in_array($message, [1,2,3,4,5])){
                          $text_message='message_'.$message;
                    }else if(str_contains($message, '_')){
                           if($message=='2_yes'){
                             $text_message='message_3';
                           }else{
                            $text_message='message_'.$message;
                           }
                    } else{
                         $text_message='message_0';
                    }
                    
                    $response = WebhookResponse::getWhatsappMessage($text_message,'heb',$client);
                    $result = Helper::sendWhatsappMessage($from,'leads',array('message'=>$response));
                }

                die('sent');


        }
    
  }
}
