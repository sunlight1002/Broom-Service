<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContractStatusEnum;
use App\Enums\LeadStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Client;
use App\Traits\PriceOffered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ContractController extends Controller
{
    use PriceOffered;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $q = $request->q;
        $result = Contract::query()->with(['client', 'offer']);

        $status = '';
        if (strtolower($q) === ContractStatusEnum::UN_VERIFIED) {
            $status = 'un-verified';
        }
        if (strtolower($q) === ContractStatusEnum::VERIFIED) {
            $status = 'verified';
        }
        if (strtolower($q) === ContractStatusEnum::NOT_SIGNED) {
            $status = 'not-signed';
        }
        if (strtolower($q) === ContractStatusEnum::DECLINED) {
            $status = '';
        }

        if ($status != '') {
            $result->orWhere('status', '=', $status);
        }

        $result = $result->orWhereHas('client', function ($qr) use ($q) {
            $qr->where(function ($qr) use ($q) {
                $qr->where(DB::raw('firstname'), 'like', '%' . $q . '%');
                $qr->orWhere(DB::raw('lastname'), 'like', '%' . $q . '%');
                $qr->orWhere(DB::raw('email'), 'like', '%' . $q . '%');
                $qr->orWhere(DB::raw('city'), 'like', '%' . $q . '%');
                $qr->orWhere(DB::raw('street_n_no'), 'like', '%' . $q . '%');
                $qr->orWhere(DB::raw('zipcode'), 'like', '%' . $q . '%');
                $qr->orWhere(DB::raw('phone'), 'like', '%' . $q . '%');
            });
        });

        $result = $result->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'contracts' => $result
        ]);
    }

    public function clientContracts(Request $request)
    {
        $contracts = Contract::query()
            ->with('offer')
            ->where('client_id', $request->id)
            ->orderBy('id', 'desc')
            ->paginate(20);

        $latest = Contract::where('client_id', $request->id)->get()->last();

        return response()->json([
            'contracts' => $contracts,
            'latest'    => $latest
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Contract  $contract
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $contract = Contract::query()
            ->with(['offer', 'client', 'job.propertyAddress'])
            ->find($id);

        $contract['offer']['services'] = $this->formatServices($contract['offer']);

        return response()->json([
            'contract' => $contract
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Contract  $contract
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $contract = Contract::find($id);

        $contract->delete();

        return response()->json([
            'message' => "Contract has been deleted"
        ]);
    }

    public function getContract($id)
    {
        $contract = Contract::query()
            ->with(['client', 'offer', 'card'])
            ->find($id);

        $contract['offer']['services'] = $this->formatServices($contract['offer']);

        return response()->json([
            'contract' => $contract,
        ]);
    }

    public function verify(Request $request)
    {
        $contract = Contract::query()
            ->with('client')
            ->find($request->id);

        if (!$contract) {
            return response()->json([
                'message' => 'Contract not found',
            ], 401);
        }

        $client = $contract->client;
        if (!$client) {
            return response()->json([
                'message' => 'Client not found',
            ], 401);
        }

        $contract->update([
            'status' => ContractStatusEnum::VERIFIED
        ]);

        //login credential send to client after contract verify by admin
        // if($client->status != 2){
        //     App::setLocale($client['lng']);
        //     Mail::send('/Mails/ClientLoginCredentialsMail', $client->toArray(), function ($messages) use ($contract, $client) {
        //         $messages->to($client['email']);
        //         $client['lng'] ?
        //           $sub = __('mail.client_credentials.credentials') . "  " . __('mail.contract.company') . " of client #" . $client['firstname'] ." ". $client['lastname']
        //           :  $sub = $client['firstname'] ." ". $client['lastname'] . "# " . __('mail.client_credentials.credentials') . "  " . __('mail.contract.company');

        //         $messages->subject($sub);
        //     });
        // }

        $client->lead_status()->updateOrCreate(
            [],
            ['lead_status' => LeadStatusEnum::FREEZE_CLIENT]
        );
        $client->update([
            'status' => '2'
        ]);
        return response()->json([
            'message' => 'Contract verified successfully'
        ]);
    }

    public function getContractByClient($id)
    {
        $contracts = Contract::query()
            ->with('offer')
            ->where('client_id', $id)
            ->where('status', ContractStatusEnum::VERIFIED)
            ->orderBy('created_at', 'desc')
            ->get();

        $contracts = $contracts->map(function ($item) {
            $item->offer->services = $this->formatServices($item->offer);
            return $item;
        });

        $client = Client::find($id);
        return response()->json([
            'contract' => $contracts,
            'client' => $client
        ]);
    }

    public function cancelJob(Request $request)
    {
        $msg = '';
        if ($request->job == 'disable') {
            Contract::where('id', $request->id)->update(['job_status' => 0]);
            $msg = 'Contract Job(s) cancelled succesfully!';
        } else {
            Contract::where('id', $request->id)->update(['job_status' => 1]);
            $msg = 'Contract Job(s) resumed succesfully!';
        }

        return response()->json([
            'msg' => $msg
        ]);
    }

    public function saveContractFile(Request $request){
        $data = $request->all();
        $contract = Contract::find($data['contractId']);

        $validator = Validator::make($request->all(), [
            'contractId'=> ['required'],
            'file'      => ['required'],
        ], [], [
            'contractId'    => 'Contract Id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $file = $request->file('file');
        $file_name = '';
        if ($request->hasFile('file')) {
            if (!Storage::disk('public')->exists('uploads/client/contract')) {
                Storage::disk('public')->makeDirectory('uploads/client/contract');
            }
            if (!empty($contract) && $contract->file) {
                if (Storage::drive('public')->exists('uploads/client/contract/' . $contract->file)) {
                    Storage::drive('public')->delete('uploads/client/contract/' . $contract->file);
                }
            }
            $tmp_file_name = $contract->id . "_" . date('s') . "_" . $file->getClientOriginalName();
            if (Storage::disk('public')->putFileAs("uploads/client/contract", $file, $tmp_file_name)) {
                $file_name = $tmp_file_name;
            }
        }
        $contract->update([
            'file' => $file_name,
        ]);

        return response()->json([
            'message' => 'Contact file uploaded!',
        ]);
    }
}
