<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\TeamMember;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\Admin\LoginOtpMail;
use Illuminate\Support\Str;
use Twilio\Rest\Client as TwilioClient;
use Illuminate\Support\Facades\Cookie;

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
            DeviceToken::where('tokenable_id', $admin->id)
            ->where('tokenable_type', Admin::class)
            ->where('expires_at', '<', now())
            ->delete();

        $rememberDeviceToken = $request->cookie('remember_device_token');
        if ($rememberDeviceToken) {
            $storedToken = DeviceToken::where('tokenable_id', $admin->id)
                ->where('tokenable_type', Admin::class)
                ->where('token', $rememberDeviceToken)
                ->where('expires_at', '>', now())
                ->first();
                if ($storedToken) {
                // Device is remembered
                $admin->token = $admin->createToken('Admin', ['admin'])->accessToken;
                return response()->json($admin);
            } 
        }
    
        // Generate 6-digit numeric OTP
        $otp = strval(random_int(100000, 999999)); // Generates a random 6-digit number
        
        // Send OTP via email and SMS if two-factor authentication is enabled and SMS if two-factor authentication is enabled
        if ($admin->two_factor_enabled) {

            // Save OTP and expiry to the database
            $admin->otp = $otp;
            $admin->otp_expiry = now()->addMinutes(10); 
            $admin->save();
            
            $emailSent = false;
            $smsSent = false;

            try {
                // Send OTP via email
                Mail::to($admin->email)->send(new LoginOtpMail($otp, $admin));
                $emailSent = true;
            } catch (\Exception $e) {
                $emailError = $e->getMessage();
            }
    

            // Save OTP and expiry to the database
            $admin->otp = $otp;
            $admin->otp_expiry = now()->addMinutes(10); 
            $admin->save();
            
            $emailSent = false;
            $smsSent = false;

            try {
                // Send OTP via email
                Mail::to($admin->email)->send(new LoginOtpMail($otp, $admin));
                $emailSent = true;
            } catch (\Exception $e) {
                $emailError = $e->getMessage();
            }
    
            try {
                // Send OTP via SMS using Twilio
                $twilioAccountSid = config('services.twilio.twilio_id');
                $twilioAuthToken = config('services.twilio.twilio_token');
                $twilioPhoneNumber = config('services.twilio.twilio_number');
    
                $twilioClient = new TwilioClient($twilioAccountSid, $twilioAuthToken);
                $phone_number = '+'.$admin->phone;
                
                $twilioClient->messages->create(
                    $phone_number,
                    ['from' => $twilioPhoneNumber, 'body' => 'Your OTP for login: ' . $otp]
                );
                $smsSent = true;
            } catch (\Exception $e) {
                $smsError = $e->getMessage();
            }
    
            // Return response based on the results of email and SMS sending
            if ($emailSent && $smsSent) {
                return response()->json([
                    "two_factor_enabled" => $admin->two_factor_enabled,
                    "email" => $admin->email,
                    "lng" => $admin->lng,
                    'message' => 'OTP sent to your email and phone number for verification'
                ]);
            } elseif ($emailSent) {
                return response()->json([
                    "two_factor_enabled" => $admin->two_factor_enabled,
                    "email" => $admin->email,
                    "lng" => $admin->lng,
                    'message' => 'OTP sent to your email for verification. Failed to send OTP via SMS.',
                    // 'errors' => ['sms' => $smsError]
                ]);
            } elseif ($smsSent) {
                return response()->json([
                    "two_factor_enabled" => $admin->two_factor_enabled,
                    "email" => $admin->email,
                    "lng" => $admin->lng,
                    'message' => 'OTP sent to your email for verification. Failed to send OTP via SMS.',
                    // 'errors' => ['sms' => $smsError]
                ]);
            } elseif ($smsSent) {
                return response()->json([
                    "two_factor_enabled" => $admin->two_factor_enabled,
                    "email" => $admin->email,
                    "lng" => $admin->lng,
                    'message' => 'OTP sent to your phone number for verification. Failed to send OTP via email.',
                    // 'errors' => ['email' => $emailError]
                ]);
            } else {
                return response()->json([
                    'errors' => ['otp' => 'Failed to send OTP via both email and SMS.'],
                    'email_error' => $emailError ?? null,
                    'sms_error' => $smsError ?? null
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
            'remember_device' => 'boolean',
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
        if ($request->remember_device) {
            $rememberDeviceToken = Str::random(60);
            DeviceToken::updateOrCreate(
                ['tokenable_id' => $admin->id, 'tokenable_type' => get_class($admin)],
                ['token' => $rememberDeviceToken, 'expires_at' => now()->addDays(30)]
            );
            Cookie::queue('remember_device_token', $rememberDeviceToken, 43200); // 30 days
        }
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

            Mail::to($admin->email)->send(new LoginOtpMail($otp,$admin));

            // Send OTP via SMS using Twilio
            $twilioAccountSid = config('services.twilio.twilio_id');
            $twilioAuthToken = config('services.twilio.twilio_token');
            $twilioPhoneNumber = config('services.twilio.twilio_number');

            $twilioClient = new TwilioClient($twilioAccountSid, $twilioAuthToken);
            $phone_number = '+'.$admin->phone;
            
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

