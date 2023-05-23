<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use App\Models\Invoices;
use App\Models\Job;
use App\Models\Order;
use App\Models\ClientCard;
use App\Models\JobService;
use App\Models\Receipts;
use App\Models\Refunds;
use Barryvdh\DomPDF\PDF as DomPDFPDF;
use PDF;
class InvoiceController extends Controller
{
    public function index(Request $request){
       $invoices = Invoices::with('client','receipt');

       if(isset($request->from_date) && isset($request->to_date)){
          $invoices = $invoices->whereDate('created_at','>=',$request->from_date)
                               ->whereDate('created_at','<=',$request->to_date);
       }

       if(isset($request->invoice_id)){
          $invoices = $invoices->where('invoice_id',$request->invoice_id);
       }

       if(isset($request->txn_id)){
        $invoices = $invoices->where('txn_id',$request->txn_id);
       }

       if(isset($request->pay_method)){
        $invoices = $invoices->where('pay_method',$request->pay_method);
       }

       if(isset($request->status)){
        $invoices = $invoices->where('status',$request->status);
       }

        if(isset($request->client)){
            $q = $request->client;
            $ex = explode(' ',$q); 
            $invoices = $invoices->WhereHas('client',function ($qr) use ($q,$ex){
                $qr->where(function($qr) use ($q,$ex) {
                $qr->where('firstname', 'like','%'.$ex[0].'%');
                if(isset($ex[1]))
                $qr->where('lastname', 'like','%'.$ex[1].'%');
            });
        });

    }

       $invoices = $invoices->orderBy('id', 'desc')->paginate(20);
       return response()->json([
        'invoices' => $invoices
       ]);
  

    }

    public function getClientInvoices($id){
        $invoices = Invoices::where('customer',$id)->with('client')->orderBy('id', 'desc')->paginate(20);
        return response()->json([
         'invoices' => $invoices
        ]);
   
 
    }

