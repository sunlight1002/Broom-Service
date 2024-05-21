<?php

namespace App\Http\Controllers\User\Auth;

use App\Enums\WorkerFormTypeEnum;
use App\Events\ContractFormSigned;
use App\Events\Form101Signed;
use App\Events\InsuranceFormSigned;
use App\Events\SafetyAndGearFormSigned;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WorkerFormService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            'firstname' => ['required', 'string', 'max:255'],
            'address'   => ['required', 'string'],
            'phone'     => ['required'],
            'worker_id' => ['required', 'unique:users,worker_id,' . Auth::user()->id],
            'status'    => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $worker                = User::find(Auth::user()->id);
        $worker->firstname     = $request->firstname;
        $worker->lastname      = ($request->lastname) ? $request->lastname : '';
        $worker->phone         = $request->phone;
        // $worker->email         = $request->email;
        $worker->address       = $request->address;
        $worker->renewal_visa  = $request->renewal_visa;
        $worker->gender        = $request->gender;
        $worker->payment_per_hour  = $request->payment_hour;
        $worker->worker_id     = $request->worker_id;
        $worker->lng           = $request->lng;
        $worker->passcode     = $request->password;
        $worker->password      = Hash::make($request->password);
        $worker->status        = $request->status;
        $worker->country       = $request->country;
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

    public function upload(Request $request, $id)
    {
        $worker = User::find($id);

        $pdf = $request->file('pdf');
        $filename = 'form101_' . $worker->id . '.' . $pdf->getClientOriginalExtension();
        $path = storage_path() . '/app/public/uploads/worker/form101/' . $worker->id;
        $pdf->move($path, $filename);

        $worker->update([
            'form_101' => $filename
        ]);

        return response()->json(['success' => true]);
    }

    public function getWorkerDetail(Request $request)
    {
        $user = User::where('worker_id', $request->worker_id)->first();

        $form = $user->forms()
            ->where('type', WorkerFormTypeEnum::CONTRACT)
            ->whereYear('created_at', now()->year)
            ->first();

        return response()->json([
            'worker' => $user,
            'form' => $form ? $form->data : NULL
        ]);
    }

    public function WorkContract(Request $request, $id)
    {
        $data = $request->all();
        $pdfFile = $data['pdf_file'];
        unset($data['pdf_file']);

        $worker = User::where('worker_id', $id)->first();
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
            'submitted_at' => now()->toDateString(),
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
        unset($data['savingType']);

        if (!Storage::disk('public')->exists('uploads/form101/documents')) {
            Storage::disk('public')->makeDirectory('uploads/form101/documents');
        }

        $form = $worker->forms()
            ->where('type', WorkerFormTypeEnum::FORM101)
            ->whereYear('created_at', now()->year)
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

        if ($form && $form->submitted_at) {
            return response()->json([
                'message' => 'Form 101 already submitted for current year.'
            ], 403);
        }

        if ($savingType == 'submit') {
            $submittedAt = now()->toDateString();
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
            $this->workerFormService->generateForm101PDF($form, $file_name);

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
            'submitted_at' => now()->toDateString(),
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
        $form = $worker->forms()
            ->where('type', WorkerFormTypeEnum::SAFTEY_AND_GEAR)
            ->first();

        return response()->json([
            'lng' => $worker->lng,
            'worker' => $worker,
            'form' => $form
        ]);
    }

    public function get101($id)
    {
        $worker = User::find($id);

        $form = $worker->forms()
            ->where('type', WorkerFormTypeEnum::FORM101)
            ->whereYear('created_at', now()->year)
            ->first();

        return response()->json([
            'lng' => $worker->lng,
            'form' => $form ? $form : NULL,
            'worker' => $worker
        ]);
    }

    public function getWorkContract($id)
    {
        $worker = User::find($id);

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
            'submitted_at' => now()->toDateString(),
            'pdf_name' => $file_name
        ]);

        event(new InsuranceFormSigned($worker, $form));

        return response()->json([
            'message' => 'Insurance form signed successfully.'
        ]);
    }
}
