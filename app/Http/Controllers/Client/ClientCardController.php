<?php

namespace App\Http\Controllers\Client;

use App\Enums\TransactionStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\ClientCard;
use App\Models\Contract;
use App\Models\Transaction;
use App\Traits\PaymentAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientCardController extends Controller
{
    use PaymentAPI;

    public function index()
    {
        $res = ClientCard::where('client_id', Auth::user()->id)->get();

        return response()->json([
            'res' => $res,
        ]);
    }

    public function createCardSession($contractHash = NULL)
    {
        if ($contractHash) {
            $belongToContract = true;
            $contract = Contract::where('unique_hash', $contractHash)->latest()->first();
            if (!$contract) {
                return response()->json([
                    'message' => "Contract not found"
                ], 404);
            }

            $client = $contract->client;
            if (!$client) {
                return response()->json([
                    'message' => "Client not found"
                ], 404);
            }
        } else {
            $belongToContract = false;
            if (!Auth::check()) {
                return response()->json([
                    'message' => "Client not autenticated"
                ], 401);
            }

            $client = Auth::user();
        }

        $amount = 1.00;

        $transaction = Transaction::create([
            'client_id' => $client->id,
            'amount' => $amount,
            'currency' => config('services.app.currency'),
            'status' => TransactionStatusEnum::INITIATED,
            'type' => 'deposit',
            'description' => 'Validate credit card',
            'source' => 'credit-card',
            'destination' => 'merchant',
            'metadata' => [],
            'gateway' => 'zcredit'
        ]);

        $uniqueID = $belongToContract ? 'BROOM-CNTRCT' . $contract->id : 'BROOM-TX' . $transaction->id;
        $successUrl = $belongToContract ? url('thanks/' . $client->id) : url('client/settings?cps=payment-success');

        $sessionResponse = $this->createSession([
            'unique_id'     => $uniqueID,
            'success_url'   => $successUrl,
            'local'         => ($client->lng == 'heb') ? 'He' : 'En',
            'client_id'     => $client->id,
            'client_name'   => $client->firstname . ' ' . $client->lastname,
            'client_email'  => $client->email,
            'client_phone'  => $client->phone,
            'card_items'    => [
                [
                    'Amount'        => $amount,
                    'Currency'      => "ILS",
                    'Name'          => (($client->lng == 'heb') ? "הוספת כרטיס - " : "Add a Card - ") . $client->firstname . " " . $client->lastname,
                    "Description"   => (($client->lng == 'heb') ? 'הוספת כ"א באופן מאובטח' : 'card validation transaction'),
                    'Quantity'      => 1,
                    "Image"         => "https://i.ibb.co/m8fr72P/sample.png",
                    "IsTaxFree"     => "false",
                    "AdjustAmount"  => "false"
                ],
            ]
        ]);

        if ($sessionResponse && $sessionResponse['HasError']) {
            return response()->json([
                'message' => "Error while initiating session"
            ], 500);
        }

        $transaction->update([
            'session_id' => $sessionResponse['Data']['SessionId'],
        ]);

        return response()->json([
            'redirect_url' => $sessionResponse['Data']['SessionUrl'],
            'session_id' => $sessionResponse['Data']['SessionId']
        ]);
    }

    public function checkContractCard($contractHash)
    {
        $contract = Contract::with('card')
            ->where('unique_hash', $contractHash)
            ->latest()
            ->first();

        if (!$contract) {
            return response()->json([
                'message' => "Contract not found"
            ], 404);
        }

        return response()->json([
            'card' => $contract->card,
        ]);
    }

    public function markDefault($id)
    {
        $card = ClientCard::find($id);

        if (!$card) {
            return response()->json([
                'message' => "Card not found"
            ], 404);
        }

        ClientCard::query()
            ->where('client_id', $card->client_id)
            ->where('id', '!=', $card->id)
            ->update([
                'is_default' => false
            ]);

        $card->update(['is_default' => true]);

        return response()->json([
            'message' => "Card marked as default successfully"
        ]);
    }

    public function destroy($id)
    {
        $card = ClientCard::find($id);

        if (!$card) {
            return response()->json([
                'message' => "Card not found"
            ], 404);
        }

        if ($card->is_default) {
            return response()->json([
                'message' => "Card is marked as default. Can't be deleted."
            ], 404);
        }

        $card->delete();

        return response()->json([
            'message' => "Card deleted successfully"
        ]);
    }
}
