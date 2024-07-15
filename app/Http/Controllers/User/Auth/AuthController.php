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
use Carbon\Carbon;

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
            'worker_id'     => $request->worker_id,
            'password'  => $request->password
        ])) {

            $user        = User::find(auth()->user()->id);
            $user->token = $user->createToken('User', ['user'])->accessToken;

            return response()->json($user);
        } else if (Auth::attempt([
            'email'     => $request->worker_id,
            'password'  => $request->password
        ])) {

            $user        = User::find(auth()->user()->id);
            $user->token = $user->createToken('User', ['user'])->accessToken;

            return response()->json($user);
        } else {
            return response()->json(['errors' => ['worker' => 'These credentials do not match our records.']]);
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
        $worker->is_afraid_by_cat       = $request->is_afraid_by_cat;
        $worker->is_afraid_by_dog       = $request->is_afraid_by_dog;
        $worker->save();

        return response()->json([
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
            ->whereYear('created_at', now()->year)
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

    public function getWorkerInvitationUpdate(Request $request) {
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
        if(!$workerInvitation) {
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
        }elseif($request->lng == 'spa'){
            $role = 'limpiador';
        }elseif ($request->lng == 'ru') {
            $role = 'Уборщица';
        }else {
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
    
        if($workerInvitation->form_101 == 1) {
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
            'url' => "worker-forms/".base64_encode($worker->id)
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
            ])
            ->whereYear('created_at', now()->year)
            ->get()
            ->groupBy('type');

        $contractForm = $forms[WorkerFormTypeEnum::CONTRACT][0] ?? null;
        $safetyAndGearForm = $forms[WorkerFormTypeEnum::SAFTEY_AND_GEAR][0] ?? null;
        $form101Form = $forms[WorkerFormTypeEnum::FORM101][0] ?? null;
        $insuranceForm = $forms[WorkerFormTypeEnum::INSURANCE][0] ?? null;

        $forms = [];

        if(!$user->is_imported) {
            if ($user->company_type == 'my-company') {
                $forms['form101Form'] = $form101Form ? $form101Form : null;
                $forms['saftyAndGearForm'] = $safetyAndGearForm ? $safetyAndGearForm : null;
                $forms['contractForm'] = $contractForm ? $contractForm : null;
            }

            if ($user->country != 'Israel' && !$user->is_existing_worker) {
                $forms['insuranceForm'] = $insuranceForm ? $insuranceForm : null;
            }
        } else {
            if($user->form101) {
                $forms['form101Form'] = $user->form101 && $form101Form ? $form101Form : null;
            }
            if($user->contract) {
                $forms['contractForm'] = $user->contract ? $contractForm : null;
            }
            if($user->saftey_and_gear) {
                $forms['saftyAndGearForm'] = $user->saftey_and_gear ? $safetyAndGearForm : null;
            }
            if($user->insurance) {
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
        $pdfFile = $data['pdf_file'];
        unset($data['pdf_file']);

        $worker = User::where('id', $id)->first();
        if (!$worker) {
            return response()->json([
                'message' => 'Worker not found',
            ], 404);
        }

        $form = $worker->forms()
            ->where('type', WorkerFormTypeEnum::CONTRACT)
            ->first();

        if ($form) {
            return response()->json([
                'message' => 'Contract already signed.'
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
            'type' => WorkerFormTypeEnum::CONTRACT,
            'data' => $data,
            'submitted_at' => now()->toDateTimeString(),
            'pdf_name' => $file_name
        ]);

        event(new ContractFormSigned($worker, $form));

        return response()->json([
            'message' => 'Contract signed successfully. Thanks, for signing the contract.'
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
        unset($data['savingType']);

        if (!Storage::disk('public')->exists('uploads/form101/documents')) {
            Storage::disk('public')->makeDirectory('uploads/form101/documents');
        }

        $form = $worker->forms()
            ->when($formId != NULL, function ($q) use ($formId) {
                $q->where('id', $formId);
            })
            ->when($formId == NULL, function ($q) use ($formId) {
                $q->where('type', WorkerFormTypeEnum::FORM101)->whereYear('created_at', now()->year);
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
        $pdfFile = $data['pdf_file'];
        unset($data['pdf_file']);

        $form = $worker->forms()
            ->where('type', WorkerFormTypeEnum::SAFTEY_AND_GEAR)
            ->first();

        if ($form) {
            return response()->json([
                'message' => 'Safety and gear already signed.'
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
            'type' => WorkerFormTypeEnum::SAFTEY_AND_GEAR,
            'data' => $data,
            'submitted_at' => now()->toDateTimeString(),
            'pdf_name' => $file_name
        ]);

        event(new SafetyAndGearFormSigned($worker, $form));

        return response()->json([
            'message' => 'Safety and gear signed successfully.'
        ]);
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
                $q->where('type', WorkerFormTypeEnum::FORM101)->whereYear('created_at', now()->year);
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
            ->whereYear('created_at', now()->year)
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
            ->whereYear('created_at', now()->year)
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

        $form = $worker->forms()
            ->where('type', WorkerFormTypeEnum::INSURANCE)
            ->whereYear('created_at', now()->year)
            ->first();

        if ($form) {
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
}
