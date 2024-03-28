<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SettingKeyEnum;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoices;
use App\Models\Job;
use App\Models\Order;
use App\Models\ClientCard;
use App\Models\JobService;
use App\Models\Receipts;
use App\Models\Refunds;
use App\Models\Services;
use App\Helpers\Helper;
use App\Models\Setting;
use App\Traits\ClientCardTrait;
use App\Traits\PaymentAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use PaymentAPI, ClientCardTrait;

    public function index(Request $request)
    {
        $invoices = Invoices::with('client', 'receipt');

        if (isset($request->from_date) && isset($request->to_date)) {
            $invoices = $invoices->whereDate('created_at', '>=', $request->from_date)
                ->whereDate('created_at', '<=', $request->to_date);
        }

        if (isset($request->invoice_id)) {
            $invoices = $invoices->where('invoice_id', $request->invoice_id);
        }

        if (isset($request->txn_id)) {
            $invoices = $invoices->where('txn_id', $request->txn_id);
        }

        if (isset($request->pay_method)) {
            $invoices = $invoices->where('pay_method', $request->pay_method);
        }

        if (isset($request->status)) {
            $invoices = $invoices->where('status', $request->status);
        }

        if (isset($request->type)) {

            if ($request == 'receipt') {
                $invoices = $invoices->where('receipt_id', '!=', 'null');
            } else if ($request == 'refund') {
                $invoices = $invoices->where('callback', '!=', 'null')->where('status', '=', 'Cancelled');
            } else {
                $invoices = $invoices->where('type', $request->type);
            }
        }

        if (isset($request->client)) {
            $q = $request->client;
            $ex = explode(' ', $q);
            $invoices = $invoices->WhereHas('client', function ($qr) use ($q, $ex) {
                $qr->where(function ($qr) use ($q, $ex) {
                    $qr->where('firstname', 'like', '%' . $ex[0] . '%');
                    if (isset($ex[1])) {
                        $qr->where('lastname', 'like', '%' . $ex[1] . '%');
                    }
                });
            });
        }

        $ta         = 0;
        $pa         = 0;
        $ua         = 0;
        $ppa        = 0;

        $all        = 0;
        $paid       = 0;
        $unpaid     = 0;
        $partial    = 0;

        $get_ta  = Invoices::get();
        $get_pa  = Invoices::where('status', 'Paid')->get();
        $get_ua  = Invoices::where('status', 'Unpaid')->get();
        $get_ppa = Invoices::where('status', 'Partially Paid')->get();

        if (!empty($get_ta)) {
            foreach ($get_ta as $inv) {
                $all++;
                $ta += (floatval($inv->amount));
            }
        }

        if (!empty($get_pa)) {
            foreach ($get_pa as $gpa) {
                $paid++;
                $pa += floatval($gpa->amount);
            }
        }

        if (!empty($get_ua)) {
            foreach ($get_ua as $gua) {
                $unpaid++;
                $ua += floatval($gua->amount);
            }
        }

        if (!empty($get_ppa)) {
            foreach ($get_ppa as $gppa) {
                $partial++;
                $ppa += floatval($gppa->amount);
            }
        }

        $invoices = $invoices->orderBy('id', 'desc')->paginate(20);

        return response()->json([
            'invoices' => $invoices,
            'ta'       => round($ta, 2),
            'pa'       => round($pa, 2),
            'ua'       => round($ua, 2),
            'ppa'      => round($ppa, 2),
            'paid'     => $paid,
            'unpaid'   => $unpaid,
            'partial'  => $partial,
            'all'      => $all,
        ]);
    }

    public function getClientInvoices(Request $request, $id)
    {
        if ($request->f == 'all') {
            $invoices = Invoices::where('customer', $id)->with('client')->orderBy('id', 'desc')->paginate(20);
        }
        if (isset($request->status)) {
            $invoices = Invoices::with('client')->where('status', $request->status)->where('customer', $id)->orderBy('id', 'desc')->paginate(20);
        }
        if (isset($request->icount_status)) {
            $invoices = Invoices::with('client')->where('invoice_icount_status', $request->icount_status)->where('customer', $id)->orderBy('id', 'desc')->paginate(20);
        }

        $open       = Invoices::where('customer', $id)->where('invoice_icount_status', 'Open')->count();
        $closed     = Invoices::where('customer', $id)->where('invoice_icount_status', 'Closed')->count();
        $paid       = Invoices::where('customer', $id)->where('status', 'Paid')->count();
        $unpaid     = Invoices::where('customer', $id)->where('status', 'Unpaid')->count();
        $partial    = Invoices::where('customer', $id)->where('status', 'Partially Paid')->count();
        $all        = Invoices::where('customer', $id)->count();

        $ta         = 0;
        $pa         = 0;
        $ua         = 0;
        $ppa        = 0;

        if (!empty($invoices) && $request->f == 'all') {

            foreach ($invoices as $inv) {
                $ta += floatval($inv->amount);
            }
        }

        $get_pa  = Invoices::where('customer', $id)->where('status', 'Paid')->get();
        $get_ua  = Invoices::where('customer', $id)->where('status', 'Unpaid')->get();
        $get_ppa = Invoices::where('customer', $id)->where('status', 'Partially Paid')->get();

        if (!empty($get_pa)) {
            foreach ($get_pa as $gpa) {
                $pa += floatval($gpa->amount);
            }
        }

        if (!empty($get_ua)) {
            foreach ($get_ua as $gua) {
                $ua += floatval($gua->amount);
            }
        }

        if (!empty($get_ppa)) {
            foreach ($get_ppa as $gppa) {
                $ppa += floatval($gppa->amount);
            }
        }

        return response()->json([
            'invoices' => $invoices,
            'paid'     => $paid,
            'unpaid'   => $unpaid,
            'open'     => $open,
            'closed'   => $closed,
            'partial'  => $partial,
            'all'      => $all,
            'ta'       => round($ta, 2),
            'pa'       => round($pa, 2),
            'ua'       => round($ua, 2),
            'ppa'      => round($ppa, 2),
        ]);
    }

    public function AddInvoice(Request $request)
    {
        $req = $request->data;

        $client = Client::where('id', $req['customer'])->first();

        $services = json_decode($req['services']);
        $total = 0;

        $card = ClientCard::where('client_id', $client->id)->first();

        $doctype  = $req['doctype'];

        $subtotal = $req['amount'];
        $tax = (config('services.app.tax_percentage') / 100) * $subtotal;
        $total = $tax + $subtotal;

        $due = ($req['due_date'] == null) ? Carbon::now()->endOfMonth()->toDateString() : $req['due_date'];
        $name = ($client->invoicename != null) ? $client->invoicename : $client->firstname . " " . $client->lastname;
        $ln = ($client->lng == 'heb') ? 'he' : 'en';
        $url = "https://api.icount.co.il/api/v3.php/doc/create";

        /* Auto payment */
        if ($doctype == 'invrec') {
            $pres = $this->commitInvoicePayment($services, $req['job'], $card->card_token, $total);
            $pre = json_encode($pres);

            if ($pres->HasError == true) {
                $doctype = 'invoice';
            }
        }

        $params = array(
            "cid"            => Helper::get_setting(SettingKeyEnum::ICOUNT_COMPANY_ID),
            "user"           => Helper::get_setting(SettingKeyEnum::ICOUNT_USERNAME),
            "pass"           => Helper::get_setting(SettingKeyEnum::ICOUNT_PASSWORD),

            "doctype"        => $doctype,
            "client_id"      => $client->id,
            "client_name"    => $name,
            "client_address" => $client->geo_address,
            "email"          => $client->email,
            "lang"           => $ln,
            "currency_code"  => "ILS",
            "doc_lang"       => $ln,
            "items"          => $services,
            "duedate"        => $due,

            "send_email"      => 1,
            "email_to_client" => 1,
            "email_to"        => $client->email,
        );

        if ($doctype == "invrec") {
            if ($card == null) {
                return response()->json([
                    'rescode' => 401,
                    'msg' => 'Card Not Added for this user'
                ]);
            }

            $ex = explode('-', $card->valid);
            $cc = ['cc' => [
                "sum" => $total,
                "card_type" => $card->card_type,
                "card_number" => $card->card_number,
                "exp_year" => $ex[0],
                "exp_month" => $ex[1],
                "holder_id" => "",
                "confirmation_code" => ""
            ]];

            $_params = array_merge($params, $cc);
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

        Job::where('id', $req['job'])->update([
            'invoice_no'    => $json["docnum"],
            'invoice_url'   => $json["doc_url"],
            'isOrdered'     => 2,
            'status'        => 'completed'
        ]);

        $invoice = [
            'invoice_id' => $json['docnum'],
            'job_id'     => $req['job'],
            'amount'     => $total,
            'paid_amount' => $total,
            'pay_method' => ((isset($pres)) && $pres->HasError == false && $doctype == 'invrec') ? 'Credit Card' : 'NA',
            'customer'   => $client->id,
            'doc_url'    => $json['doc_url'],
            'type'       => $doctype,
            'invoice_icount_status' => 'Open',
            'due_date'   => $due,
            'txn_id'     => ((isset($pres)) && $pres->HasError == false && $doctype == 'invrec') ? $pres->ReferenceNumber : '',
            'callback'   => ((isset($pres))) ? $pre : '',
            'status'     => ((isset($pres))  && $pres->HasError == false && $doctype == 'invrec') ? 'Paid' : ((isset($pres)) ? $pres->ReturnMessage : 'Unpaid'),
        ];

        $inv = Invoices::create($invoice);
        if ((isset($pres))  && $pres->HasError == false && $doctype == 'invrec') {
            //close invoice
            $this->closeDoc($json['docnum'], 'invrec');
            Invoices::where('id', $inv->id)->update(['invoice_icount_status' => 'Closed']);
        }
        /*Close Order */
        if (!empty($req['codes'])) {
            $codes = $req['codes'];
            foreach ($codes as $code) {
                $this->closeDoc($code, 'order');
                Order::where('id', $code)->update(['status' => 'Closed', 'invoice_status' => 2]);
            }
        }

        //JobService::where('id',$job->jobservice->id)->update(['order_status'=>2]);

        return response()->json([
            'msg' => 'Invoice created successfully'
        ]);
    }

    public function getInvoice($id)
    {
        $invoice = Invoices::query()->with('client')->find($id);

        return response()->json([
            'rescode' => 201,
            'invoice' => $invoice
        ]);
    }

    public function getInvoiceIcount($docnum)
    {
        $url = "https://api.icount.co.il/api/v3.php/doc/info";

        $params = array(
            "cid"            => Helper::get_setting(SettingKeyEnum::ICOUNT_COMPANY_ID),
            "user"           => Helper::get_setting(SettingKeyEnum::ICOUNT_USERNAME),
            "pass"           => Helper::get_setting(SettingKeyEnum::ICOUNT_PASSWORD),
            "doctype"        => 'invoice',
            "docnum"         => $docnum,
            "get_items"      => 1
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, null, '&'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $re = json_decode($response);
        return $re;
    }

    public function updateInvoice(Request $request, $id)
    {
        $inv = Invoices::where('id', $id)->with('client')->get()->first();
        $client = $inv->client;
        $mode = $request->data['pay_method'];
        $pdata = $request->data;
        $total = 0;
        $cb =  '';

        if ($inv->amount <= $pdata['paid_amount'] && $inv->type == 'invoice') {

            $services = $this->getInvoiceIcount($inv->invoice_id);

            $sum = 0;
            $items = ($services->doc_info->items);
            if (!empty($items)) {
                foreach ($items as $itm) {
                    $sum += (int)$itm->unitprice;
                }

                $card = ClientCard::where('client_id', $client->id)->first();
                $name =  ($client->invoicename != null) ? $client->invoicename : $client->firstname . " " . $client->lastname;
                $url = "https://api.icount.co.il/api/v3.php/doc/create";
                $ln = ($client->lng == 'heb') ? 'he' : 'en';

                $params = array(
                    "cid"            => Helper::get_setting(SettingKeyEnum::ICOUNT_COMPANY_ID),
                    "user"           => Helper::get_setting(SettingKeyEnum::ICOUNT_USERNAME),
                    "pass"           => Helper::get_setting(SettingKeyEnum::ICOUNT_PASSWORD),

                    "doctype"        => 'receipt',
                    "client_name"    => $name,
                    "client_address" => $client->geo_address,
                    "email"          => $client->email,
                    "lang"           => $ln,
                    "currency_code"  => "ILS",
                    "doc_lang"       => $ln,
                    "items"          => $items,
                    "based_on"       => ['docnum' => $inv->invoice_id, 'doctype' => 'invoice'],

                    "send_email"      => 1,
                    "email_to_client" => 1,
                    "email_to"        => $client->email,
                );

                $txnID = $request->data['txn_id'];

                if ($mode == "Credit Card") {

                    if ($card == null) {
                        return response()->json([
                            'rescode' => 401,
                            'msg' => 'Card Not Added for this user'
                        ]);
                    }

                    $ex = explode('-', $card->valid);
                    $cc = ['cc' => [
                        "sum" => $sum,
                        "card_type" => $card->card_type,
                        "card_number" => $card->card_number,
                        "exp_year" => $ex[0],
                        "exp_month" => $ex[1],
                        "holder_id" => "",
                        "confirmation_code" => ""
                    ]];

                    $_params = array_merge($params, $cc);

                    $payment =  $this->commitInvoicePayment($items, $inv->job_id, $card->card_token, $sum);

                    $txnID  = $payment->ReferenceNumber;

                    $cb = json_encode($payment);
                } else if ($mode == "Bank Transfer") {
                    $bt = ["banktransfer" => [
                        "sum"    => $sum,
                        "date"   => $pdata['date'],
                        "account" => $pdata['account'],
                    ]];
                    $_params = array_merge($params, $bt);
                } else if ($mode == "Cheque") {
                    $ch = ["cheques" => [[
                        "sum"    => $sum,
                        "date"   => $pdata['date'],
                        "bank"   => $pdata['bank'],
                        "branch" => $pdata['branch'],
                        "account" => $pdata['account'],
                        "number" => $pdata['number']
                    ]]];
                    $_params = array_merge($params, $ch);
                } else if ($mode == "Cash") {
                    $cs = ["cash" => [
                        "sum"    => $sum,
                    ]];
                    $_params = array_merge($params, $cs);
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

                if (!empty($json)) {
                    $args = [
                        'invoice_id' => $inv->id,
                        'invoice_icount_id' => $inv->invoice_id,
                        'receipt_id' => $json['docnum'],
                        'docurl' => $json['doc_url'],
                    ];
                    $rcp = Receipts::create($args);
                    $this->closeDoc($inv->invoice_id, 'invoice');
                    Invoices::where('id', $inv->id)->update(['invoice_icount_status' => 'Closed', 'receipt_id' => $rcp->id]);
                }
            }
        }

        $idata = [
            'pay_method'  => $request->data['pay_method'],
            'txn_id'      => $txnID,
            'callback'    => $cb,
            'paid_amount' => $request->data['paid_amount'],
            'status'      => $request->data['status']
        ];

        Invoices::where('id', $id)->update($idata);
        return response()->json([
            'msg' => 'Invoice Updated successfully'
        ]);
    }

    public function invoiceJobs(Request $request)
    {
        $jobs = Job::where('client_id', $request->cid)
            ->where('status', '!=', 'completed')
            ->where('isOrdered', 0)
            ->orWhere('isOrdered', 'c')
            ->get();

        foreach ($jobs as $j => $job) {
            $sv = Services::query()->select('name')->find($job->schedule_id);
            $jobs[$j]['service_name'] = $sv->name;
        }

        return response()->json([
            'jobs' => $jobs
        ]);
    }

    public function invoiceJobOrder(Request $request)
    {
        $jobs = Job::where('client_id', $request->cid)
            ->where('status', '!=', 'completed')
            ->where('isOrdered', 1)
            ->get();

        foreach ($jobs as $j => $job) {
            $sv = Services::query()->select('name')->find($job->schedule_id);
            $jobs[$j]['service_name'] = $sv->name;
        }

        return response()->json([
            'jobs' => $jobs
        ]);
    }

    public function orderJobs(Request $request)
    {
        $job = Job::query()
            ->with(['jobservice', 'client'])
            ->find($request->id);

        return response()->json([
            'data' => $job
        ]);
    }

    public function viewInvoice($gid)
    {
        $id = base64_decode($gid);
        $invoice = Invoices::where('id', $id)->with('client')->get()->first();
        $pdf = Pdf::loadView('InvoicePdf', compact('invoice'));

        return $pdf->stream('invoice_' . $id . '.pdf');
    }

    public function displayThanks($id)
    {
        $client = Client::find($id)->toArray();

        return view('thanks', compact('client'));
    }

    public function deleteInvoice($id)
    {
        $invoice = Invoices::find($id);

        $invoice->delete();
    }

    public function closeDoc($docnum, $type)
    {
        $url = "https://api.icount.co.il/api/v3.php/doc/close";

        $params = array(
            "cid"  => Helper::get_setting(SettingKeyEnum::ICOUNT_COMPANY_ID),
            "user" => Helper::get_setting(SettingKeyEnum::ICOUNT_USERNAME),
            "pass" => Helper::get_setting(SettingKeyEnum::ICOUNT_PASSWORD),
            "doctype" => $type,
            "docnum"  => $docnum,
            "based_on" => $docnum
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, null, '&'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $resp =  curl_exec($ch);
        $res  =  json_decode($resp);

        $msg = ($res->status == true) ? 'Doc closed successfully!' : $res->reason;
        if ($res->status == true) {
            if ($type == 'invoice') {
                Invoices::where('invoice_id', $docnum)->update(['invoice_icount_status' => 'Closed']);
            }
            if ($type == 'order') {
                Order::where('order_id', $docnum)->update(['status' => 'Closed']);
            }
        }
        return response()->json(['msg' => $msg]);
    }

    public function refund($tid, $amount)
    {
        $curl = curl_init();

        $data = '{
            "TerminalNumber": "' . Helper::get_setting(SettingKeyEnum::ZCREDIT_TERMINAL_NUMBER) . '",
            "Password": "' . Helper::get_setting(SettingKeyEnum::ZCREDIT_TERMINAL_PASS) . '",
            "TransactionIdToCancelOrRefund": "' . $tid . '",
            "TransactionSum": "' . $amount . '"
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
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }

    public function cancelDoc(Request $request)
    {
        $url = "https://api.icount.co.il/api/v3.php/doc/cancel";

        $params = array(
            "cid"  => Helper::get_setting(SettingKeyEnum::ICOUNT_COMPANY_ID),
            "user" => Helper::get_setting(SettingKeyEnum::ICOUNT_USERNAME),
            "pass" => Helper::get_setting(SettingKeyEnum::ICOUNT_PASSWORD),
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
        if ($res->status == true) {

            if ($type == 'invoice' || $type == 'invrec') {

                //initiate refund 
                $inv = Invoices::where('invoice_id', $docnum)->get()->first();
                if ($inv->txn_id != null && $inv->callback != null) {
                    $re = $this->refund($inv->txn_id, $inv->amount);
                    if (!empty($re)) {
                        $args = [
                            'invoice_id' => $inv->id,
                            'invoice_icount_id' => $inv->invoice_id,
                            'refrence' => $re->ReferenceNumber,
                            'message' => $re->ReturnMessage
                        ];
                        Refunds::create($args);
                    }
                }

                Invoices::where('invoice_id', $docnum)->update(['invoice_icount_status' => 'Cancelled']);
            }

            if ($type == 'order') {
                $jid = Order::where('order_id', $docnum)->get('job_id')->first();
                Job::where('id', $jid->job_id)->update(['isOrdered' => 'c']);
                Order::where('order_id', $docnum)->update(['status' => 'Cancelled']);
            }
        }
        return response()->json(['msg' => $msg]);
    }

    public function commitInvoicePayment($services, $id, $token, $stotal)
    {
        $job = Job::query()
            ->with(['jobservice', 'client', 'contract', 'order'])
            ->find($id);

        $client = $job->client;
        $address = $client->property_addresses()->first();

        $pitems = [];

        $subtotal = (int) $stotal;
        $tax = (config('services.app.tax_percentage') / 100) * $subtotal;
        $total = $tax + $subtotal;

        if (!empty($services)) {
            foreach ($services as $k => $service) {
                if ($k == 0) {
                    $pitems[] = [
                        'ItemDescription' => $service->description,
                        'ItemQuantity'    => $service->quantity,
                        'ItemPrice'       => $total,
                        'IsTaxFree'       => "false"
                    ];
                }
            }
        }
        $pay_items = json_encode($pitems);

        $captureChargeResponse =  $this->captureCardCharge([
            'card_number' => $token,
            'amount' => $total,
            'client_name' => $client->firstname . ' ' . $client->lastname,
            'client_address' => $address ? $address->geo_address : '',
            'client_email' => $client->email,
            'client_phone' => $client->phone,
            'J' => 0,
            'obeligo_action' => "",
            'original_zcredit_reference_number' => "",
            'items' => $pay_items
        ]);

        if ($captureChargeResponse && !$captureChargeResponse['HasError']) {
            return $captureChargeResponse;
        }

        throw new Exception("Error Processing Charge Request", 500);
    }

    public function manualInvoice($oid)
    {
        $_order = Order::find($oid);

        $job = Job::query()
            ->with(['jobservice', 'client', 'contract', 'order'])
            ->find($_order->job_id);

        $services = json_decode($job->order[0]->items);

        $card = $this->getClientCard($job->client_id);
        $payment_method = $job->client->payment_method;

        $doctype = (!$card && $payment_method == 'cc') ? "invrec" : "invoice";

        if ($doctype == "invrec" && !$card) {
            throw new Exception("'Card not added for this client'", 1);
        }

        $subtotal = (int)$services[0]->unitprice;
        $tax = (config('services.app.tax_percentage') / 100) * $subtotal;
        $total = $tax + $subtotal;

        $order = Order::where('job_id', $_order->job_id)->first();
        $o_res = json_decode($order->response);

        $due = Carbon::now()->endOfMonth()->toDateString();
        $url = "https://api.icount.co.il/api/v3.php/doc/create";
        $ln = ($job->client->lng == 'heb') ? 'he' : 'en';

        /* Auto payment */
        if ($doctype == 'invrec') {
            $pres = $this->commitInvoicePayment($services, $_order->job_id, $card->card_token, $total);
            $pre = json_encode($pres);

            if ($pres->HasError == true) {
                $doctype = 'invoice';
            }
        }

        $params = array(
            "cid"            => Helper::get_setting(SettingKeyEnum::ICOUNT_COMPANY_ID),
            "user"           => Helper::get_setting(SettingKeyEnum::ICOUNT_USERNAME),
            "pass"           => Helper::get_setting(SettingKeyEnum::ICOUNT_PASSWORD),

            "doctype"        => $doctype,
            "client_id"      => $o_res->client_id,
            "client_name"    => $job->client->invoicename,
            "client_address" => $job->client->geo_address,
            "email"          => $job->client->email,
            "lang"           => $ln,
            "currency_code"  => "ILS",
            "doc_lang"       => $ln,
            "items"          => $services,
            "duedate"        => $due,
            "based_on"       => [
                'docnum'  => $order->order_id,
                'doctype' => 'order'
            ],
            "send_email"      => 1,
            "email_to_client" => 1,
            "email_to"        => $job->client->email,
        );

        if ($doctype == "invrec") {
            $ex = explode('-', $card->valid);
            $cc = ['cc' => [
                "sum" => $total,
                "num_of_payments" => 1,
                "first_payment" => 1,
                "token_id" => $card->card_token,
            ]];

            $_params = array_merge($params, $cc);
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

        /*Close Order */
        $this->closeDoc($job->order[0]->order_id, 'order');

        Job::where('id', $_order->job_id)->update([
            'invoice_no'    => $json["docnum"],
            'invoice_url'   => $json["doc_url"],
            'isOrdered'     => 2,
            'status'        => 'completed'
        ]);

        $invoice = Invoices::create([
            'invoice_id' => $json['docnum'],
            'job_id'     => $_order->job_id,
            'amount'     => $total,
            'paid_amount' => $total,
            'pay_method' => ((isset($pres)) && $pres->HasError == false && $doctype == 'invrec') ? 'Credit Card' : 'NA',
            'customer'   => $job->client->id,
            'doc_url'    => $json['doc_url'],
            'type'       => $doctype,
            'invoice_icount_status' => 'Open',
            'due_date'   => $due,
            'txn_id'     => ((isset($pres)) && $pres->HasError == false && $doctype == 'invrec') ? $pres->ReferenceNumber : '',
            'callback'   => ((isset($pres))) ? $pre : '',
            'status'     => ((isset($pres))  && $pres->HasError == false && $doctype == 'invrec') ? 'Paid' : ((isset($pres)) ? $pres->ReturnMessage : 'Unpaid'),
        ]);

        if ((isset($pres))  && $pres->HasError == false && $doctype == 'invrec') {
            //close invoice
            $this->closeDoc($json['docnum'], 'invrec');
            $invoice->update(['invoice_icount_status' => 'Closed']);
        }
        Order::where('id', $job->order[0]->id)->update(['status' => 'Closed']);
        JobService::where('id', $job->jobservice->id)->update(['order_status' => 2]);
        Order::where('id', $oid)->update(['invoice_status' => 2]);
    }

    public function multipleInvoices(Request $request)
    {
        $orders = Order::find($request->all());

        foreach ($orders as $order) {
            if ($order->invoice_status == 1 || $order->invoice_status == 0) {
                $this->manualInvoice($order->id);
            }
        }
    }
    /*Orders Apis */

    public function getOrders(Request $request)
    {
        $orders = Order::with('job', 'client');

        if (isset($request->status)) {
            $orders = $orders->where('status', $request->status);
        }

        if (isset($request->order_id)) {
            $orders = $orders->where('order_id', $request->order_id);
        }

        if (isset($request->from_dat) && isset($request->to_date)) {
            $orders = $orders->whereDate('created_at', '>=', $request->from_date)
                ->whereDate('created_at', '<=', $request->to_date);
        }

        if (isset($request->client)) {
            $q = $request->client;
            $ex = explode(' ', $q);
            $orders = $orders->WhereHas('client', function ($qr) use ($q, $ex) {
                $qr->where(function ($qr) use ($q, $ex) {
                    $qr->where('firstname', 'like', '%' . $ex[0] . '%');
                    if (isset($ex[1]))
                        $qr->where('lastname', 'like', '%' . $ex[1] . '%');
                });
            });
        }

        $orders = $orders->orderBy('id', 'desc')->paginate(20);
        return response()->json([
            'orders' => $orders
        ]);
    }

    public function createOrder(Request $request)
    {
        $id = $request->job_id;
        $job = Job::query()->with(['jobservice', 'client'])->find($id);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found'
            ], 404);
        }

        if ($job->is_order_generated) {
            return response()->json([
                'message' => 'Order is already created for the Job'
            ], 403);
        }

        if (empty($job->client->invoicename)) {
            return response()->json([
                'message' => "Client's invoice name is not set"
            ], 403);
        }

        $services = $request->services;

        $this->generateOrderDocument($job, $services);

        return response()->json([
            'message' => 'Order generated successfully'
        ]);
    }

    public function multipleOrders(Request $request)
    {
        $jids = $request->ar;

        if (!empty($jids)) {
            foreach ($jids as $jid) {

                $job = Job::query()
                    ->with(['jobservice', 'client', 'order'])
                    ->whereDoesntHave('order')
                    ->find($jid);

                if ($job) {
                    $items = [];
                    if (isset($job->jobservice)) {
                        $service = $job->jobservice;

                        $itm = [
                            "description" => ($job->client->lng == 'en') ?  $service->name : $service->heb_name . " - " . Carbon::today()->format('d, M Y'),
                            "unitprice"   => $service->total,
                            "quantity"    => 1,
                        ];
                        array_push($items, $itm);
                    }

                    $invoice  = 1;
                    if (str_contains($job->schedule, 'w')) {
                        $invoice = 0;
                    }
                    $name = ($job->client->invoicename != null) ? $job->client->invoicename : $job->client->firstname . " " . $job->client->lastname;
                    $url = "https://api.icount.co.il/api/v3.php/doc/create";
                    $ln = ($job->client->lng == 'heb') ? 'he' : 'en';

                    $params = array(
                        "cid"  => Helper::get_setting(SettingKeyEnum::ICOUNT_COMPANY_ID),
                        "user" => Helper::get_setting(SettingKeyEnum::ICOUNT_USERNAME),
                        "pass" => Helper::get_setting(SettingKeyEnum::ICOUNT_PASSWORD),
                        "doctype" => "order",
                        "client_name" => $name,
                        "client_address" => $job->client->geo_address,
                        "email" => $job->client->email,
                        "lang" => $ln,
                        "currency_code" => "ILS",
                        "items" => $items,
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
                        'order_id' => $json['docnum'],
                        'doc_url' => $json['doc_url'],
                        'job_id' => $job->id,
                        'contract_id' => $job->contract_id,
                        'client_id' => $job->client->id,
                        'response' => $response,
                        'items' => json_encode($items),
                        'status' => 'Open',
                        'invoice_status' => ($invoice == 1) ? 1 : 0,
                    ]);

                    $job->update(['isOrdered' => 1]);
                }
            }
        }
    }

    public function getClientOrders(Request $request, $id)
    {
        if ($request->f == 'all') {
            $orders = Order::query()
                ->with(['job', 'client'])
                ->where('client_id', $id)
                ->orderBy('id', 'desc')
                ->paginate(20);
        }

        if (isset($request->status)) {
            $orders = Order::query()
                ->with(['job', 'client'])
                ->where('status', $request->status)
                ->where('client_id', $id)
                ->orderBy('id', 'desc')
                ->paginate(20);
        }

        if (isset($request->invoice_status) && $request->invoice_status != 'm') {
            $orders = Order::query()
                ->with(['job', 'client'])
                ->where('invoice_status', $request->invoice_status)
                ->where('client_id', $id)
                ->orderBy('id', 'desc')
                ->paginate(20);
        }

        if (isset($request->invoice_status) && $request->invoice_status == 'm') {
            $orders = Order::query()
                ->with(['job', 'client'])
                ->where('invoice_status', 1)
                ->orwhere('invoice_status', 2)
                ->where('client_id', $id)
                ->orderBy('id', 'desc')
                ->paginate(20);
        }

        $open     = Order::where('client_id', $id)->where('status', 'Open')->count();
        $closed   = Order::where('client_id', $id)->where('status', 'Closed')->count();
        $gen      = Order::where('invoice_status', '1')->orwhere('invoice_status', '2')->where('client_id', $id)->count();
        $ngen     = Order::where('client_id', $id)->where('invoice_status', '0')->count();
        $all      = Order::where('client_id', $id)->count();

        return response()->json([
            'orders'        => $orders,
            'open'          => $open,
            'closed'        => $closed,
            'generated'     => $gen,
            'not_generated' => $ngen,
            'all'           => $all,
        ]);
    }

    public function deleteOrders($id)
    {
        $order = Order::find($id);

        $order->delete();
    }

    public function getPayments(Request $request)
    {
        $payments = Invoices::with('job', 'client');

        if (isset($request->from_date) && isset($request->to_date)) {
            $payments = $payments->whereDate('created_at', '>=', $request->from_date)
                ->whereDate('created_at', '<=', $request->to_date);
        }

        if (isset($request->invoice_id)) {
            $payments = $payments->where('invoice_id', $request->invoice_id);
        }

        if (isset($request->txn_id)) {
            $payments = $payments->where('txn_id', $request->txn_id);
        }

        if (isset($request->pay_method)) {
            $payments = $payments->where('pay_method', $request->pay_method);
        }

        if (isset($request->client)) {
            $q = $request->client;
            $ex = explode(' ', $q);
            $payments = $payments->WhereHas('client', function ($qr) use ($q, $ex) {
                $qr->where(function ($qr) use ($q, $ex) {
                    $qr->where('firstname', 'like', '%' . $ex[0] . '%');
                    if (isset($ex[1]))
                        $qr->where('lastname', 'like', '%' . $ex[1] . '%');
                });
            });
        }

        $payments = $payments->orderBy('id', 'desc')->where('status', 'Paid')->orWhere('status', 'Partial Paid')->paginate(20);

        return response()->json([
            'pay' => $payments
        ]);
    }

    public function getClientPayments($id)
    {
        $payments = Invoices::query()
            ->with('job', 'client')
            ->where('customer', $id)
            ->where('status', 'Paid')
            ->orWhere('status', 'Partial Paid')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return response()->json([
            'pay' => $payments
        ]);
    }

    /*Manual Invoice from add*/

    public function getClientInvoiceJob($cid)
    {
        $clientJobs = Order::where('client_id', $cid)->get();
        return response()->json([
            'clientJobs' => $clientJobs
        ]);
    }

    public function clientInvoiceOrders(Request $request)
    {
        $orders = Order::where('job_id', $request->cid)->get();
        return response()->json([
            'orders' => $orders
        ]);
    }

    public function getCodesOrders(Request $request)
    {
        $codes = $request->codes;

        if (!empty($codes)) {
            $jservices = [];

            foreach ($codes as $code) {
                $od = Order::find($code);
                $service = json_decode($od->items);
                $jservices[] = $service[0];
            }

            return response()->json([
                'services' => $jservices
            ]);
        }
    }
}
