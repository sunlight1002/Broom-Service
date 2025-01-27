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
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Contracts\ResetPasswordViewResponse;


class AuthController extends Controller
{
    // use SendsPasswordResetEmails;

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

    // public function sendResetLinkEmail(Request $request)
    // {
    //     \Log::info($request->email);

    //     // Validate the incoming request
    //     $request->validate([
    //         'email' => 'required|email|exists:clients,email',
    //     ]);

    //     // Attempt to send the reset link for clients
    //     $status = Password::broker('clients')->sendResetLink(
    //         ['email' => $request->email]
    //     );
    
    //     \Log::info($status);

    //     // Return the response based on the result
    //     return $status === Password::RESET_LINK_SENT
    //         ? response()->json(['message' => __($status)], 200)
    //         : response()->json(['message' => __($status)], 400);
    // }

    // public function showResetForm(Request $request, $token = null)
    // {
    //     return response()->json([
    //         'token' => $token,
    //         'email' => $request->email, 
    //     ]);
    // }
    
    // public function resetPassword(Request $request)
    // {
    //     // Validate incoming request
    //     $request->validate([
    //         'email' => 'required|email|exists:clients,email',
    //         'password' => 'required|confirmed|min:8',
    //         'token' => 'required',
    //     ]);

    //     // Attempt to reset the password
    //     $status = Password::broker('clients')->reset(
    //         $request->only('email', 'password', 'password_confirmation', 'token'),
    //         function ($client) use ($request) {
    //             // Update the client's password
    //             $client->forceFill([
    //                 'password' => Hash::make($request->password),
    //                 'passcode' => $request->password
    //             ])->save();
    //         }
    //     );

    //     // Return the response based on the result
    //     return $status === Password::PASSWORD_RESET
    //         ? redirect()->route('login')->with('status', __('Your password has been reset!'))
    //         : back()->withErrors(['email' => [__($status)]]);
    // }

    // public function updatePassword(Request $request)
    // {
    //     // Validate the incoming request
    //     $request->validate([
    //         'email' => 'required|email|exists:clients,email',
    //         'password' => 'required|string|min:6|confirmed',
    //         'token' => 'required'
    //     ]);

    //     // Check the reset token
    //     $status = Password::broker('clients')->reset(
    //         $request->only('email', 'password', 'token'),
    //         function ($client, $password) {
    //             // Update the password
    //             $client->forceFill([
    //                 'password' => Hash::make($password),
    //                 'passcode' => $password
    //             ])->save();
    //         }
    //     );

    //     // Return a response based on the result
    //     if ($status === Password::PASSWORD_RESET) {
    //         return redirect()->route('client/login')->with('status', 'Your password has been reset successfully.');
    //     } else {
    //         return back()->withErrors(['email' => [trans($status)]]);
    //     }
    // }

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

         // Initialize rememberDeviceToken to null
         $rememberDeviceToken = null;
         
        if ($request->remember_device) {
            $rememberDeviceToken = Str::random(60);
            DeviceToken::updateOrCreate(
                ['tokenable_id' => $client->id, 'tokenable_type' => get_class($client)],
                ['token' => $rememberDeviceToken, 'expires_at' => now()->addDays(30)]
            );
        }

        $client->save();
    
        // Generate token for the authenticated admin
        $client->token = $client->createToken('Client', ['client'])->accessToken;
        $response = [
            'client' => $client,
        ];
    
        // Add remember_token to response only if it exists
        if ($rememberDeviceToken) {
            $response['remember_token'] = $rememberDeviceToken;
        }
    
        return response()->json($response);
    }


    public function resendOtp(Request $request)
    {
        // Retrieve the client by email
        $client = Client::where('email', $request->email)->first();
    
        if (!$client) {
            return response()->json(['errors' => ['user' => 'User not authenticated']], 401);
        }
    
        // Generate a new OTP and set the expiration time
        $otp = strval(random_int(100000, 999999));
        $client->otp = $otp;
        $client->otp_expiry = now()->addMinutes(10);
        $client->save();
    
        $emailSent = false;
        $smsSent = false;
        $emailError = null;
        $smsError = null;
    
        // Attempt to send the OTP via email
        try {
            Mail::to($client->email)->send(new LoginOtpMail($otp, $client));
            $emailSent = true;
        } catch (\Exception $e) {
            $emailError = $e->getMessage();
        }
    
        // Attempt to send the OTP via SMS using Twilio
        try {
            App::setLocale($client->lng);
            $otpMessage = __('mail.otp.body', ['otp' => $otp]);
    
            $twilioAccountSid = config('services.twilio.twilio_id');
            $twilioAuthToken = config('services.twilio.twilio_token');
            $twilioPhoneNumber = config('services.twilio.twilio_number');
    
            $twilioClient = new TwilioClient($twilioAccountSid, $twilioAuthToken);
            $phone_number = '+' . $client->phone;
    
            $twilioClient->messages->create(
                $phone_number,
                ['from' => $twilioPhoneNumber, 'body' => $otpMessage]
            );
            $smsSent = true;
        } catch (\Exception $e) {
            $smsError = $e->getMessage();
        }
    
        // Determine the appropriate response based on the success or failure of each attempt
        if ($emailSent && $smsSent) {
            return response()->json(['message' => 'OTP sent to your email and phone number for verification']);
        } elseif ($emailSent) {
            return response()->json([
                'message' => 'OTP sent to your email. Failed to send OTP via SMS.',
                // 'sms_error' => $smsError
            ]);
        } elseif ($smsSent) {
            return response()->json([
                'message' => 'OTP sent to your phone number. Failed to send OTP via email.',
                // 'email_error' => $emailError
            ]);
        } else {
            return response()->json([
                'errors' => ['otp' => 'Failed to send OTP via both email and SMS.'],
                // 'email_error' => $emailError ?? null,
                // 'sms_error' => $smsError ?? null
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'new_password' => 'required|string|min:6|confirmed', // 'confirmed' ensures new_password == confirm_password
        ]);
    
        // Handle validation errors
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
    
        try {
            $clientId = $request->id;
    
            // Fetch the client
            $client = Client::findOrFail($clientId);
    
            // Update the password and passcode
            $client->password = bcrypt($request->new_password);
            $client->passcode = $request->new_password; // Assuming passcode is stored in plain text
            $client->first_login = false;
            $client->save();
    
            return response()->json([
                'message' => 'Password updated successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the password.',
                'error' => $e->getMessage()
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
