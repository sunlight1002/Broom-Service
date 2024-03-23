<?php

namespace App\Http\Controllers\Client;

use App\Enums\TransactionStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\ClientCard;
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

    public function createCardSession()
    {
        $client = Auth::user();

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
            'unique_id' => 'BROOM-TX' . $transaction->id,
            'client_name' => $client->firstname . ' ' . $client->lastname,
            'client_email' => $client->email,
            'client_phone' => $client->phone,
            'card_items' => [
                [
                    'Amount' => $amount,
                    'Currency' => "ILS",
                    'Name' => "Validate credit card",
                    'Quantity' => 1,
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
            'redirect_url' => $sessionResponse['Data']['SessionUrl']
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