    public function AddInvoice( Request $request ){
       
        $req = $request->data;
        $client = Client::where('id',$req['customer'])->get()->first();
       
        $services = json_decode($req['services']);
        $total = 0;
        
        $card = ClientCard::where('client_id',$client->id)->get()->first();
       
        $doctype  = $req['doctype']; 
       
        $subtotal = $req['amount'];
        $tax = (17/100) * $subtotal;
        $total = $tax+$subtotal;
     
        $due      = ($req['due_date'] == null) ? \Carbon\Carbon::now()->endOfMonth()->toDateString() : $req['due_date'];
        $name     = ($client->invoicename != null) ? $client->invoicename : $client->firstname." ".$client->lastname;
 
        $url = "https://api.icount.co.il/api/v3.php/doc/create";
        $params = Array(
   
                "cid"            => env('ICOUNT_COMPANYID'),
                "user"           => env('ICOUNT_USERNAME'),
                "pass"           => env('ICOUNT_PASS'),
   
                "doctype"        => $doctype,
                "client_id"      => $client->id,
                "client_name"    => $name, 
                "client_address" => $client->geo_address,
                "email"          => $client->email, 
                "lang"           => $client->lng,
                "currency_code"  => "ILS",
                "doc_lang"       => $client->lng,
                "items"          => $services,
                "duedate"        => $due,
                
                "send_email"      => 1, 
                "email_to_client" => 1, 
                "email_to"        => $client->email, 
                
        );
        if($doctype == "invrec"){
   
            $ex = explode('-',$card->valid);
            $cc = ['cc'=>[
                "sum" => $total,
                "card_type" => $card->card_type,
                "card_number" => substr($card->card_number,12), 
                "exp_year" => $ex[0],
                "exp_month" => $ex[1],
                "holder_id" => "",
                "confirmation_code" => ""
            ]];
   
            $_params = array_merge($params,$cc);
   
        } else {
            $_params = $params;
        }
      
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_params, null, '&'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        
        //if(!$info["http_code"] || $info["http_code"]!=200) die("HTTP Error");
        $json = json_decode($response, true);
        //dd($json);
        //if(!$json["status"]) die($json["reason"]);
   
        /* Auto payment */
        if( $doctype == 'invrec'){
            $pres = $this->commitInvoicePayment($services, $req['job'], $card->card_token);
            $pre = json_encode($pres);
          }
          
      
          job::where('id',$req['job'])->update([
              'invoice_no'    =>$json["docnum"],
              'invoice_url'   =>$json["doc_url"],
              'isOrdered'     => 2
          ]);
          $invoice = [
              'invoice_id' => $json['docnum'],
              'job_id'     => $req['job'],
              'amount'     => $total,
              'paid_amount'=> $total,
              'pay_method' => ( (isset($pres)) && $pres->HasError == false && $doctype == 'invrec' ) ? 'Credit Card' : 'NA',
              'customer'   => $client->id,
              'doc_url'    => $json['doc_url'],
              'type'       => $doctype,
              'invoice_icount_status' => 'Open',
              'due_date'   => $due,
              'txn_id'     => ( (isset($pres)) && $pres->HasError == false && $doctype == 'invrec' ) ? $pres->ReferenceNumber : '',
              'callback'   => ( (isset($pres)) && $pres->HasError == false && $doctype == 'invrec') ? $pre : '',
              'status'     => ( (isset($pres))  && $pres->HasError == false && $doctype == 'invrec') ? 'Paid' : ( (isset($pres)) ? $pres->ReturnMessage : 'Unpaid'),
          ];
        
          
          $inv = Invoices::create($invoice);
           if((isset($pres))  && $pres->HasError == false && $doctype == 'invrec' ){
            //close invoice
            $this->closeDoc($json['docnum'],'invrec');
            Invoices::where('id',$inv->id)->update(['invoice_icount_status'=>'Closed']);
   
        }
         /*Close Order */
         if(!empty($req['codes'])){
            $codes = $req['codes'];
            foreach($codes as $code){
                $this->closeDoc($code,'order');
                Order::where('id',$code)->update(['status'=>'Closed','invoice_status'=>2]);
            }
          }

          //JobService::where('id',$job->jobservice[0]->id)->update(['order_status'=>2]);
        
        return response()->json([
            'msg' => 'Invoice created successfully'
        ]);
    }
    
    public function getInvoice($id){
        $invoice = Invoices::where('id',$id)->with('client')->get()->first();
        return response()->json([
            'invoice' => $invoice
        ]);
    }

    public function getInvoiceIcount($docnum){
        
        $url = "https://api.icount.co.il/api/v3.php/doc/info";
        $params = Array(

                "cid"            => env('ICOUNT_COMPANYID'),
                "user"           => env('ICOUNT_USERNAME'),
                "pass"           => env('ICOUNT_PASS'),
                "doctype"        => 'invoice',
                "docnum"         => $docnum,
                "get_items"      =>1
                
        );
      
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, null, '&'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $re = json_decode($response);
        return $re;

    }

