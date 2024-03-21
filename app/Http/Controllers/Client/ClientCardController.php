<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ClientCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientCardController extends Controller
{
    public function index()
    {
        $res = ClientCard::where('client_id', Auth::user()->id)->get();

        return response()->json([
            'res' => $res,
        ]);
    }

    public function update(Request $request)
    {
        if (isset($request->cdata['cid'])) {
            $cc = ClientCard::query()
                ->select('cc_charge')
                ->find($request->cdata['cid']);

            $nc = (int)$cc->cc_charge +  (int)$request->cdata['cc_charge'];
            $args = [
                'card_type'   => $request->cdata['card_type'],
                'card_number' => $request->cdata['card_number'],
                'valid'       => $request->cdata['valid'],
                'cvv'         => $request->cdata['cvv'],
                'cc_charge'   => $nc,
                'card_token'  => $request->cdata['card_token'],
            ];

            $cc->update($args);
        } else {
            $args = [
                'card_type'   => $request->cdata['card_type'],
                'client_id'   => Auth::user()->id,
                'card_number' => $request->cdata['card_number'],
                'valid'       => $request->cdata['valid'],
                'cvv'         => $request->cdata['cvv'],
                'cc_charge'   => $request->cdata['cc_charge'],
                'card_token'  => $request->cdata['card_token'],
            ];

            $card = ClientCard::create($args);

            if (!ClientCard::where('client_id', $card->client_id)->where('is_default', true)->exists()) {
                $card->update(['is_default' => true]);
            }
        }

        return response()->json([
            'message' => "Card validated successfully"
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
