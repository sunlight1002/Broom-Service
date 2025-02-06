<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContractStatusEnum;
use App\Enums\LeadStatusEnum;
use App\Models\LeadActivity;
use App\Events\ClientLeadStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Client;
use App\Traits\PriceOffered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use Illuminate\Support\Facades\Mail;
use App\Models\Notification;
use App\Enums\NotificationTypeEnum;
use App\Jobs\SendUninterestedClientEmail;
use Illuminate\Mail\Mailable;
use App\Jobs\SendNotificationJob;
use App\Traits\ICountDocument;


class ContractController extends Controller
{
    use PriceOffered, ICountDocument;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $status = $request->get('status');

        $query = Contract::query()
            ->leftJoin('offers', 'offers.id', '=', 'contracts.offer_id')
            ->leftJoin('clients', 'contracts.client_id', '=', 'clients.id')
            ->when($status != 'All', function ($q) use ($status) {
                return $q->where('contracts.status', $status);
            })
            ->select('contracts.id', 'clients.id as client_id', 'clients.firstname', 'clients.lastname', 'clients.email', 'clients.phone', 'contracts.status', 'contracts.job_status', 'offers.subtotal', 'offers.services', 'contracts.created_at');

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                if (request()->has('search')) {
                    $keyword = request()->get('search')['value'];

                    if (!empty($keyword)) {
                        $query->where(function ($sq) use ($keyword) {
                            $sq->whereRaw("CONCAT_WS(' ', clients.firstname, clients.lastname) like ?", ["%{$keyword}%"])
                                ->orWhere('clients.email', 'like', "%" . $keyword . "%")
                                ->orWhere('clients.phone', 'like', "%" . $keyword . "%");
                        });
                    }
                }
            })
            ->editColumn('client_name', function ($data) {
                return $data->firstname . ' ' . $data->lastname;
            })
            ->filterColumn('client_name', function ($query, $keyword) {
                $sql = "CONCAT_WS(' ', clients.firstname, clients.lastname) like ?";
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->orderColumn('client_name', function ($query, $order) {
                $query->orderBy('clients.firstname', $order);
            })
            ->editColumn('services', function ($data) {
                return json_decode($data->services);
            })
            ->addColumn('action', function ($data) {
                return '';
            })
            ->rawColumns(['action'])
            ->toJson();
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
            ->with(['offer', 'client.property_addresses', 'job.propertyAddress'])
            ->find($id);

        \Log::info($contract);

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
            ->with(['client.property_addresses', 'offer', 'card'])
            ->find($id);

        if ($contract && $contract->offer) {
            // Convert offer to array
            $offerArray = $contract->offer->toArray();

            // Format services
            $formattedServices = $this->formatServices($offerArray);

            // Include formatted services in response without modifying the original model
            $contractData = $contract->toArray();
            $contractData['offer']['services'] = $formattedServices;
        } else {
            $contractData = $contract ? $contract->toArray() : [];
        }

        return response()->json([
            'contract' => $contractData,
        ]);
    }

    public function verify(Request $request)
    {
        $contract = Contract::query()
            ->with('client.property_addresses')
            ->find($request->id);

        if (!$contract) {
            return response()->json([
                'message' => 'Contract not found',
            ], 401);
        }

        $client = $contract->client;
        $address = $contract->client->property_addresses->first();

        // if (!$address) {
        //     return response()->json([
        //         'message' => 'Client address not found',
        //     ])
        // }
        if (!$client) {
            return response()->json([
                'message' => 'Client not found',
            ], 401);
        }

        $contract->update([
            'status' => ContractStatusEnum::VERIFIED
        ]);

        $newLeadStatus = LeadStatusEnum::FREEZE_CLIENT;

        if (!$client->lead_status || $client->lead_status->lead_status != $newLeadStatus) {
            $client->lead_status()->updateOrCreate(
                [],
                ['lead_status' => $newLeadStatus]
            );

            $emailData = [
                'client' => $client->toArray(),
                'status' => $newLeadStatus,
            ];

            SendNotificationJob::dispatch($client, $newLeadStatus, $emailData, $contract);
        }

        // Create user in iCount
        $iCountResponse = $this->createOrUpdateUser($client, $address);

        if ($iCountResponse->status() != 200) {
            return response()->json(['error' => 'Failed to create user in iCount'], 500);
        }

        $iCountData = $iCountResponse->json();

        // Update client with iCount client_id
        if (isset($iCountData['client_id'])) {
            $client->update(['icount_client_id' => $iCountData['client_id']]);
        }

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

    public function saveContractFile(Request $request)
    {
        $data = $request->all();
        $contract = Contract::find($data['contractId']);

        $validator = Validator::make($request->all(), [
            'contractId' => ['required'],
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

    public function setToActiveClient($id){

        $client = Client::find($id);

        if (!$client) {
            return response()->json([
                'message' => 'Client not found!',
            ]);
        }
        $client->status = 2;
        $client->save();
    
        $newLeadStatus = LeadStatusEnum::ACTIVE_CLIENT;
    
        if (!$client->lead_status || $client->lead_status->lead_status != $newLeadStatus) {
            $client->lead_status()->updateOrCreate(
                [],
                ['lead_status' => $newLeadStatus]
            );
    
            event(new ClientLeadStatusChanged($client, $newLeadStatus));
        }
    
        $client->logs()->create([
            'status' => 2,
            'reason' => "",
        ]);
    
        // Log the status change in LeadActivity
        $activity = LeadActivity::create([
            'client_id' => $id,
            'created_date' => now(),
            'status_changed_date' => now(),
            'changes_status' => $newLeadStatus,
        ]);
    
        return response()->json([
            'message' => 'Status has been changed successfully!',
        ]);
    }
}
