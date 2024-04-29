<?php

namespace App\Traits;

use App\Enums\OrderPaidStatusEnum;
use App\Models\Invoices;
use App\Models\Job;
use App\Models\JobCancellationFee;
use App\Models\Order;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

trait ICountDocument
{
    private function closeDoc($docnum, $type)
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

    private function generateOrderInvoice($client, $order, $card)
    {
        $payment_method = $client->payment_method;

        $services = json_decode($order->items, true);
        $doctype = ($payment_method == 'cc') ? "invrec" : "invoice";

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
                $order->update([
                    'paid_status' => OrderPaidStatusEnum::PROBLEM
                ]);

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

        if ($isPaymentProcessed) {
            $orderUpdateData['paid_status'] = OrderPaidStatusEnum::PAID;
        } elseif ($payment_method != 'cc') {
            $orderUpdateData['paid_status'] = OrderPaidStatusEnum::UNPAID;
        } else {
            $orderUpdateData['paid_status'] = OrderPaidStatusEnum::PROBLEM;
        }

        $order->update($orderUpdateData);

        if ($doctype == 'invrec') {
            Job::where('order_id', $order->id)
                ->update([
                    'is_paid' => true
                ]);

            JobCancellationFee::where('order_id', $order->id)
                ->update([
                    'is_paid' => true
                ]);
        }
    }
}
