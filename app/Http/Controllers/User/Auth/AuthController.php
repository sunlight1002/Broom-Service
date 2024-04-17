<?php

namespace App\Http\Controllers\User\Auth;

use App\Enums\WorkerFormTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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
        $worker->email         = $request->email;
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

    public function WorkContract(Request $request)
    {
        $data = $request->all();
        try {
            $worker = User::where('worker_id', $data['worker_id'])->first();

            if (!$worker) {
                return response()->json([
                    'message' => 'Worker not found',
                ], 404);
            }

            $form = $worker->forms()
                ->where('type', WorkerFormTypeEnum::CONTRACT)
                ->whereYear('created_at', now()->year)
                ->first();

            if ($form) {
                return response()->json([
                    'message' => 'Contract already submitted for current year.'
                ], 403);
            }

            $worker->forms()->create([
                'type' => WorkerFormTypeEnum::CONTRACT,
                'data' => $data['worker_contract_json']
            ]);

            return response()->json([
                'message' => 'Contract signed successfully. Thanks, for accepting contract.'
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    public function form101(Request $request)
    {
        $worker = User::find($request->id);

        if (!$worker) {
            return response()->json([
                'message' => 'Worker not found',
            ], 404);
        }

        $data = $request->all();

        $form = $worker->forms()
            ->where('type', WorkerFormTypeEnum::FORM101)
            ->whereYear('created_at', now()->year)
            ->first();

        if ($form) {
            return response()->json([
                'message' => 'Form 101 already submitted for current year.'
            ], 403);
        }

        $worker->forms()->create([
            'type' => WorkerFormTypeEnum::FORM101,
            'data' => $data['data']
        ]);

        return response()->json([
            'message' => 'Form 101 signed successfully.'
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
            'success_code' => 200,
            'lng' => $worker->lng,
            'form' => $form ? $form->data : NULL
        ]);
    }

    public function getWorkContract($id){
        $worker = User::find($id);

        $form = $worker->forms()
            ->where('type', WorkerFormTypeEnum::CONTRACT)
            ->whereYear('created_at', now()->year)
            ->first();

        return response()->json([
            'success_code' => 200,
            'worker' => $worker,
            'form' => $form ? $form->data : NULL
        ]);
    }
    // public function pdf101($id)
    // {
    //     $user = User::find(base64_decode($id))->toArray();
    //     $form = json_decode($user['form_101'], true);
    //     $form['data']['signed_on'] = $user['created_at'];
    //     $f = $form['data'];
    //     $pdf = Pdf::loadView('pdf101', compact('f'));
    //     $paper_size = array(0, 0, 800, 1000);
    //     $pdf->set_paper($paper_size);

    //     return $pdf->stream('form101_' . $user['id'] . '.pdf');
    // }
}
