<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Offer;
use App\Models\Schedule;
use App\Models\Contract;
use App\Models\Files;
use App\Models\Client;
use App\Models\ClientPropertyAddress;
use App\Models\ManageTime;
use App\Traits\PriceOffered;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Yajra\DataTables\Facades\DataTables;
use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\WhatsappNotificationEvent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\App;
use App\Jobs\ProcessFileAndNotify;


class DashboardController extends Controller
{
    use PriceOffered;

    public function dashboard()
    {
        $total_jobs      = Job::where('client_id', Auth::user()->id)->count();
        $total_offers    = Offer::where('client_id', Auth::user()->id)->count();
        $latest_jobs     = Job::query()
            ->with(['client', 'service', 'worker', 'jobservice'])
            ->where('client_id', Auth::user()->id)
            ->whereDate('start_date', '>=', today()->toDateString())
            ->orderBy('start_date', 'asc')
            ->take(10)
            ->get();

        return response()->json([
            'total_jobs'         => $total_jobs,
            'total_offers'       => $total_offers,
            'latest_jobs'        => $latest_jobs
        ]);
    }

    //Schedules
    public function meetings(Request $request)
    {
        $query = Schedule::query()
            ->leftJoin('admins', 'schedules.team_id', '=', 'admins.id')
            ->leftJoin('client_property_addresses', 'schedules.address_id', '=', 'client_property_addresses.id')
            ->leftJoin('files', 'files.meeting', '=', 'schedules.id')
            ->where('schedules.client_id', Auth::user()->id)
            ->select('schedules.id', 'schedules.booking_status', 'client_property_addresses.address_name', 'client_property_addresses.latitude', 'client_property_addresses.longitude', 'admins.name as attender_name', 'schedules.start_date', 'schedules.start_time', 'schedules.end_time', 'schedules.purpose', 'client_property_addresses.geo_address')
            ->selectRaw('IF(files.id IS NULL, 0, 1) as file_exists');

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                if (request()->has('search')) {
                    $keyword = request()->get('search')['value'];

                    if (!empty($keyword)) {
                        $query->where(function ($sq) use ($keyword) {
                            $sq->where('admins.name', 'like', "%" . $keyword . "%");
                        });
                    }
                }
            })
            ->orderColumn('start_date', function ($query, $order) {
                $query->orderBy('start_date', $order)
                    ->orderBy('start_time_standard_format', $order);
            })
            ->addColumn('action', function ($data) {
                return '';
            })
            ->rawColumns(['action'])
            ->toJson();
    }

    public function showMeetings($id)
    {
        $schedule = Schedule::query()
            ->with('client', 'team')
            ->where('client_id', Auth::user()->id)
            ->find($id);

        return response()->json([
            'schedule' => $schedule
        ]);
    }

    public function offers(Request $request)
    {
        $query = Offer::query()
            ->where('offers.client_id', Auth::user()->id)
            ->select('offers.id', 'offers.status', 'offers.subtotal', 'offers.total', 'offers.created_at', 'offers.services');

        return DataTables::eloquent($query)
            ->editColumn('created_at', function ($data) {
                return $data->created_at ? Carbon::parse($data->created_at)->format('d/m/Y') : '-';
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

    public function viewOffer(Request $request)
    {
        $offer = Offer::query()
            ->with('client')
            ->where('client_id', Auth::user()->id)
            ->find($request->id);

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

    public function contracts(Request $request)
    {
        $query = Contract::query()
            ->leftJoin('offers', 'offers.id', '=', 'contracts.offer_id')
            ->where('contracts.client_id', Auth::user()->id)
            ->select('contracts.id', 'contracts.status', 'contracts.job_status', 'offers.services', 'contracts.created_at', 'contracts.unique_hash');

        return DataTables::eloquent($query)
            ->editColumn('created_at', function ($data) {
                return $data->created_at ? Carbon::parse($data->created_at)->format('d/m/Y') : '-';
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

    public function getContract($id)
    {
        $contract = Contract::query()
            ->with('client')
            ->where('client_id', Auth::user()->id)
            ->find($id);

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

        $schedule = Schedule::find($request->meeting);
        $schedule->load(['client', 'team', 'propertyAddress']);
        $client = $schedule->client;

        // Process the file synchronously before dispatching the job
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
                $path = $destinationPath . $fname;
                File::exists($destinationPath) or File::makeDirectory($destinationPath, 0777, true, true);
                $img->save($path, 90);
                $file_nm  = $fname;
            }
        }

        ProcessFileAndNotify::dispatch($request->user_id, $client, $schedule, $request->type, $file_nm, $request->note);

        return response()->json([
            'message' => 'File is being uploaded and processed',
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

        $client->is_first_login = $client->first_login;
        
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
            'two_factor_enabled' => ['nullable', 'boolean']
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $input = $request->all();

        if (!isset($input['two_factor_enabled'])) {
            $input['two_factor_enabled'] = true; // or 1
        }

        if ($request->hasfile('avatar')) {
            $image = $request->file('avatar');
            $name = $image->getClientOriginalName();
            $image->storeAs('uploads/client/', $name, 'public');

            $input['avatar'] = $name;
        }

        if ($request->has('twostepverification')) {
            $input['two_factor_enabled'] = $request->input('twostepverification') == 'true';
        }

        $client = Client::find(Auth::user()->id);

        $client->update($input);
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
            $client->passcode = $request->password;
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

    public function getTime()
    {
        return response()->json([
            'data' => ManageTime::where('id', 1)->first()
        ]);
    }



    public function storeAddress(Request $request)
    {
        // Validate the incoming data
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|integer|exists:clients,id',
            'address_name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'geo_address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'zipcode' => 'nullable|string|max:20',
            'floor' => 'nullable|string|max:20',
            'apt_no' => 'nullable|string|max:20',
            'entrence_code' => 'nullable|string|max:20',
            'parking' => 'nullable|string|max:50',
            'prefer_type' => 'nullable|in:default,female,male,both',
            'is_cat_avail' => 'nullable|boolean',
            'is_dog_avail' => 'nullable|boolean',
            'key' => 'nullable|string|max:50',
            'lobby' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create or update the address
        try {
            $propertyAddress = ClientPropertyAddress::create($request->all());
            return response()->json(['message' => 'Address saved successfully', 'address' => $propertyAddress], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to save address. ' . $e->getMessage()], 500);
        }
    }

    public function destroyAddress($id)
    {
        try {
            // Find the address by ID
            $propertyAddress = ClientPropertyAddress::findOrFail($id);

            if ($propertyAddress->client_id !== Auth::user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Delete the address
            $propertyAddress->delete();

            return response()->json(['message' => 'Address deleted successfully'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Address not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete address. ' . $e->getMessage()], 500);
        }
    }

    public function updateAddress(Request $request, $id)
    {
        try {
            // Find the address by ID
            $address = ClientPropertyAddress::findOrFail($id);
    
            // Get only the fields present in the request that are different from the existing data
            $updatedFields = [];
            foreach ($request->all() as $key => $value) {
                if ($address->$key !== $value && $value !== null) {
                    $updatedFields[$key] = $value;
                }
            }
    
            // Check if there are any fields to update
            if (!empty($updatedFields)) {
                $address->update($updatedFields);
    
                return response()->json([
                    'message' => 'Address updated successfully',
                    'data' => $address,
                ], 200);
            }
    
            // No fields to update
            return response()->json([
                'message' => 'No changes detected',
            ], 200);
        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json([
                'message' => 'Failed to update address',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    

    public function getAllPropertyAddresses()
    {
        $total_address = ClientPropertyAddress::where('client_id', Auth::user()->id)->get();
        
        if(!isset($total_address)){
            return response()->json([
                'total_address' => 0
            ]);
        }

        return response()->json([
            'total_address' => $total_address
        ]);
    }

    public function getPropertyAddress($id)
    {
        $address = ClientPropertyAddress::where('client_id', Auth::user()->id)
            ->where('id', $id)->first();
        
        return response()->json([
            'address' => $address
        ]);
    }
}
