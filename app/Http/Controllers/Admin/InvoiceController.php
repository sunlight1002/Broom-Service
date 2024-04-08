<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JobStatusEnum;
use App\Enums\SettingKeyEnum;
use App\Enums\TransactionStatusEnum;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoices;
use App\Models\Job;
use App\Models\Order;
use App\Models\Receipts;
use App\Models\Refunds;
use App\Models\Services;
use App\Models\Transaction;
use App\Traits\ClientCardTrait;
use App\Traits\PaymentAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            $invoices = Invoices::query()
                ->with('client')
                ->where('client_id', $id)
                ->orderBy('id', 'desc')
                ->paginate(20);
        }
        if (isset($request->status)) {
            $invoices = Invoices::query()
                ->with('client')
                ->where('status', $request->status)
                ->where('client_id', $id)
                ->orderBy('id', 'desc')
                ->paginate(20);
        }
        if (isset($request->icount_status)) {
            $invoices = Invoices::query()
                ->with('client')
                ->where('invoice_icount_status', $request->icount_status)
                ->where('client_id', $id)
                ->orderBy('id', 'desc')
                ->paginate(20);
        }

        $open       = Invoices::where('client_id', $id)->where('invoice_icount_status', 'Open')->count();
        $closed     = Invoices::where('client_id', $id)->where('invoice_icount_status', 'Closed')->count();
        $paid       = Invoices::where('client_id', $id)->where('status', 'Paid')->count();
        $unpaid     = Invoices::where('client_id', $id)->where('status', 'Unpaid')->count();
        $partial    = Invoices::where('client_id', $id)->where('status', 'Partially Paid')->count();
        $all        = Invoices::where('client_id', $id)->count();

        $ta         = 0;
        $pa         = 0;
        $ua         = 0;
        $ppa        = 0;

        if (!empty($invoices) && $request->f == 'all') {

            foreach ($invoices as $inv) {
                $ta += floatval($inv->amount);
            }
        }

        $get_pa  = Invoices::where('client_id', $id)->where('status', 'Paid')->get();
        $get_ua  = Invoices::where('client_id', $id)->where('status', 'Unpaid')->get();
        $get_ppa = Invoices::where('client_id', $id)->where('status', 'Partially Paid')->get();

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
        $data = $request->all();

        $client = Client::find($data['client_id']);

        if (empty($client->invoicename)) {
            return response()->json([
                'message' => "Client's invoice name is not set"
            ], 403);
        }

        $services = json_decode($data['services'], true);
        $total = 0;

        $card = $this->getClientCard($client->id);

        $payment_method = $client->payment_method;

        $doctype = $data['doctype'];

        $subtotal = $data['amount'];
        $tax = (config('services.app.tax_percentage') / 100) * $subtotal;
        $total = $tax + $subtotal;

        $isPaymentProcessed = false;
        /* Auto payment */
        if ($payment_method == 'cc') {
            if (!$card) {
                return response()->json([
                    'message' => 'Card not added for this client'
                ], 404);
            }

            $paymentResponse = $this->commitInvoicePayment($client, $services, $card->card_token, $total);

            if ($paymentResponse['HasError'] == true) {
                $doctype = 'invoice';
            } else {
                $isPaymentProcessed = true;
            }
        }

        $duedate = ($data['due_date'] == null) ? Carbon::now()->endOfMonth()->toDateString() : $data['due_date'];

        $url = "https://api.icount.co.il/api/v3.php/doc/create";

        $params = array(
            "cid"            => Helper::get_setting(SettingKeyEnum::ICOUNT_COMPANY_ID),
            "user"           => Helper::get_setting(SettingKeyEnum::ICOUNT_USERNAME),
            "pass"           => Helper::get_setting(SettingKeyEnum::ICOUNT_PASSWORD),

            "doctype"        => $doctype,
            "client_id"      => $client->id,
            "client_name"    => $client->invoicename,
            "client_address" => $client->geo_address,
            "email"          => $client->email,
            "lang"           => ($client->lng == 'heb') ? 'he' : 'en',
            "currency_code"  => "ILS",
            "doc_lang"       => ($client->lng == 'heb') ? 'he' : 'en',
            "items"          => $services,
            "duedate"        => $duedate,

            "send_email"      => 1,
            "email_to_client" => 1,
            "email_to"        => $client->email,
        );

        if ($payment_method == 'cc') {
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

        Job::where('id', $data['job'])->update([
            'invoice_no'    => $json["docnum"],
            'invoice_url'   => $json["doc_url"],
            'isOrdered'     => 2,
            'status'        => JobStatusEnum::COMPLETED
        ]);

        $invoice = Invoices::create([
            'invoice_id' => $json['docnum'],
            'amount'     => $total,
            'paid_amount' => $total,
            'pay_method' => ($isPaymentProcessed) ? 'Credit Card' : 'NA',
            'client_id'   => $client->id,
            'doc_url'    => $json['doc_url'],
            'type'       => $doctype,
            'invoice_icount_status' => 'Open',
            'due_date'   => $duedate,
            'txn_id'     => ($isPaymentProcessed) ? $paymentResponse['ReferenceNumber'] : '',
            'callback'   => isset($paymentResponse) ? json_encode($paymentResponse, true) : '',
            'status'     => ($isPaymentProcessed) ? 'Paid' : (isset($paymentResponse) ? $paymentResponse['ReturnMessage'] : 'Unpaid'),
        ]);

        // if ($isPaymentProcessed && $doctype == 'invrec') {
        //     //close invoice
        //     $this->closeDoc($json['docnum'], 'invrec');
        //     $invoice->update(['invoice_icount_status' => 'Closed']);
        // }
        /*Close Order */
        if (!empty($data['codes'])) {
            $codes = $data['codes'];
            foreach ($codes as $code) {
                $this->closeDoc($code, 'order');
                Order::where('id', $code)->update(['status' => 'Closed', 'invoice_status' => 2]);
            }
        }

        return response()->json([
            'message' => 'Invoice created successfully'
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

    public function updateInvoice(Request $request, $id)
    {
        $data = $request->all();

        $invoice = Invoices::query()->with(['client', 'order'])->find($id);
        if (empty($invoice)) {
            return response()->json([
                'message' => "Invoice not found"
            ], 404);
        }

        $client = $invoice->client;
        if (empty($client->invoicename)) {
            return response()->json([
                'message' => "Client's invoice name is not set"
            ], 403);
        }

        $mode = $data['pay_method'];
        if ($mode == "Credit Card") {
            $card = $this->getClientCard($client->id);
            if (!$card) {
                return response()->json([
                    'message' => 'Card not found for this client'
                ], 404);
            }
        }

        if ($invoice->type != 'invoice') {
            return response()->json([
                'message' => 'Document is not an invoice'
            ], 403);
        }

        // if ($invoice->amount != $data['paid_amount']) {
        //     return response()->json([
        //         'message' => 'Entered amount is equal to invoice amount'
        //     ], 403);
        // }

        $order = $invoice->order;
        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        $orderResponse = json_decode($order->response, true);

        $iCountClientID = $orderResponse['client_id'];
        $iCountDocument = $this->getICountDocument($invoice->invoice_id, 'invoice');

        if (!$iCountDocument["status"]) {
            throw new Exception($iCountDocument["reason"], 500);
        }

        $subtotal = 0;
        $services = [];
        $items = $iCountDocument['doc_info']['items'];
        foreach ($items as $item) {
            $subtotal += (int)$item['unitprice'];

            $services[] = [
                'description' => $item['description'],
                'unitprice' => $item['unitprice'],
                'quantity' => $item['quantity'],
            ];
        }

        $tax = (config('services.app.tax_percentage') / 100) * $subtotal;
        $total = $tax + $subtotal;

        $otherInvDocOptions = [
            'based_on' => [
                [
                    'docnum'  => $order->order_id,
                    'doctype' => 'order'
                ],
                [
                    'docnum'  => $invoice->invoice_id,
                    'doctype' => 'invoice'
                ]
            ]
        ];

        $txnID = $data['txn_id'];

        if ($mode == "Credit Card") {
            $otherInvDocOptions['cc'] = [
                "sum" => $total,
                "card_type" => $card->card_type,
                "card_number" => $card->card_number,
                "exp_year" => explode('/', $card->valid)[0],
                "exp_month" => explode('/', $card->valid)[1],
                "holder_id" => $card->card_holder_id,
            ];

            $paymentResponse = $this->commitInvoicePayment($client, $items, $card->card_token, $subtotal);

            if ($paymentResponse['HasError'] == true) {
                return response()->json([
                    'message' => 'Unable to pay from credit card'
                ], 500);
            }

            $txnID = $paymentResponse['ReferenceNumber'];
        } else if ($mode == "Bank Transfer") {
            $otherInvDocOptions['banktransfer'] = [
                "sum" => $total,
                "date"   => $data['date'],
                "account" => $data['account'],
            ];
        } else if ($mode == "Cheque") {
            $otherInvDocOptions['cheques'] = [
                [
                    "sum" => $total,
                    "date"   => $data['date'],
                    "bank"   => $data['bank'],
                    "branch" => $data['branch'],
                    "account" => $data['account'],
                    "number" => $data['number']
                ]
            ];
        } else if ($mode == "Cash") {
            $otherInvDocOptions['cash'] = [
                "sum" => $total,
            ];
        }

        $duedate = Carbon::today()->endOfMonth()->toDateString();

        $json = $this->generateInvRecDocument(
            $iCountClientID,
            $client,
            $services,
            $duedate,
            $otherInvDocOptions
        );

        $rcp = Receipts::create([
            'invoice_id' => $invoice->id,
            'invoice_icount_id' => $invoice->invoice_id,
            'receipt_id' => $json['docnum'],
            'docurl' => $json['doc_url'],
        ]);

        // $this->closeDoc($invoice->invoice_id, 'invoice');
        $invoice->update([
            'invoice_icount_status' => 'Closed',
            'receipt_id' => $rcp->id,
            'pay_method'  => $data['pay_method'],
            'txn_id'      => $txnID,
            'callback'    => isset($paymentResponse) ? json_encode($paymentResponse, true) : '',
            'paid_amount' => $data['paid_amount'],
            'status'      => $data['status']
        ]);

        return response()->json([
            'message' => 'Invoice updated successfully'
        ]);
    }

    public function invoiceJobs(Request $request)
    {
        $jobs = Job::where('client_id', $request->cid)
            ->where('status', '!=', JobStatusEnum::COMPLETED)
            ->where(function ($q) {
                $q
                    ->where('isOrdered', 0)
                    ->orWhere('isOrdered', 'c');
            })
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
            ->where('status', '!=', JobStatusEnum::COMPLETED)
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
        $invoice = Invoices::query()->with('client')->find($id);
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
        Log::info('close doc.' . $docnum . '-' . $type);
        $closeDocResponse = $this->closeICountDocument($docnum, $type);

        if (!$closeDocResponse["status"]) {
            throw new Exception($closeDocResponse["reason"], 500);
        }

        if ($type == 'invoice') {
            Invoices::where('invoice_id', $docnum)->update(['invoice_icount_status' => 'Closed']);
        }

        if ($type == 'order') {
            Order::where('order_id', $docnum)->update(['status' => 'Closed']);
        }

        return response()->json(['message' => 'Doc closed successfully!']);
    }

    public function cancelDoc(Request $request)
    {
        $data = $request->all();

        $doctype = $data['doctype'];
        $docnum = $data['docnum'];

        $closeDocResponse = $this->cancelICountDocument($docnum, $doctype, $data['reason']);

        if ($closeDocResponse['status'] != true) {
            return response()->json([
                'message' => $closeDocResponse['reason']
            ], 500);
        }

        if ($doctype == 'invoice' || $doctype == 'invrec') {

            //initiate refund 
            $invoice = Invoices::where('invoice_id', $docnum)->first();
            if ($invoice->txn_id != null && $invoice->callback != null) {
                $refundResponse = $this->refundByReferenceID($invoice->txn_id, $invoice->amount);

                if ($refundResponse && !$refundResponse['HasError']) {
                    Refunds::create([
                        'invoice_id' => $invoice->id,
                        'invoice_icount_id' => $invoice->invoice_id,
                        'refrence' => $refundResponse['ReferenceNumber'],
                        'message' => $refundResponse['ReturnMessage']
                    ]);
                }
            }

            $invoice->update(['invoice_icount_status' => 'Cancelled']);
        }

        if ($doctype == 'order') {
            $order = Order::where('order_id', $docnum)->first();
            $order->jobs()->update(['isOrdered' => 'c']);
            $order->update(['status' => 'Cancelled']);
        }

        return response()->json(['message' => 'Doc cancelled successfully!']);
    }

    public function commitInvoicePayment($client, $services, $token, $subtotal)
    {
        $address = $client->property_addresses()->first();

        $pay_items = [];

        foreach ($services as $k => $service) {
            $pay_items[] = [
                'ItemDescription' => $service['description'],
                'ItemQuantity'    => $service['quantity'],
                'ItemPrice'       => $service['unitprice'],
                'IsTaxFree'       => "false"
            ];
        }

        $transaction = Transaction::create([
            'client_id' => $client->id,
            'amount' => $subtotal,
            'currency' => config('services.app.currency'),
            'status' => TransactionStatusEnum::INITIATED,
            'type' => 'deposit',
            'description' => 'Pay for Invoice',
            'source' => 'credit-card',
            'destination' => 'merchant',
            'gateway' => 'zcredit'
        ]);

        $captureChargeResponse = $this->captureCardCharge([
            'card_number' => $token,
            'amount' => $subtotal,
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
            $transaction->update([
                'status' => TransactionStatusEnum::COMPLETED,
                'transaction_id' => $captureChargeResponse['ReferenceNumber'],
                'transaction_at' => now(),
                'metadata' => ['card_number' => $token],
            ]);

            return $captureChargeResponse;
        }

        throw new Exception("Error Processing Charge Request", 500);
    }

    public function manualInvoice($id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        $client = $order->client;
        if (!$client) {
            return response()->json([
                'message' => 'Client not found'
            ], 404);
        }

        $services = json_decode($order->items, true);

        $card = $this->getClientCard($client->id);

        $payment_method = $client->payment_method;

        $doctype = ($payment_method == 'cc') ? "invrec" : "invoice";

        if ($payment_method == 'cc' && !$card) {
            throw new Exception("Card not added for this client", 1);
        }

        $subtotal = 0;
        foreach ($services as $key => $service) {
            $subtotal += (int)$service['unitprice'];
        }
        $tax = (config('services.app.tax_percentage') / 100) * $subtotal;
        $total = $tax + $subtotal;

        $orderResponse = json_decode($order->response, true);

        $duedate = Carbon::today()->endOfMonth()->toDateString();

        $isPaymentProcessed = false;
        /* Auto payment */
        if ($payment_method == 'cc') {
            $paymentResponse = $this->commitInvoicePayment($client, $services, $card->card_token, $subtotal);

            if ($paymentResponse['HasError'] == true) {
                $doctype = 'invoice';
            } else {
                $isPaymentProcessed = true;
            }
        }

        $iCountClientID = $orderResponse['client_id'];
        $otherInvDocOptions = [
            'based_on' => [
                [
                    'docnum'  => $order->order_id,
                    'doctype' => 'order'
                ]
            ]
        ];

        if ($payment_method == 'cc') {
            $otherInvDocOptions['cc'] = [
                "sum" => $total,
                "card_type" => $card->card_type,
                "card_number" => $card->card_number,
                "exp_year" => explode('/', $card->valid)[0],
                "exp_month" => explode('/', $card->valid)[1],
                "holder_id" => $card->card_holder_id,
            ];
        }

        if ($doctype == 'invoice') {
            $json = $this->generateInvoiceDocument(
                $iCountClientID,
                $client,
                $services,
                $duedate,
                $otherInvDocOptions
            );
        } else {
            $json = $this->generateInvRecDocument(
                $iCountClientID,
                $client,
                $services,
                $duedate,
                $otherInvDocOptions
            );
        }

        $invoice = Invoices::create([
            'invoice_id' => $json['docnum'],
            'order_id'  => $order->id,
            'amount'     => $total,
            'paid_amount' => $total,
            'pay_method' => ($isPaymentProcessed) ? 'Credit Card' : 'NA',
            'client_id'   => $client->id,
            'doc_url'    => $json['doc_url'],
            'type'       => $doctype,
            'invoice_icount_status' => 'Open',
            'due_date'   => $duedate,
            'txn_id'     => ($isPaymentProcessed) ? $paymentResponse['ReferenceNumber'] : '',
            'callback'   => isset($paymentResponse) ? json_encode($paymentResponse, true) : '',
            'status'     => ($isPaymentProcessed) ? 'Paid' : (isset($paymentResponse) ? $paymentResponse['ReturnMessage'] : 'Unpaid'),
        ]);

        $order->jobs()->update([
            'invoice_id'            => $invoice->id,
            'is_invoice_generated'  => true,
            'invoice_no'            => $json["docnum"],
            'invoice_url'           => $json["doc_url"],
            'isOrdered'             => 2,
            'status'                => JobStatusEnum::COMPLETED
        ]);

        /*Close Order */
        $this->closeDoc($order->order_id, 'order');

        // if ($isPaymentProcessed && $doctype == 'invrec') {
        //     //close invoice
        //     $this->closeDoc($json['docnum'], 'invrec');
        //     $invoice->update(['invoice_icount_status' => 'Closed']);
        // }

        $order->update([
            'status' => 'Closed',
            'invoice_status' => 2
        ]);

        return response()->json([
            'message' => 'Invoice generated successfully'
        ]);
    }

    public function multipleInvoices(Request $request)
    {
        $orders = Order::query()
            ->whereIn('invoice_status', ['1', '0'])
            ->find($request->all());

        foreach ($orders as $order) {
            $this->manualInvoice($order->id);
        }
    }
    /*Orders Apis */

    public function getOrders(Request $request)
    {
        $orders = Order::with(['jobs', 'client']);

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

        $client = $job->client;
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

        if (empty($client->invoicename)) {
            return response()->json([
                'message' => "Client's invoice name is not set"
            ], 403);
        }

        $items = $request->services;
        $dueDate = Carbon::today()->endOfMonth()->toDateString();

        $this->generateOrderDocument($client, [$job->id], $items, $dueDate, $job->is_one_time_job);

        return response()->json([
            'message' => 'Order generated successfully'
        ]);
    }

    public function multipleOrders(Request $request)
    {
        $jobs = Job::query()
            ->with(['jobservice'])
            ->whereHas('jobservice')
            ->where('is_order_generated', false)
            ->find($request->all());

        if ($jobs->count() == 0) {
            return response()->json([
                'message' => "No outstanding job remain to create orders"
            ], 404);
        }

        if ($jobs->pluck('client_id')->unique()->count() > 1) {
            return response()->json([
                'message' => "Jobs belong to more than one client"
            ], 422);
        }

        $clientID = $jobs->pluck('client_id')->unique()->first();

        $client = Client::find($clientID);
        if (!$client) {
            return response()->json([
                'message' => "Client not found"
            ], 404);
        }

        if (empty($client->invoicename)) {
            return response()->json([
                'message' => "Client's invoice name is not set"
            ], 403);
        }

        $oneTimeJobs = $jobs->where('is_one_time_job', true)->values();
        $notOneTimeJobs = $jobs->where('is_one_time_job', false)->values();

        $not_one_time_job_ids = [];
        $items = [];
        $lang = $client->lng;
        foreach ($notOneTimeJobs as $job) {
            $service = $job->jobservice;

            $not_one_time_job_ids[] = $job->id;
            $items[] = [
                "description" => ($lang == 'en') ?  $service->name : $service->heb_name . " - " . Carbon::today()->format('d, M Y'),
                "unitprice"   => $service->total,
                "quantity"    => 1,
            ];
        }

        $dueDate = Carbon::today()->endOfMonth()->toDateString();
        if (count($not_one_time_job_ids) > 0) {
            $this->generateOrderDocument($client, $not_one_time_job_ids, $items, $dueDate, false);
        }

        foreach ($oneTimeJobs as $job) {
            $service = $job->jobservice;

            $item = [
                "description" => ($lang == 'en') ?  $service->name : $service->heb_name . " - " . Carbon::today()->format('d, M Y'),
                "unitprice"   => $service->total,
                "quantity"    => 1,
            ];

            $this->generateOrderDocument($client, [$job->id], [$item], $dueDate, $job->is_one_time_job);
        }

        return response()->json([
            'message' => "Orders have been created successfully"
        ]);
    }

    public function getClientOrders(Request $request, $id)
    {
        if ($request->f == 'all') {
            $orders = Order::query()
                ->with(['jobs', 'client'])
                ->where('client_id', $id)
                ->orderBy('id', 'desc')
                ->paginate(20);
        }

        if (isset($request->status)) {
            $orders = Order::query()
                ->with(['jobs', 'client'])
                ->where('status', $request->status)
                ->where('client_id', $id)
                ->orderBy('id', 'desc')
                ->paginate(20);
        }

        if (isset($request->invoice_status) && $request->invoice_status != 'm') {
            $orders = Order::query()
                ->with(['jobs', 'client'])
                ->where('invoice_status', $request->invoice_status)
                ->where('client_id', $id)
                ->orderBy('id', 'desc')
                ->paginate(20);
        }

        if (isset($request->invoice_status) && $request->invoice_status == 'm') {
            $orders = Order::query()
                ->with(['jobs', 'client'])
                ->where('client_id', $id)
                ->where(function ($q) {
                    $q->where('invoice_status', '1')
                        ->orWhere('invoice_status', '2');
                })
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

    public function payments(Request $request)
    {
        $payments = Invoices::with(['jobs', 'client']);

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
                    if (isset($ex[1])) {
                        $qr->where('lastname', 'like', '%' . $ex[1] . '%');
                    }
                });
            });
        }

        $payments = $payments
            ->where(function ($q) {
                $q->where('status', 'Paid')
                    ->orWhere('status', 'Partial Paid');
            })
            ->orderBy('id', 'desc')
            ->paginate(20);

        return response()->json([
            'pay' => $payments
        ]);
    }

    public function clientPayments($id)
    {
        $payments = Invoices::query()
            ->with('jobs', 'client')
            ->where('client_id', $id)
            ->where(function ($q) {
                $q->where('status', 'Paid')
                    ->orWhere('status', 'Partial Paid');
            })
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

    public function clientInvoiceOrders(Request $request, $id)
    {
        $orders = Order::query()
            ->where('client_id', $id)
            ->where(function ($q) {
                $q->where('invoice_status', '0')
                    ->orWhere('invoice_status', '1');
            })
            ->get();

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
                $jservices = array_merge($jservices, $service);
            }

            return response()->json([
                'services' => $jservices
            ]);
        }
    }
}
