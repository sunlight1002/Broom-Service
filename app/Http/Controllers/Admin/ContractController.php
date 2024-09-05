<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContractStatusEnum;
use App\Enums\LeadStatusEnum;
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

        $newLeadStatus = LeadStatusEnum::FREEZE_CLIENT;

        if ($client->lead_status->lead_status != $newLeadStatus) {
            $client->lead_status()->updateOrCreate(
                [],
                ['lead_status' => $newLeadStatus]
            );

            event(new ClientLeadStatusChanged($client, $newLeadStatus));

            $emailData = [
                'client' => $client->toArray(),
                'status' => $newLeadStatus,
            ];

            if($newLeadStatus === 'freeze client'){
                // Trigger WhatsApp Notification
                event(new WhatsappNotificationEvent([
                   "type" => WhatsappMessageTemplateEnum::CLIENT_IN_FREEZE_STATUS,
                   "notificationData" => [
                       'client' => $client->toArray(),
                   ]
               ]));
           }
            
           if ($client->notification_type === "both") {
            if ($newLeadStatus === 'unanswered') {
      
                Notification::create([
                    'user_id' => $client->id,
                    'user_type' => get_class($client),
                    'type' => NotificationTypeEnum::UNANSWERED_LEAD,
                    'status' => $newLeadStatus
                ]);
      
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));
      
                Mail::send('Mails.UnansweredLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                    $messages->to($emailData['client']['email']);
                    $sub = __('mail.unanswered_lead.header');
                    $messages->subject($sub);
                });
            }
            
            if ($newLeadStatus === 'irrelevant') {
      
                Notification::create([
                    'user_id' => $client->id,
                    'user_type' => get_class($client),
                    'type' => NotificationTypeEnum::INQUIRY_RESPONSE, 
                    'status' => $newLeadStatus
                ]);
      
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));
      
                Mail::send('Mails.IrrelevantLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                    $messages->to($emailData['client']['email']);
                    $sub = __('mail.irrelevant_lead.header');
                    $messages->subject($sub);
                });
            }; 
            
            Notification::create([
                'user_id' => $client->id,
                'user_type' => Client::class,
                'type' => NotificationTypeEnum::USER_STATUS_CHANGED, 
                'status' => $newLeadStatus
            ]);
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::USER_STATUS_CHANGED,
                    "notificationData" => [
                        'client' => $client->toArray(),
                        'status' => $newLeadStatus,
                    ]
                ]));
      
                Mail::send('Mails.UserChangedStatus', $emailData, function ($messages) use ($emailData) {
                    $messages->to($emailData['client']['email']);
                    $sub = __('mail.user_status_changed.header');
                    $messages->subject($sub);
                });
            
          } elseif ($client->notification_type === "email") {
            if ($newLeadStatus === 'unanswered') {
      
                Notification::create([
                    'user_id' => $client->id,
                    'user_type' => get_class($client),
                    'type' => NotificationTypeEnum::UNANSWERED_LEAD, 
                    'status' => $newLeadStatus
                ]);
      
                Mail::send('Mails.UnansweredLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                    $messages->to($emailData['client']['email']);
                    $sub = __('mail.unanswered_lead.header');
                    $messages->subject($sub);
                });
            }
            if ($newLeadStatus === 'irrelevant') {
                Notification::create([
                    'user_id' => $client->id,
                    'user_type' => get_class($client),
                    'type' => NotificationTypeEnum::INQUIRY_RESPONSE,
                    'status' => $newLeadStatus
                ]);
                Mail::send('Mails.IrrelevantLead', ['client' => $emailData['client']], function ($messages) use ($emailData) {
                    $messages->to($emailData['client']['email']);
                    $sub = __('mail.irrelevant_lead.header');
                    $messages->subject($sub);
                });
            }
      
            Notification::create([
                'user_id' => $client->id,
                'user_type' => Client::class,
                'type' => NotificationTypeEnum::USER_STATUS_CHANGED, 
                'status' => $newLeadStatus
            ]);
                Mail::send('Mails.UserChangedStatus', $emailData, function ($messages) use ($emailData) {
                    $messages->to('pratik.panchal@spexiontechnologies.com');
                    $sub = __('mail.user_status_changed.header');
                    $messages->subject($sub);
                });
            
          } else {
            if ($newLeadStatus === 'unanswered') {
      
                Notification::create([
                    'user_id' => $client->id,
                    'user_type' => get_class($client),
                    'type' => NotificationTypeEnum::UNANSWERED_LEAD, 
                    'status' => $newLeadStatus
                ]);
      
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));
            }
            if ($newLeadStatus === 'irrelevant') {
      
                Notification::create([
                    'user_id' => $client->id,
                    'user_type' => get_class($client),
                    'type' => NotificationTypeEnum::INQUIRY_RESPONSE, 
                    'status' => $newLeadStatus
                ]);
      
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
                    "notificationData" => [
                        'client' => $client->toArray(),
                    ]
                ]));
            }
      
            Notification::create([
                'user_id' => $client->id,
                'user_type' => get_class($client),
                'type' => NotificationTypeEnum::USER_STATUS_CHANGED,  
                'status' => $newLeadStatus
            ]);
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::USER_STATUS_CHANGED,
                    "notificationData" => [
                        'client' => $client->toArray(),
                        'status' => $newLeadStatus,
                    ]
                ]));
            }
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
}
