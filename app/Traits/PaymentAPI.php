<?php

namespace App\Traits;

use App\Enums\JobStatusEnum;
use App\Enums\OrderPaidStatusEnum;
use App\Enums\SettingKeyEnum;
use App\Enums\TransactionStatusEnum;
use App\Models\Job;
use App\Models\JobCancellationFee;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Transaction;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;

trait PaymentAPI
{
    private function validateCard($cardData)
    {
        $zcreditTerminalNumber = Setting::query()
            ->where('key', SettingKeyEnum::ZCREDIT_TERMINAL_NUMBER)
            ->value('value');

        $zcreditPassword = Setting::query()
            ->where('key', SettingKeyEnum::ZCREDIT_TERMINAL_PASS)
            ->value('value');

        $url = 'https://pci.zcredit.co.il/ZCreditWS/api/Transaction/ValidateCard';

        $postData = [
            'TerminalNumber' => $zcreditTerminalNumber,
            'Password' => $zcreditPassword,
            'Track2' => "",
            'CardNumber' => $cardData['card_number'],
            'ExpDate_MMYY' => $cardData['card_exp'],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            $url,
            $postData
        );

        $data = $response->json();
        $http_code = $response->status();

        if ($http_code != 200) {
            throw new Exception('Error : Failed to validate card');
        }

        return $data;
    }

    private function getCardByToken($token)
    {
        $zcreditTerminalNumber = Setting::query()
            ->where('key', SettingKeyEnum::ZCREDIT_TERMINAL_NUMBER)
            ->value('value');

        $zcreditPassword = Setting::query()
            ->where('key', SettingKeyEnum::ZCREDIT_TERMINAL_PASS)
            ->value('value');

        $url = 'https://pci.zcredit.co.il/ZCreditWS/api/Token/GetTokenData';

        $postData = [
            'TerminalNumber' => $zcreditTerminalNumber,
            'Password' => $zcreditPassword,
            'Token' => $token,
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            $url,
            $postData
        );

        $data = $response->json();
        $http_code = $response->status();

        if ($http_code != 200) {
            throw new Exception('Error : Failed to validate card');
        }

        return $data;
    }

    private function captureCardCharge($chargeData)
    {
        $zcreditTerminalNumber = Setting::query()
            ->where('key', SettingKeyEnum::ZCREDIT_TERMINAL_NUMBER)
            ->value('value');

        $zcreditPassword = Setting::query()
            ->where('key', SettingKeyEnum::ZCREDIT_TERMINAL_PASS)
            ->value('value');

        $url = 'https://pci.zcredit.co.il/ZCreditWS/api/Transaction/CommitFullTransaction';

        // https://zcreditws.docs.apiary.io/#reference/0/make-transaction/commitfulltransaction
        $postData = [
            'TerminalNumber' => $zcreditTerminalNumber,
            'Password' => $zcreditPassword,
            'Track2' => "",
            'CardNumber' => $chargeData['card_number'],
            'CVV' => "",
            'ExpDate_MMYY' => "",
            'TransactionSum' => $chargeData['amount'],
            'NumberOfPayments' => "1",
            'FirstPaymentSum' => "0",
            'OtherPaymentsSum' => "0",
            'TransactionType' => "01",           // 01 - regular transaction, 53 - refund transaction
            'CurrencyType' => "1",                // 1 - NIS, 2 - USD, 3 - EUR
            'CreditType' => "1",
            'J' => $chargeData['J'],
            'IsCustomerPresent' => false,
            'AuthNum' => "",
            'HolderID' => "",
            'ExtraData' => "",
            'CustomerName' => $chargeData['client_name'],
            'CustomerAddress' => $chargeData['client_address'],
            'CustomerEmail' => $chargeData['client_email'],
            'PhoneNumber' => $chargeData['client_phone'],
            'ItemDescription' => "",
            'ObeligoAction' => isset($chargeData['obeligo_action']) ?
                $chargeData['obeligo_action'] : 0,
            'OriginalZCreditReferenceNumber' => isset($chargeData['original_zcredit_reference_number']) ?
                $chargeData['original_zcredit_reference_number'] : 0,
            'TransactionUniqueIdForQuery' => "",
            'TransactionUniqueID' => "",
            'UseAdvancedDuplicatesCheck' => "",
        ];

        // if(!isset($chargeData['obeligo_action']) || $chargeData['obeligo_action'] != 2) {
        //     $postData['ZCreditInvoiceReceipt'] = [
        //         'Type' => "0",
        //         'TaxRate' => config('services.app.tax_percentage'),
        //         'RecepientName' => "",
        //         'RecepientCompanyID' => "",
        //         'Address' => "",
        //         'City' => "",
        //         'ZipCode' => "",
        //         'PhoneNum' => "",
        //         'FaxNum' => "",
        //         'Comment' => "",
        //         'ReceipientEmail' => "",
        //         'EmailDocumentToReceipient' => "",
        //         'ReturnDocumentInResponse' => "",
        //         'Items' => $chargeData['items'],
        //     ];
        // }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            $url,
            $postData
        );

