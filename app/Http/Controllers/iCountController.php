<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\OrderPaidStatusEnum;
use App\Enums\SettingKeyEnum;
use App\Events\ClientPaymentPaid;
use App\Models\Client;
use App\Models\Invoices;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Setting;
use App\Traits\PaymentAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;


class iCountController extends Controller
{
    use PaymentAPI;

    public function webhook(Request $request)
    {
        Log::info('ICOUNT WEBHOOK...');
        $webhookJson = file_get_contents('php://input');

        file_put_contents(base_path('icount-webhook.txt'), $webhookJson);

        if ($request->hasHeader('X-iCount-Secret')) {
            $headerValue = $request->header('X-iCount-Secret');
            $secretValue = Setting::where('key', SettingKeyEnum::ICOUNT_X_SECRET)->value('value');

            if ($headerValue == $secretValue) {
                Log::info('ICOUNT WEBHOOK SIGN MATCH...');
                $data = json_decode($webhookJson, true);

                if (isset($data['doctype']) && in_array($data['doctype'], ['order', 'invoice', 'invrec'])) {

                    if (isset($data['client_id']) && isset($data['client']['email'])) {
                        $client = Client::where('icount_client_id', $data['client_id'])->first();

                        if (!$client) {
                            $client = Client::where('email', $data['client']['email'])
                                ->first();
                        }

                        if (!$client && isset($data['client']['phone'])) {
                            $client = Client::where('phone', $data['client']['phone'])
                                ->first();
                        }

                        if ($client) {
                            if (!$client->icount_client_id) {
                                $client->update([
                                    'icount_client_id' => $data['client_id']
                                ]);
                            }

                            if ($data['doctype'] == 'order') {
                                $orderData = $this->getICountDocument($data['docnum'], 'order');

                                $this->processOrderDoc($client, $webhookJson, $orderData);
                            } else if ($data['doctype'] == 'invoice') {

                                if ($data['docnum_base'] && $data['doctype_base'] == 'order') {
                                    $orderData = $this->getICountDocument($data['docnum_base'], 'order');

                                    $order = $this->processOrderDoc($client, $webhookJson, $orderData);

                                    if ($order) {
                                        $invoiceData = $this->getICountDocument($data['docnum'], 'invoice');

                                        $totalwithvat = (float)$invoiceData['doc_info']['totalwithvat'];
                                        $remainingsum = (float)$invoiceData['doc_info']['remainingsum'];
                                        $paid_amount = $totalwithvat - $remainingsum;
                                        if ($remainingsum == 0) {
                                            $status = InvoiceStatusEnum::PAID;
                                            $paid_status = OrderPaidStatusEnum::PAID;
                                        } else if ($paid_amount > 0) {
                                            $status = InvoiceStatusEnum::PARTIALLY_PAID;
                                            $paid_status = OrderPaidStatusEnum::UNPAID;
                                        } else {
                                            $status = InvoiceStatusEnum::UNPAID;
                                            $paid_status = OrderPaidStatusEnum::UNPAID;
                                        }

                                        $invoice_icount_status = NULL;
                                        if ($invoiceData['doc_info']['is_cancelled']) {
                                            $invoice_icount_status = 'Cancelled';
                                        } else {
                                            if ($invoiceData['doc_info']['status'] == 0) {
                                                $invoice_icount_status = 'Open';
                                            } else if ($invoiceData['doc_info']['status'] == 1) {
                                                $invoice_icount_status = 'Closed';
                                            }
                                        }

                                        $invoice = Invoices::query()
                                            ->where('client_id', $client->id)
                                            ->where('type', 'invoice')
                                            ->where('invoice_id', $data['docnum'])
                                            ->first();

                                        if (!$invoice) {
                                            $invoice = Invoices::create([
                                                'invoice_id'            => $data['docnum'],
                                                'order_id'              => $order->id,
                                                'type'                  => 'invoice',
                                                'client_id'             => $client->id,
                                                'doc_url'               => $invoiceData['doc_info']['doc_url'],
                                                'response'              => $webhookJson,
                                                'amount'                => $invoiceData['doc_info']['totalsum'],
                                                'discount_amount'       => isset($invoiceData['doc_info']['discount'])
                                                    ? $invoiceData['doc_info']['discount']
                                                    : 0,
                                                'total_amount'          => $invoiceData['doc_info']['afterdiscount'],
                                                'paid_amount'           => $paid_amount,
                                                'amount_with_tax'       => $invoiceData['doc_info']['totalwithvat'],
                                                // 'pay_method' => ,
                                                // 'txn_id' => ,
                                                'status'                => $status,
                                                'invoice_icount_status' => $invoice_icount_status,
                                                'is_webhook_created'    => true
                                            ]);
                                        } else {
                                            if ($invoice->is_webhook_created) {
                                                $invoice->update([
                                                    'doc_url'               => $invoiceData['doc_info']['doc_url'],
                                                    'response'              => $webhookJson,
                                                    'amount'                => $invoiceData['doc_info']['totalsum'],
                                                    'discount_amount'       => isset($invoiceData['doc_info']['discount'])
                                                        ? $invoiceData['doc_info']['discount']
                                                        : 0,
                                                    'total_amount'          => $invoiceData['doc_info']['afterdiscount'],
                                                    'paid_amount'           => $paid_amount,
                                                    'amount_with_tax'       => $invoiceData['doc_info']['totalwithvat'],
                                                    // 'pay_method' => ,
                                                    // 'txn_id' => ,
                                                    'status'                => $status,
                                                    'invoice_icount_status' => $invoice_icount_status,
                                                ]);
                                            }
                                        }

                                        if ($invoice) {
                                            $order->update([
                                                'invoice_status' => 1,
                                                'paid_status'    => $paid_status,
                                            ]);
                                        }
                                    }
                                }
                            } else if ($data['doctype'] == 'invrec') {

                                if ($data['docnum_base'] && $data['doctype_base'] == 'order') {
                                    $orderData = $this->getICountDocument($data['docnum_base'], 'order');

                                    $order = $this->processOrderDoc($client, $webhookJson, $orderData);

                                    if ($order) {
                                        $invrecData = $this->getICountDocument($data['docnum'], 'invrec');
                                        $totalwithvat = (float)$invrecData['doc_info']['totalwithvat'];
                                        $paid_amount = (float)$invrecData['doc_info']['totalpaid'];
                                        $remainingsum = $totalwithvat - $paid_amount;
                                        if ($remainingsum == 0) {
                                            $status = InvoiceStatusEnum::PAID;
                                            $paid_status = OrderPaidStatusEnum::PAID;
                                        } else if ($paid_amount > 0) {
                                            $status = InvoiceStatusEnum::PARTIALLY_PAID;
                                            $paid_status = OrderPaidStatusEnum::UNPAID;
                                        } else {
                                            $status = InvoiceStatusEnum::UNPAID;
                                            $paid_status = OrderPaidStatusEnum::UNPAID;
                                        }

                                        $invoice_icount_status = NULL;
                                        if ($invrecData['doc_info']['is_cancelled']) {
                                            $invoice_icount_status = 'Cancelled';
                                        } else {
                                            if ($invrecData['doc_info']['status'] == 0) {
                                                $invoice_icount_status = 'Open';
                                            } else if ($invrecData['doc_info']['status'] == 1) {
                                                $invoice_icount_status = 'Closed';
                                            }
                                        }

                                        $invoice = Invoices::query()
                                            ->where('client_id', $client->id)
                                            ->where('type', 'invrec')
                                            ->where('invoice_id', $data['docnum'])
                                            ->first();

                                            \Log::info(["orderData" => $orderData]);
                                            
                                            $clientData = [
                                                'client' => [
                                                    'id'       => $orderData['doc_info']['client_id'] ?? null,
                                                    'phone'    => $client->phone ?? null,
                                                    'lng'      => $client->lng ?? 'heb',
                                                    'client_name'     => $orderData['doc_info']['client_name'] ?? null,
                                                    'client_address'  => $orderData['doc_info']['client_address'] ?? null,
                                                ]
                                            ];                                            
                                            
                                            \Log::info(["data" => $clientData]);
                                            if (!$invoice) {
                                                $invoice = Invoices::create([
                                                    'invoice_id'            => $data['docnum'],
                                                    'order_id'              => $order->id,
                                                    'type'                  => 'invrec',
                                                    'client_id'             => $client->id,
                                                    'doc_url'               => $invrecData['doc_info']['doc_url'],
                                                    'response'              => $webhookJson,
                                                    'amount'                => $invrecData['doc_info']['totalsum'],
                                                    'discount_amount'       => $invrecData['doc_info']['discount'] ?? 0,
                                                    'total_amount'          => $invrecData['doc_info']['afterdiscount'],
                                                    'paid_amount'           => $paid_amount,
                                                    'amount_with_tax'       => $invrecData['doc_info']['totalwithvat'],
                                                    'status'                => $status,
                                                    'invoice_icount_status' => $invoice_icount_status,
                                                    'is_webhook_created'    => true
                                                ]);
                                            } else {
                                                if ($invoice->is_webhook_created) {
                                                    $invoice->update([
                                                        'doc_url'               => $invrecData['doc_info']['doc_url'],
                                                        'response'              => $webhookJson,
                                                        'amount'                => $invrecData['doc_info']['totalsum'],
                                                        'discount_amount'       => $invrecData['doc_info']['discount'] ?? 0,
                                                        'total_amount'          => $invrecData['doc_info']['afterdiscount'],
                                                        'paid_amount'           => $paid_amount,
                                                        'amount_with_tax'       => $invrecData['doc_info']['totalwithvat'],
                                                        'status'                => $status,
                                                        'invoice_icount_status' => $invoice_icount_status,
                                                    ]);
                                                }
                                            }
                                            
                                            // Trigger event if invoice is paid
                                            if ($invoice->status == InvoiceStatusEnum::PAID) {
                                                Notification::create([
                                                    'user_id' => $client->id ?? null,
                                                    'user_type' => get_class($client),
                                                    'type' => NotificationTypeEnum::PAYMENT_PAID,
                                                    'data' => [
                                                        'amount' => $paid_amount
                                                    ],
                                                    'status' => 'paid'
                                                ]);
                                        
                                                event(new WhatsappNotificationEvent([
                                                    "type" => WhatsappMessageTemplateEnum::PAYMENT_PAID,
                                                    "notificationData" => [
                                                        'client' => $clientData['client'],
                                                        'amount' => $paid_amount
                                                    ]
                                                ]));
                                            }                                            

                                        if ($invoice) {
                                            $order->update([
                                                'invoice_status' => 1,
                                                'paid_status'    => $paid_status,
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private function processOrderDoc($client, $webhookJson, $orderData)
    {
        $status = NULL;
        if ($orderData['doc_info']['is_cancelled']) {
            $status = 'Cancelled';
        } else {
            if ($orderData['doc_info']['status'] == 0) {
                $status = 'Open';
            } else if ($orderData['doc_info']['status'] == 1) {
                $status = 'Closed';
            }
        }

        $items = [];
        foreach ($orderData['doc_info']['items'] as $item) {
            $items[] = [
                'description'   => $item['description'],
                'unitprice'     => $this->convertToNumber($item['unitprice']),
                'quantity'      => $this->convertToNumber($item['quantity']),
            ];
        }

        $order = Order::query()
            ->where('client_id', $client->id)
            ->where('order_id', $orderData['doc_info']['docnum'])
            ->first();

        if (!$order) {
            $order = Order::create([
                'order_id'              => $orderData['doc_info']['docnum'],
                'client_id'             => $client->id,
                'doc_url'               => $orderData['doc_info']['doc_url'],
                'response'              => $webhookJson,
                'items'                 => json_encode($items, JSON_UNESCAPED_UNICODE),
                'status'                => $status,
                'invoice_status'        => 0,
                // 'paid_status' => ,
                'amount'                => $orderData['doc_info']['totalsum'],
                'discount_amount'       => isset($orderData['doc_info']['discount'])
                    ? $orderData['doc_info']['discount']
                    : 0,
                'total_amount'          => $orderData['doc_info']['afterdiscount'],
                'amount_with_tax'       => $orderData['doc_info']['totalwithvat'],
                // 'paid_amount' => ,
                // 'unpaid_amount' => ,
                'is_webhook_created'    => true
            ]);
        } else {
            if ($order->is_webhook_created) {
                $order->update([
                    'doc_url'               => $orderData['doc_info']['doc_url'],
                    'response'              => $webhookJson,
                    'items'                 => json_encode($items, JSON_UNESCAPED_UNICODE),
                    'status'                => $status,
                    'invoice_status'        => 0,
                    // 'paid_status' => ,
                    'amount'                => $orderData['doc_info']['totalsum'],
                    'discount_amount'       => isset($orderData['doc_info']['discount'])
                        ? $orderData['doc_info']['discount']
                        : 0,
                    'total_amount'          => $orderData['doc_info']['afterdiscount'],
                    'amount_with_tax'       => $orderData['doc_info']['totalwithvat'],
                    // 'paid_amount' => ,
                    // 'unpaid_amount' => ,
                ]);
            }
        }

        return $order;
    }

    private function convertToNumber($string)
    {
        if (is_numeric($string)) {
            if (strpos($string, '.') !== false) {
                return (float)$string;
            } else {
                return (int)$string;
            }
        } else {
            return null; // or handle the error as you see fit
        }
    }
}
