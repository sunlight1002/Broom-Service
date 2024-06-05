<?php

namespace App\Http\Controllers\Client\Auth;

use App\Enums\LeadStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /** 
     * Login api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'     => ['required', 'string', 'email', 'max:255'],
            'password'  => ['required', 'string', 'min:6'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->messages()
            ]);
        }

        if (Auth::guard('client')->attempt([
            'email'     => $request->email,
            'password'  => $request->password
        ])) {
            $client = Client::find(auth()->guard('client')->user()->id);
            if ($client->status == 2) {
                $client->token = $client->createToken('Client', ['client'])->accessToken;
                return response()->json($client);
            } else {
                return response()->json([
                    'errors' => [
                        'email' => 'These credentials are not authorized to login for now. Please contact administrator.'
                    ]
                ]);
            }
        } else {
            return response()->json([
                'errors' => [
                    'email' => 'These credentials do not match our records.'
                ]
            ]);
        }
    }
    /** 
     * Register api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => ['required', 'string', 'max:255'],
            'address'   => ['required', 'string'],
            'role'      => ['required', 'string'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:Clients'],
            'password'  => ['required', 'string', 'min:6'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $input                  = $request->all();
        $input['status']        = 0;
        $input['password']      = bcrypt($input['password']);
        $input['passcode']      = $input['password'];
        $Client                   = Client::create($input);
        $Client->token            = $Client->createToken('Client', ['Client'])->accessToken;

        $Client->lead_status()->updateOrCreate(
            [],
            ['lead_status' => LeadStatusEnum::PENDING_LEAD]
        );

        return response()->json($Client);
    }
    /** 
     * Client Detail api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function details()
    {
        $Client = Auth::Client();
        return response()->json([
            'success' => $Client
        ]);
    }

    public function logout()
    {
        $user = Auth::user()->token();
        $user->revoke();
        return response()->json([
            'success' => 'Logged Out Successfully!'
        ]);
    }
}
