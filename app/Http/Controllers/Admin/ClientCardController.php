<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientCard;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Traits\JobSchedule;
use App\Traits\PaymentAPI;

class ClientCardController extends Controller
{
    use JobSchedule, PaymentAPI;

    public function index($id)
    {
        $cards = ClientCard::where('client_id', $id)->get();

        return response()->json([
            'cards' => $cards
        ]);
    }

    public function createClientCardSession($id)
    {
        $client = Client::with('property_addresses')->find($id);

        if (!$client) {
            return response()->json([
                'message' => 'Client not found!',
            ], 404);
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

        $sessionResponse = $this->createSession([
            'unique_id'     => 'BROOM-TX' . $transaction->id,
            'success_url'   => url('thanks/' . $client->id),
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
                    "Description"   => 'card validation transaction',
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

    public function checkTranxBySessionId(Request $request, $id)
    {
        $transaction = Transaction::query()
            ->where('session_id', $request->get('session_id'))
            ->where('client_id', $id)
            ->first();

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction not found!',
            ], 404);
        }

        return response()->json([
            'status' => $transaction->status,
        ]);
    }

    public function markDefault($clientID, $id)
    {
        $card = ClientCard::query()
            ->where('client_id', $clientID)
            ->find($id);

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

    public function destroy($clientID, $id)
    {
        $card = ClientCard::query()
            ->where('client_id', $clientID)
            ->find($id);

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
