<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Lead;
use App\Models\Fblead;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;

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

        // $ch = curl_init();

        // $mobile_no = $lead->phone;

        // $params = [
        //     "messaging_product" => "whatsapp", 
        //     "recipient_type" => "individual", 
        //     "to" => (strlen($mobile_no) <=10) ? '91'.$mobile_no : $mobile_no,
        //     "type" => "template", 
        //     "template" => [
        //         "name" => "booking_voucher", 
        //         "language" => [
        //             "code" => "en_US"
        //         ], 
        //         "components" => [
        //             [
        //                 "type" => "header", 
        //                 "parameters" => [
        //                     [
        //                         "type" => "text", 
        //                         "text" => @$lead->name 
        //                     ] 
        //                 ]
        //             ],
        //             [
        //                 "type" => "body", 
        //                 "parameters" => [                           
        //                     [
        //                         "type" => "text", 
        //                         "text" => @$name ?? 'Abhishek'
        //                     ],
        //                     [
        //                         "type" => "text", 
        //                         "text" => @$mobile ?? "919971717045"
        //                     ],
        //                 ] 
        //             ],
        //             [
        //                 "type" => "button", 
        //                 "sub_type" => "url",
        //                 "index" => 0,
        //                 "parameters" => [
        //                     [
        //                         "type" => "text", 
        //                         "text" => @$path 
        //                     ] 
        //                 ] 
        //             ] 
        //         ] 
        //     ] 
        // ]; 

        // curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/v16.0/'.env('WHATSAPP_API_CODE').'/messages');
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($ch, CURLOPT_POST, 1);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

        // $headers = array();
        // $headers[] = 'Authorization: Bearer '.env('WHATSAPP_API_SECRET');
        // $headers[] = 'Content-Type: application/json';
        // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // $result = curl_exec($ch);
        // if (curl_errno($ch)) {
        //     echo 'Error:' . curl_error($ch);
        // }
        // $data = json_decode($result, 1);

        // curl_close($ch);
        // if ($data && isset($data['error']) && !empty( $data['error'])) {

           
        // }
         }
        return response()->json([
            'message'       => 'Lead created successfully',            
        ], 200);

    }
}
