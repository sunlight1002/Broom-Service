<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Lead;
use App\Models\Fblead;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;
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

          $result = Helper::sendWhatsappMessage($lead->phone,'hello_world',array('name'=>$lead->firstname));
         }
        return response()->json([
            'message'       => $lead,            
        ], 200);

    }
}