    public function updateInvoice( Request $request, $id ){

        $inv = Invoices::where('id',$id)->with('client')->get()->first();
        $client = $inv->client;
        $mode = $request->data['pay_method'];
        $pdata = $request->data;
        $total = 0;
        
        if($inv->amount <= $pdata['paid_amount'] && $inv->type == 'invoice'):

            $services = $this->getInvoiceIcount($inv->invoice_id);

            $sum = 0;
            $items = ($services->doc_info->items);
            if(!empty($items)){
                foreach($items as $itm){
                    $sum += (int)$itm->unitprice;
                }
    

            $card = ClientCard::where('client_id',$client->id)->get();
            $name =  ($client->invoicename != null) ? $client->invoicename : $client->firstname." ".$client->lastname;
            $url = "https://api.icount.co.il/api/v3.php/doc/create";
            $params = Array(
    
                    "cid"            => env('ICOUNT_COMPANYID'),
                    "user"           => env('ICOUNT_USERNAME'),
                    "pass"           => env('ICOUNT_PASS'),
    
                    "doctype"        => 'receipt',
                    "client_name"    => $name, 
                    "client_address" => $client->geo_address,
                    "email"          => $client->email, 
                    "lang"           => $client->lng,
                    "currency_code"  => "ILS",
                    "doc_lang"       => $client->lng,
                    "items"          => $items,
                    "based_on"       =>['docnum'=>$inv->invoice_id,'doctype'=>'invoice'],
                    
                    "send_email"      => 1, 
                    "email_to_client" => 1, 
                    "email_to"        => $client->email, 
                    
            );
            if($mode == "Credit Card"){
    
                $ex = explode('-',$card->valid);
                $cc = ['cc'=>[
                    "sum" => $sum,
                    "card_type" => $card->card_type,
                    "card_number" => substr($card->card_number,12), 
                    "exp_year" => $ex[0],
                    "exp_month" => $ex[1],
                    "holder_id" => "",
                    "confirmation_code" => ""
                ]];
    
                $_params = array_merge($params,$cc);
    
            }
            else if($mode == "Bank Transfer"){
                    $bt = ["banktransfer" =>[
                        "sum"    => $sum,
                        "date"   => $pdata['date'],
                        "account"=> $pdata['account'],
                    ]];
                    $_params = array_merge($params,$bt);
            }
            else if($mode == "Cheque"){
                $ch = ["cheques" =>[[
                    "sum"    => $sum,
                    "date"   => $pdata['date'],
                    "bank"   => $pdata['bank'],
                    "branch" => $pdata['branch'],
                    "account" =>$pdata['account'],
                    "number" => $pdata['number']
                ]]];
                $_params = array_merge($params,$ch);
           }
           else if($mode == "Cash"){
                $cs = ["cash" =>[
                    "sum"    => $sum,
                ]];
                $_params = array_merge($params,$cs);
           }

            else {
                $_params = $params;
            }
           
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_params, null, '&'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            $info = curl_getinfo($ch);
            
            //if(!$info["http_code"] || $info["http_code"]!=200) die("HTTP Error");
            $json = json_decode($response, true);
           
            if(!empty($json)){
                $args = [
                    'invoice_id' => $inv->id,
                    'invoice_icount_id' => $inv->invoice_id,
                    'receipt_id' => $json['docnum'],
                    'docurl' => $json['doc_url'],
                ];
                $rcp = Receipts::create($args);
                $this->closeDoc($inv->invoice_id,'invoice');
                Invoices::where('id',$inv->id)->update(['invoice_icount_status'=>'Closed','receipt_id'=>$rcp->id]);
            }
          
        }
           
    endif;

        $idata = [
            'pay_method'=>$request->data['pay_method'],
            'txn_id'    =>$request->data['txn_id'],
            'paid_amount'=>$request->data['paid_amount'],
            'status'     => $request->data['status']
        ];
        
        Invoices::where('id',$id)->update($idata);
        return response()->json([
            'msg' => 'Invoice Updated successfully'
        ]);
    }
    public function invoiceJobs( Request $request){

        $jobs = Job::where('client_id',$request->cid)->where('status','!=','completed')->get();            
        return response()->json([
            'jobs' => $jobs
        ]);
    }

    public function orderJobs( Request $request ){
        $services = Job::where('id',$request->id)->with('jobservice','client')->get();            
        return response()->json([
            'services' => $services
        ]);
    }

    public function viewInvoice($gid){
        $id = base64_decode($gid); 
        $invoice = Invoices::where('id',$id)->with('client')->get()->first();
        $pdf = PDF::loadView('InvoicePdf', compact('invoice'));
        
        return $pdf->stream('invoice_'.$id.'.pdf');
    }

