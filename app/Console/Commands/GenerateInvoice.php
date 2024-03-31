<?php

namespace App\Console\Commands;

use App\Traits\PaymentAPI;
use Illuminate\Console\Command;

class GenerateInvoice extends Command
{
    use PaymentAPI;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate invoice';

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
        $orders = Order::where('invoice_status', '1')->orWhere('invoice_status', '0')->get();
        foreach ($orders as $od) {

            $id = $od->job_id;
            $oid = $od->id;

            $job = Job::query()->with(['jobservice', 'client', 'contract', 'order'])->find($id);
            $services = json_decode($job->order->items);
            $total = 0;

            $p_method = $job->client->payment_method;
            $card = ClientCard::where('client_id', $job->client_id)->first();
            $doctype = ($card != null && $card->card_token != null && $p_method == 'cc') ? "invrec" : "invoice";

            if (str_contains($job->schedule, 'w') == false) {
                $subtotal = (int)$services[0]->unitprice;
                $tax = (config('services.app.tax_percentage') / 100) * $subtotal;
                $total = $tax + $subtotal;

                $order = $job->order;
                $o_res = json_decode($order->response);

                $due = Carbon::now()->endOfMonth()->toDateString();
                $url = "https://api.icount.co.il/api/v3.php/doc/create";

                $params = array(
                    "cid"            => Helper::get_setting(SettingKeyEnum::ICOUNT_COMPANY_ID),
                    "user"           => Helper::get_setting(SettingKeyEnum::ICOUNT_USERNAME),
                    "pass"           => Helper::get_setting(SettingKeyEnum::ICOUNT_PASSWORD),

                    "doctype"        => $doctype,
                    "client_id"      => $o_res->client_id,
                    "client_name"    => $job->client->invoicename,
                    "client_address" => $job->client->geo_address,
                    "email"          => $job->client->email,
                    "lang"           => ($job->client->lng == 'heb') ? 'he' : 'en',
                    "currency_code"  => "ILS",
                    "doc_lang"       => ($job->client->lng == 'heb') ? 'he' : 'en',
                    "items"          => $services,
                    "duedate"        => $due,
                    "based_on"       => ['docnum' => $order->order_id, 'doctype' => 'order'],

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

                // if(!$json["status"]) die($json["reason"]);

                // Helper::sendInvoicePayToClient($id, $json["doc_url"], $json["docnum"],$inv->id);

                /* Auto payment */
                if ($doctype == 'invrec') {

                    $pres = $this->commitPayment($services, $id, $card->card_token);
                    $pre = json_encode($pres);
                }

                /*Close Order */
                $this->closeICountDocument($job->order->order_id, 'order');

                Job::where('id', $id)->update([
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
                    'due_date'   => $due,
                    'invoice_icount_status' => 'Open',
                    'txn_id'     => ((isset($pres)) && $pres->HasError == false && $doctype == 'invrec') ? $pres->ReferenceNumber : '',
                    'callback'   => ((isset($pres)) && $pres->HasError == false && $doctype == 'invrec') ? $pre : '',
                    'status'     => ((isset($pres))  && $pres->HasError == false && $doctype == 'invrec') ? 'Paid' : ((isset($pres)) ? $pres->ReturnMessage : 'Unpaid'),
                ]);

                if ((isset($pres))  && $pres->HasError == false && $doctype == 'invrec') {
                    //close invoice
                    $this->closeICountDocument($json['docnum'], 'invrec');
                    $invoice->update(['invoice_icount_status' => 'Closed']);
                }

                Order::where('id', $job->order->id)->update(['status' => 'Closed']);
                Order::where('id', $oid)->update(['invoice_status' => 2]);
            }
        }

        return 0;
    }

    private function commitPayment($services, $id, $token)
    {
        $job = Job::query()->with(['jobservice', 'client', 'contract', 'order'])->find($id);
        $pitems = [];
        $subtotal = (int)$services[0]->unitprice;
        $tax = (config('services.app.tax_percentage') / 100) * $subtotal;
        $total = $tax + $subtotal;

        $zcreditTerminalNumber = Setting::query()
            ->where('key', SettingKeyEnum::ZCREDIT_TERMINAL_NUMBER)
            ->value('value');

        $zcreditPassword = Setting::query()
            ->where('key', SettingKeyEnum::ZCREDIT_TERMINAL_PASS)
            ->value('value');

        if (!empty($services)) {
            foreach ($services as $service) {
                $pitems[] = [
                    'ItemDescription' => $service->description,
                    'ItemQuantity'    => $service->quantity,
                    'ItemPrice'       => $total,
                    'IsTaxFree'       => "false"
                ];
            }
        }
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
        "CustomerName":"' . $job->client->firstname . " " . $job->client->lastname . '",
        "CustomerAddress": "' . $job->client->geo_address . '",
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
