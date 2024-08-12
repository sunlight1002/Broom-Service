<?php

namespace App\Http\Controllers\Client\Auth;


use App\Enums\LeadStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Twilio\Rest\Client as TwilioClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\Client\LoginOtpMail;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use App\Models\DeviceToken;


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
            $client = Auth::guard('client')->user();
        
             DeviceToken::where('tokenable_id', $client->id)
             ->where('tokenable_type', Client::class)
             ->where('expires_at', '<', now())
             ->delete();

             $rememberDeviceToken = $request->cookie('remember_device_token');
             if ($rememberDeviceToken) {
                 $storedToken = DeviceToken::where('tokenable_id', $client->id)
                     ->where('tokenable_type', Client::class)
                     ->where('token', $rememberDeviceToken)
                     ->where('expires_at', '>', now())
                     ->first();
                     if ($storedToken) {
                        // Device is remembered
                        $client->token = $client->createToken('Client', ['client'])->accessToken;
                        return response()->json($client);
                    } 
                }

            if ($client->status == 2) {
                if($client->two_factor_enabled){
                    try {
                        $otp = strval(random_int(100000, 999999)); // Generates a random 6-digit number

                        $client->otp = $otp;
                        $client->otp_expiry = now()->addMinutes(10); 
                        $client->save();

                        $emailSent = false;
                        $smsSent = false;
                        $emailError = null;
                        $smsError = null;
        
                        try {
                            // Send OTP via email
                            Mail::to($client->email)->send(new LoginOtpMail($otp, $client));
                            $emailSent = true;
                        } catch (\Exception $e) {
                            $emailError = $e->getMessage();
                        }
        
                        // Send OTP via SMS using Twilio
                        App::setLocale($client->lng);
                        $otpMessage = __('mail.otp.body', ['otp' => $otp]);
    
                        $twilioAccountSid = config('services.twilio.twilio_id');
                        $twilioAuthToken = config('services.twilio.twilio_token');
                        $twilioPhoneNumber = config('services.twilio.twilio_number');
    
                        $twilioClient = new TwilioClient($twilioAccountSid, $twilioAuthToken);
                        $phone_number = '+'.$client->phone;
                    
                        $twilioClient->messages->create(
                            $phone_number,
                            ['from' => $twilioPhoneNumber, 'body' => $otpMessage]
                        );
                        $smsSent = true;
                    } catch (\Exception $e) {
                        $smsError = $e->getMessage();
                    }
    
                    if ($emailSent && $smsSent) {
                        return response()->json([
                            "two_factor_enabled" => $client->two_factor_enabled,
                            "email" => $client->email,
                            "lng" => $client->lng,
                            'message' => 'OTP sent to your email and phone number for verification'
                        ]);
                    } elseif ($emailSent) {
                        return response()->json([
                            "two_factor_enabled" => $client->two_factor_enabled,
                            "email" => $client->email,
                            "lng" => $client->lng,
                            'message' => 'OTP sent to your email for verification. Failed to send OTP via SMS.',
                            // 'errors' => ['sms' => $smsError]
                        ]);
                    } elseif ($smsSent) {
                        return response()->json([
                            "two_factor_enabled" => $client->two_factor_enabled,
                            "email" => $client->email,
                            "lng" => $client->lng,
                            'message' => 'OTP sent to your phone number for verification. Failed to send OTP via email.',
                            // 'errors' => ['email' => $emailError]
                        ]);
                    } else {
                        return response()->json([
                            'errors' => ['otp' => 'Failed to send OTP via both email and SMS.'],
                            'email_error' => $emailError,
                            'sms_error' => $smsError
                        ], 500);
                    }
                } else {
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
            'remember_device' => 'boolean',
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

        if ($request->remember_device) {
            $rememberDeviceToken = Str::random(60);
            DeviceToken::updateOrCreate(
                ['tokenable_id' => $client->id, 'tokenable_type' => get_class($client)],
                ['token' => $rememberDeviceToken, 'expires_at' => now()->addDays(30)]
            );
            Cookie::queue('remember_device_token', $rememberDeviceToken, 43200); // 30 days
        }

        $client->save();
    
        // Generate token for the authenticated admin
        $client->token = $client->createToken('Client', ['client'])->accessToken;
    
        return response()->json($client);
    }


    public function resendOtp(Request $request)
    {
        $client = Client::where('email', $request->email)->first();

        if (!$client) {
            return response()->json(['errors' => ['user' => 'User not authenticated']], 401);
        }


        $otp = strval(random_int(100000, 999999));
        try {
            $client->otp = $otp;
            $client->otp_expiry = now()->addMinutes(10);
            $client->save();

            Mail::to($client->email)->send(new LoginOtpMail($otp,$client));

            App::setLocale($client->lng);
            // Send OTP via SMS using Twilio
            $otpMessage = __('mail.otp.body', ['otp' => $otp]);

            $twilioAccountSid = config('services.twilio.twilio_id');
            $twilioAuthToken = config('services.twilio.twilio_token');
            $twilioPhoneNumber = config('services.twilio.twilio_number');

            $twilioClient = new TwilioClient($twilioAccountSid, $twilioAuthToken);
            $phone_number = '+'.$client->phone;
            
            $twilioClient->messages->create(
                $phone_number,
                ['from' => $twilioPhoneNumber, 'body' => $otpMessage]
            );

            return response()->json([
                'message' => 'OTP sent to your email and phone number for verification'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => ['otp' => 'Failed to send OTP. Please try again.'],
                'exception' => $e->getMessage()
            ], 500);
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
