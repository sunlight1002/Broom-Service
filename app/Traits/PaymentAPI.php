<?php

namespace App\Traits;

use App\Enums\SettingKeyEnum;
use App\Models\Job;
use App\Models\Order;
use App\Models\Setting;
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
            'ZCreditInvoiceReceipt' => [
                'Type' => "0",
                'TaxRate' => config('services.app.tax_percentage'),
                'RecepientName' => "",
                'RecepientCompanyID' => "",
                'Address' => "",
                'City' => "",
                'ZipCode' => "",
                'PhoneNum' => "",
                'FaxNum' => "",
                'Comment' => "",
                'ReceipientEmail' => "",
                'EmailDocumentToReceipient' => "",
                'ReturnDocumentInResponse' => "",
                'Items' => $chargeData['items'],
            ],
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
            'SuccessUrl' => url('thanks/' . $sessionData['client_id']),
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

    private function generateOrderDocument($client, $jobIDs, $items, $isOneTime)
    {
        $address = $client->property_addresses()->first();

        $json = $this->createICountDocument([
            "doctype" => 'order',
            "client_id" => null,
            "client_name" => $client->invoicename,
            "client_address" => $address ? $address->geo_address : '',
            "email" => $client->email,
            "lang" => ($client->lng == 'heb') ? 'he' : 'en',
            "items" => $items,
            "email_to" => $client->email,
            "duedate" => '',
            "based_on" => [],
            "send_email" => 0,
            "email_to_client" => 0,
            "cc" => [],
        ]);

        if (!$json["status"]) {
            throw new Exception($json["reason"], 500);
        }

        $order = Order::create([
            'order_id'          => $json['docnum'],
            'doc_url'           => $json['doc_url'],
            'client_id'         => $client->id,
            'response'          => json_encode($json, true),
            'items'             => json_encode($items),
            'status'            => 'Open',
            'invoice_status'    => $isOneTime ? 0 : 1,
        ]);

        Job::whereIn('id', $jobIDs)
            ->update([
                'isOrdered' => '1',
                'order_id' => $order->id,
                'is_order_generated' => true
            ]);

        return $order;
    }

    private function createICountDocument($documentData)
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

        $url = 'https://api.icount.co.il/api/v3.php/doc/create';

        $postData = [
            "cid"  => $iCountCompanyID,
            "lang" => $documentData['lang'],
            "user" => $iCountUsername,
            "pass" => $iCountPassword,
            "email" => $documentData['email'],
            "doctype" => $documentData['doctype'],
            "client_id" => $documentData['client_id'],
            "client_name" => $documentData['client_name'],
            "client_address" => $documentData['client_address'],
            "currency_code" => "ILS",
            "doc_lang" => $documentData['lang'],
            "items" => $documentData['items'],
            "duedate" => $documentData['duedate'],
            "based_on" => $documentData['based_on'],
            "send_email" => $documentData['send_email'],
            "email_to_client" => $documentData['email_to_client'],
            "email_to" => $documentData['email_to'],
            "cc" => $documentData['cc'],
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
            throw new Exception('Error : Failed to create document');
        }

        return $data;
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
            // "based_on"  => $docnum
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
        $iCountClientID,
        $client,
        $items,
        $doctype,
        $duedate,
        $otherInvDocOptions
    ) {
        $address = $client->property_addresses()->first();

        $json = $this->createICountDocument([
            "doctype" => $doctype,
            "client_id" => $iCountClientID,
            "client_name" => $client->invoicename,
            "client_address" => $address ? $address->geo_address : '',
            "email" => $client->email,
            "lang" => ($client->lng == 'heb') ? 'he' : 'en',
            "items" => $items,
            "email_to" => $client->email,
            "duedate" => $duedate,
            "based_on" => $otherInvDocOptions['based_on'],
            "send_email" => 1,
            "email_to_client" => 1,
            "cc" => isset($otherInvDocOptions['cc']) ? $otherInvDocOptions['cc'] : []
        ]);

        if (!$json["status"]) {
            throw new Exception($json["reason"], 500);
        }

        return $json;
    }
}