        $data = $response->json();
        $http_code = $response->status();

        if ($http_code != 200) {
            throw new Exception('Error : Failed to validate card');
        }

        return $data;
    }

    private function createSession($sessionData)
    {
        $zcreditKey = Setting::query()
            ->where('key', SettingKeyEnum::ZCREDIT_KEY)
            ->value('value');

        $url = 'https://pci.zcredit.co.il/webcheckout/api/WebCheckout/CreateSession';

        // https://zcreditwc.docs.apiary.io/#reference/0/create-webcheckout-session/createsession
        $postData = [
            'Key' => $zcreditKey,
            'Local' => $sessionData['local'],
            'UniqueId' => $sessionData['unique_id'],
            'SuccessUrl' => $sessionData['success_url'],
            'CancelUrl' => url('client/settings?cps=payment-cancelled'),
            'CallbackUrl' => config('services.zcredit.callback-url'),
            'PaymentType' => 'authorize',
            'CreateInvoice' => false,
            'AdditionalText' => '',
            'ShowCart' => true,
            "ThemeColor" => "005ebb",
            "BitButtonEnabled" => "false",
            "ApplePayButtonEnabled" => "true",
            "GooglePayButtonEnabled" => "true",
            "Installments" => [
                "Type" => "regular",
                "MinQuantity" => "1",
                "MaxQuantity" => "1"
            ],
            // 'ShowTotalSumInPayButton' => true,
            // 'Bypass3DS' => false,
            'Customer' => [
                'Email' => $sessionData['client_email'],
                'Name' => $sessionData['client_name'],
                'PhoneNumber' => $sessionData['client_phone'],
                "Attributes" => [
                    "HolderId" =>  "required",
                    "Name" =>  "required",
                    "PhoneNumber" =>  "required",
                    "Email" =>  "required"
                ]
            ],
            'CartItems' => $sessionData['card_items'],
            "CardIcons" => [
                "ShowVisaIcon" => "true",
                "ShowMastercardIcon" => "true",
                "ShowDinersIcon" => "true",
                "ShowAmericanExpressIcon" => "true",
                "ShowIsracardIcon" => "true",
            ],
            "IssuerWhiteList" => "[1,2,3,4,5,6]",
            "BrandWhiteList" => "[1,2,3,4,5,6]",
            'FocusType' => "None",
            "UseLightMode" => "false",
            "UseCustomCSS" => "false"
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            $url,
            $postData
        );

        $data = $response->json();
        $http_code = $response->status();

        if ($http_code != 200) {
            throw new Exception('Error : Failed to create session');
        }

        return $data;
    }

    private function getTransactionByReferenceID($referenceID)
    {
        $zcreditTerminalNumber = Setting::query()
            ->where('key', SettingKeyEnum::ZCREDIT_TERMINAL_NUMBER)
            ->value('value');

        $zcreditPassword = Setting::query()
            ->where('key', SettingKeyEnum::ZCREDIT_TERMINAL_PASS)
            ->value('value');

        $url = 'https://pci.zcredit.co.il/ZCreditWS/api/Transaction/GetTransactionStatusByReferenceId';

        $postData = [
            'TerminalNumber' => $zcreditTerminalNumber,
            'Password' => $zcreditPassword,
            'ReferenceID' => $referenceID
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            $url,
            $postData
        );

        $data = $response->json();
        $http_code = $response->status();

        if ($http_code != 200) {
            throw new Exception('Error : Failed to get transaction');
        }

        return $data;
    }

    private function refundByReferenceID($referenceID, $amount = NULL)
    {
        $zcreditTerminalNumber = Setting::query()
            ->where('key', SettingKeyEnum::ZCREDIT_TERMINAL_NUMBER)
            ->value('value');

        $zcreditPassword = Setting::query()
            ->where('key', SettingKeyEnum::ZCREDIT_TERMINAL_PASS)
            ->value('value');

        $url = 'https://pci.zcredit.co.il/ZCreditWS/api/Transaction/RefundTransaction';

        $postData = [
            'TerminalNumber' => $zcreditTerminalNumber,
            'Password' => $zcreditPassword,
            'TransactionIdToCancelOrRefund' => $referenceID
        ];

        if ($amount) {
            $postData['TransactionSum'] = $amount;
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            $url,
            $postData
        );

        $data = $response->json();
        $http_code = $response->status();

        if ($http_code != 200) {
            throw new Exception('Error : Failed to get transaction');
        }

        return $data;
    }

    private function cardBrandNameByCode($code)
    {
        $brands = [
            'Private Label Brand',
            'Mastercard',
            'Visa',
            'Diners',
            'Amex',
            'Isracard',
            'JCB',
            'Discover',
            'Maestro'
        ];

        if (array_key_exists($code, $brands)) {
            return $brands[$code];
        } else {
            return NULL;
        }
    }

    // Same API but different configuration for 'order' doctype.
    private function generateOrderDocument($client, $items, $duedate, $data)
    {
        $address = $client->property_addresses()->first();

        $iCountCompanyID = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_COMPANY_ID)
            ->value('value');

        $iCountUsername = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_USERNAME)
            ->value('value');

        $iCountPassword = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_PASSWORD)
            ->value('value');

        $totalsum = 0;
        foreach ($items as $key => $item) {
            $totalsum = $totalsum + ($item['unitprice'] * $item['quantity']);
        }

        $discount = 0;
        $total = $totalsum;
        if ($data['discount_amount'] > 0) {
            $discount = $data['discount_amount'];
            $total = $totalsum - $discount;
        }
        $roundup = number_format((float)(ceil($total) - $total), 2, '.', '');

        $url = 'https://api.icount.co.il/api/v3.php/doc/create';

        $postData = [
            "cid"  => $iCountCompanyID,
            "lang" => ($client->lng == 'heb') ? 'he' : 'en',
            "user" => $iCountUsername,
            "pass" => $iCountPassword,
            "email" => $client->email,
            "doctype" => 'order',
            "client_name" => $client->invoicename ? $client->invoicename : $client->firstname . ' ' . $client->lastname,
            "client_address" => $address ? $address->geo_address : '',
            "currency_code" => "ILS",
            "doc_lang" => ($client->lng == 'heb') ? 'he' : 'en',
            "items" => $items,
            "totalsum" => $totalsum,
            "discount" => $discount,
            "roundup" => $roundup,
            "afterdiscount" => $total,
            "duedate" => $duedate,
            "send_email" => 0,
            "email_to_client" => 0,
            "email_to" => $client->email,
        ];

        if($client->icount_client_id) {
            $postData['client_id'] = $client->icount_client_id;
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            $url,
            $postData
        );

        $json = $response->json();
        $http_code = $response->status();

        \Log::info([$json]);

        if ($http_code != 200) {
            throw new Exception('Error : Failed to create order document');
        }

        if (!$json["status"]) {
            throw new Exception($json["reason"], 500);
        }

        $documentInfoJson = $this->getICountDocument($json['docnum'], 'order');

        $invoice_status = 1;
        $paid_status = NULL;
        if (isset($data['job_ids'])) {
            $hasPendingJobInMonth = Job::query()
                ->where('client_id', $client->id)
                ->where(function ($q) {
                    $q
                        ->where(function ($q1) {
                            $q1
                                ->whereDate('start_date', '>=', Carbon::today()->toDateString())
                                ->whereDate('start_date', '<=', Carbon::today()->endOfMonth()->toDateString());
                        })
                        ->orWhereNotNull('next_start_date')
                        ->where(function ($q2) {
                            $q2
                                ->whereDate('next_start_date', '>=', Carbon::today()->toDateString())
                                ->whereDate('next_start_date', '<=', Carbon::today()->endOfMonth()->toDateString());
                        });
                })
                ->whereIn('status', [
                    JobStatusEnum::PROGRESS,
                    JobStatusEnum::SCHEDULED,
                    JobStatusEnum::UNSCHEDULED
                ])
                ->exists();

            $paid_status = $hasPendingJobInMonth ? OrderPaidStatusEnum::UNDONE : NULL;
        }

        if (isset($data['is_one_time_in_month'])) {
            $invoice_status = $data['is_one_time_in_month'] ? 0 : 1;
        }

        $discount = isset($documentInfoJson['doc_info']['discount']) ? $documentInfoJson['doc_info']['discount'] : NULL;
        $totalAmount = isset($documentInfoJson['doc_info']['afterdiscount']) ? $documentInfoJson['doc_info']['afterdiscount'] : NULL;
        $order = Order::create([
            'order_id'          => $json['docnum'],
            'doc_url'           => $json['doc_url'],
            'client_id'         => $client->id,
            'response'          => json_encode($json, true),
            'items'             => json_encode($items),
            'status'            => 'Open',
            'invoice_status'    => $invoice_status,             // 1 = regular invoice, 0 = immediate invoice
            'paid_status'       => $paid_status,
            'amount'            => $documentInfoJson['doc_info']['totalsum'],
            'discount_amount'   => $discount,
            'total_amount'      => $totalAmount,
            'amount_with_tax'   => $documentInfoJson['doc_info']['totalwithvat']
        ]);

        if (isset($data['job_ids'])) {
            Job::query()
                ->whereIn('id', $data['job_ids'])
                ->where('is_order_generated', false)
                ->update([
                    'isOrdered' => '1',
                    'order_id' => $order->id,
                    'is_order_generated' => true
                ]);

            JobCancellationFee::query()
                ->whereIn('job_id', $data['job_ids'])
                ->where('is_order_generated', false)
                ->update([
                    'order_id' => $order->id,
                    'is_order_generated' => true
                ]);
        }

        $client->update(['icount_client_id' => $json['client_id']]);

        return $order;
    }

    private function getICountDocument($docnum, $doctype)
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

        $url = 'https://api.icount.co.il/api/v3.php/doc/info';

        $postData = [
            "cid"  => $iCountCompanyID,
            "user" => $iCountUsername,
            "pass" => $iCountPassword,
            "doctype"   => $doctype,
            "docnum"    => $docnum,
            "get_items" => 1
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            $url,
            $postData
        );

        $data = $response->json();
        $http_code = $response->status();

        if ($http_code != 200) {
            throw new Exception('Error : Failed to get document');
        }

        return $data;
    }

    private function closeICountDocument($docnum, $doctype)
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

        $url = 'https://api.icount.co.il/api/v3.php/doc/close';

        $postData = [
            "cid"  => $iCountCompanyID,
            "user" => $iCountUsername,
            "pass" => $iCountPassword,
            "doctype"   => $doctype,
            "docnum"    => $docnum,
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            $url,
            $postData
        );

        $data = $response->json();
        $http_code = $response->status();

        if ($http_code != 200) {
            throw new Exception('Error : Failed to close document');
        }

        return $data;
    }

    private function cancelICountDocument($docnum, $doctype, $reason)
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

        $url = 'https://api.icount.co.il/api/v3.php/doc/cancel';

        $postData = [
            "cid"  => $iCountCompanyID,
            "user" => $iCountUsername,
            "pass" => $iCountPassword,
            "doctype"   => $doctype,
            "docnum"    => $docnum,
            "reason"    => $reason,
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            $url,
            $postData
        );

        $data = $response->json();
        $http_code = $response->status();

        if ($http_code != 200) {
            throw new Exception('Error : Failed to close document');
        }

        return $data;
    }

    private function generateInvoiceDocument(
        $client,
        $order,
        $duedate,
        $otherInvDocOptions
    ) {
        $address = $client->property_addresses()->first();

        $iCountCompanyID = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_COMPANY_ID)
            ->value('value');

        $iCountUsername = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_USERNAME)
            ->value('value');

        $iCountPassword = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_PASSWORD)
            ->value('value');

        $items = json_decode($order->items, true);
        $totalsum = $order->amount;

        $discount = $order->discount_amount;
        $total = $totalsum - $discount;
        $roundup = number_format((float)(ceil($total) - $total), 2, '.', '');

        $url = 'https://api.icount.co.il/api/v3.php/doc/create';

        $postData = [
            "cid"  => $iCountCompanyID,
            "lang" => ($client->lng == 'heb') ? 'he' : 'en',
            "user" => $iCountUsername,
            "pass" => $iCountPassword,
            "email" => $client->email,
            "doctype" => 'invoice',
            "client_id" => $client->icount_client_id,
            "client_name" => $client->invoicename ? $client->invoicename : $client->firstname . ' ' . $client->lastname,
            "client_address" => $address ? $address->geo_address : '',
            "currency_code" => "ILS",
            "doc_lang" => ($client->lng == 'heb') ? 'he' : 'en',
            "items" => $items,
            "totalsum" => $totalsum,
            "discount" => $discount,
            "roundup" => $roundup,
            "afterdiscount" => $total,
            "duedate" => $duedate,
            "based_on" => $otherInvDocOptions['based_on'],
            "send_email" => 1,
            "email_to_client" => 1,
            "email_to" => $client->email,
            "cc" => isset($otherInvDocOptions['cc']) ? $otherInvDocOptions['cc'] : [],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            $url,
            $postData
        );

        $json = $response->json();
        $http_code = $response->status();

        if ($http_code != 200) {
            throw new Exception('Error : Failed to create invoice document');
        }

        if (!$json["status"]) {
            throw new Exception($json["reason"], 500);
        }

        $documentInfoJson = $this->getICountDocument($json['docnum'], 'invoice');

        return $documentInfoJson;
    }

    private function generateInvRecDocument(
        $client,
        $items,
        $duedate,
        $otherInvDocOptions,
        $totalsum,
        $discount
    ) {
        $address = $client->property_addresses()->first();

        $iCountCompanyID = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_COMPANY_ID)
            ->value('value');

        $iCountUsername = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_USERNAME)
            ->value('value');

        $iCountPassword = Setting::query()
            ->where('key', SettingKeyEnum::ICOUNT_PASSWORD)
            ->value('value');

        $total = $totalsum - $discount;
        $roundup = number_format((float)(ceil($total) - $total), 2, '.', '');

        $url = 'https://api.icount.co.il/api/v3.php/doc/create';

        $postData = [
            "cid"  => $iCountCompanyID,
            "lang" => ($client->lng == 'heb') ? 'he' : 'en',
            "user" => $iCountUsername,
            "pass" => $iCountPassword,
            "email" => $client->email,
            "doctype" => 'invrec',
            "client_id" => $client->icount_client_id,
            "client_name" => $client->invoicename ? $client->invoicename : $client->firstname . ' ' . $client->lastname,
            "client_address" => $address ? $address->geo_address : '',
            "currency_code" => "ILS",
            "doc_lang" => ($client->lng == 'heb') ? 'he' : 'en',
            "items" => $items,
            "totalsum" => $totalsum,
            "discount" => $discount,
            "roundup" => $roundup,
            "afterdiscount" => $total,
            "duedate" => $duedate,
            "based_on" => $otherInvDocOptions['based_on'],
            "send_email" => 1,
            "email_to_client" => 1,
            "email_to" => $client->email,
            "cc" => isset($otherInvDocOptions['cc']) ? $otherInvDocOptions['cc'] : [],
            "banktransfer" => isset($otherInvDocOptions['banktransfer']) ? $otherInvDocOptions['banktransfer'] : [],
            "cheques" => isset($otherInvDocOptions['cheques']) ? $otherInvDocOptions['cheques'] : [],
            "cash" => isset($otherInvDocOptions['cash']) ? $otherInvDocOptions['cash'] : [],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            $url,
            $postData
        );

        $json = $response->json();

        \Log::info('generateInvRecDocument doc create response : ', $json);

        $http_code = $response->status();

        if ($http_code != 200) {
            throw new Exception('Error : Failed to create invoice receipt document');
        }

        if (!$json["status"]) {
            throw new Exception($json["reason"], 500);
        }

        $documentInfoJson = $this->getICountDocument($json['docnum'], 'invrec');

        return $documentInfoJson;
    }

    private function commitInvoicePayment($client, $services, $token, $subtotal)
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

        if (!$captureChargeResponse['HasError']) {
            $transaction->update([
                'status' => TransactionStatusEnum::COMPLETED,
                'transaction_id' => $captureChargeResponse['ReferenceNumber'],
                'transaction_at' => now(),
                'metadata' => ['card_number' => $token],
            ]);
        }

        return $captureChargeResponse;
    }
}
