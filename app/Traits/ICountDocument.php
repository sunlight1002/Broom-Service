<?php



namespace App\Traits;

use Illuminate\Support\Facades\Http;

use App\Enums\InvoiceStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\OrderPaidStatusEnum;
use App\Events\ClientInvoiceCreated;
use App\Events\ClientInvRecCreated;
use App\Events\ClientPaymentFailed;
use App\Events\ClientPaymentPaid;
use App\Models\Invoices;
use App\Models\Job;
use App\Models\JobCancellationFee;
use App\Models\User;
use App\Models\Setting;
use App\Enums\SettingKeyEnum;
use App\Models\Notification;
use App\Models\Order;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

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
        \Log::info([$card]);

        $services = json_decode($order->items, true);
        $doctype = ($payment_method == 'cc') ? "invrec" : "invoice";

        $subtotal = 0;
        foreach ($services as $key => $service) {
            $subtotal += (float)$service['unitprice'] * (int)$service['quantity'];
        }
        $tax = (config('services.app.tax_percentage') / 100) * $subtotal;
        $total = $tax + $subtotal;

        $duedate = Carbon::today()->endOfMonth()->toDateString();

        $isPaymentProcessed = false;
        /* Auto payment */
        if ($payment_method == 'cc') {

            \Log::info('Payment IcountDoc commitInvoicePayment Initiate');

            $paymentResponse = $this->commitInvoicePayment($client, $services, $card->card_token, $subtotal);

            \Log::info('Payment IcountDoc commitInvoicePayment Finish');

            if ($paymentResponse['HasError'] == true) {

                \Log::info('Payment IcountDoc commitInvoicePayment Error');

                $order->update([
                    'paid_status' => OrderPaidStatusEnum::PROBLEM
                ]);

                event(new ClientPaymentFailed($client, $card));

                $doctype = 'invoice';
            } else {
                $isPaymentProcessed = true;
            }
        }

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

            \Log::info('Payment IcountDoc generateInvoiceDocument doctype : invoice');

            $json = $this->generateInvoiceDocument(
                $client,
                $order,
                $duedate,
                $otherInvDocOptions
            );
        } else {

            \Log::info('Payment IcountDoc generateInvRecDocument');

            $json = $this->generateInvRecDocument(
                $client,
                $services,
                $duedate,
                $otherInvDocOptions,
                $order->amount,
                $order->discount_amount,
            );
        }
        \Log::info('Payment IcountDoc generateDocument Finish');

        $discount = isset($json['doc_info']['discount']) ? $json['doc_info']['discount'] : NULL;
        $totalAmount = isset($json['doc_info']['afterdiscount']) ? $json['doc_info']['afterdiscount'] : NULL;

        \Log::info('Payment IcountDoc Invoice create');

        $invoice = Invoices::create([
            'invoice_id'        => $json['docnum'],
            'order_id'          => $order->id,
            'amount'            => $json['doc_info']['totalsum'],
            'paid_amount'       => $isPaymentProcessed ? $json['doc_info']['totalsum'] : 0,
            'discount_amount'   => $discount,
            'total_amount'      => $totalAmount,
            'amount_with_tax'   => $json['doc_info']['totalwithvat'],
            'pay_method'        => ($isPaymentProcessed) ? 'Credit Card' : 'NA',
            'client_id'         => $client->id,
            'doc_url'    => $json['doc_info']['doc_url'],
            'type'       => $doctype,
            'invoice_icount_status' => 'Open',
            'due_date'   => $duedate,
            'txn_id'     => ($isPaymentProcessed) ? $paymentResponse['ReferenceNumber'] : '',
            'callback'   => isset($paymentResponse) ? json_encode($paymentResponse, true) : '',
            'status'     => ($isPaymentProcessed) ? InvoiceStatusEnum::PAID : (isset($paymentResponse) ? $paymentResponse['ReturnMessage'] : InvoiceStatusEnum::UNPAID),
        ]);

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

        \Log::info('Payment IcountDoc close order');

        /*Close Order */
        $this->closeDoc($order->order_id, 'order');

        // if ($isPaymentProcessed && $doctype == 'invrec') {
        //     //close invoice
        //     $this->closeDoc($json['docnum'], 'invrec');
        //     $invoice->update(['invoice_icount_status' => 'Closed']);
        // }

        $orderUpdateData = [
            'status' => 'Closed',
            'invoice_status' => 2,
        ];

        if ($isPaymentProcessed) {
            $orderUpdateData['paid_status'] = OrderPaidStatusEnum::PAID;
            $orderUpdateData['paid_amount'] = $subtotal;
            $orderUpdateData['unpaid_amount'] = 0;
        } elseif ($payment_method != 'cc') {
            $orderUpdateData['paid_status'] = OrderPaidStatusEnum::UNPAID;
            $orderUpdateData['paid_amount'] = 0;
            $orderUpdateData['unpaid_amount'] = $subtotal;
        } else {
            $orderUpdateData['paid_status'] = OrderPaidStatusEnum::PROBLEM;
            $orderUpdateData['paid_amount'] = 0;
            $orderUpdateData['unpaid_amount'] = $subtotal;
        }

        $order->update($orderUpdateData);

        \Log::info('Payment IcountDoc update Job and JobCancellationFee for paid status');

        if ($doctype == 'invrec') {
            Job::where('order_id', $order->id)
                ->update([
                    'is_paid' => true
                ]);

            JobCancellationFee::where('order_id', $order->id)
                ->update([
                    'is_paid' => true
                ]);

            event(new ClientInvRecCreated($client, $invoice->invoice_id));
        } else {
            event(new ClientInvoiceCreated($client, $invoice));
        }

        if ($isPaymentProcessed) {
            event(new ClientPaymentPaid($client, $subtotal));
        }

        \Log::info('Payment IcountDoc finish');
    }



    private function createOrUpdateUser(Request $request)
    {
        $input = $request->data;

        $iCountCompanyID = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_COMPANY_ID)
            ->value('value');

        $iCountUsername = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_USERNAME)
            ->value('value');

        $iCountPassword = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_PASSWORD)
            ->value('value');

        $url = 'https://api.icount.co.il/api/v3.php/client/create_or_update';

        $requestData = [
            'cid' => $iCountCompanyID,
            'user' => $iCountUsername,
            'pass' => $iCountPassword,
            'client_type_name' => $input['firstname'] ?? null,
            'client_name' => $input['firstname'] ?? null,
            'first_name' => $input['firstname'] ?? null,
            'last_name' => $input['lastname'] ?? null,
            'custom_client_id' => $input['id'] ?? 0,
            'client_id' => $input['id'] ?? 0,
            'phone' => $input['phone'] ?? null, 
            'email' => $input['email'] ?? null,
            'vat_id' => $input['vat_number'] ?? null,
            'custom_info' => json_decode(json_encode([
                'status' => $input['status'] ?? null,
                'invoicename' => $input['invoicename'] ?? null,
            ]))
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $requestData);

        $data = $response->json();
        $http_code = $response->status();

        if ($http_code != 200) {
            throw new Exception('Error: Failed to create or update user');
        }

        return $response;
    }

    private function deleteUser($id)
{
    $iCountCompanyID = Setting::query()
        ->where('key', SettingKeyEnum::ICOUNT_COMPANY_ID)
        ->value('value');

    $iCountUsername = Setting::query()
        ->where('key', SettingKeyEnum::ICOUNT_USERNAME)
        ->value('value');

    $iCountPassword = Setting::query()
        ->where('key', SettingKeyEnum::ICOUNT_PASSWORD)
        ->value('value');

    $url = 'https://api.icount.co.il/api/v3.php/client/delete';

    $requestData = [
        'cid' => $iCountCompanyID,
        'user' => $iCountUsername,
        'pass' => $iCountPassword,
        'client_id' => $id,
    ];

    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
    ])->post($url, $requestData);

    if ($response->status() != 200) {
        throw new \Exception('Error: Failed to delete user');
    }

    return $response;
}


}
