<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Admin;
use App\Models\Offer;
use App\Models\Schedule;
use App\Models\Contract;
use App\Models\Files;
use App\Models\Client;
use App\Models\ClientCard;
use App\Models\ClientPropertyAddress;
use App\Models\Notification;
use App\Traits\PriceOffered;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class DashboardController extends Controller
{
    use PriceOffered;

    public function dashboard(Request $request)
    {
        $id              = $request->id;
        $total_jobs      = Job::where('client_id', $id)->count();
        $total_offers    = Offer::where('client_id', $id)->count();
        $total_schedules = Schedule::where('client_id', $id)->count();
        $total_contracts = Contract::where('client_id', $id)->count();
        $latest_jobs     = Job::query()
            ->where('client_id', $id)
            ->with(['client', 'service', 'worker', 'jobservice'])
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'total_jobs'         => $total_jobs,
            'total_offers'       => $total_offers,
            'total_schedules'    => $total_schedules,
            'total_contracts'    => $total_contracts,
            'latest_jobs'        => $latest_jobs
        ]);
    }

    //Schedules
    public function meetings(Request $request)
    {
        $id = $request->id;
        $result = Schedule::with('team');

        if (isset($request->q)) {
            $q = $request->q;

            $result = $result->orWhereHas('team', function ($qr) use ($q, $id) {
                $qr->where(function ($qr) use ($q, $id) {
                    $qr->where('name', 'like', '%' . $q . '%')
                        ->where('client_id', $id);
                });
            });

            $result->orWhere(function ($qry) use ($q, $id) {
                $qry->where('booking_status', 'like', '%' . $q . '%')
                    ->orWhere('end_time',   'like', '%' . $q . '%')
                    ->orWhere('start_date', 'like', '%' . $q . '%')
                    ->orWhere('start_time', 'like', '%' . $q . '%')
                    ->where('client_id', $id);
            });
        }

        $result = $result->where('client_id', $id)->paginate(20);

        return response()->json([
            'schedules' => $result
        ]);
    }

    public function showMeetings($id)
    {
        $schedule = Schedule::where('id', $id)->with('client', 'team')->get()->first();
        return response()->json([
            'schedule' => $schedule
        ]);
    }

    //Offers
    public function offers(Request $request)
    {
        $id = $request->id;
        $result = Offer::query();

        if (isset($request->q)) {
            $q = $request->q;
            $result->orWhere(function ($qry) use ($q, $id) {
                $qry->where('status', 'like', '%' . $q . '%')
                    ->orWhere('total',   'like', '%' . $q . '%')
                    ->where('client_id', $id);
            });
        }

        $result = $result->orderBy('id', 'desc')->where('client_id', $id)->paginate(20);

        return response()->json([
            'offers' => $result
        ]);
    }

    public function viewOffer(Request $request)
    {
        $offer = Offer::query()->with('client')->find($request->id);
        if (isset($offer)) {
            $perhour = false;
            $services = json_decode($offer->services);
            if (isset($services)) {
                foreach ($services as $service) {
                    if ($service->type == 'hourly') {
                        $perhour = true;
                    }
                }
            }
            $offer->services = $this->formatServices($offer);
            ($perhour == true) ? $offer->perhour = 1 : $offer->perhour = 0;
        }
        return response()->json([
            'offer' => $offer
        ]);
    }

    //Contracts
    public function contracts(Request $request)
    {
        $q = $request->q;
        $id = $request->id;
        $result = Contract::with('client', 'offer');
        if (!is_null($q)) {
            $result = $result->orWhereHas('client', function ($qr) use ($q, $id) {
                $qr->where(function ($qr) use ($q, $id) {
                    $qr->where('firstname', 'like', '%' . $q . '%');
                    $qr->orWhere('lastname', 'like', '%' . $q . '%');
                    $qr->orWhere('email', 'like', '%' . $q . '%');
                    $qr->orWhere('city', 'like', '%' . $q . '%');
                    $qr->orWhere('street_n_no', 'like', '%' . $q . '%');
                    $qr->orWhere('zipcode', 'like', '%' . $q . '%');
                    $qr->orWhere('phone', 'like', '%' . $q . '%');
                    $qr->where('client_id', $id);
                });
            });

            $result->orWhere(function ($qry) use ($q, $id) {
                $qry->where('status', 'like', '%' . $q . '%')
                    ->where('client_id', $id);
            });
        }

        $result = $result->orderBy('id', 'desc')->where('client_id', $id)->paginate(20);

        return response()->json([
            'contracts' => $result
        ]);
    }

    public function getContract(Request $request)
    {
        $contract = Contract::where('id', $request->id)->with('client')->get();

        return response()->json([
            'contract' => $contract
        ]);
    }

    public function addfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role'   => 'required',
            'user_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()]);
        }

        $file_nm = '';
        if ($request->type == 'video') {

            $video = $request->file('file');
            $vname = $request->user_id . "_" . date('s') . "_" . $video->getClientOriginalName();
            $path = storage_path() . '/app/public/uploads/ClientFiles';
            $video->move($path, $vname);
            $file_nm = $vname;
        } else {
            if ($request->hasfile('file')) {

                $image = $request->file('file');
                $name = $image->getClientOriginalName();
                $img = Image::make($image)->resize(350, 227);
                $destinationPath = storage_path() . '/app/public/uploads/ClientFiles/';
                $fname = 'file_' . $request->user_id . '_' . date('s') . '_' . $name;
                $path = storage_path() . '/app/public/uploads/ClientFiles/' . $fname;
                File::exists($destinationPath) or File::makeDirectory($destinationPath, 0777, true, true);
                $img->save($path, 90);
                $file_nm  = $fname;
            }
        }

        Files::create([
            'user_id'   => $request->user_id,
            'meeting'   => $request->meeting,
            'note'      => $request->note,
            'role'      => 'client',
            'type'      => $request->type,
            'file'      => $file_nm
        ]);

        return response()->json([
            'message' => 'File uploaded',
        ]);
    }

    public function getfiles(Request $request)
    {
        $files = Files::where([
            'user_id' => $request->id,
            'role' => 'client',
            'meeting' => $request->meet_id
        ])->get();

        if (isset($files)) {
            foreach ($files as $k => $file) {
                $files[$k]->path = asset('storage/uploads/ClientFiles') . "/" . $file->file;
            }
        }

        return response()->json([
            'files' => $files
        ]);
    }

    public function deletefile(Request $request)
    {
        $file = Files::find($request->id);
        $file->delete();

        return response()->json([
            'message' => 'File deleted',
        ]);
    }

    public function getAccountDetails()
    {
        $client = Auth::user();

        if (!$client) {
            return response()->json([
                'error' => [
                    'message' => 'Account not found!',
                    'code' => 404
                ]
            ], 404);
        }

        $client->primary_address = ClientPropertyAddress::where('client_id', $client->id)->first();
        $client->avatar = $client->avatar ? Storage::disk('public')->url('uploads/client/' . $client->avatar) : asset('images/man.png');

        return response()->json([
            'account' => $client,
        ]);
    }

    public function saveAccountDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'invoicename' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:clients,email,' . Auth::user()->id],
            'city' => ['required', 'string', 'max:255'],
            'street_n_no' => ['required', 'string', 'max:255'],
            'dob' => ['required'],
            'floor' => ['required'],
            'entrence_code' => ['required'],
            'lng' => ['required'],
            'apt_no' => ['required'],
            'zipcode' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $input = $request->all();
        if ($request->hasfile('avatar')) {
            $image = $request->file('avatar');
            $name = $image->getClientOriginalName();
            $image->storeAs('uploads/client/', $name, 'public');

            $input['avatar'] = $name;
        }

        Client::where('id', Auth::user()->id)->update($input);
        return response()->json([
            'message' => 'Account details updated successfully',
        ]);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'min:6'],
            'password' => ['required', 'min:6', 'confirmed']
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $client = Client::find(Auth::user()->id);
        if (Hash::check($request->get('current_password'), $client->password)) {
            $client->password = Hash::make($request->password);
            $client->save();

            return response()->json([
                'message' => 'Password changed successfully',
            ]);
        } else {
            return response()->json([
                'errors' => [
                    'current_password' => 'Current password is incorrect.'
                ]
            ]);
        }

        return response()->json([
            'message' => 'Password changed successfully',
        ]);
    }

    public function getCard()
    {
        $res = ClientCard::where('client_id', Auth::user()->id)->get();

        return response()->json([
            'res' => $res,
        ]);
    }

    public function updateCard(Request $request)
    {
        if (isset($request->cdata['cid'])) {
            $cc = ClientCard::query()
                ->select('cc_charge')
                ->find($request->cdata['cid']);

            $nc = (int)$cc->cc_charge +  (int)$request->cdata['cc_charge'];
            $args = [
                'card_type'   => $request->cdata['card_type'],
                'card_number' => $request->cdata['card_number'],
                'valid'       => $request->cdata['valid'],
                'cvv'         => $request->cdata['cvv'],
                'cc_charge'   => $nc,
                'card_token'  => $request->cdata['card_token'],
            ];

            $cc->update($args);
        } else {
            $args = [
                'card_type'   => $request->cdata['card_type'],
                'client_id'   => Auth::user()->id,
                'card_number' => $request->cdata['card_number'],
                'valid'       => $request->cdata['valid'],
                'cvv'         => $request->cdata['cvv'],
                'cc_charge'   => $request->cdata['cc_charge'],
                'card_token'  => $request->cdata['card_token'],
            ];

            ClientCard::create($args);
        }

        return response()->json([
            'message' => "Card validated successfully"
        ]);
    }

    public function listJobs(Request $request)
    {
        $jobs = Job::query()
            ->with(['offer', 'client', 'worker', 'jobservice'])
            ->where('client_id', $request->cid)
            ->orderBy('start_date')
            ->get();

        return response()->json([
            'jobs' => $jobs,
        ]);
    }

    public function viewJob(Request $request)
    {
        $job = Job::query()
            ->with(['client', 'worker', 'service', 'offer', 'jobservice'])
            ->find($request->id);

        return response()->json([
            'job' => $job,
        ]);
    }

    public function cancelJob(Request $request, $id)
    {
        $job = Job::with(['client', 'offer', 'worker', 'jobservice'])
            ->find($id);

        $feePercentage = Carbon::parse($job->start_date)->diffInDays(today(), false) <= -1 ? 50 : 100;
        $feeAmount = ($feePercentage / 100) * $job->offer->total;

        $job->update([
            'status' => 'cancel',
            'cancellation_fee_percentage' => $feePercentage,
            'cancellation_fee_amount' => $feeAmount,
            'cancelled_by_role' => 'client',
            'cancelled_at' => now()
        ]);

        Notification::create([
            'user_id' => $job->client->id,
            'type' => 'client-cancel-job',
            'job_id' => $job->id,
            'status' => 'declined'
        ]);

        $admin = Admin::find(1)->first();
        App::setLocale('en');
        $data = array(
            'by'         => 'client',
            'email'      => $admin->email,
            'admin'      => $admin->toArray(),
            'job'        => $job->toArray(),
        );

        Mail::send('/ClientPanelMail/JobStatusNotification', $data, function ($messages) use ($data) {
            $messages->to($data['email']);
            $sub = __('mail.client_job_status.subject');
            $messages->subject($sub);
        });

        return response()->json([
            'job' => $job,
        ]);
    }
}
