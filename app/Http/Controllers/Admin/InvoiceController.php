<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatusEnum;
use App\Enums\JobStatusEnum;
use App\Enums\OrderPaidStatusEnum;
use App\Enums\SettingKeyEnum;
use App\Enums\TransactionStatusEnum;
use App\Events\ClientPaymentFailed;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoices;
use App\Models\Job;
use App\Models\JobCancellationFee;
use App\Models\Order;
use App\Models\Receipts;
use App\Models\Refunds;
use App\Models\Services;
use App\Models\Transaction;
use App\Traits\ClientCardTrait;
use App\Traits\ICountDocument;
use App\Traits\PaymentAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    use PaymentAPI, ClientCardTrait, ICountDocument;

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
        $get_pa  = Invoices::where('status', InvoiceStatusEnum::PAID)->get();
        $get_ua  = Invoices::where('status', InvoiceStatusEnum::UNPAID)->get();
        $get_ppa = Invoices::where('status', InvoiceStatusEnum::PARTIALLY_PAID)->get();

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
        $status = $request->get('status');
        $icountStatus = $request->get('icount_status');
        $type = $request->get('type');

        $invoices = Invoices::query()
            ->with('client')
            ->where('client_id', $id)
            ->when($status, function ($q) use ($status) {
                return $q->where('status', $status);
            })
            ->when($icountStatus, function ($q) use ($icountStatus) {
                return $q->where('invoice_icount_status', $icountStatus);
            })
            ->when($type == 'invoice', function ($q) use ($type) {
                return $q
                    ->where('type', $type)
                    ->whereDoesntHave('receipt');
            })
            ->when($type == 'invrec', function ($q) use ($type) {
                return $q
                    ->where('type', $type)
                    ->orWhereHas('receipt');
            })
            ->latest()
            ->paginate(20);

        $open       = Invoices::where('client_id', $id)->where('invoice_icount_status', 'Open')->count();
        $closed     = Invoices::where('client_id', $id)->where('invoice_icount_status', 'Closed')->count();
        $paid       = Invoices::where('client_id', $id)->where('status', InvoiceStatusEnum::PAID)->count();
        $unpaid     = Invoices::where('client_id', $id)->where('status', InvoiceStatusEnum::UNPAID)->count();
        $partial    = Invoices::where('client_id', $id)->where('status', InvoiceStatusEnum::PARTIALLY_PAID)->count();
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

        $get_pa  = Invoices::where('client_id', $id)->where('status', InvoiceStatusEnum::PAID)->get();
        $get_ua  = Invoices::where('client_id', $id)->where('status', InvoiceStatusEnum::UNPAID)->get();
        $get_ppa = Invoices::where('client_id', $id)->where('status', InvoiceStatusEnum::PARTIALLY_PAID)->get();

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

    // public function AddInvoice(Request $request)
    // {
    //     $data = $request->all();

    //     $client = Client::find($data['client_id']);

    //     if (empty($client->invoicename)) {
    //         return response()->json([
    //             'message' => "Client's invoice name is not set"
    //         ], 403);
    //     }

    //     $services = json_decode($data['services'], true);
    //     $total = 0;

    //     $card = $this->getClientCard($client->id);

    //     $payment_method = $client->payment_method;

    //     $doctype = $data['doctype'];

    //     $subtotal = $data['amount'];
    //     $tax = (config('services.app.tax_percentage') / 100) * $subtotal;
    //     $total = $tax + $subtotal;

    //     $isPaymentProcessed = false;
    //     /* Auto payment */
    //     if ($payment_method == 'cc') {
    //         if (!$card) {
    //             return response()->json([
    //                 'message' => 'Card not added for this client'
    //             ], 404);
    //         }

    //         $paymentResponse = $this->commitInvoicePayment($client, $services, $card->card_token, $total);

    //         if ($paymentResponse['HasError'] == true) {
    //             $doctype = 'invoice';
    //         } else {
    //             $isPaymentProcessed = true;
    //         }
    //     }

    //     $duedate = ($data['due_date'] == null) ? Carbon::now()->endOfMonth()->toDateString() : $data['due_date'];

    //     $url = "https://api.icount.co.il/api/v3.php/doc/create";

    //     $params = array(
    //         "cid"            => get_setting(SettingKeyEnum::ICOUNT_COMPANY_ID),
    //         "user"           => get_setting(SettingKeyEnum::ICOUNT_USERNAME),
    //         "pass"           => get_setting(SettingKeyEnum::ICOUNT_PASSWORD),

    //         "doctype"        => $doctype,
    //         "client_id"      => $client->id,
    //         "client_name"    => $client->invoicename,
    //         "client_address" => $client->geo_address,
    //         "email"          => $client->email,
    //         "lang"           => ($client->lng == 'heb') ? 'he' : 'en',
    //         "currency_code"  => "ILS",
    //         "doc_lang"       => ($client->lng == 'heb') ? 'he' : 'en',
    //         "items"          => $services,
    //         "duedate"        => $duedate,

    //         "send_email"      => 1,
    //         "email_to_client" => 1,
    //         "email_to"        => $client->email,
    //     );

    //     if ($payment_method == 'cc') {
    //         $ex = explode('-', $card->valid);
    //         $cc = ['cc' => [
    //             "sum" => $total,
    //             "card_type" => $card->card_type,
    //             "card_number" => $card->card_number,
    //             "exp_year" => $ex[0],
    //             "exp_month" => $ex[1],
    //             "holder_id" => "",
    //             "confirmation_code" => ""
    //         ]];

    //         $_params = array_merge($params, $cc);
    //     } else {
    //         $_params = $params;
    //     }

    //     $ch = curl_init($url);
    //     curl_setopt($ch, CURLOPT_POST, 1);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_params, null, '&'));
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //     $response = curl_exec($ch);
    //     $info = curl_getinfo($ch);

    //     //if(!$info["http_code"] || $info["http_code"]!=200) die("HTTP Error");
    //     $json = json_decode($response, true);

    //     //if(!$json["status"]) die($json["reason"]);

    //     Job::where('id', $data['job'])->update([
    //         'invoice_no'    => $json["docnum"],
    //         'invoice_url'   => $json["doc_url"],
    //         'isOrdered'     => 2,
    //         'status'        => JobStatusEnum::COMPLETED
    //     ]);

    //     $invoice = Invoices::create([
    //         'invoice_id' => $json['docnum'],
    //         'amount'     => $total,
    //         'paid_amount' => $total,
    //         'pay_method' => ($isPaymentProcessed) ? 'Credit Card' : 'NA',
    //         'client_id'   => $client->id,
    //         'doc_url'    => $json['doc_url'],
    //         'type'       => $doctype,
    //         'invoice_icount_status' => 'Open',
    //         'due_date'   => $duedate,
    //         'txn_id'     => ($isPaymentProcessed) ? $paymentResponse['ReferenceNumber'] : '',
    //         'callback'   => isset($paymentResponse) ? json_encode($paymentResponse, true) : '',
    //         'status'     => ($isPaymentProcessed) ? 'Paid' : (isset($paymentResponse) ? $paymentResponse['ReturnMessage'] : 'Unpaid'),
    //     ]);

    //     // if ($isPaymentProcessed && $doctype == 'invrec') {
    //     //     //close invoice
    //     //     $this->closeDoc($json['docnum'], 'invrec');
    //     //     $invoice->update(['invoice_icount_status' => 'Closed']);
    //     // }
    //     /*Close Order */
    //     if (!empty($data['codes'])) {
    //         $codes = $data['codes'];
    //         foreach ($codes as $code) {
    //             $this->closeDoc($code, 'order');
    //             Order::where('id', $code)->update(['status' => 'Closed', 'invoice_status' => 2]);
    //         }
    //     }

    //     return response()->json([
    //         'message' => 'Invoice created successfully'
    //     ]);
    // }

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

        $iCountDocument = $this->getICountDocument($invoice->invoice_id, 'invoice');

        if (!$iCountDocument["status"]) {
            throw new Exception($iCountDocument["reason"], 500);
        }

        $subtotal = 0;
        $services = [];
        $items = $iCountDocument['doc_info']['items'];
        foreach ($items as $item) {
            $subtotal += (float)$item['unitprice'] * (int)$item['quantity'];

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
                $order->update([
                    'paid_status' => OrderPaidStatusEnum::PROBLEM
                ]);

                event(new ClientPaymentFailed($client, $card));

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
            $client,
            $services,
            $duedate,
            $otherInvDocOptions,
            $subtotal,
            0,
        );

        $rcp = Receipts::create([
            'invoice_id' => $invoice->id,
            'invoice_icount_id' => $invoice->invoice_id,
            'receipt_id' => $json['docnum'],
            'docurl' => $json['doc_info']['doc_url'],
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

        Job::where('order_id', $order->id)
            ->update([
                'is_paid' => true
            ]);

        JobCancellationFee::where('order_id', $order->id)
            ->update([
                'is_paid' => true
            ]);

        return response()->json([
            'message' => 'Invoice updated successfully'
        ]);
    }

    public function invoiceJobs(Request $request)
    {
        $jobs = Job::where('client_id', $request->cid)
            ->where('is_order_generated', false)
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
                $refundResponse = $this->refundByReferenceID($invoice->txn_id, $invoice->amount_with_tax);

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
            if ($order->status == 'Closed') {
                return response()->json([
                    'message' => 'Order is already closed',
                ], 403);
            }

            $order->jobs()->update([
                'isOrdered' => 'c',
                'order_id' => NULL,
                'is_order_generated' => false
            ]);
            $order->update(['status' => 'Cancelled']);
        }

        return response()->json(['message' => 'Doc cancelled successfully!']);
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

        $card = $this->getClientCard($client->id);

        $payment_method = $client->payment_method;

        if ($payment_method == 'cc' && !$card) {
            throw new Exception("Card not added for this client", 1);
        }

        $this->generateOrderInvoice($client, $order, $card);

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
        $job = Job::query()
            ->with(['jobservice', 'client'])
            ->find($id);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found'
            ], 404);
        }

        if (
            $job->status != JobStatusEnum::COMPLETED ||
            !$job->is_job_done
        ) {
            return response()->json([
                'message' => 'Job not completed'
            ], 403);
        }

        $client = $job->client;
        if (!$client) {
            return response()->json([
                'message' => 'Client not found'
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

        $this->generateOrderDocument(
            $client,
            $items,
            $dueDate,
            [
                'job_ids' => [$job->id],
                'is_one_time_in_month' => $job->is_one_time_in_month_job,
                'discount_amount' => $job->discount_amount
            ]
        );

        return response()->json([
            'message' => 'Order generated successfully'
        ]);
    }

    public function multipleOrders(Request $request)
    {
        $jobs = Job::query()
            ->with(['jobservice'])
            ->whereHas('jobservice')
            ->where('status', JobStatusEnum::COMPLETED)
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

        $oneTimeJobs = $jobs->where('is_one_time_in_month_job', true)->values();
        $notOneTimeJobs = $jobs->where('is_one_time_in_month_job', false)->values();

        $not_one_time_job_ids = [];
        $items = [];
        $lang = $client->lng;
        $discount_amount = 0;
        foreach ($notOneTimeJobs as $job) {
            $service = $job->jobservice;

            $not_one_time_job_ids[] = $job->id;
            $items[] = [
                "description" => ($lang == 'en') ?  $service->name : $service->heb_name . " - " . Carbon::today()->format('d, M Y'),
                "unitprice"   => $job->subtotal_amount,
                "quantity"    => 1,
            ];
            $discount_amount = $discount_amount + $job->discount_amount;
        }

        $dueDate = Carbon::today()->endOfMonth()->toDateString();
        if (count($not_one_time_job_ids) > 0) {
            $this->generateOrderDocument(
                $client,
                $items,
                $dueDate,
                [
                    'job_ids' => [$not_one_time_job_ids],
                    'is_one_time_in_month' => false,
                    'discount_amount' => $discount_amount
                ]
            );
        }

        foreach ($oneTimeJobs as $job) {
            $service = $job->jobservice;

            $item = [
                "description" => ($lang == 'en') ?  $service->name : $service->heb_name . " - " . Carbon::today()->format('d, M Y'),
                "unitprice"   => $job->subtotal_amount,
                "quantity"    => 1,
            ];

            $this->generateOrderDocument(
                $client,
                [$item],
                $dueDate,
                [
                    'job_ids' => [$job->id],
                    'is_one_time_in_month' => $job->is_one_time_in_month_job,
                    'discount_amount' => $job->discount_amount
                ]
            );
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
                $q->where('status', InvoiceStatusEnum::PAID)
                    ->orWhere('status', InvoiceStatusEnum::PARTIALLY_PAID);
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
                $q->where('status', InvoiceStatusEnum::PAID)
                    ->orWhere('status', InvoiceStatusEnum::PARTIALLY_PAID);
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

    public function paymentClientWise(Request $request)
    {
        $keyword = $request->get('keyword');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $priority_paid_status = $request->get('priority_paid_status');

        $priority_statuses = ['', '', 'paid', 'undone', 'unpaid', 'problem'];
        $priority_paid_status = array_search($priority_paid_status, $priority_statuses);

        $jobVisits = Job::query()
            ->when($start_date, function ($q) use ($start_date) {
                return $q->whereDate('start_date', '>=', $start_date);
            })
            ->when($end_date, function ($q) use ($end_date) {
                return $q->whereDate('start_date', '<=', $end_date);
            })
            ->select('jobs.client_id')
            ->selectRaw('COUNT(jobs.id) AS visits')
            ->selectRaw('MAX(start_date) AS last_activity_date')
            ->groupBy('jobs.client_id');

        $orderPaidStatus = Order::query()
            ->when($start_date, function ($q) use ($start_date) {
                return $q->whereDate('created_at', '>=', $start_date);
            })
            ->when($end_date, function ($q) use ($end_date) {
                return $q->whereDate('created_at', '<=', $end_date);
            })
            ->select('order.client_id')
            ->selectRaw("MAX(CASE WHEN order.paid_status = 'problem' THEN 5 WHEN order.paid_status = 'unpaid' THEN 4 WHEN order.paid_status = 'undone' THEN 3 WHEN order.paid_status = 'paid' THEN 2 ELSE 1 END) AS priority")
            ->groupBy('order.client_id');

        $data = Client::query()
            ->leftJoinSub($jobVisits, 'job_visits', function ($join) {
                $join->on('clients.id', '=', 'job_visits.client_id');
            })
            ->leftJoinSub($orderPaidStatus, 'order_paid_status', function ($join) {
                $join->on('clients.id', '=', 'order_paid_status.client_id');
            })
            ->leftJoin('jobs as completed_jobs', function ($join) {
                $join->on('completed_jobs.client_id', '=', 'clients.id')
                    ->where(function ($q) {
                        $q
                            ->where('completed_jobs.status', '=', JobStatusEnum::COMPLETED)
                            ->orWhere('completed_jobs.is_job_done', true);
                    });
            })
            ->when($priority_paid_status, function ($q) use ($priority_paid_status) {
                return $q->where('order_paid_status.priority', $priority_paid_status);
            })
            ->when($keyword, function ($q) use ($keyword) {
                return $q->whereRaw('CONCAT(clients.firstname, " ", COALESCE(clients.lastname, "")) like "%' . $keyword . '%"');
            })
            ->where('job_visits.visits', '>', 0)
            ->select('clients.id AS client_id', 'job_visits.last_activity_date', 'clients.payment_method')
            ->selectRaw('IFNULL(job_visits.visits, 0) AS visits')
            ->selectRaw('CONCAT(clients.firstname, " ", COALESCE(clients.lastname, "")) AS client_name')
            ->selectRaw('order_paid_status.priority AS priority_paid_status')
            ->selectRaw('COUNT(completed_jobs.id) as completed_jobs')
            ->groupBy('clients.id')
            // ->orderBy('clients.id', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $data
        ]);
    }

    public function clientUnpaidInvoice(Request $request, $id)
    {
        $client = Client::find($id);

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

        $total_unpaid = Invoices::query()
            ->where('client_id', $client->id)
            ->where('type', 'invoice')
            ->where(function ($q) {
                $q->where('status', InvoiceStatusEnum::UNPAID)
                    ->orWhere('status', InvoiceStatusEnum::PARTIALLY_PAID);
            })
            ->selectRaw('SUM(total_amount - paid_amount) as amount')
            ->first();

        return response()->json([
            'total_unpaid_amount' => number_format((float)$total_unpaid->amount, 2, '.', '')
        ]);
    }

    public function closeClientInvoicesWithReceipt(Request $request, $id)
    {
        $client = Client::find($id);

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

        $data = $request->all();

        $invoices = Invoices::query()
            ->with(['client', 'order'])
            ->where('client_id', $client->id)
            ->where('type', 'invoice')
            ->where(function ($q) {
                $q->where('status', InvoiceStatusEnum::UNPAID)
                    ->orWhere('status', InvoiceStatusEnum::PARTIALLY_PAID);
            })
            ->get();

        if ($invoices->count() == 0) {
            return response()->json([
                'message' => "Invoice not found"
            ], 404);
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

        $order = $invoices[0]->order;
        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        $subtotal = 0;
        $basedOns = [];
        $pendingAmount = $amountToPay = (float)$data['paid_amount'];
        $payForInvoices = [];
        $services = [];
        foreach ($invoices as $key => $invoice) {
            $items = [];
            $isPartialPayment = false;
            $order = $invoice->order;
            $iCountDocument = $this->getICountDocument($invoice->invoice_id, 'invoice');

            if (!$iCountDocument["status"]) {
                throw new Exception($iCountDocument["reason"], 500);
            }

            $unpaidAmount = $invoice->total_amount - $invoice->paid_amount;

            if ($unpaidAmount <= $pendingAmount) {
                $invoiceItems = $iCountDocument['doc_info']['items'];
                foreach ($invoiceItems as $item) {
                    $subtotal += (float)$item['unitprice'] * (int)$item['quantity'];

                    $items[] = [
                        'description' => $item['description'],
                        'unitprice' => $item['unitprice'],
                        'quantity' => $item['quantity'],
                    ];
                }

                $basedOns[] = [
                    'docnum'  => $order->order_id,
                    'doctype' => 'order'
                ];

                $basedOns[] = [
                    'docnum'  => $invoice->invoice_id,
                    'doctype' => 'invoice'
                ];

                $pendingAmount = $pendingAmount - $unpaidAmount;
            } else {
                $isPartialPayment = true;
                $unpaidAmount = $pendingAmount - $invoice->paid_amount;
                $invoiceItems = $iCountDocument['doc_info']['items'];

                foreach ($invoiceItems as $item) {
                    $subtotal += $unpaidAmount;

                    $items[] = [
                        'description' => $item['description'],
                        'unitprice' => $unpaidAmount,
                        'quantity' => $item['quantity'],
                    ];
                }

                $basedOns[] = [
                    'docnum'  => $order->order_id,
                    'doctype' => 'order'
                ];

                $basedOns[] = [
                    'docnum'  => $invoice->invoice_id,
                    'doctype' => 'invoice'
                ];

                $pendingAmount = $pendingAmount - $unpaidAmount;
            }

            $payForInvoices[] = [
                'invoice_id' => $invoice->id,
                'items' => $items,
                'paying_amount' => $unpaidAmount,
                'is_full_payment' => !$isPartialPayment
            ];

            $services = array_merge($services, $items);

            if ($pendingAmount == 0) {
                break;
            }
        }

        if ($pendingAmount > 0) {
            return response()->json([
                'message' => 'Entered amount is more than invoice amount'
            ], 403);
        }

        $otherInvDocOptions = [
            'based_on' => $basedOns,
        ];

        $tax = (config('services.app.tax_percentage') / 100) * $subtotal;
        $total = $tax + $subtotal;

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

            $paymentResponse = $this->commitInvoicePayment($client, $services, $card->card_token, $subtotal);

            if ($paymentResponse['HasError'] == true) {
                foreach ($payForInvoices as $key => $_pinvoice) {
                    $invoice = $invoices->where('id', $_pinvoice['invoice_id'])->first();
                    $order = $invoice->order;

                    $order->update([
                        'paid_status' => OrderPaidStatusEnum::PROBLEM
                    ]);
                }

                event(new ClientPaymentFailed($client, $card));

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
            $client,
            $services,
            $duedate,
            $otherInvDocOptions,
            $subtotal,
            0,
        );

        foreach ($payForInvoices as $key => $_pinvoice) {
            $invoice = $invoices->where('id', $_pinvoice['invoice_id'])->first();

            $rcp = Receipts::create([
                'invoice_id' => $invoice->id,
                'invoice_icount_id' => $invoice->invoice_id,
                'receipt_id' => $json['docnum'],
                'docurl' => $json['doc_info']['doc_url'],
            ]);

            // $this->closeDoc($invoice->invoice_id, 'invoice');
            $paidAmount = (float)$invoice->paid_amount + (float)$_pinvoice['paying_amount'];

            $invoice->update([
                'invoice_icount_status' => 'Closed',
                'receipt_id' => $rcp->id,
                'pay_method'  => $data['pay_method'],
                'txn_id'      => $txnID,
                'callback'    => isset($paymentResponse) ? json_encode($paymentResponse, true) : '',
                'paid_amount' => $paidAmount,
                'status'      => $_pinvoice['is_full_payment'] ?
                    InvoiceStatusEnum::PAID : InvoiceStatusEnum::PARTIALLY_PAID
            ]);

            $order = $invoice->order;
            $order->update([
                'paid_status' => $_pinvoice['is_full_payment'] ?
                    OrderPaidStatusEnum::PAID : OrderPaidStatusEnum::UNPAID
            ]);

            Job::where('order_id', $order->id)
                ->update([
                    'is_paid' => true
                ]);

            JobCancellationFee::where('order_id', $order->id)
                ->update([
                    'is_paid' => true
                ]);
        }

        return response()->json([
            'message' => 'Invoice updated successfully'
        ]);
    }

    public function closeClientForPayment(Request $request, $id)
    {
        $client = Client::find($id);

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

        if ($client->payment_method != 'cc') {
            return response()->json([
                'message' => "Payment is not set to credit card"
            ], 403);
        }

        $card = $this->getClientCard($client->id);
        if (!$card) {
            return response()->json([
                'message' => "Client's credit card not found"
            ], 404);
        }

        $orders = Order::query()
            ->where('client_id', $client->id)
            ->where('status', 'Open')
            ->get();

        if ($orders->count() == 0) {
            return response()->json([
                'message' => "Invoice not found"
            ], 404);
        }

        $subtotal = 0;
        $basedOns = [];
        foreach ($orders as $key => $order) {
            $services = json_decode($order->items, true);

            foreach ($services as $key => $service) {
                $subtotal += (float)$service['unitprice'] * (int)$service['quantity'];
            }

            $basedOns[] = [
                'docnum'  => $order->order_id,
                'doctype' => 'order'
            ];
        }

        $tax = (config('services.app.tax_percentage') / 100) * $subtotal;
        $total = $tax + $subtotal;

        $otherInvDocOptions = [
            'based_on' => $basedOns
        ];

        $otherInvDocOptions['cc'] = [
            "sum" => $total,
            "card_type" => $card->card_type,
            "card_number" => $card->card_number,
            "exp_year" => explode('/', $card->valid)[0],
            "exp_month" => explode('/', $card->valid)[1],
            "holder_id" => $card->card_holder_id,
        ];

        $paymentResponse = $this->commitInvoicePayment($client, $services, $card->card_token, $subtotal);

        if ($paymentResponse['HasError'] == true) {
            foreach ($orders as $key => $order) {
                $order->update([
                    'paid_status' => OrderPaidStatusEnum::PROBLEM
                ]);
            }

            event(new ClientPaymentFailed($client, $card));

            return response()->json([
                'message' => 'Unable to pay from credit card'
            ], 500);
        }

        $duedate = Carbon::today()->endOfMonth()->toDateString();

        $json = $this->generateInvRecDocument(
            $client,
            $services,
            $duedate,
            $otherInvDocOptions,
            $order->amount,
            $order->discount_amount,
        );

        $discount = isset($json['doc_info']['discount']) ? $json['doc_info']['discount'] : NULL;
        $totalAmount = isset($json['doc_info']['afterdiscount']) ? $json['doc_info']['afterdiscount'] : NULL;

        $invoice = Invoices::create([
            'invoice_id' => $json['docnum'],
            'order_id'  => $order->id,
            'amount'            => $json['doc_info']['totalsum'],
            'paid_amount'       => $json['doc_info']['totalsum'],
            'discount_amount'   => $discount,
            'total_amount'      => $totalAmount,
            'amount_with_tax'   => $json['doc_info']['totalwithvat'],
            'pay_method' => 'Credit Card',
            'client_id'  => $client->id,
            'doc_url'    => $json['doc_info']['doc_url'],
            'type'       => 'invrec',
            'invoice_icount_status' => 'Open',
            'due_date'   => $duedate,
            'txn_id'     => $paymentResponse['ReferenceNumber'],
            'callback'   => isset($paymentResponse) ? json_encode($paymentResponse, true) : '',
            'status'     => InvoiceStatusEnum::PAID,
        ]);

        foreach ($orders as $order) {
            $order->jobs()->update([
                'invoice_id'            => $invoice->id,
                'is_invoice_generated'  => true,
                'invoice_no'            => $json["docnum"],
                'invoice_url'           => $json['doc_info']['doc_url'],
                'isOrdered'             => 2,
            ]);

            $order->jobCancellationFees()->update([
                'invoice_id'            => $invoice->id,
                'is_invoice_generated'  => true,
            ]);

            /*Close Order */
            $this->closeDoc($order->order_id, 'order');

            // if ($isPaymentProcessed && $doctype == 'invrec') {
            //     //close invoice
            //     $this->closeDoc($json['docnum'], 'invrec');
            //     $invoice->update(['invoice_icount_status' => 'Closed']);
            // }

            $orderUpdateData = [
                'status' => 'Closed',
                'invoice_status' => 2
            ];

            $orderUpdateData['paid_status'] = OrderPaidStatusEnum::PAID;
            $orderUpdateData['paid_amount'] = $order->total_amount;
            $orderUpdateData['unpaid_amount'] = 0;

            $order->update($orderUpdateData);

            Job::where('order_id', $order->id)
                ->update([
                    'is_paid' => true
                ]);

            JobCancellationFee::where('order_id', $order->id)
                ->update([
                    'is_paid' => true
                ]);
        }

        return response()->json([
            'message' => 'Invoice updated successfully'
        ]);
    }

    public function closeClientWithoutPayment(Request $request, $id)
    {
        $client = Client::find($id);

        if (!$client) {
            return response()->json([
                'message' => "Client not found"
            ], 404);
        }

        $orders = Order::query()
            ->where('client_id', $client->id)
            ->where('status', 'Open')
            ->get();

        if ($orders->count() == 0) {
            return response()->json([
                'message' => "Open order not found"
            ], 404);
        }

        foreach ($orders as $order) {
            /*Close Order */
            $this->closeDoc($order->order_id, 'order');

            $order->update([
                'status' => 'Closed',
                'invoice_status' => 2,
                'paid_status' => OrderPaidStatusEnum::PAID,
                'is_force_closed' => true,
                'force_closed_at' => now()->toDateTimeString()
            ]);

            Job::where('order_id', $order->id)
                ->update([
                    'is_paid' => true
                ]);
        }

        return response()->json([
            'message' => 'Order closed successfully'
        ]);
    }
}
