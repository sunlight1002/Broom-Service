<?php

namespace App\Http\Controllers\User\Auth;

use App\Enums\WorkerFormTypeEnum;
use App\Events\ContractFormSigned;
use App\Events\Form101Signed;
use App\Models\ManpowerCompany;
use App\Models\InsuranceCompany;
use App\Events\InsuranceFormSigned;
use App\Events\SafetyAndGearFormSigned;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Admin;
use App\Models\WorkerLeads;
use App\Enums\Form101FieldEnum;
use App\Models\WorkerInvitation;
use App\Models\WorkerAvailability;
use App\Services\WorkerFormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\Worker\LoginOtpMail;
use Carbon\Carbon;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use App\Models\DeviceToken;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Contracts\ResetPasswordViewResponse;

class AuthController extends Controller
{
    protected $workerFormService;

    public function __construct(WorkerFormService $workerFormService)
    {
        $this->workerFormService = $workerFormService;
    }

    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */

     public function login(Request $request)
     {
         $validator = Validator::make($request->all(), [
             'email' => ['required'],
             'password'  => ['required', 'string', 'min:6'],
         ]);

         if ($validator->fails()) {
             return response()->json(['errors' => $validator->messages()]);
         }

         if (Auth::attempt([
             'email'     => $request->email,
             'password'  => $request->password
         ])) {
             $user = User::find(auth()->user()->id);
             DeviceToken::where('tokenable_id', $user->id)
             ->where('tokenable_type', User::class)
             ->where('expires_at', '<', now())
             ->where('status', 1)
             ->delete();

             $rememberDeviceToken = $request->cookie('remember_device_token');
             if ($rememberDeviceToken) {
                 $storedToken = DeviceToken::where('tokenable_id', $user->id)
                     ->where('tokenable_type', User::class)
                     ->where('token', $rememberDeviceToken)
                     ->where('expires_at', '>', now())
                     ->where('status', 1)
                     ->first();
                     if ($storedToken) {
                        // Device is remembered
                        $user->token = $user->createToken('User', ['user'])->accessToken;
                        return response()->json($user);
                    }
                }

             if ($user->two_factor_enabled) {
                 $otp = strval(random_int(100000, 999999)); // Generate a random 6-digit number
                 $user->otp = $otp;
                 $user->otp_expiry = now()->addMinutes(10);
                 $user->save();

                 $emailSent = false;
                 $smsSent = false;
                 $emailError = null;
                 $smsError = null;

                 try {
                     Mail::to($user->email)->send(new LoginOtpMail($otp, $user));
                     $emailSent = true;
                 } catch (\Exception $e) {
                     $emailError = $e->getMessage();
                 }

                 try {
                     App::setLocale($user->lng);
                     // Send OTP via SMS using Twilio
                     $otpMessage = __('mail.otp.body', ['otp' => $otp]);

                     $twilioAccountSid = config('services.twilio.twilio_id');
                     $twilioAuthToken = config('services.twilio.twilio_token');
                     $twilioPhoneNumber = config('services.twilio.twilio_number');

                     $twilioClient = new Client($twilioAccountSid, $twilioAuthToken);
                     $phone_number = '+' . $user->phone;

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
                         "two_factor_enabled" => $user->two_factor_enabled,
                         "email" => $user->email,
                         "lng" => $user->lng,
                         'message' => 'OTP sent to your email and phone number for verification'
                     ]);
                 } elseif ($emailSent) {
                     return response()->json([
                         "two_factor_enabled" => $user->two_factor_enabled,
                         "email" => $user->email,
                         "lng" => $user->lng,
                         'message' => 'OTP sent to your email for verification. Failed to send OTP via SMS.',
                        //  'errors' => ['sms' => $smsError]
                     ]);
                 } elseif ($smsSent) {
                     return response()->json([
                         "two_factor_enabled" => $user->two_factor_enabled,
                         "email" => $user->email,
                         "lng" => $user->lng,
                         'message' => 'OTP sent to your phone number for verification. Failed to send OTP via email.',
                        //  'errors' => ['email' => $emailError]
                     ]);
                 } else {
                     return response()->json([
                         'errors' => ['otp' => 'Failed to send OTP via both email and SMS.'],
                         'email_error' => $emailError,
                         'sms_error' => $smsError
                     ], 500);
                 }
             } else {
                 $user->token = $user->createToken('User', ['user'])->accessToken;
                 return response()->json($user);
             }
         } else {
             return response()->json(['errors' => ['worker' => 'These credentials do not match our records.']]);
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

        $user = User::where('otp', $request->otp)
                    ->where('otp_expiry', '>=', now())
                    ->where('status', 1)
                    ->first();

        if (!$user) {
            return response()->json(['errors' => ['otp' => 'Invalid OTP or OTP expired']]);
        }

        // Clear OTP after successful verification
        $user->otp = null;
        $user->otp_expiry = null;

        $rememberDeviceToken = null;

        if ($request->remember_device) {
            $rememberDeviceToken = Str::random(60);
            DeviceToken::updateOrCreate(
                ['tokenable_id' => $user->id, 'tokenable_type' => get_class($user)],
                ['token' => $rememberDeviceToken, 'expires_at' => now()->addDays(30)]
            );
        }
        $user->save();

        // Generate token for the authenticated user
        $user->token = $user->createToken('User', ['user'])->accessToken;

        $response = [
            'user' => $user,
        ];

        // Add remember_token to response only if it exists
        if ($rememberDeviceToken) {
            $response['remember_token'] = $rememberDeviceToken;
        }

        return response()->json($response);
    }

    public function resendOtp(Request $request)
        {
            $user = User::where('email', $request->email)
            ->where('status', 1)
            ->first();

            if (!$user) {
                return response()->json(['errors' => ['user' => 'User not authenticated']], 401);
            }

            $otp = strval(random_int(100000, 999999));
            $user->otp = $otp;
            $user->otp_expiry = now()->addMinutes(10);
            $user->save();

            // Attempt to send OTP via Email
            try {
                Mail::to($user->email)->send(new LoginOtpMail($otp, $user));
                $emailSent = true;
            } catch (\Exception $e) {
                $emailSent = false;
                $emailError = $e->getMessage();
            }

            // Attempt to send OTP via SMS using Twilio
            try {
                App::setLocale($user->lng);
                $otpMessage = __('mail.otp.body', ['otp' => $otp]);

                $twilioAccountSid = config('services.twilio.twilio_id');
                $twilioAuthToken = config('services.twilio.twilio_token');
                $twilioPhoneNumber = config('services.twilio.twilio_number');

                $twilioClient = new Client($twilioAccountSid, $twilioAuthToken);
                $phone_number = '+' . $user->phone;

                $twilioClient->messages->create(
                    $phone_number,
                    ['from' => $twilioPhoneNumber, 'body' => $otpMessage]
                );
                $smsSent = true;
            } catch (\Exception $e) {
                $smsSent = false;
                $smsError = $e->getMessage();
            }

            // Return the appropriate response
            if ($emailSent && $smsSent) {
                return response()->json(['message' => 'OTP sent to your email and phone number for verification']);
            } elseif ($emailSent) {
                return response()->json([
                    'message' => 'OTP sent to your email. Failed to send OTP via SMS.',
                    'sms_error' => $smsError
                ]);
            } elseif ($smsSent) {
                return response()->json([
                    'message' => 'OTP sent to your phone number. Failed to send OTP via email.',
                    'email_error' => $emailError
                ]);
            } else {
                return response()->json([
                    'errors' => ['otp' => 'Failed to send OTP via both email and SMS.'],
                    'email_error' => $emailError ?? null,
                    'sms_error' => $smsError ?? null
                ], 500);
            }
        }



    public function sendResetLinkEmail(Request $request)
    {

        // Validate the incoming request
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Attempt to send the reset link for clients
        $status = Password::broker('users')->sendResetLink(
            ['email' => $request->email]
        );

        // Return the response based on the result
        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)], 200)
            : response()->json(['message' => __($status)], 400);
    }

    public function showResetForm(Request $request, $token = null)
    {
        // Custom logic, if needed
        return view('auth.worker-reset-password', ['token' => $token]);
    }

    public function resetPassword(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|confirmed|min:6',
            'token' => 'required',
        ]);

        // Attempt to reset the password
        $status = Password::broker('users')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                // Update the client's password
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'passcode' => $request->password
                ])->save();
            }
        );

        // Return the response based on the result
        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __('Your password has been reset!'))
            : back()->withErrors(['email' => [__($status)]]);
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
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'  => ['required', 'string', 'min:6'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $input                  = $request->all();
        $input['status']        = 0;
        $input['passcode']      = $input['password'];
        $input['password']      = bcrypt($input['password']);
        $user                   = User::create($input);
        $user->token            = $user->createToken('User', ['user'])->accessToken;

        return response()->json($user);
    }
    /**
     * User Detail api
     *
     * @return \Illuminate\Http\Response
     */
    public function details()
    {
        $user = Auth::user();
        return response()->json(['success' => $user]);
    }

    public function logout()
    {
        $user = Auth::user()->token();
        $user->revoke();
        return response()->json(['success' => 'Logged Out Successfully!']);
    }

    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address'   => ['required', 'string'],
            'phone'     => ['required'],
            'two_factor_enabled' => ['nullable', 'boolean'],
            'payment_type' => ['required', 'string'],
            'full_name' => ['required_if:payment_type,money_transfer'],
            'bank_name' => ['required_if:payment_type,money_transfer'],
            'bank_number' => ['required_if:payment_type,money_transfer'],
            'branch_number' => ['required_if:payment_type,money_transfer'],
            'account_number' => ['required_if:payment_type,money_transfer'],
        ], [
            'payment_type.required' => 'The payment type is required.',
            'full_name.required_if' => 'The full name is required.',
            'bank_name.required_if' => 'The bank name is required .',
            'bank_number.required_if' => 'The bank number is required.',
            'branch_number.required_if' => 'The branch number is required.',
            'account_number.required_if' => 'The account number is required.',
            'manpower_company_id.required_if' => 'Manpower Company ID is required.'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $worker                = User::find(Auth::user()->id);
        $worker->phone         = $request->phone;
        $worker->address       = $request->address;
        $worker->renewal_visa  = $request->renewal_visa;
        $worker->lng           = $request->lng;
        $worker->passcode      = $request->password;
        $worker->password      = Hash::make($request->password);
        $worker->is_afraid_by_cat  = $request->is_afraid_by_cat;
        $worker->is_afraid_by_dog  = $request->is_afraid_by_dog;
        $worker->two_factor_enabled = $request->twostepverification;
        $worker->payment_type = $request->payment_type;
        $worker->full_name = $request->full_name;
        $worker->bank_name = $request->bank_name;
        $worker->branch_number = $request->branch_number;
        $worker->account_number = $request->account_number;
        $worker->save();

        return response()->json([
            $request->all(),
            'message' => 'Account updated successfully',
        ]);
    }

    public function showPdf($id)
    {
        $worker = User::find($id);
        $pdf = Storage::get('/public/uploads/worker/form101/' . $worker->id . '/' . $worker->form_101);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename=form101.pdf');
    }

    public function getWorkerDetail(Request $request)
    {
        $user = $request->type == 'lead' ? WorkerLeads::where('id', $request->worker_id)->first() : User::where('id', $request->worker_id)->first();

        $form = $user->forms()
            ->where('type', WorkerFormTypeEnum::CONTRACT)
            // ->whereYear('created_at', now()->year)
            ->first();

        return response()->json([
            'worker' => $user,
            'form' => $form
        ]);
    }

    public function saveWorkerDetail(Request $request)
    {
        // Validation Rules
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|min:2|max:255',
            'address' => 'required|string',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($request->worker_id),
            ],
            'country' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'renewal_visa' => [
                'nullable', // Default is nullable
                'date',
                function ($attribute, $value, $fail) use ($request) {
                    if (strtolower($request->input('country')) !== 'israel' && !$value) {
                        $fail("The field is required.");
                    }
                },
            ],
            'passportNumber' => 'nullable|string|max:50',
            'IDNumber' => 'nullable|string|max:50',
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $type = $request->type;
        // Get Worker
        $user = $type == 'worker' ? User::find($request->worker_id) : WorkerLeads::find($request->worker_id);

        // Update User Details
        $user->update([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'lng' => $request->lng,
            'country' => $request->country,
            'gender' => $request->gender,
            'renewal_visa' => $request->renewal_visa,
            'address' => $request->address,
            'is_afraid_by_dog' => $request->is_afraid_by_dog,
            'is_afraid_by_cat' => $request->is_afraid_by_cat,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'updated_at' => Carbon::now()
        ]);

        $user->step = 1;
        $user->save();

        if ($request->has('passportNumber')) {
            $user->passport = $request->passportNumber;
        }

        if ($request->has('IDNumber')) {
            $user->id_number = $request->IDNumber;
        }
        $user->save();

        // Handle File Uploads
        if ($request->hasFile('passport')) {
            $path = $request->file('passport')->store('worker_documents/passports', 'public');
            $user->passport = $path;
        }
        if ($request->hasFile('visa')) {
            $path = $request->file('visa')->store('worker_documents/visas', 'public');
            $user->visa = $path;
        }
        if ($request->hasFile('id_card')) {
            $path = $request->file('id_card')->store('worker_documents/id_cards', 'public');
            $user->id_card = $path;
        }

        $user->save();

        if($type == 'lead') {
            $formEnum = new Form101FieldEnum;

            $defaultFields = $formEnum->getDefaultFields();
            $defaultFields['employeeFirstName'] = $user->firstname;
            $defaultFields['employeeLastName'] = $user->lastname;
            $defaultFields['employeeMobileNo'] = $user->phone;
            $defaultFields['employeeEmail'] = $user->email;
            $defaultFields['employeecountry'] = $user->country;
            $defaultFields['sender']['employeeEmail'] = $user->email;
            $defaultFields['employeeSex'] = Str::ucfirst($user->gender);
            $formData = app('App\Http\Controllers\User\Auth\AuthController')->transformFormDataForBoolean($defaultFields);

            $user->forms()->create([
                'type' => WorkerFormTypeEnum::FORM101,
                'data' => $formData,
                'submitted_at' => NULL
            ]);

        }

        return response()->json(['message' => 'Worker details updated successfully', 'worker' => $user], 200);
    }

    public function workerLeadDetails($id)
    {
        $workerLead = WorkerLeads::find($id);
        if (!$workerLead) {
            return response()->json(['message' => 'Worker Lead not found'], 404);
        }

        return response()->json($workerLead);
    }

    public function getWorkerInvitation(Request $request)
    {
        $workerInvitation = WorkerInvitation::where('id', base64_decode($request->id))->first();

        return response()->json([
            'worker_invitation' => $workerInvitation,
            'lng' => (substr($workerInvitation->phone ?? '', 0, 4) === '+972') ? 'heb' : 'en'
        ]);
    }

    public function getWorkerInvitationUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
            'address'   => ['required', 'string'],
            'phone'     => ['required'],
            'email'     => ['required'],
            'gender'    => ['required'],
            'last_name' => ['required', 'string', 'max:255'],
            'worker_id'  => ['required_without:passport'],
            'passport'   => ['required_without:worker_id']
        ], [], [
            'manpower_company_id' => 'Manpower',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $workerInvitation = WorkerInvitation::where('id', base64_decode($request->id))->first();
        if (!$workerInvitation) {
            return response()->json([
                'message' => 'Worker not found',
            ], 404);
        }
        $manpowerCompanyID = NULL;
        if ($workerInvitation->company == 0) {
            $manpowerCompany = ManpowerCompany::where('name', $workerInvitation->manpower_company_name)->first();
            if (!$manpowerCompany && !empty($workerInvitation->manpower_company_name)) {
                $manpowerCompany = ManpowerCompany::Create(['name' => $workerInvitation->manpower_company_name]);
            }

            $manpowerCompanyID = $manpowerCompany->id;
        }

        if ($request->lng == 'heb') {
            $role = 'מנקה';
        } elseif ($request->lng == 'spa') {
            $role = 'limpiador';
        } elseif ($request->lng == 'ru') {
            $role = 'Уборщица';
        } else {
            $role = 'Cleaner';
        }

        $workerData = [
            'firstname' => $request->first_name ?? '',
            'lastname'  => $request->last_name ?? '',
            'phone'     => $request->phone ?? '',
            'email'     => $request->email ?? '',
            'role'      => $role,
            'address'           => $request->address ?? '',
            'payment_per_hour'  => $workerInvitation->payment ?? 45,
            'renewal_visa'      => '',
            'worker_id'         => $request->worker_id ?? null,
            'passcode'  => '',
            'passport' => $request->passport ?? null,
            'password'  => Hash::make($row['password'] ?? ''),
            'country'   => $request->country ?? NULL,
            'company_type'      => $workerInvitation->company ? 'my-company' : 'manpower',
            'manpower_company_id' => $manpowerCompanyID,
            'lng'       => $request->lng ?? 'heb',
            'gender'    => $request->gender ?? '',
            'is_afraid_by_cat' => 0,
            'is_afraid_by_dog' => 0,
            'status'    => 1,
            'form101'   => $workerInvitation->form_101 ?? 0,
            'contract'  => $workerInvitation->contact ?? 0,
            'saftey_and_gear' => $workerInvitation->safety ?? 0,
            'insurance'   => $workerInvitation->safety ?? 0,
            'is_imported' => 0,
            'is_existing_worker' => 1,
            'first_date' => $workerInvitation->first_date ?? 0
        ];

        $worker = User::where('phone', $workerData['phone'])
            ->orWhere('email', $workerData['email'])
            ->first();

        if (empty($worker)) {
            $worker = User::create($workerData);
            $i = 1;
            $j = 0;
            $check_friday = 1;
            while ($i == 1) {
                $current = Carbon::now();
                $day = $current->addDays($j);
                if ($this->isWeekend($day->toDateString())) {
                    $check_friday++;
                } else {
                    $w_a = new WorkerAvailability;
                    $w_a->user_id = $worker->id;
                    $w_a->date = $day->toDateString();
                    $w_a->start_time = '08:00:00';
                    $w_a->end_time = '17:00:00';
                    $w_a->status = 1;
                    $w_a->save();
                }
                $j++;
                if ($check_friday == 6) {
                    $i = 2;
                }
            }
        } else {
            $worker->update($workerData);
        }

        if ($workerInvitation->form_101 == 1) {
            $formEnum = new Form101FieldEnum;

            $defaultFields = $formEnum->getDefaultFields();
            $defaultFields['employeeFirstName'] = $worker->firstname;
            $defaultFields['employeeLastName'] = $worker->lastname;
            $defaultFields['employeeMobileNo'] = $worker->phone;
            $defaultFields['employeeEmail'] = $worker->email;
            $defaultFields['sender']['employeeEmail'] = $worker->email;
            $defaultFields['employeeSex'] = Str::ucfirst($worker->gender);
            $formData = app('App\Http\Controllers\User\Auth\AuthController')->transformFormDataForBoolean($defaultFields);

            $worker->forms()->create([
                'type' => WorkerFormTypeEnum::FORM101,
                'data' => $formData,
                'submitted_at' => NULL
            ]);
        }

        return response()->json([
            'worker' => $worker,
            'base64_id' => base64_encode($worker->id),
            'url' => "worker-forms/" . base64_encode($worker->id)
        ]);
    }


    public function isWeekend($date)
    {
        $weekDay = date('w', strtotime($date));
        return ($weekDay == 5 || $weekDay == 6);
    }

    public function getWorker($id)
    {
        $workerId = base64_decode($id);
        $user = User::where('status', 1)->find($workerId);
        if (!$user) {
            return response()->json([
                'message' => 'Worker not found',
            ], 404);
        }
        $forms = $user->forms()
            ->whereIn('type', [
                WorkerFormTypeEnum::CONTRACT,
                WorkerFormTypeEnum::SAFTEY_AND_GEAR,
                WorkerFormTypeEnum::FORM101,
                WorkerFormTypeEnum::INSURANCE,
                WorkerFormTypeEnum::MANPOWER_SAFTEY,
            ])
            // ->whereYear('created_at', now()->year)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->groupBy('type');

        \Log::info([$forms]);

        $contractForm = $forms[WorkerFormTypeEnum::CONTRACT][0] ?? null;
        $safetyAndGearForm = $forms[WorkerFormTypeEnum::SAFTEY_AND_GEAR][0] ?? null;
        $form101Form = $forms[WorkerFormTypeEnum::FORM101][0] ?? null;
        $insuranceForm = $forms[WorkerFormTypeEnum::INSURANCE][0] ?? null;
        $manpowerForm = $forms[WorkerFormTypeEnum::MANPOWER_SAFTEY][0] ?? null;

        $forms = [];

        if (!$user->is_imported) {
            if ($user->company_type == 'my-company') {
                $forms['form101Form'] = $form101Form ? $form101Form : null;
                $forms['saftyAndGearForm'] = $safetyAndGearForm ? $safetyAndGearForm : null;
                $forms['contractForm'] = $contractForm ? $contractForm : null;
            }

            if ($user->company_type == 'manpower'){
                $forms['manpowerSaftyForm'] = $manpowerForm ? $manpowerForm : null;
            }

            if ($user->country != 'Israel' && !$user->is_existing_worker) {
                $forms['insuranceForm'] = $insuranceForm ? $insuranceForm : null;
            }
        } else {
            if ($user->form101) {
                $forms['form101Form'] = $user->form101 && $form101Form ? $form101Form : null;
            }
            if ($user->contract) {
                $forms['contractForm'] = $user->contract ? $contractForm : null;
            }
            if ($user->saftey_and_gear) {
                $forms['saftyAndGearForm'] = $user->saftey_and_gear ? $safetyAndGearForm : null;
            }
            if ($user->insurance) {
                $forms['insuranceForm'] = $user->insurance ? $insuranceForm : null;
            }
        }

        return response()->json([
            'worker' => $user,
            'forms' => $forms,
        ]);
    }

    public function WorkContract(Request $request, $id)
    {
        $data = $request->all();
        $savingType = $request->input('savingType', 'submit'); // Default to 'submit'
        $pdfFile = isset($data['pdf_file']) ? $data['pdf_file'] : null;
        unset($data['pdf_file']);

        // Find worker based on type (lead or user)
        $worker = $request->type == 'lead' ? WorkerLeads::find($id) : User::where('id', $id)->where('status', 1)->first();
        if (!$worker) {
            return response()->json([
                'message' => 'Worker not found',
            ], 404);
        }

        $step = $data['step'] ?? 1;  // Retrieve 'step' from the request
        if ($step) {
            $worker->step = $step;
            $worker->save();
        }

        // Check if form already exists
        $form = $worker->forms()
            ->where('type', WorkerFormTypeEnum::CONTRACT)
            ->first();

        // If the contract is already signed, prevent resubmission
        if ($form && $savingType === 'submit' && $form->submitted_at) {
            return response()->json([
                'message' => 'Contract already signed.'
            ], 403);
        }

        // Prepare form data
        $formData = [
            'type' => WorkerFormTypeEnum::CONTRACT,
            'data' => $data,
            'submitted_at' => $savingType === 'submit' ? now()->toDateTimeString() : null,
            'pdf_name' => null
        ];

        // Handle PDF saving only when submitting
        if ($savingType === 'submit') {
            if (!$pdfFile) {
                return response()->json([
                    'message' => "PDF file is required to submit the contract."
                ], 400);
            }

            if (!Storage::drive('public')->exists('signed-docs')) {
                Storage::drive('public')->makeDirectory('signed-docs');
            }

            $file_name = Str::uuid()->toString() . '.pdf';
            if (!Storage::disk('public')->putFileAs("signed-docs", $pdfFile, $file_name)) {
                return response()->json([
                    'message' => "Can't save PDF"
                ], 500);
            }

            // Update contract status and assign PDF
            $formData['pdf_name'] = $file_name;
            // $worker->contract = 1;
            $worker->save();
        }

        // Create or update the form
        if ($form) {
            $form->update($formData);
            $message = ($savingType === 'submit') ? 'Contract signed successfully.' : 'Draft saved successfully.';
        } else {
            $form = $worker->forms()->create($formData);
            $message = ($savingType === 'submit') ? 'Contract created and signed successfully.' : 'Draft saved successfully.';
        }

        // Trigger event only when the contract is fully submitted
        $user = null;
        if ($savingType === 'submit') {
            event(new ContractFormSigned($worker, $form));

            if ($request->type == 'lead' && $worker->company_type == 'my-company' && $worker->country == 'Israel') {
                $user = $this->createUser($worker);
            }

            if($worker->company_type == 'my-company' && $worker->country == 'Israel') {
                App::setLocale('heb');

                // **Retrieve all forms of the worker**
                $workerForms = $worker->forms()->get();
                $attachments = [];
                $workerName = trim(($worker->firstname ?? '') . '-' . ($worker->lastname ?? ''));
                $admin = Admin::where('role', 'hr')->first();

                foreach ($workerForms as $workerForm) {
                    $formType = $workerForm->type; // e.g., "form101"
                    $filePath = storage_path("app/public/signed-docs/{$workerForm->pdf_name}");

                    if (file_exists($filePath)) {
                        $workerIdentifier = $worker->id_number ?: $worker->passport;
                        $fileName = "{$formType}-{$workerName}-{$workerIdentifier}.pdf";
                        $fileName = str_replace(' ', '-', $fileName);

                        $attachments[$filePath] = $fileName;
                    }

                }
                // Send email with all form attachments
                Mail::send('/sendAllFormsToAdmin', ["worker" => $worker], function ($message) use ($worker, $attachments) {
                    $message->to("office@broomservice.co.il");
                    $message->bcc($admin->email);
                    $message->subject(__('mail.all_forms.subject'));

                    // Attach all available forms
                    foreach ($attachments as $filePath => $fileName) {
                        $message->attach($filePath, ['as' => $fileName]);
                    }
                });
            }

        }

        return response()->json([
            'message' => $message,
            'id' => $worker->country == "Israel" && $request->type == 'lead' && $savingType === 'submit' ? $user->id : null
        ]);
    }



    public function transformFormDataForBoolean(&$array)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                // Recursively call the function for nested arrays
                $array[$key] = $this->transformFormDataForBoolean($value);
            } elseif (is_string($value)) {
                if ($value === 'true') {
                    $array[$key] = true;
                } elseif ($value === 'false') {
                    $array[$key] = false;
                }
            }
        }

        return $array;
    }

    public function form101(Request $request, $id)
    {
        $worker = $request->input('type') == 'lead' ? WorkerLeads::find($id) : User::where('status', 1)->find($id);

        if (!$worker) {
            return response()->json([
                'message' => 'Worker not found',
            ], 404);
        }

        $data = $request->all();
        $data = $this->transformFormDataForBoolean($data);
        $savingType = $data['savingType'];
        $formId = $data['formId'] ?? null;
        $step = $data['step'] ?? 1;  // Retrieve 'step' from the request (if exists)
        $idNumber = $data['employeeIdNumber'] ?? null;
        $dateOfBeginningWork = $data['DateOfBeginningWork'] ?? null;

        unset($data['savingType']);

        // Save the 'step' value to the worker's record
        if ($step) {
            $worker->step = $step;  // Assuming the 'step' field exists on the worker model
            $worker->id_number = $idNumber ?? $worker->id_number ?? null;
            $worker->date_of_beginning_work = $dateOfBeginningWork ?? $worker->date_of_beginning_work ?? null;
            $worker->save();
        }

        if (!Storage::disk('public')->exists('uploads/form101/documents')) {
            Storage::disk('public')->makeDirectory('uploads/form101/documents');
        }

        // Look for an existing form (draft or submitted) for this worker

        $form = $worker->forms()->where('type', WorkerFormTypeEnum::FORM101)
        ->when(!empty($formId) && $formId != "null", function ($q) use ($formId) {
            $q->where('id', $formId);
        })
        ->when(empty($formId) || $formId == "null", function ($q) {
            $q->whereNull('submitted_at');
        })
        ->orderBy('created_at', 'DESC')
        ->first();

        $formOldData = $form ? $form->data : [];

        // Process all document uploads
        $data = $this->saveForm101UploadedDocument($data, 'employeepassportCopy', $formOldData);
        $data = $this->saveForm101UploadedDocument($data, 'employeeResidencePermit', $formOldData);
        $data = $this->saveForm101UploadedDocument($data, 'employeeIdCardCopy', $formOldData);
        $data = $this->saveForm101UploadedDocument($data, 'TaxExemption.disabledCertificate', $formOldData);
        $data = $this->saveForm101UploadedDocument($data, 'TaxExemption.disabledCompensationCertificate', $formOldData);
        $data = $this->saveForm101UploadedDocument($data, 'TaxExemption.exm3Certificate', $formOldData);
        $data = $this->saveForm101UploadedDocument($data, 'TaxExemption.exm4ImmigrationCertificate', $formOldData);
        $data = $this->saveForm101UploadedDocument($data, 'TaxExemption.exm5disabledCirtificate', $formOldData);
        $data = $this->saveForm101UploadedDocument($data, 'TaxExemption.exm10Certificate', $formOldData);
        $data = $this->saveForm101UploadedDocument($data, 'TaxExemption.exm11Certificate', $formOldData);
        $data = $this->saveForm101UploadedDocument($data, 'TaxExemption.exm12Certificate', $formOldData);
        $data = $this->saveForm101UploadedDocument($data, 'TaxExemption.exm14Certificate', $formOldData);
        $data = $this->saveForm101UploadedDocument($data, 'TaxExemption.exm15Certificate', $formOldData);
        $data = $this->saveForm101UploadedDocument($data, 'TaxCoordination.requestReason1Certificate', $formOldData);
        $data = $this->saveForm101UploadedDocument($data, 'TaxCoordination.requestReason3Certificate', $formOldData);

        // Handle employer-related documents (if any)
        if (
            isset($data['TaxCoordination']['employer']) &&
            is_array($data['TaxCoordination']['employer'])
        ) {
            foreach ($data['TaxCoordination']['employer'] as $key => $value) {
                if (isset($value['payslip'])) {
                    $data = $this->saveForm101UploadedDocument($data, "TaxCoordination.employer.$key.payslip", $formOldData);
                }
            }
        }

        // Check if the form has already been submitted
        if ($form && $form->submitted_at) {
            return response()->json([
                'message' => 'Form 101 already submitted for the current year.'
            ], 403);
        }

        // Set the submission timestamp based on the saving type (draft or submit)
        $submittedAt = ($savingType == 'submit') ? now()->toDateTimeString() : NULL;

        // If a form already exists (draft), update it
        if ($form) {
            $form->update([
                'data' => $data,
                'submitted_at' => $submittedAt
            ]);
        } else {
            // If no form exists, create a new one
            $form = $worker->forms()->create([
                'type' => WorkerFormTypeEnum::FORM101,
                'data' => $data,
                'submitted_at' => $submittedAt
            ]);
        }

        // // Generate PDF if the form has been submitted
        if ($form->submitted_at) {
            $file_name = Str::uuid()->toString() . '.pdf';
            $worker->form101 = 1;
            // $worker->form_101 = $file_name;
            $worker->save();
            $this->workerFormService->generateForm101PDF($form, $file_name, $worker->lng);

            $form->update([
                'pdf_name' => $file_name
            ]);

            event(new Form101Signed($worker, $form));
        }

        // Return the appropriate message based on the saving type
        return response()->json([
            'message' => $savingType === 'draft'
                ? 'Form 101 saved as draft.'
                : 'Form 101 signed successfully.'
        ]);
    }


    private function saveForm101UploadedDocument($data, $key, $formOldData)
    {
        $originalKeys = explode('.', $key);

        // Helper function to handle the recursive traversal and removing existing
        $removeFileRecursively = function (&$item, $keys) use (&$removeFileRecursively) {
            if (count($keys) == 1) {
                $currentKey = $keys[0];
                if (isset($item[$currentKey]) && is_string($item[$currentKey])) {
                    if (Storage::disk('public')->exists("uploads/form101/documents/" . $item[$currentKey])) {
                        Storage::disk('public')->delete("uploads/form101/documents/" . $item[$currentKey]);
                    }
                }
            } else {
                $currentKey = array_shift($keys);
                if (isset($item[$currentKey]) && is_array($item[$currentKey])) {
                    $removeFileRecursively($item[$currentKey], $keys);
                }
            }
        };

        // Helper function to handle the recursive traversal and file saving
        $saveFileRecursively = function (&$item, $keys) use (&$saveFileRecursively, $formOldData, $originalKeys, $removeFileRecursively) {
            if (count($keys) == 1) {
                $currentKey = $keys[0];
                if (isset($item[$currentKey]) && !is_string($item[$currentKey]) && $item[$currentKey]->isFile()) {
                    $file = $item[$currentKey];
                    $file_name = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();

                    if (Storage::disk('public')->putFileAs("uploads/form101/documents", $file, $file_name)) {
                        $removeFileRecursively($formOldData, $originalKeys);

                        $item[$currentKey] = $file_name;
                    }
                }
            } else {
                $currentKey = array_shift($keys);
                if (isset($item[$currentKey]) && is_array($item[$currentKey])) {
                    $saveFileRecursively($item[$currentKey], $keys);
                }
            }
        };

        $keys = $originalKeys;
        $saveFileRecursively($data, $keys);

        return $data;
    }

    public function safegear(Request $request, $id)
    {
        $worker = $request->type == 'lead' ? WorkerLeads::find($id) : User::where('status', 1)->find($id);

        if (!$worker) {
            return response()->json([
                'message' => 'Worker not found',
            ], 404);
        }

        $data = $request->all();
        $savingType = $data['savingType'] ?? 'submit';
        $pdfFile = $data['pdf_file'] ?? null;
        $step = $data['step'] ?? 1;
        unset($data['pdf_file'], $data['savingType']);

        if ($step) {
            $worker->step = $step;  // Assuming the 'step' field exists on the worker model
            $worker->save();
        }

        // Check if the form already exists
        $form = $worker->forms()
            ->where('type', WorkerFormTypeEnum::SAFTEY_AND_GEAR)
            ->first();

        if ($form && $form->submitted_at) {
            return response()->json([
                'message' => 'Safety and gear already signed.'
            ], 403);
        }

        // If form exists, update it; otherwise, create a new form
        if ($form) {
            // Form exists, let's update it
            $form->data = $data;
            $form->submitted_at = $savingType === 'submit' ? now()->toDateTimeString() : null;

            // If it's a submission, handle the PDF file saving
            if ($savingType === 'submit' && $pdfFile) {
                // Ensure the directory exists and store the PDF only on submission
                if (!Storage::disk('public')->exists('signed-docs')) {
                    Storage::disk('public')->makeDirectory('signed-docs');
                }

                $file_name = Str::uuid()->toString() . '.pdf';
                if (!Storage::disk('public')->putFileAs('signed-docs', $pdfFile, $file_name)) {
                    return response()->json([
                        'message' => "Can't save PDF"
                    ], 403);
                }

                // Update the form with the PDF file name
                $form->pdf_name = $file_name;
            }

            $form->save();
            $message = 'Form updated successfully.';
        } else {
            // Form doesn't exist, create a new one
            if ($savingType === 'submit' && $pdfFile) {
                // Ensure the directory exists and store the PDF only on submission
                if (!Storage::disk('public')->exists('signed-docs')) {
                    Storage::disk('public')->makeDirectory('signed-docs');
                }

                $file_name = Str::uuid()->toString() . '.pdf';
                if (!Storage::disk('public')->putFileAs('signed-docs', $pdfFile, $file_name)) {
                    return response()->json([
                        'message' => "Can't save PDF"
                    ], 403);
                }
            }

            // $worker->saftey_and_gear = $savingType === 'submit' ? 1 : 0;
            // $worker->safety_and_gear_form = $savingType === 'submit' ? $file_name : null;
            $worker->save();

            // Create the form
            $form = $worker->forms()->create([
                'type' => WorkerFormTypeEnum::SAFTEY_AND_GEAR,
                'data' => $data,
                'submitted_at' => $savingType === 'submit' ? now()->toDateTimeString() : null,
                'pdf_name' => $savingType === 'submit' ? $file_name : null,
            ]);

            $message = 'Safety and gear form created successfully.';
        }

        $user = null;
        // Trigger the event only when a submission is made
        if ($savingType === 'submit') {
            if($request->type == 'lead' && $worker->company_type == 'manpower') {
                $user = $this->createUser($worker);
            }
            event(new SafetyAndGearFormSigned($worker, $form));

            if($worker->company_type == 'manpower' ) {
                App::setLocale('heb');

                // **Retrieve all forms of the worker**
                $workerForms = $worker->forms()->get();
                $attachments = [];
                $workerName = trim(($worker->firstname ?? '') . '-' . ($worker->lastname ?? ''));
                $admin = Admin::where('role', 'hr')->first();

                foreach ($workerForms as $workerForm) {
                    $formType = $workerForm->type; // e.g., "form101"
                    $filePath = storage_path("app/public/signed-docs/{$workerForm->pdf_name}");

                    if (file_exists($filePath)) {
                        $workerIdentifier = $worker->id_number ?: $worker->passport;
                        $fileName = "{$formType}-{$workerName}-{$workerIdentifier}.pdf";
                        $fileName = str_replace(' ', '-', $fileName);

                        $attachments[$filePath] = $fileName;
                    }

                }
                // Send email with all form attachments
                Mail::send('/sendAllFormsToAdmin', ["worker" => $worker], function ($message) use ($worker, $attachments) {
                    $message->to("office@broomservice.co.il");
                    $message->bcc($admin->email);
                    $message->subject(__('mail.all_forms.subject'));

                    // Attach all available forms
                    foreach ($attachments as $filePath => $fileName) {
                        $message->attach($filePath, ['as' => $fileName]);
                    }
                });
            }

        }

        return response()->json([
            'message' => $message,
            'id' => $worker->company_type == "manpower" && $request->type == 'lead' ? $user->id : null
        ]);
    }

    public function getSafegear($id, $type = null)
    {
        $worker = $type == 'lead' ? WorkerLeads::find($id) : User::where('status', 1)->find($id);
        if (!$worker) {
            return response()->json([
                'message' => 'Worker not found',
            ], 404);
        }

        $form = $worker->forms()
            ->where('type', WorkerFormTypeEnum::SAFTEY_AND_GEAR)
            ->first();

        return response()->json([
            'lng' => $worker->lng,
            'worker' => $worker,
            'form' => $form
        ]);
    }

    public function get101($id, $formId = null, $type = null)
    {
        $worker = $type == 'lead' ? WorkerLeads::find($id) : User::where('status', 1)->find($id);
        if (!$worker) {
            return response()->json([
                'message' => 'Worker not found',
            ], 404);
        }

        $form = $worker->forms()
            ->when(!empty($formId) && $formId != "null", function ($q) use ($formId) {
                $q->where('id', $formId);
            })
            ->when(empty($formId) || $formId == "null", function ($q) use ($formId) {
                $q->where('type', WorkerFormTypeEnum::FORM101);
                // ->whereYear('created_at', now()->year);
            })
            ->first();

            // $form = $worker->forms()->where('type', WorkerFormTypeEnum::FORM101)
            // ->when(!empty($formId) && $formId != "null", function ($q) use ($formId) {
            //     $q->where('id', $formId);
            // })
            // ->when(empty($formId) || $formId == "null", function ($q) {
            //     $q->whereNull('submitted_at');
            // })
            // ->orderBy('created_at', 'DESC')
            // ->first();

        return response()->json([
            'lng' => $worker->lng,
            'form' => $form ? $form : NULL,
            'worker' => $worker
        ]);
    }
    public function getAllForms($id, $type = null)
    {
        $worker = $type == 'lead' ? WorkerLeads::find($id) : User::where('status', 1)->find($id);
        if (!$worker) {
            return response()->json([
                'message' => 'Worker not found',
            ], 404);
        }
        $form = $worker->forms()
            // ->whereYear('created_at', now()->year)
            ->get();
        return response()->json([
            'lng' => $worker->lng,
            'forms' => $form->count() > 0 ? $form : [],
            'worker' => $worker
        ]);
    }

    public function getWorkContract($id)
    {
        $worker = User::where('status', 1)->find($id);
        if (!$worker) {
            return response()->json([
                'message' => 'Worker not found',
            ], 404);
        }

        $form = $worker->forms()
            ->where('type', WorkerFormTypeEnum::CONTRACT)
            ->first();

        return response()->json([
            'worker' => $worker,
            'form' => $form
        ]);
    }

    public function getInsuranceForm($id, $type = null)
    {
        $worker =$type == 'lead' ? WorkerLeads::find($id) :  User::where('status', 1)->find($id);
        if (!$worker) {
            return response()->json([
                'message' => 'Worker not found',
            ], 404);
        }

        $form = $worker->forms()
            ->where('type', WorkerFormTypeEnum::INSURANCE)
            // ->whereYear('created_at', now()->year)
            ->first();

        return response()->json([
            'lng' => $worker->lng,
            'worker' => $worker,
            'form' => $form
        ]);
    }

    public function saveInsuranceForm(Request $request, $id)
    {
        $worker = $request->type == 'lead' ? WorkerLeads::find($id) : User::where('status', 1)->find($id);
        $insuranceCompany = InsuranceCompany::first();

        if (!$worker) {
            return response()->json([
                'message' => 'Worker not found',
            ], 404);
        }

        $data = $request->all();
        $pdfFile = $data['pdf_file'];
        unset($data['pdf_file']);

        $step = $data['step'] ?? 1;  // Retrieve 'step' from the request (if exists)

        // Save the 'step' value to the worker's record
        if ($step) {
            $worker->step = $step;  // Assuming the 'step' field exists on the worker model
            $worker->save();
        }

        // Check if a form of type INSURANCE already exists for the worker
        $form = $worker->forms()
            ->where('type', WorkerFormTypeEnum::INSURANCE)
            ->first();

        // If the form exists and is already submitted, return a message
        if ($form && $form->submitted_at) {
            return response()->json([
                'message' => 'Insurance form already signed.'
            ], 403);
        }

        // Handle PDF saving
        if (!Storage::drive('public')->exists('signed-docs')) {
            Storage::drive('public')->makeDirectory('signed-docs');
        }

        $file_name = Str::uuid()->toString() . '.pdf';
        if (!Storage::disk('public')->putFileAs("signed-docs", $pdfFile, $file_name)) {
            return response()->json([
                'message' => "Can't save PDF"
            ], 403);
        }

        // $worker->form_insurance = $file_name;
        // $worker->insurance = 1;
        $worker->save();

        // Prepare form data
        $formData = [
            'type' => WorkerFormTypeEnum::INSURANCE,
            'data' => $data,
            'submitted_at' => now()->toDateTimeString(),
            'pdf_name' => $file_name
        ];

        // Create or update the form based on its existence
        if ($form) {
            // Update the existing form with new data
            $form->update($formData);
            $message = 'Insurance form updated successfully.';
        } else {
            // Create a new form if it doesn't exist
            $form = $worker->forms()->create($formData);
            $message = 'Insurance form created successfully.';
        }

        $user = null;
        // Trigger the event only if the form is submitted
        if ($form && $form->submitted_at) {
            if($request->type == 'lead' && $worker->company_type == 'my-company' && $worker->country != 'Israel') {
                $user = $this->createUser($worker);
            }
            event(new InsuranceFormSigned($worker, $form));

            $form101 = $worker->forms()
            ->where('type', WorkerFormTypeEnum::FORM101)
            ->first();

            $dateOfBeginningWork = $form101 ? data_get($form101->data, 'DateOfBeginningWork') : null;
            $workerName = trim(($worker->firstname ?? '') . '-' . ($worker->lastname ?? ''));


            if ($insuranceCompany && $insuranceCompany->email) {
                App::setLocale('heb');

                // Determine the correct document file name
                $workerPassport = $worker->passport_card ?? null;
                $workerVisa = $worker->visa ?? null;

                $workerPassportDocName = "Passport-{$workerName}";
                $workerVisaDocName = "Visa-{$workerName}";

                $workerPassportDocName = str_replace(' ', '-', $workerPassportDocName);
                $workerVisaDocName = str_replace(' ', '-', $workerVisaDocName);

                // Send email
                Mail::send('/insuaranceCompany', ['worker' => $worker, 'dateOfBeginningWork' => $dateOfBeginningWork],
                    function ($message) use ($worker, $insuranceCompany, $file_name, $workerPassport, $workerPassportDocName, $workerVisa, $workerVisaDocName) {
                        $message->to($insuranceCompany->email)
                            ->subject(__('mail.insuarance_company.subject', [
                                'worker_name' => ($worker['firstname'] ?? '') . ' ' . ($worker['lastname'] ?? '')
                            ]))
                            ->attach(storage_path("app/public/signed-docs/{$file_name}"));

                        // Attach document if it exists
                        if ($workerPassport && $workerVisa) {
                            $message->attach(storage_path("app/public/uploads/documents/{$workerPassport}"), ['as' => $workerPassportDocName]);
                            $message->attach(storage_path("app/public/uploads/documents/{$workerVisa}"), ['as' => $workerVisaDocName]);
                        }
                    }
                );
            }

            App::setLocale('heb');

            // **Retrieve all forms of the worker**
            $workerForms = $worker->forms()->get();
            $attachments = [];
            $admin = Admin::where('role', 'hr')->first();

            foreach ($workerForms as $workerForm) {
                $formType = $workerForm->type; // e.g., "form101"
                $filePath = storage_path("app/public/signed-docs/{$workerForm->pdf_name}");

                if (file_exists($filePath)) {
                    $workerIdentifier = $worker->id_number ?: $worker->passport;
                    $fileName = "{$formType}-{$workerName}-{$workerIdentifier}.pdf";
                    $fileName = str_replace(' ', '-', $fileName);

                    $attachments[$filePath] = $fileName;
                }

            }
            // Send email with all form attachments
            Mail::send('/sendAllFormsToAdmin', ["worker" => $worker], function ($message) use ($worker, $attachments, $admin) {
                $message->to(config('services.mail.default'));
                if($admin) {
                    $message->bcc($admin->email);
                }
                $message->subject(__('mail.all_forms.subject'));

                // Attach all available forms
                foreach ($attachments as $filePath => $fileName) {
                    $message->attach($filePath, ['as' => $fileName]);
                }
            });
        }

        return response()->json([
            'message' => $message,
            'id' => $request->type == 'lead' ? $user->id : null
        ]);
    }



    public function manpowerForm(Request $request, $id)
    {
        $worker = $request->type == 'lead' ? WorkerLeads::find($id) : User::where('status', 1)->find($id);

        if (!$worker) {
            return response()->json([
                'message' => 'Worker not found',
            ], 404);
        }

        $data = $request->all();
        $pdfFile = $data['pdf_file'];
        unset($data['pdf_file']);

        $form = $worker->forms()
            ->where('type', WorkerFormTypeEnum::MANPOWER_SAFTEY)
            ->first();

        if ($form && $form->pdf_name !== null && $form->submitted_at !== null) {
            return response()->json([
                'message' => 'Manpower safety already signed.'
            ], 403);
        }

        if (!Storage::drive('public')->exists('signed-docs')) {
            Storage::drive('public')->makeDirectory('signed-docs');
        }

        $file_name = Str::uuid()->toString() . '.pdf';
        if (!Storage::disk('public')->putFileAs("signed-docs", $pdfFile, $file_name)) {
            return response()->json([
                'message' => "Can't save PDF"
            ], 403);
        }

        $manpowerCompany = ManpowerCompany::find($worker->manpower_company_id);

        if (!$manpowerCompany) {
            return response()->json([
                'message' => 'Manpower company not found.'
            ], 404);
        }

        // Prepare email data
        $emailData = [
            'worker' => $worker,
            'manpowerCompany' => $manpowerCompany,
            'formUrl' => Storage::disk('public')->url("signed-docs/{$file_name}")
        ];

        if($manpowerCompany->email){
            App::setLocale('heb');
            // Send email
            Mail::send('/manpowerCompany', $emailData, function ($message) use ($worker, $manpowerCompany, $file_name) {
                $message->to($manpowerCompany->email)
                    ->subject(__('mail.manpower_company.subject'))
                    ->attach(storage_path("app/public/signed-docs/{$file_name}"));
            });
        }

        // Update or create the form
        if ($form) {
            $form->update([
                'data' => $data,
                'submitted_at' => now()->toDateTimeString(),
                'pdf_name' => $file_name
            ]);
        } else {
            $worker->forms()->create([
                'type' => WorkerFormTypeEnum::MANPOWER_SAFTEY,
                'data' => $data,
                'submitted_at' => now()->toDateTimeString(),
                'pdf_name' => $file_name
            ]);
        }

        return response()->json([
            'message' => 'Manpower form signed successfully.'
        ]);
    }



    public function getManpowerSafty($id, $type = null)
    {
        $worker = $type == 'lead' ? WorkerLeads::find($id) : User::find($id);

        if (!$worker) {
            return response()->json([
                'message' => 'Worker not found',
            ], 404);
        }

        $form = $worker->forms()
            ->where('type', WorkerFormTypeEnum::MANPOWER_SAFTEY)
            ->first();

        // Fetch Manpower Company name if available
        $manpowerCompany = null;
        if ($worker->manpower_company_id) {
            $manpowerCompany = ManpowerCompany::find($worker->manpower_company_id);
        }

        return response()->json([
            'lng' => $worker->lng,
            'worker' => $worker,
            'form' => $form,
            'manpower_company_name' => $manpowerCompany->name ?? null, // Include name or null
        ]);
    }

    public function createUser($workerLead){
        $role = $workerLead->role ?? 'cleaner';
        $lng = $workerLead->lng;

        if ($role == 'cleaner') {
            $role = match ($lng) {
                'heb' => "מנקה",
                'en' => "Cleaner",
                'ru' => "уборщик",
                default => "limpiador"
            };
        } elseif ($role == 'general_worker') {
            $role = match ($lng) {
                'heb' => "עובד כללי",
                'en' => "General worker",
                'ru' => "Общий рабочий",
                default => "Trabajador general"
            };
        }

        // Create new user
        $worker = User::create([
            'firstname' => $workerLead->firstname,
            'lastname' => $workerLead->lastname ?? '',
            'phone' => $workerLead->phone,
            'email' => $workerLead->email ?? null,
            'gender' => $workerLead->gender,
            'first_date' => $workerLead->first_date ?? null,
            'role' => $role,
            'lng' => $lng,
            'passcode' => $workerLead->phone,
            'password' => Hash::make($workerLead->phone),
            'company_type' => $workerLead->company_type,
            'visa' => $workerLead->visa ?? NULL,
            'passport' => $workerLead->passport ?? NULL,
            'passport_card' => $workerLead->passport_card ?? NULL,
            'id_number' => $workerLead->id_number ?? NULL,
            'status' => 1,
            'is_afraid_by_cat' => $workerLead->is_afraid_by_cat == 1,
            'is_afraid_by_dog' => $workerLead->is_afraid_by_dog == 1,
            'renewal_visa' => $workerLead->renewal_visa ?? NULL,
            'address' => $workerLead->address ?? NULL,
            'latitude' => $workerLead->latitude ?? NULL,
            'longitude' => $workerLead->longitude ?? NULL,
            'manpower_company_id' => $workerLead->company_type == "manpower" ? $workerLead->manpower_company_id : NULL,
            'step' => $workerLead->step ?? 1
        ]);

        $i = 1;
        $j = 0;
        $check_friday = 1;
        while ($i == 1) {
            $current = Carbon::now();
            $day = $current->addDays($j);
            if ($this->isWeekend($day->toDateString())) {
                $check_friday++;
            } else {
                $w_a = new WorkerAvailability;
                $w_a->user_id = $worker->id;
                $w_a->date = $day->toDateString();
                $w_a->start_time = '08:00:00';
                $w_a->end_time = '17:00:00';
                $w_a->status = 1;
                $w_a->save();
            }
            $j++;
            if ($check_friday == 6) {
                $i = 2;
            }
        }


        $forms = $workerLead->forms()->get();
            foreach ($forms as $form) {
                $form->update([
                    'user_type' => User::class,
                    'user_id' => $worker->id
                ]);
            }

        $workerLead->delete();

        return $worker;
    }

}
