<?php
namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Schedule;
use App\Models\Offer;
use App\Models\Services;
use App\Models\Contract;
use App\Models\notifications;
use Mail;

class ClientEmailController extends Controller
{
     public function ShowMeeting(Request $request){

       $id = $request->id;
       $schedule = Schedule::where('id',$id)->with('client','team')->get()->first();
       $services = Offer::where('client_id',$schedule->client->id)->get()->last();
       $str = '';
       if(!empty($services->services)){
         
         $allServices = json_decode($services->services);
         foreach($allServices as $k=> $serv){
            $s = Services::where('id',$serv->service)->get('name')->first()->toArray();
            if($k != count($s))
            $str .= $s['name'].", ";
            else
            $str .= $s['name'];
         }

       } 
       $schedule->service_names = $str; 
       return response()->json([
           'schedule' => $schedule
       ]);

     }
     
     public function GetOffer(Request $request){

      $id = $request->id;
      $offer = Offer::where('id',$id)->with('client')->get();
      $services = ($offer[0]->services != '') ? json_decode($offer[0]->services) : [];
     
      return response()->json([
          'offer' => $offer
      ]);
     }

     public function AcceptOffer(Request $request)
     {

       Offer::where('id',$request->id)->update([
        'status' =>'accepted'
      ]);
      
      $ofr  = Offer::with('client')->where('id',$request->id)->get()->first()->toArray();
      notifications::create([
        'user_id'=>$ofr['client']['id'],
        'type'=>'accept-offer',
        'offer_id'=>$request->id,
        'status' => 'accepted'
    ]);
      $hash = md5($ofr['client']['email'].$ofr['id']); 
      
      $contract = Contract::create([
         'offer_id'   =>$request->id,
         'client_id'  =>$ofr['client']['id'],
         'unique_hash'=>$hash,
         'status'     =>'not-signed'
      ]);
          $ofr['contract_id'] = $hash;

      \App::setLocale($ofr['client']['lng']);
      
      Mail::send('/Mails/ContractMail',$ofr,function($messages) use ($ofr){
        $messages->to($ofr['client']['email']);
        $sub = __('mail.contract.subject')."  ".__('mail.contract.company');
        $messages->subject($sub);
      });
      
      return response()->json([
       'message' => 'Offer is accepted'
      ],200);

   }

   public function RejectOffer(Request $request)
   {

     Offer::where('id',$request->id)->update([
      'status' =>'declined'
    ]);
    
    $ofr  = Offer::with('client')->where('id',$request->id)->get()->first()->toArray();
    notifications::create([
      'user_id'=>$ofr['client']['id'],
      'type'=>'reject-offer',
      'offer_id'=>$request->id,
      'status' => 'declined'
  ]);
  }

   public function AcceptMeeting(Request $request){
   
    try{
      
    Schedule::where('id',$request->id)->update([
      'booking_status' => $request->response
    ]);
      $sch = Schedule::find($request->id)->get('client_id')->first();
      if($request->response =='confirmed'):

          Client::where('id',$sch->client_id)->update(['status'=>1]);
          notifications::create([
            'user_id'=>$sch->client_id,
            'type'=>'accept-meeting',
            'meet_id'=>$request->id,
            'status' => $request->response
        ]);

        else:

          Client::where('id',$sch->client_id)->update(['status'=>0]);
          notifications::create([
            'user_id'=>$sch->client_id,
            'type'=>'reject-meeting',
            'meet_id'=>$request->id,
            'status' => $request->response
        ]);

      endif;

    return response()->json([
     'message' => 'Thanks, your meeting is '.$request->response
    ],200);

   } catch (\Exception $e) {

    return $e->getMessage();
   }
   }
   
   public function AcceptContract(Request $request){
     
    try{

      Contract::where('unique_hash',$request->unique_hash)->update($request->input());
      $contract = Contract::with('client')->where('unique_hash',$request->unique_hash)->get()->first();
      Client::where('id',$contract->client_id)->update(['status'=>2]);
      notifications::create([
        'user_id'=>$contract->client_id,
        'type'=>'contract-accept',
        'contract_id'=>$contract->id,
        'status' => 'accepted'
      ]);
      return response()->json([
        'message'=>"Thanks, for accepting contract"
       ],200);

    } catch(\Exception $e){
      
       return response()->json([
        'error'=>$e->getMessage()
       ],200);
    }
    
  }

  public function RejectContract(Request $request){
     
    try{
      Contract::where('id',$request->id)->update(['status' =>'declined']);
      $contract = Contract::with('client')->where('id',$request->id)->get()->first();
      Client::where('id',$contract->client_id)->update(['status'=>1]);
      notifications::create([
        'user_id'=>$contract->client_id,
        'type'=>'contract-reject',
        'contract_id'=>$contract->id,
        'status' => 'declined'
      ]);
      return response()->json([
        'message'=>"Contract has been rejected"
       ],200);

    } catch(\Exception $e){
      
       return response()->json([
        'error'=>$e->getMessage()
       ],200);
    }
    
  }

  public function GetOfferFromHash(Request $request){

      $offer = Contract::where('unique_hash',$request->token)->get()->last();
      $goffer = Offer::where('id',$offer->offer_id)->with('client')->get();
      return response()->json([
        'offer' => $goffer,
        'contract'=>$offer,
      ]);
  }

  public function serviceTemplate(Request $request){
    $template = Services::where('id',$request->id)->get('template')->first();
    return response()->json(['template'=>$template]);
  }
}

