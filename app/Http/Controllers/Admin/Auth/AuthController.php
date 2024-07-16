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
        // try {
            $admin->otp = $otp;
            $admin->otp_expiry = now()->addMinutes(10); 
            $admin->save();

            Mail::to($admin->email)->send(new LoginOtpMail($otp)); 

            return response()->json([
                $admin,
                'message' => 'OTP sent to your email for verification'
            ]);
        // } catch (\Exception $e) {
        //     \Log::error('Error sending OTP email: ' . $e);
        //     return response()->json(['error' => 'Failed to send OTP email'], 500);
        // }
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

        // $admin->otp = null;
        // $admin->otp_expiry = null;
        // $admin->save();
        $admin = Admin::find(auth()->guard('admin')->user()->id);
        $accessToken = $admin->createToken('Admin', ['admin'])->accessToken;

        return response()->json(['admin' => $admin, 'access_token' => $accessToken]);
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
