<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\Admin\LoginOtpMail;
use Illuminate\Support\Str;
use Twilio\Rest\Client as TwilioClient;

class AuthController extends Controller
{
    /** 
     * Login api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function login(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        // Authenticate the admin
        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Auth::guard('admin')->attempt(['email' => $request->email, 'password' => $request->password])) {
            return response()->json(['errors' => ['email' => 'These credentials do not match our records.']]);
        }

        // Generate 6-digit numeric OTP
        $otp = strval(random_int(100000, 999999)); // Generates a random 6-digit number

        // Send OTP via email
        if ($admin->two_factor_enabled) {
            try {
                $admin->otp = $otp;
                $admin->otp_expiry = now()->addMinutes(10); 
                $admin->save();

                Mail::to($admin->email)->send(new LoginOtpMail($otp)); 

                // Send OTP via SMS using Twilio
                $twilioAccountSid = config('services.twilio.twilio_id');
                $twilioAuthToken = config('services.twilio.twilio_token');
                $twilioPhoneNumber = config('services.twilio.twilio_number');

                $twilioClient = new TwilioClient($twilioAccountSid, $twilioAuthToken);
                $phone_number = '+91'.$admin->phone;
                
                $twilioClient->messages->create(
                    $phone_number,
                    ['from' => $twilioPhoneNumber, 'body' => 'Your OTP for login: ' . $otp]
                );
            
                return response()->json([
                    "two_factor_enabled" =>$admin->two_factor_enabled,
                    "email" => $admin->email,
                    'message' => 'OTP sent to your email and phone number for verification'
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'errors' => ['otp' => 'Failed to send OTP. Please try again.'],
                    'exception' => $e->getMessage()
                ], 500);
            }
        } else {
            // Login without OTP
            $admin = Admin::find(auth()->guard('admin')->user()->id);
            $admin->token = $admin->createToken('Admin', ['admin'])->accessToken;

            return response()->json($admin);
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
    
        $admin = Admin::where('otp', $request->otp)
                        ->where('otp_expiry', '>=', now())
                        ->first();
    
        if (!$admin) {
            return response()->json(['errors' => ['otp' => 'Invalid OTP or OTP expired']]);
        }
    
        // Clear OTP after successful verification
        $admin->otp = null;
        $admin->otp_expiry = null;
        $admin->save();
    
        // Generate token for the authenticated admin
        $admin->token = $admin->createToken('Admin', ['admin'])->accessToken;
    
        return response()->json($admin);
    }

    public function resendOtp(Request $request)
    {
        // Authenticate the admin
        $admin = Admin::where('email', $request->email)->first();

        if (!$admin) {
            return response()->json(['errors' => ['user' => 'User not found']], 401);
        }

        $otp = strval(random_int(100000, 999999));
        try {
            $admin->otp = $otp;
            $admin->otp_expiry = now()->addMinutes(10);
            $admin->save();

            Mail::to($admin->email)->send(new LoginOtpMail($otp));

            // Send OTP via SMS using Twilio
            $twilioAccountSid = config('services.twilio.twilio_id');
            $twilioAuthToken = config('services.twilio.twilio_token');
            $twilioPhoneNumber = config('services.twilio.twilio_number');

            $twilioClient = new TwilioClient($twilioAccountSid, $twilioAuthToken);
            $phone_number = '+91'.$admin->phone;
            
            $twilioClient->messages->create(
                $phone_number,
                ['from' => $twilioPhoneNumber, 'body' => 'Your OTP for login: ' . $otp]
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
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:admins'],
            'password'  => ['required', 'string', 'min:6'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $input                  = $request->all();
        $input['password']      = bcrypt($input['password']);
        $admin                  = Admin::create($input);
        $admin->token           = $admin->createToken('Admin', ['admin'])->accessToken;

        return response()->json($admin);
    }

    /** 
     * details api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function details()
    {
        $admin = Auth::user();
        return response()->json(['success' => $admin]);
    }

    public function logout()
    {
        $user = Auth::user()->token();
        $user->revoke();
        return response()->json(['success' => 'Logged Out Successfully!']);
    }

    
}
