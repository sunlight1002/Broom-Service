<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatusEnum;
use App\Models\Client;
use App\Models\ClientCard;
use App\Models\Transaction;
use App\Traits\PaymentAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    use PaymentAPI;

    public function callback(Request $request)
    {
        Log::info('ZCREDIT CALLABLE...');
        $json = file_get_contents('php://input');

        file_put_contents('/var/www/html/broom-service/zcredit.txt', $json);

        $data = json_decode($json, true);

        if (isset($data['SessionId']) && $data['ReferenceNumber']) {
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

                    $captureChargeResponse = $this->chargeCard([
                        'card_number' => $ZCreditTrx['Token'],
                        'card_cvv' => '',
                        'card_exp' => '',
                        'amount' => $transaction->amount,
                        'client_name' => $client->firstname . ' ' . $client->lastname,
                        'client_address' => $address ? $address->geo_address : '',
                        'client_email' => $client->email,
                        'client_phone' => $client->phone,
                        'J' => 0,
                        'obeligo_action' => 1,
                        'original_zcredit_reference_number' => $ZCreditTrx['ReferenceNumber']
                    ]);

                    if ($captureChargeResponse && !$captureChargeResponse['HasError']) {
                        $refundResponse = $this->refundByReferenceID($captureChargeResponse['ReferenceNumber']);

                        if ($refundResponse && !$refundResponse['HasError']) {
                            $cardType = $this->cardBrandNameByCode($ZCreditTrx['CardBrandCode']);

                            $card = ClientCard::create([
                                'client_id'   => $client->id,
                                'card_number' => $ZCreditTrx['CardNumber'],
                                'card_type'   => $cardType,
                                'card_holder' => $ZCreditTrx['HolderID'],
                                'valid'       => $ZCreditTrx['ExpDate_MMYY'],
                                'cc_charge'   => $ZCreditTrx['TransactionSum'],
                                'card_token'  => $ZCreditTrx['Token'],
                            ]);

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
                                    'card_holder' => $ZCreditTrx['HolderID'],
                                ],
                            ]);
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
