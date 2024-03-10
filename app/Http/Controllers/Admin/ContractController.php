<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContractStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Client;
use App\Models\ClientCard;
use App\Models\LeadStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
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
        $contracts = Contract::query()
            ->with(['offer', 'client', 'job.propertyAddress'])
            ->find($id);

        return response()->json([
            'contract' => $contracts
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

    public function getContract(Request $request)
    {
        $contract = Contract::query()
            ->with(['client', 'offer'])
            ->where('id', $request->id)
            ->get();
        $card = ClientCard::where('client_id', $contract[0]->client->id)->first();

        $contract[0]['card'] = $card;

        return response()->json([
            'contract' => $contract,
        ]);
    }

    public function verifyContract(Request $request)
    {
        $contract = Contract::query()
            ->with('client')
            ->find($request->id);

        $contract->update([
            'status' => ContractStatusEnum::VERIFIED
        ]);

        LeadStatus::updateOrCreate(
            [
                'client_id' => $contract->client->id,
            ],
            [
                'client_id' => $contract->client->id,
                'lead_status' =>  'Contract Verified'
            ]
        );

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
}