    public function generatePayment($cid){
        
        $client = Client::where('id',$cid)->get()->first();
      
           $services[] = [
            
            "Amount"       => "1.00",
            "Currency"     => "ILS",
            "Name"         => "card Validation - ".$client->firstname." ".$client->lastname,
            "Description"  => 'card validation transaction', 
            "Quantity"     =>  1,
            "Image"        =>  "" ,
            "IsTaxFree"    =>  "false",
            "AdjustAmount" => "false"
            
           ];
        $se = json_encode($services);
      
        $username = '0882016016';
        $password = 'Z0882016016';

        $data = '{
            "Key": "'.env("ZCREDIT_KEY").'",
            "Local": "He",
            "UniqueId": "",
            "SuccessUrl": "'.url('/thanks').'/'.$cid.'",
            "CancelUrl": "",
            "CallbackUrl": "'.url('/thanks').'/'.$cid.'",
            "PaymentType": "regular",
            "CreateInvoice": "false",
            "AdditionalText": "",
            "ShowCart": "true",
            "ThemeColor": "005ebb",
            "BitButtonEnabled": "true",
            "ApplePayButtonEnabled": "true",
            "GooglePayButtonEnabled": "true",   
            "Installments": {
                "Type": "regular" , 
                "MinQuantity": "1",
                "MaxQuantity": "1"
            },
            "Customer": {
                "Email": "'.$client->email.'",
                "Name": "'.$client->firstname." ".$client->lastname.'" ,
                "PhoneNumber":  "'.$client->phone.'",
                "Attributes": {
                    "HolderId":  "none" ,
                    "Name":  "required" ,
                    "PhoneNumber":  "optional" ,
                    "Email":  "optional"
                }
            },
        "CartItems": '.$se.',

            "FocusType": "None",
            "CardIcons": {
                "ShowVisaIcon": "true",
                "ShowMastercardIcon": "true",
                "ShowDinersIcon": "true",
                "ShowAmericanExpressIcon": "true",
                "ShowIsracardIcon": "true",
            },
            "IssuerWhiteList": "1,2,3,4,5,6",
            "BrandWhiteList": "1,2,3,4,5,6",
            "UseLightMode": "false",
            "UseCustomCSS": "false"
        }';
       
        $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://pci.zcredit.co.il/webcheckout/api/WebCheckout/CreateSession',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_USERPWD => $username . ":" . $password,
            
