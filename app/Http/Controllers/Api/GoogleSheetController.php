<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Http\Response;


class GoogleSheetController extends Controller
{
    public function getClientInfo(Request $request)
    {
        $client = Client::when($request->has('id'), function ($q) use ($request) {
            $q->where('id', $request->get('id'));
        })->when($request->has('email'), function ($q) use ($request) {
            $q->where('email', $request->get('id'));
        })->with(['offers' => function ($q) use ($request) {
            if ($request->has('offer_id')) {
                $q->where('id', $request->get('offer_id'));
            }
        }, 'jobs', 'property_addresses', 'lead_status'])->first();



        return response()->json(
            $client
        );
    }

    public function getWorkers()
    {
        $sheetsName = [
            "22" => "ויקטוריה",
            "26" => "גלדיס",
            "45" => "דימה",
            "67" => "ילנה",
            "81" => "סינטיצ",
            "87" => "סאצין",
            "117" => "ארתור",
            "119" => "מיכאלה",
            "120" => "גידלין",
            "122" => "קליטוס",
            "123" => "ויליאם",
            "124" => "יבגניה",
            "125" => "אניה",
            "130" => "לייסן",
            "132" => "אליס",
            "133" => "אינה",
            "142" => "פריאנקה",
            "144" => "איירין",
            "146" => "ולדימיר",
            "147" => "וסיליי",
            "151" => "אופליה",
            "159" => "ולדימיר 2",
            "166" => "אמינה",
            "184" => "ליובה",
            "185" => "ארתיום",
            "186" => "אניה 2",
            "187" => "אוקסנה",
            "188" => "ליידה",
            "189" => "אזת",
            "191" => "חנה",
            "192" => "אניה 3",
            "196" => "טורי",
            "197" => "סיבי",
            "198" => "אטלברט",
            "203" => "מקסים",
            "121" => "יוליה",
            "201" => "טרנס",
            "202" => "דדונו",
        ];
        $users = User::where('status', '!=' , 0)->get();

        $users = $users->map(function ($user) use ($sheetsName) {
            // Check if a sheet name exists for this user's id.
            // Casting $user->id to string is optional if you're sure about types.
            $key = (string) $user->id;
            $user->sheet_name = isset($sheetsName[$key]) ? $sheetsName[$key] : null;
            return $user;
        });

        return response()->json($users);
    }
}
