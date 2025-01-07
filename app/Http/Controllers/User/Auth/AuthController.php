<?php

namespace App\Http\Controllers\User\Auth;

use App\Enums\WorkerFormTypeEnum;
use App\Events\ContractFormSigned;
use App\Events\Form101Signed;
use App\Models\ManpowerCompany;
use App\Events\InsuranceFormSigned;
use App\Events\SafetyAndGearFormSigned;
use App\Http\Controllers\Controller;
use App\Models\User;
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
             'worker_id' => ['required'],
             'password'  => ['required', 'string', 'min:6'],
         ]);
     
         if ($validator->fails()) {
             return response()->json(['errors' => $validator->messages()]);
         }
     
         if (Auth::attempt([
             'worker_id' => $request->worker_id,
             'password'  => $request->password
         ]) || Auth::attempt([
             'email'     => $request->worker_id,
             'password'  => $request->password
         ])) {
             $user = User::find(auth()->user()->id);
             DeviceToken::where('tokenable_id', $user->id)
             ->where('tokenable_type', User::class)
             ->where('expires_at', '<', now())
             ->delete();

             $rememberDeviceToken = $request->cookie('remember_device_token');
             if ($rememberDeviceToken) {
                 $storedToken = DeviceToken::where('tokenable_id', $user->id)
                     ->where('tokenable_type', User::class)
                     ->where('token', $rememberDeviceToken)
                     ->where('expires_at', '>', now())
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
            $user = User::where('email', $request->email)->first();

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
        $user = User::where('id', $request->worker_id)->first();

        $form = $user->forms()
            ->where('type', WorkerFormTypeEnum::CONTRACT)
            // ->whereYear('created_at', now()->year)
            ->first();

        return response()->json([
            'worker' => $user,
            'form' => $form
        ]);
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
        $user = User::find($workerId);
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
        $savingType = $request->input('savingType', 'submit'); // Default to 'submit' if not provided
        $pdfFile = isset($data['pdf_file']) ? $data['pdf_file'] : null;
        unset($data['pdf_file']);

        $worker = User::where('id', $id)->first();
        if (!$worker) {
            return response()->json([
                'message' => 'Worker not found',
            ], 404);
        }

        $step = $data['step'] ?? 1;  // Retrieve 'step' from the request (if exists)
    
         // Save the 'step' value to the worker's record
         if ($step) {
             $worker->step = $step;  // Assuming the 'step' field exists on the worker model
             $worker->save();
         }

        $form = $worker->forms()
            ->where('type', WorkerFormTypeEnum::CONTRACT)
            ->first();

        if ($form && $savingType == 'submit' && $form->submitted_at) {
            return response()->json([
                'message' => 'Contract already signed.'
            ], 403);
        }

        // Save the form data
        $formData = [
            'type' => WorkerFormTypeEnum::CONTRACT,
            'data' => $data,
            'submitted_at' => $savingType === 'submit' ? now()->toDateTimeString() : null,
            'pdf_name' => null
        ];

        // If savingType is 'submit', generate the PDF and save it
        if ($savingType === 'submit') {
            if (!Storage::drive('public')->exists('signed-docs')) {
                Storage::drive('public')->makeDirectory('signed-docs');
            }

            $file_name = Str::uuid()->toString() . '.pdf';
            if (!Storage::disk('public')->putFileAs("signed-docs", $pdfFile, $file_name)) {
                return response()->json([
                    'message' => "Can't save PDF"
                ], 403);
            }

            // Update the form data with the PDF file name
            $formData['pdf_name'] = $file_name;
        }

        // Create or update the form in the database
        $form = $worker->forms()->updateOrCreate(
            ['type' => WorkerFormTypeEnum::CONTRACT],
            $formData
        );

        // Trigger the event only if the form is fully submitted
        if ($savingType === 'submit') {
            event(new ContractFormSigned($worker, $form));
            return response()->json([
                'message' => 'Contract signed successfully. Thanks for signing the contract.'
            ]);
        } else {
            return response()->json([
                'message' => 'Contract saved as draft.'
            ]);
        }
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
        $worker = User::find($id);

        if (!$worker) {
            return response()->json([
                'message' => 'Worker not found',
            ], 404);
        }

        $data = $request->all();
        $data = $this->transformFormDataForBoolean($data);
        $savingType = $data['savingType'];
        $formId = $data['formId'];
        $step = $data['step'] ?? 1;  // Retrieve 'step' from the request (if exists)
        $idNumber = $data['employeeIdNumber'];
        unset($data['savingType']);
    
        // Save the 'step' value to the worker's record
        if ($step) {
            $worker->step = $step;  // Assuming the 'step' field exists on the worker model
            $worker->id_number = $idNumber;
            $worker->save();
        }

        if (!Storage::disk('public')->exists('uploads/form101/documents')) {
            Storage::disk('public')->makeDirectory('uploads/form101/documents');
        }

        $form = $worker->forms()
            ->when($formId != NULL, function ($q) use ($formId) {
                $q->where('id', $formId);
            })
            ->when($formId == NULL, function ($q) use ($formId) {
                $q->where('type', WorkerFormTypeEnum::FORM101);
                // ->whereYear('created_at', now()->year);
            })
            ->first();

        $formOldData = $form ? $form->data : [];

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

        if ($form && $form->submitted_at) {
            return response()->json([
                'message' => 'Form 101 already submitted for current year.'
            ], 403);
        }

        if ($savingType == 'submit') {
            $submittedAt = now()->toDateTimeString();
        } else {
            $submittedAt = NULL;
        }

        if ($form) {
            $form->update([
                'data' => $data,
                'submitted_at' => $submittedAt
            ]);
        } else {
            $form = $worker->forms()->create([
                'type' => WorkerFormTypeEnum::FORM101,
                'data' => $data,
                'submitted_at' => $submittedAt
            ]);
        }

        if ($form->submitted_at) {
            $file_name = Str::uuid()->toString() . '.pdf';
            $this->workerFormService->generateForm101PDF($form, $file_name, $worker->lng);

            $form->update([
                'pdf_name' => $file_name
            ]);

            event(new Form101Signed($worker, $form));
        }

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
        $worker = User::find($id);

        if (!$worker) {
            return response()->json([
                'message' => 'Worker not found',
            ], 404);
        }

        $data = $request->all();
        $savingType = $data['savingType'] ?? 'submit'; // Default to submit
        $pdfFile = $data['pdf_file'] ?? null; // PDF may not be sent for drafts
        $step = $data['step'] ?? 1;  // Retrieve 'step' from the request (if exists)
        unset($data['pdf_file'], $data['savingType']); // Remove them from $data

        if ($step) {
            $worker->step = $step;  // Assuming the 'step' field exists on the worker model
            $worker->save();
        }

        // Check if the form already exists and has been submitted
        $form = $worker->forms()
            ->where('type', WorkerFormTypeEnum::SAFTEY_AND_GEAR)
            ->first();

        if ($form && $form->submitted_at) {
            return response()->json([
                'message' => 'Safety and gear already signed.'
            ], 403);
        }

        // Save data based on whether it's a draft or a submission
        if ($savingType === 'submit') {
            // Ensure the directory exists and store the PDF only on submission
            if (!Storage::drive('public')->exists('signed-docs')) {
                Storage::drive('public')->makeDirectory('signed-docs');
            }

            $file_name = Str::uuid()->toString() . '.pdf';
            if (!Storage::disk('public')->putFileAs("signed-docs", $pdfFile, $file_name)) {
                return response()->json([
                    'message' => "Can't save PDF"
                ], 403);
            }

            // Save the form with the submitted_at timestamp and the PDF
            $form = $worker->forms()->create([
                'type' => WorkerFormTypeEnum::SAFTEY_AND_GEAR,
                'data' => $data,
                'submitted_at' => now()->toDateTimeString(),
                'pdf_name' => $file_name,
            ]);

            event(new SafetyAndGearFormSigned($worker, $form));

            return response()->json([
                'message' => 'Safety and gear signed successfully.'
            ]);
        } else {
            // Save as a draft, without generating the PDF and with submitted_at as null
            $form = $worker->forms()->updateOrCreate(
                ['type' => WorkerFormTypeEnum::SAFTEY_AND_GEAR],
                [
                    'data' => $data,
                    'submitted_at' => null, // No submission date for drafts
                    'pdf_name' => null,     // No PDF for drafts
                ]
            );

            return response()->json([
                'message' => 'Draft saved successfully.'
            ]);
        }
    }

    public function getSafegear($id)
    {
        $worker = User::find($id);
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

    public function get101($id, $formId = NULL)
    {
        $worker = User::find($id);
        if (!$worker) {
            return response()->json([
                'message' => 'Worker not found',
            ], 404);
        }

        $form = $worker->forms()
            ->when($formId != NULL, function ($q) use ($formId) {
                $q->where('id', $formId);
            })
            ->when($formId == NULL, function ($q) use ($formId) {
                $q->where('type', WorkerFormTypeEnum::FORM101);
                // ->whereYear('created_at', now()->year);
            })
            ->first();

        return response()->json([
            'lng' => $worker->lng,
            'form' => $form ? $form : NULL,
            'worker' => $worker
        ]);
    }
    public function getAllForms($id)
    {
        $worker = User::find($id);
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
        $worker = User::find($id);
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

    public function getInsuranceForm($id)
    {
        $worker = User::find($id);
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
        $worker = User::find($id);

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

        $form = $worker->forms()
            ->where('type', WorkerFormTypeEnum::INSURANCE)
            // ->whereYear('created_at', now()->year)
            ->first();

        if ($form && $form->submitted_at) {
            return response()->json([
                'message' => 'Insurance form already signed.'
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

        $form = $worker->forms()->create([
            'type' => WorkerFormTypeEnum::INSURANCE,
            'data' => $data,
            'submitted_at' => now()->toDateTimeString(),
            'pdf_name' => $file_name
        ]);

        event(new InsuranceFormSigned($worker, $form));

        return response()->json([
            'message' => 'Insurance form signed successfully.'
        ]);
    }


    public function manpowerForm(Request $request, $id)
    {
        $worker = User::find($id);

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


          // Update the existing form if it exists and is not signed yet, otherwise create a new form
          if ($form) {
            $form->update([
                'data' => $data,
                'submitted_at' => now()->toDateTimeString(),
                'pdf_name' => $file_name
            ]);
        } else {
            $form = $worker->forms()->create([
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


    public function getManpowerSafty($id)
    {
        $worker = User::find($id);

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
    
}