            CURLOPT_POSTFIELDS =>$data,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
            ));

            $response = curl_exec($curl);
            $re = json_decode($response);
            if($re->HasError == true){
                die('Something went wrong ! Please contact Administrator !');
            }
            //Invoices::where('id',$id)->update(['session_id'=>$re->Data->SessionId]);
            return response()->json(["url"=>$re->Data->SessionUrl,'session_id'=>$re->Data->SessionId]);
    }
   
    public function recordInvoice($sid, $cid){

        $key = env('ZCREDIT_KEY');

        $curl = curl_init();
      
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://pci.zcredit.co.il/webcheckout/api/WebCheckout/GetSessionStatus',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
            "Key": "'.$key.'",
            "SessionId": "'.$sid.'"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $re = json_decode($response);
        curl_close($curl);
        $cb = json_decode($re->CallBackJSON);
   
        $x = explode('/',$cb->ExpDate_MMYY);
        $expiry = "20".$x[1]."-".$x[0];
        if(!empty($cb)){
           $args = [
             'client_id' => $cid,
             'card_token'=>$cb->Token,
             'valid'     =>$expiry,
             'cc_charge' => 1,
           ];
        ClientCard::create($args);
        
    
    }
}

    public function displayThanks($id){
        $client = Client::where('id',$id)->get()->first()->toArray();
        return view('thanks',compact('client'));
    
    } 
    public function deleteInvoice($id){
        Invoices::where('id',$id)->delete();
    }

    public function closeDoc($docnum,$type){

        $url = "https://api.icount.co.il/api/v3.php/doc/close";
        $params = Array(
    
        "cid"  => env('ICOUNT_COMPANYID'),
        "user" => env('ICOUNT_USERNAME'),
        "pass" => env('ICOUNT_PASS'),
        "doctype" => $type,
        "docnum"  => $docnum,
        "based_on"=> $docnum
        );
    
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, null, '&'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
           $resp =  curl_exec($ch);
           $res  =  json_decode($resp);

           $msg = ($res->status == true) ? 'Doc closed successfully!' : $res->reason;
           if($res->status == true){
             if($type == 'invoice'){
                Invoices::where('invoice_id',$docnum)->update(['invoice_icount_status'=>'Closed']);
             }
             if($type == 'order'){
                Order::where('order_id',$docnum)->update(['status'=>'Closed']);
             }
           }
           return response()->json(['msg'=>$msg]); 

    }

    public function refund($tid ,$amount){
        $curl = curl_init();
        
        $data = '{
            "TerminalNumber": "0882016016",
            "Password": "Z0882016016",
            "TransactionIdToCancelOrRefund": "'.$tid.'",
            "TransactionSum": "'.$amount.'"
            }';

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://pci.zcredit.co.il/ZCreditWS/api/Transaction/RefundTransaction',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>$data,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
       
    }

    public function cancelDoc(Request $request){
        
        $url = "https://api.icount.co.il/api/v3.php/doc/cancel";
        $params = Array(
    
        "cid"  => env('ICOUNT_COMPANYID'),
        "user" => env('ICOUNT_USERNAME'),
        "pass" => env('ICOUNT_PASS'),
        "doctype" => $request->data['doctype'],
        "docnum"  => $request->data['docnum'],
        "reason"  => $request->data['reason'],
        );
        $type = $request->data['doctype'];
        $docnum = $request->data['docnum'];
    
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, null, '&'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
           $resp =  curl_exec($ch);
           $res  =  json_decode($resp);
          
           $msg = ($res->status == true) ? 'Doc cancelled successfully!' : $res->reason;
           if($res->status == true){

             if($type == 'invoice' || $type =='invrec'){

                //initiate refund 
                $inv = Invoices::where('invoice_id',$docnum)->get()->first();
                if($inv->txn_id != null && $inv->callback != null){
                   $re = $this->refund($inv->txn_id,$inv->amount);
                   if(!empty($re)){
                    $args = [
                        'invoice_id' => $inv->id,
                        'invoice_icount_id' =>$inv->invoice_id,
                        'refrence'=>$re->ReferenceNumber,
                        'message'=>$re->ReturnMessage
                    ];
                    Refunds::create($args);
                   }
                }

                Invoices::where('invoice_id',$docnum)->update(['invoice_icount_status'=>'Cancelled']);
             }
             if($type == 'order'){
                Order::where('order_id',$docnum)->update(['status'=>'Cancelled']);
             }
           }
           return response()->json(['msg'=>$msg]); 

    }

    public function commitInvoicePayment( $services , $id, $token){

        $job = Job::where('id',$id)->with('jobservice','client','contract','order')->get()->first();
        $pitems = [];
        $subtotal = (int)$services[0]->unitprice;
        $tax = (17/100) * $subtotal;
        $total = $tax+$subtotal;

        if(!empty($services)){
            foreach($services as $service){
                $pitems[] = [
                    'ItemDescription' => $service->description,
                    'ItemQuantity'    => $service->quantity,
                    'ItemPrice'       => $total,
                    'IsTaxFree'       => "false"
                ];
               
            }
        }
        $pay_items = json_encode($pitems);

      $curl = curl_init();

      $pdata = '{
        "TerminalNumber": "'.env("ZCREDIT_TERMINALNUMBER").'",
        "Password": "'.env("ZCREDIT_TERMINALPASSWORD").'",
        "Track2": "",
        "CardNumber": "'.$token.'",
        "CVV": "",
        "ExpDate_MMYY": "",
        "TransactionSum": "'.$total.'",
        "NumberOfPayments": "1",
        "FirstPaymentSum": "0",
        "OtherPaymentsSum": "0",
        "TransactionType": "01",
        "CurrencyType": "1",
        "CreditType": "1",
        "J": "0",
        "IsCustomerPresent": "true",
        "AuthNum": "",
        "HolderID": "",
        "ExtraData": "",
        "CustomerName":"'.$job->client->firstname." ".$job->client->lastname.'",
        "CustomerAddress": "'.$job->client->geo_address.'",
        "CustomerEmail": "",
        "PhoneNumber": "",
        "ItemDescription": "",
        "ObeligoAction": "",
        "OriginalZCreditReferenceNumber": "",
        "TransactionUniqueIdForQuery": "",
        "TransactionUniqueID": "",
        "UseAdvancedDuplicatesCheck": "",
        "ZCreditInvoiceReceipt": {
          "Type": "0",
          "RecepientName": "",
          "RecepientCompanyID": "",
          "Address": "",
          "City": "",
          "ZipCode": "",
          "PhoneNum": "",
          "FaxNum": "",
          "TaxRate": "17",
          "Comment": "",
          "ReceipientEmail": "",
          "EmailDocumentToReceipient": "",
          "ReturnDocumentInResponse": "",
          "Items": '.$pay_items.'
        }
      }';
    
      curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://pci.zcredit.co.il/ZCreditWS/api/Transaction/CommitFullTransaction',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>$pdata,
        CURLOPT_HTTPHEADER => array(
          'Content-Type: application/json'
        ),
      ));
     
      $pre = curl_exec($curl);
      $pres = json_decode($pre);
      curl_close($curl);
      return $pres;

    }

    public function manualInvoice($oid){

     $_order = Order::where('id',$oid)->get()->first();
     $id = $_order->job_id;
     $job = Job::where('id',$id)->with('jobservice','client','contract','order')->get()->first();
     $services = json_decode($job->order->items);
     $total = 0;
     
     $card = ClientCard::where('client_id',$job->client_id)->get()->first();
     $p_method = $job->client->payment_method;
     $contract = $job->contract; 
     $doctype  = ($card != null && $card->card_token != null && $p_method == 'cc') ? "invrec" : "invoice"; 
    
     $subtotal = (int)$services[0]->unitprice;
     $tax = (17/100) * $subtotal;
     $total = $tax+$subtotal;
   
     $order = Order::where('job_id',$id)->get()->first();
     $o_res = json_decode($order->response);
  
     $due      = \Carbon\Carbon::now()->endOfMonth()->toDateString();
     $name     =  ($job->client->invoicename != null) ? $job->client->invoicename : $job->client->firstname." ".$job->client->lastname;
     $url = "https://api.icount.co.il/api/v3.php/doc/create";
     $params = Array(

             "cid"            => env('ICOUNT_COMPANYID'),
             "user"           => env('ICOUNT_USERNAME'),
             "pass"           => env('ICOUNT_PASS'),

             "doctype"        => $doctype,
             "client_id"      => $o_res->client_id,
             "client_name"    => $name, 
             "client_address" => $job->client->geo_address,
             "email"          => $job->client->email, 
             "lang"           => $job->client->lng,
             "currency_code"  => "ILS",
             "doc_lang"       => $job->client->lng,
             "items"          => $services,
             "duedate"        => $due,
             "based_on"       =>['docnum'=>$order->order_id,'doctype'=>'order'],
             
             "send_email"      => 1, 
             "email_to_client" => 1, 
             "email_to"        => $job->client->email, 
             
     );
     if($doctype == "invrec"){

         $ex = explode('-',$card->valid);
         $cc = ['cc'=>[
             "sum" => $total,
             "card_type" => $card->card_type,
             "card_number" => substr($card->card_number,12), 
             "exp_year" => $ex[0],
             "exp_month" => $ex[1],
             "holder_id" => "",
             "holder_name" => $contract->name_on_card,
             "confirmation_code" => ""
         ]];

         $_params = array_merge($params,$cc);

     } else {
         $_params = $params;
     }
   
     $ch = curl_init($url);
     curl_setopt($ch, CURLOPT_POST, 1);
     curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_params, null, '&'));
     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
     $response = curl_exec($ch);
     $info = curl_getinfo($ch);
     
     //if(!$info["http_code"] || $info["http_code"]!=200) die("HTTP Error");
     $json = json_decode($response, true);
   
     //if(!$json["status"]) die($json["reason"]);

     /* Auto payment */
     if( $doctype == 'invrec'){
         $pres = $this->commitInvoicePayment($services, $id, $card->card_token);
         $pre = json_encode($pres);
       }

   /*Close Order */
       $this->closeDoc($job->order->order_id,'order');
   
       job::where('id',$id)->update([
           'invoice_no'    =>$json["docnum"],
           'invoice_url'   =>$json["doc_url"],
           'isOrdered'     => 2
       ]);
       $invoice = [
           'invoice_id' => $json['docnum'],
           'job_id'     => $id,
           'amount'     => $total,
           'paid_amount'=> $total,
           'pay_method' => ( (isset($pres)) && $pres->HasError == false && $doctype == 'invrec' ) ? 'Credit Card' : 'NA',
           'customer'   => $job->client->id,
           'doc_url'    => $json['doc_url'],
           'type'       => $doctype,
           'invoice_icount_status' => 'Open',
           'due_date'   => $due,
           'txn_id'     => ( (isset($pres)) && $pres->HasError == false && $doctype == 'invrec' ) ? $pres->ReferenceNumber : '',
           'callback'   => ( (isset($pres)) && $pres->HasError == false && $doctype == 'invrec') ? $pre : '',
           'status'     => ( (isset($pres))  && $pres->HasError == false && $doctype == 'invrec') ? 'Paid' : ( (isset($pres)) ? $pres->ReturnMessage : 'Unpaid'),
       ];
       
       $inv = Invoices::create($invoice);
        if((isset($pres))  && $pres->HasError == false && $doctype == 'invrec' ){
         //close invoice
         $this->closeDoc($json['docnum'],'invrec');
         Invoices::where('id',$inv->id)->update(['invoice_icount_status'=>'Closed']);

     }
       Order::where('id',$job->order->id)->update(['status'=>'Closed']);
       JobService::where('id',$job->jobservice[0]->id)->update(['order_status'=>2]);
       Order::where('id',$oid)->update(['invoice_status'=>2]);
    }


    /*Orders Apis */

    public function getOrders(Request $request){
        
        $orders = Order::with('job','client');

        if(isset($request->status)){
            $orders = $orders->where('status',$request->status);
        }

        if(isset($request->order_id)){
            $orders = $orders->where('order_id',$request->order_id);
        }

        if(isset($request->from_dat ) && isset($request->to_date)){

            $orders = $orders->whereDate('created_at','>=',$request->from_date)
                              ->whereDate('created_at','<=',$request->to_date);
        }

        if(isset($request->client)){
            $q = $request->client;
            $ex = explode(' ',$q); 
            $orders = $orders->WhereHas('client',function ($qr) use ($q,$ex){
                $qr->where(function($qr) use ($q,$ex) {
                $qr->where('firstname', 'like','%'.$ex[0].'%');
                if(isset($ex[1]))
                $qr->where('lastname', 'like','%'.$ex[1].'%');
            });
        });

        }

        $orders = $orders->orderBy('id','desc')->paginate(20);
        return response()->json([
            'orders' =>$orders
        ]);
    }

    public function AddOrder(Request $request){

        $id = $request->data['job'];
        $job = Job::where('id',$id)->with('jobservice','client')->get()->first();
      
        $services = json_decode($request->data['services']);

        // $items = []; 
        // if(isset($services)){
        //     foreach($services as $service){
                
        //       $itm = [
        //         "description" => $service->name." - ".\Carbon\Carbon::today()->format('d, M Y'),
        //         "unitprice"   => $service->total,
        //         "quantity"    => 1,
        //       ];
        //       array_push($items,$itm);
        //     }
        //     JobService::where('id',$service->id)->update(['order_status'=>1]);
        // }
     
        $invoice  = 1;
        if( str_contains($job->schedule,'w') ){ 
            $invoice = 0;
        }
        $name     =  ($job->client->invoicename != null) ? $job->client->invoicename : $job->client->firstname." ".$job->client->lastname;
        $url = "https://api.icount.co.il/api/v3.php/doc/create";
        $params = Array(

        "cid"  => env('ICOUNT_COMPANYID'),
        "user" => env('ICOUNT_USERNAME'),
        "pass" => env('ICOUNT_PASS'),
        "doctype" => "order",
        "client_name" => $name, 
        "client_address" => $job->client->geo_address,
        "email" => $job->client->email, 
        "lang" => $job->client->lng,
        "currency_code" => "ILS",
        
        "items" => $services,
    

        "send_email" => 0, 
        "email_to_client" => 0, 
        "email_to" => $job->client->email, 
        );
       

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, null, '&'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            $info = curl_getinfo($ch);
         
            //if(!$info["http_code"] || $info["http_code"]!=200) die("HTTP Error");
            $json = json_decode($response, true);
           
           //if(!$json["status"]) die($json["reason"]);
           
            Order::create([
                'order_id'=>$json['docnum'],
                'doc_url' =>$json['doc_url'],
                'job_id'=>$id,
                'contract_id'=>$job->contract_id,
                'client_id'=>$job->client->id,
                'response' => $response,
                'items' => json_encode($services),
                'status' => 'Open',
                'invoice_status'=>( $invoice == 1) ? 1 : 0,
            ]);   
            Job::where('id',$id)->update(['isOrdered'=>1]);   

    
    }

    public function getClientOrders($id){
        $orders = Order::where('client_id',$id)->with('job','client')->orderBy('id','desc')->paginate(20);
        return response()->json([
            'orders' =>$orders
        ]);
    }


    public function deleteOrders($id){
      Order::where('id',$id)->delete();
    }

    public function getPayments(Request $request){

        $payments = Invoices::with('job','client');

        if(isset($request->from_date) && isset($request->to_date)){
            $payments = $payments->whereDate('created_at','>=',$request->from_date)
                                 ->whereDate('created_at','<=',$request->to_date);
         }
  
         if(isset($request->invoice_id)){
            $payments = $payments->where('invoice_id',$request->invoice_id);
         }
  
         if(isset($request->txn_id)){
          $payments = $payments->where('txn_id',$request->txn_id);
         }
  
         if(isset($request->pay_method)){
          $payments = $payments->where('pay_method',$request->pay_method);
         }
  
  
          if(isset($request->client)){
              $q = $request->client;
              $ex = explode(' ',$q); 
              $payments = $payments->WhereHas('client',function ($qr) use ($q,$ex){
                  $qr->where(function($qr) use ($q,$ex) {
                  $qr->where('firstname', 'like','%'.$ex[0].'%');
                  if(isset($ex[1]))
                  $qr->where('lastname', 'like','%'.$ex[1].'%');
              });
          });
        }
         
        $payments = $payments->orderBy('id','desc')->where('status','Paid')->orWhere('status','Partial Paid')->paginate(20);
       
        return response()->json([
            'pay' =>$payments
        ]);
    }

    public function getClientPayments($id){

        $payments = Invoices::where('customer',$id)->where('status','Paid')->orWhere('status','Partial Paid')->with('job','client')->orderBy('id','desc')->paginate(20);
        return response()->json([
            'pay' =>$payments
        ]);
    }

    /*Manual Invoice from add*/

    public function getClientInvoiceJob($cid){
        $clientJobs = Order::where('client_id',$cid)->get();
        return response()->json([
            'clientJobs' => $clientJobs
        ]);
    }

    public function clientInvoiceOrders(Request $request){
      $orders = Order::where('job_id',$request->cid)->get();
      return response()->json([
        'orders'=>$orders
      ]);
    }

   
    public function getCodesOrders( Request $request){
        $codes = $request->codes;

        if(!empty($codes)){
            $jservices = [];
            foreach($codes as $code){
            
                $od = Order::where('id',$code)->get()->first();
                $service = json_decode($od->items);
                $jservices[] = $service[0];
            }
            return response()->json([
                'services' => $jservices
            ]);
        }
    }

}
