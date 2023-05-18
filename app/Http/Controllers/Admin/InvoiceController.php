<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use App\Models\Invoices;
use App\Models\Job;
use App\Models\Order;
use App\Models\JobService;
use Barryvdh\DomPDF\PDF as DomPDFPDF;
use PDF;
class InvoiceController extends Controller
{
    public function index(){
       $invoices = Invoices::with('client')->orderBy('created_at', 'desc')->paginate(20);
       return response()->json([
        'invoices' => $invoices
       ]);
  

    }
    public function AddInvoice( Request $request ){
        
       
        $services = json_decode($request->data['services']);
       
        if(!empty($services)){
            foreach($services as $s){
                JobService::where('id',$s->id)->update(['order_status'=>1]);
            }
        }
        Invoices::create($request->data);
        
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
    public function updateInvoice( Request $request, $id ){
        Invoices::where('id',$id)->update($request->data);
        return response()->json([
            'msg' => 'Invoice Updated successfully'
        ]);
    }
    public function invoiceJobs( Request $request){
        $codes = $request->codes;
        if(!empty($codes)){
            $jservices = [];
            foreach($codes as $code){
                $job = Job::where('id',$code)->with('jobservice')->get()->first();
                $service = $job->jobservice[0]->toarray();
                $jservices[] = $service;
            }
            return response()->json([
                'services' => $jservices
            ]);
        }
    }

    public function viewInvoice($gid){
        $id = base64_decode($gid); 
        $invoice = Invoices::where('id',$id)->with('client')->get()->first();
        $pdf = PDF::loadView('InvoicePdf', compact('invoice'));
        
        return $pdf->stream('invoice_'.$id.'.pdf');
    }

    public function generatePayment($nid){
        
        $id = base64_decode($nid);
        $invoice = Invoices::where('id',$id)->get()->first();
        $job  = Job::where('id',$invoice->job_id)->with('client','jobservice','order')->get()->first();
        $_services = json_decode($job->order->items);
        $client = $job->client;
        
        $subtotal = (int)$_services[0]->unitprice;
        $tax = (17/100) * $subtotal;
        $total = $tax+$subtotal;
        
        $services = array();
       
        foreach( $_services as $serv ){
           $services[] = [
            
            "Amount"       => $total,
            "Currency"     => "ILS",
            "Name"         => $serv->description,
            "Description"  => '', 
            "Quantity"     =>  $serv->quantity,
            "Image"        =>  "" ,
            "IsTaxFree"    =>  "false",
            "AdjustAmount" => "false"
            
           ];
        }
        $se = json_encode($services);
       
        $username = '0882016016';
        $password = 'Z0882016016';

        $data = '{
            "Key": "'.env("ZCREDIT_KEY").'",
            "Local": "He",
            "UniqueId": "",
            "SuccessUrl": "'.url('/record-invoice?cb='.$nid).'",
            "CancelUrl": "",
            "CallbackUrl": "'.url('/record-invoice?cb='.$nid).'",
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
            Invoices::where('id',$id)->update(['session_id'=>$re->Data->SessionId]);
            return redirect($re->Data->SessionUrl);
    }
    public function recordInvoice(Request $request){
       
        $id = base64_decode($request->cb);
       
        $invoice = Invoices::where('id',$id)->get()->first();
        $job = Job::where('id',$invoice->job_id)->with('order','client')->get()->first();
        $isreceipt = 0;
        if($job->client->payment_method != 'cc'){ $isreceipt = 1; }
       
        $docnum = $job->order->order_id;
        $sid = $invoice->session_id;
        $key = env('ZCREDIT_KEY');
       
    if(is_null($invoice->callback)):

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
        if(!empty($cb)){
           $args = [
             'callback' => $re->CallBackJSON,
             'status' => 'paid',
             'txn_id' => $re->TransactionID,
           ];

        // $services = json_decode($invoice->services);
    
        // if(!empty($services)){
        //     foreach($services as $s){
        //         JobService::where('id',$s->id)->update(['pay_status'=>1]);
        //     }
        // }
      
        $url = "https://api.icount.co.il/api/v3.php/doc/close";
        $params = Array(

        "cid"  => env('ICOUNT_COMPANYID'),
        "user" => env('ICOUNT_USERNAME'),
        "pass" => env('ICOUNT_PASS'),
        "doctype" => "order",
        "docnum"  => $docnum,
        "based_on"=> $docnum
        );

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, null, '&'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $rec = curl_exec($ch);
            $r_info = curl_getinfo($ch);

            //if(!$info["http_code"] || $info["http_code"]!=200) die("HTTP Error");
           // $json = json_decode($response, true);
        Order::where('id',$job->order->id)->update(['status'=>'Closed']);
        Invoices::where('id',$id)->update($args);
        
        } else {
            die('Something went wrong ! Please contact Administrator !');
        }
       
    endif;
    return redirect('thanks?cb='.$request->cb.'');
     
    }

    public function displayThanks(Request $request){
        $invoice = Invoices::where('id',base64_decode($request->cb))->with('client')->get()->first();
        $pm = json_decode($invoice->callback)->Total;
        return view('thanks',compact('invoice','pm'));
    
    } 
    public function deleteInvoice($id){
        Invoices::where('id',$id)->delete();
    }

    public function CloseDoc($docnum){

        $url = "https://api.icount.co.il/api/v3.php/doc/close";
        $params = Array(
    
        "cid"  => env('ICOUNT_COMPANYID'),
        "user" => env('ICOUNT_USERNAME'),
        "pass" => env('ICOUNT_PASS'),
        "doctype" => "order",
        "docnum"  => $docnum,
        "based_on"=> $docnum
        );
    
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, null, '&'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
           $resp =  curl_exec($ch);
           $res  =  json_decode($resp);

           $msg = ($res->status == 'true') ? 'Doc closed successfully!' : $res->reason;
           return response()->json(['msg'=>$msg]); 

    }


    /*Orders Apis */

    public function getOrders(){

        $orders = Order::with('job','client')->orderBy('id','desc')->paginate(20);
        return response()->json([
            'orders' =>$orders
        ]);
    }

    public function deleteOrders($id){
      Order::where('id',$id)->delete();
    }

    public function getPayments(){

        $payments = Invoices::where('status','Paid')->orWhere('status','Partial Paid')->with('job','client')->paginate(20);
        return response()->json([
            'pay' =>$payments
        ]);
    }

}
