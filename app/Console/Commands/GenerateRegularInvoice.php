<?php

namespace App\Console\Commands;

use App\Models\Invoices;
use App\Models\Job;
use App\Models\Order;
use App\Traits\PaymentAPI;
use Illuminate\Console\Command;

class GenerateRegularInvoice extends Command
{
    use PaymentAPI;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'regular-invoice:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate regular invoice';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $orders = Order::with(['client', 'jobs'])->where('invoice_status', '0')->get();

        foreach ($orders as $c => $order) {
            $client = $order->client;

            $st = 0;
            $based_on = [];
            $services = [];
            $jids = [];
            $makeInvoice = false;

            $bo = ['docnum' => $order->order_id, 'doctype' => 'order'];
            $based_on[] = $bo;

            $s = json_decode($order->items);
            $st += (int)$s[0]->unitprice;
            $services[] = $s[0];

            $job = $order->job;

            if ($job->schedule == 'w') {
                $date = Carbon::parse($job->start_date);
                $newDate = $date->addDays(7);
            }
            if ($job->schedule == '2w') {
                $date = Carbon::parse($job->start_date);
                $newDate = $date->addDays(14);
            }
            if ($job->schedule == '3w') {
                $date = Carbon::parse($job->start_date);
                $newDate = $date->addDays(21);
            }
            if ($job->schedule == 'm') {
                $date = Carbon::parse($job->start_date);
                $newDate = $date->addMonths(1);
            }
            if ($job->schedule == '2m') {
                $date = Carbon::parse($job->start_date);
                $newDate = $date->addMonths(2);
            }
            if ($job->schedule == '3m') {
                $date = Carbon::parse($job->start_date);
                $newDate = $date->addMonths(3);
            }

            $today = Carbon::today()->format('Y-m-d');

            if ($today !== $newDate->format('Y-m-d')) {
                $jids[] = $job->id;
                $makeInvoice = true;
            }

            if ($makeInvoice == true) {
                $total = 0;

                $card = ClientCard::where('client_id', $client->id)->first();
                $p_method = $client->payment_method;

                $doctype  = ($card != null && $card->card_token != null && $p_method == 'cc') ? "invrec" : "invoice";

                $subtotal = (int)$st;
                $tax = (config('services.app.tax_percentage') / 100) * $subtotal;
                $total = $tax + $subtotal;

                $o_res = json_decode($orders[0]->response);

                $due = Carbon::now()->endOfMonth()->toDateString();
                $name = ($job->client->invoicename != null) ? $job->client->invoicename : $job->client->firstname . " " . $job->client->lastname;
                $url = "https://api.icount.co.il/api/v3.php/doc/create";

                $params = array(
                    "cid"            => Helper::get_setting(SettingKeyEnum::ICOUNT_COMPANY_ID),
                    "user"           => Helper::get_setting(SettingKeyEnum::ICOUNT_USERNAME),
                    "pass"           => Helper::get_setting(SettingKeyEnum::ICOUNT_PASSWORD),

                    "doctype"        => $doctype,
                    "client_id"      => $o_res->client_id,
                    "client_name"    => $name,
                    "client_address" => $job->client->geo_address,
                    "email"          => $job->client->email,
                    "lang"           => ($job->client->lng == 'heb') ? 'he' : 'en',
                    "currency_code"  => "ILS",
                    "doc_lang"       => ($job->client->lng == 'heb') ? 'he' : 'en',
                    "items"          => $services,
                    "duedate"        => $due,
                    "based_on"       => $based_on,

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

                /* Auto payment */
                if ($doctype == 'invrec') {
                    $pres = $this->commitRegularInvoicePayment($services, $total, $card->card_token, $client);
                    $pre = json_encode($pres);
                }

                Job::whereIn('id', $jids)->update([
                    'invoice_no'    => $json["docnum"],
                    'invoice_url'   => $json["doc_url"],
                    'isOrdered'     => 2
                ]);

                $invoice = Invoices::create([
                    'invoice_id' => $json['docnum'],
                    'amount'     => $total,
                    'paid_amount' => $total,
                    'pay_method' => ((isset($pres)) && $pres->HasError == false && $doctype == 'invrec') ? 'Credit Card' : 'NA',
                    'client_id'   => $job->client->id,
                    'doc_url'    => $json['doc_url'],
                    'type'       => $doctype,
                    'invoice_icount_status' => 'Open',
                    'due_date'   => $due,
                    'txn_id'     => ((isset($pres)) && $pres->HasError == false && $doctype == 'invrec') ? $pres->ReferenceNumber : '',
                    'callback'   => ((isset($pres)) && $pres->HasError == false && $doctype == 'invrec') ? $pre : '',
                    'status'     => ((isset($pres))  && $pres->HasError == false && $doctype == 'invrec') ? 'Paid' : ((isset($pres)) ? $pres->ReturnMessage : 'Unpaid'),
                ]);

                if ((isset($pres))  && $pres->HasError == false && $doctype == 'invrec') {
                    // close invoice
                    $this->closeICountDocument($json['docnum'], 'invrec');
                    $invoice->update(['invoice_icount_status' => 'Closed']);
                }

                /*Close Order */
                foreach ($orders as $o) {
                    $this->closeICountDocument($o->order_id, 'order');
                    Order::where('id', $o->id)->update(['status' => 'Closed', 'invoice_status' => 2]);
                }
            }
        }

        return 0;
    }

    private function commitRegularInvoicePayment($service, $total, $token, $client)
    {
        $zcreditTerminalNumber = Setting::query()
            ->where('key', SettingKeyEnum::ZCREDIT_TERMINAL_NUMBER)
            ->value('value');

        $zcreditPassword = Setting::query()
            ->where('key', SettingKeyEnum::ZCREDIT_TERMINAL_PASS)
            ->value('value');

        $pitems[] = [
            'ItemDescription' => $service[0]->description,
            'ItemQuantity'    => $service[0]->quantity,
            'ItemPrice'       => $total,
            'IsTaxFree'       => "false"
        ];

        $pay_items = json_encode($pitems);

        $curl = curl_init();

        $pdata = '{
        "TerminalNumber": "' . $zcreditTerminalNumber . '",
        "Password": "' . $zcreditPassword . '",
        "Track2": "",
        "CardNumber": "' . $token . '",
        "CVV": "",
        "ExpDate_MMYY": "",
        "TransactionSum": "' . $total . '",
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
        "CustomerName":"' . $client->firstname . " " . $client->lastname . '",
        "CustomerAddress": "' . $client->geo_address . '",
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
          "TaxRate": "' . config('services.app.tax_percentage') . '",
          "Comment": "",
          "ReceipientEmail": "",
          "EmailDocumentToReceipient": "",
          "ReturnDocumentInResponse": "",
          "Items": ' . $pay_items . '
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
            CURLOPT_POSTFIELDS => $pdata,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $pre = curl_exec($curl);
        $pres = json_decode($pre);
        curl_close($curl);
        return $pres;
    }
}
