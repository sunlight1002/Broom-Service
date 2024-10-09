<?php

namespace App\Http\Controllers;

use App\Enums\ContractStatusEnum;
use App\Enums\OrderPaidStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Models\Client;
use App\Models\ClientCard;
use App\Models\Contract;
use App\Models\Order;
use App\Models\Transaction;
use App\Traits\PaymentAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    use PaymentAPI;

    public function callback(Request $request)
    {
        Log::info('ZCREDIT CALLABLE...');
        $json = file_get_contents('php://input');

        file_put_contents(base_path('zcredit.txt'), $json);

        $data = json_decode($json, true);

        if (isset($data['SessionId']) && $data['ReferenceNumber']) {
            $belongToContract = false;
            $contract = NULL;
            if (Str::contains($data['UniqueID'], 'BROOM-CNTRCT')) {
                $belongToContract = true;

                $contractID = (int)Str::replace('BROOM-CNTRCT', '', $data['UniqueID']);
                $contract = Contract::find($contractID);
            }

            $ZCreditTrx = $this->getTransactionByReferenceID($data['ReferenceNumber']);
            $transaction = Transaction::query()
                ->where('session_id', $data['SessionId'])
                ->whereNotIn('status', [TransactionStatusEnum::COMPLETED, TransactionStatusEnum::FAILED])
                ->first();

            if ($ZCreditTrx && !$ZCreditTrx['HasError']) {
                if ($transaction) {
                    $client = Client::find($transaction->client_id);

                    if ($client) {
                        $address = $client->property_addresses()->first();
                    }

                    if ($transaction->description == 'Validate credit card') {
                        $captureChargeResponse = $this->captureCardCharge([
                            'card_number' => $ZCreditTrx['Token'],
                            'amount' => $transaction->amount,
                            'client_name' => $client->firstname . ' ' . $client->lastname,
                            'client_address' => $address ? $address->geo_address : '',
                            'client_email' => $client->email,
                            'client_phone' => $client->phone,
                            'J' => 0,
                            'obeligo_action' => 2,
                            'original_zcredit_reference_number' => $ZCreditTrx['ReferenceNumber'],
                            'items' => [
                                [
                                    'ItemDescription' => "Authorize card",
                                    'ItemQuantity' => "1",
                                    'ItemPrice' => "1",
                                    'IsTaxFree' => "false",
                                ],
                            ]
                        ]);
                        if ($captureChargeResponse && !$captureChargeResponse['HasError']) {
                            $refundResponse = $this->getTransactionByReferenceID($captureChargeResponse['ReferenceNumber']);

                            if ($refundResponse && !$refundResponse['HasError']) {
                                $cardType = $this->cardBrandNameByCode($ZCreditTrx['CardBrandCode']);

                                $card = ClientCard::create([
                                    'client_id'   => $client->id,
                                    'card_number' => $ZCreditTrx['CardNumber'],
                                    'card_type'   => $cardType,
                                    'card_holder_id' => $ZCreditTrx['HolderID'],
                                    'card_holder_name' => $ZCreditTrx['CustomerName'],
                                    'valid'       => $ZCreditTrx['ExpDate_MMYY'],
                                    'cc_charge'   => $ZCreditTrx['TransactionSum'],
                                    'card_token'  => $ZCreditTrx['Token'],
                                ]);

                                if ($belongToContract && $contract) {
                                    $contract->update([
                                        'card_id' => $card->id
                                    ]);

                                    Contract::query()
                                        ->where('client_id', $client->id)
                                        ->where('status', ContractStatusEnum::VERIFIED)
                                        ->whereNull('card_id')
                                        ->update([
                                            'card_id' => $card->id
                                        ]);
                                } else {
                                    Order::query()
                                        ->where('client_id', $client->id)
                                        ->where('status', 'Open')
                                        ->where('paid_status', OrderPaidStatusEnum::PROBLEM)
                                        ->update([
                                            'paid_status' => OrderPaidStatusEnum::UNPAID
                                        ]);
                                }

                                if (
                                    !ClientCard::where('client_id', $card->client_id)
                                        ->where('is_default', true)
                                        ->exists()
                                ) {
                                    $card->update(['is_default' => true]);
                                }

                                $transaction->update([
                                    'status' => TransactionStatusEnum::COMPLETED,
                                    'transaction_id' => $captureChargeResponse['ReferenceNumber'],
                                    'transaction_at' => now(),
                                    'metadata' => [
                                        'card_type' => $cardType,
                                        'card_exp' => $ZCreditTrx['ExpDate_MMYY'],
                                        'card_number' => $ZCreditTrx['CardNumber'],
                                        'card_holder_id' => $ZCreditTrx['HolderID'],
                                        'card_holder_name' => $ZCreditTrx['CustomerName'],
                                    ],
                                ]);
                            }
                        }
                    }
                }
            } else {
                $transaction->update([
                    'status' => TransactionStatusEnum::FAILED
                ]);
            }
        }
    }
}
