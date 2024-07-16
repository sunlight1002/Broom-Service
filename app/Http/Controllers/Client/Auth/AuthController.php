<?php

namespace App\Http\Controllers\Client\Auth;

use App\Enums\LeadStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\Client\LoginOtpMail;

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
                if($client->two_factor_enabled){
                    $otp = strval(random_int(100000, 999999)); // Generates a random 6-digit number

                    $client->otp = $otp;
                    $client->otp_expiry = now()->addMinutes(10); 
                    $client->save();

                    Mail::to($client->email)->send(new LoginOtpMail($otp)); 

                    return response()->json([
                        $client->two_factor_enabled,
                        'message' => 'OTP sent to your email for verification'
                    ]);
                }else{ 
                    $client->token = $client->createToken('Client', ['client'])->accessToken;
                    return response()->json($client);
                }
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

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => ['required', 'string', 'digits:6'],
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }
    
        $client = Client::where('otp', $request->otp)
                      ->where('otp_expiry', '>=', now())
                      ->first();
    
        if (!$client) {
            return response()->json(['errors' => ['otp' => 'Invalid OTP or OTP expired']]);
        }
    
        // Clear OTP after successful verification
        $client->otp = null;
        $client->otp_expiry = null;
        $client->save();
    
        // Generate token for the authenticated admin
        $client->token = $client->createToken('Client', ['client'])->accessToken;
    
        return response()->json($admin);
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
            ['lead_status' => LeadStatusEnum::PENDING]
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
