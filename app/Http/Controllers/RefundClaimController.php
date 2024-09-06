<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RefundClaim;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Enums\NotificationTypeEnum;
use App\Models\Notification;
use Carbon\Carbon;
use App\Traits\NotifyRefundClaim;

class RefundClaimController extends Controller
{
    use NotifyRefundClaim;
    
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $columns = ['id', 'date', 'amount', 'status'];
    
        $length = $request->get('length', 10); // Number of records per page
        $start = $request->get('start', 0); // Pagination start
    
        $order = $request->get('order', []);
        $columnIndex = $order[0]['column'] ?? 0;
        $dir = $order[0]['dir'] ?? 'desc';

    
        $query = RefundClaim::where('user_id', $user->id)
                    ->with('user');
    
        if ($search = $request->get('search')) {
            $query->where(function ($query) use ($search) {
                $query->where('date', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%");
            });
        }
    
        // Sorting
        $query->orderBy($columns[$columnIndex] ?? 'id', $dir);
    
        $totalRecords = $query->count();
        $refundClaim = $query->skip($start)->take($length)->get();
    
        return response()->json([
            'draw' => intval($request->get('draw')),
            'data' => $refundClaim,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
        ]);
    }
    
    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([     
            'date' => 'required|date',
            'amount' => 'required',
            'bill_file' => 'required|file|mimes:pdf,jpg,png',
        ]);


        $reportPath = null;
        if ($request->hasFile('bill_file')) {
            $reportPath = $request->file('bill_file')->store('bill_files', 'public');
        }

        $refundClaim = RefundClaim::create([
            'user_id' => $user->id,
            'user_type' => get_class($user),
            'date' => $validated['date'],
            'amount' => $validated['amount'],
            'bill_file' => $reportPath,
            'status' => 'pending', 
            'paid_status' => 'unpaid',   
        ]);

         //Send notification to admin
         Notification::create([
            'user_id' => $refundClaim->user_id,
            'type' => NotificationTypeEnum::REFUND_CLAIM_REQUEST,  
            'status' => $refundClaim->status
        ]);


        return response()->json($refundClaim, 201);
    
    }

    public function show($id)
    {
        $refundClaim = RefundClaim::findOrFail($id);

        if (!$refundClaim) {
            return response()->json(['message' => 'Data not found'], 404);
        }   
        $refundClaim->bill_file_url = $refundClaim->bill_file ? asset('storage/' . $refundClaim->bill_file) : null;

        return response()->json($refundClaim);
        
    }

    public function update(Request $request, $id)
    {
        $refundClaim = RefundClaim::findOrFail($id);

        if ($refundClaim->status === 'approved') {
            return response()->json(['error' => 'Approved request cannot be updated.'], 403);
        }
       
        // Validate the request data with required fields
        $validated = $request->validate([
            'date' => 'required|date',
            'amount' => 'required',
            'bill_file' => 'file|mimes:pdf,jpg,png',
        ]);
    
        $workerId = Auth::id();
        $user = auth()->user();
    
        $refundClaim->user_id = $workerId;
        $refundClaim->user_type = get_class($user);
        $refundClaim->date = $validated['date'];
        $refundClaim->amount = $validated['amount'];
    
        if ($request->hasFile('bill_file')) {
            if ($refundClaim->bill_file) {
                Storage::disk('public')->delete($refundClaim->bill_file);
            }
            $reportPath = $request->file('bill_file')->store('bill_files', 'public');
            $refundClaim->bill_file = $reportPath;
        }
    
        $refundClaim->save();
    
        return response()->json($refundClaim);
    }
    

    public function destroy($id)
    {
        $refundClaim = RefundClaim::findOrFail($id);
        
        if ($refundClaim->status === 'approved') {
            return response()->json(['error' => 'Approved Request cannot be deleted.'], 403);
        }
        if ($refundClaim->bill_file) {
            Storage::disk('public')->delete($refundClaim->bill_file);
        }
        $refundClaim->delete();
    
        return response()->json(['message' => 'Deleted successfully.'], 204);
    }


    public function allRequests (Request $request)
    {
        $columns = ['id', 'worker_name', 'date', 'amount', 'status'];
    
        $length = $request->get('length', 10);
        $start = $request->get('start', 0);
        $column = $request->get('column', 0);
        $dir = $request->get('dir', 'desc'); 
        $search = $request->get('search', '');
        $status = $request->get('status');
    
        $query = RefundClaim::with('user')
            ->select('refund_claim.*');
    
        // Search filter
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->whereHas('user', function ($query) use ($search) {
                    $query->where('firstname', 'like', "%{$search}%")
                        ->orWhere('lastname', 'like', "%{$search}%");
                })
                ->orWhere('date', 'like', "%{$search}%")
                ->orWhere('amount', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%");
            });
        }
    
        // Status filter
        if ($status !== 'All') {
            $query->where('status', $status);
        }
    
        // Sorting
        $query->orderBy($columns[$column], $dir);
    
        // Pagination
        $totalRecords = $query->count();
        $claimRequests = $query->skip($start)->take($length)->get()
            ->map(function ($claim) {
                $claim->date = Carbon::parse($claim->date)->format('Y-m-d');
                
                $claim->bill_file = $claim->bill_file
                    ? url('storage/bill_files/' . basename($claim->bill_file))
                    : null;
    
                $claim->worker_name = $claim->user ? $claim->user->firstname . ' ' . $claim->user->lastname : 'Unknown';
    
                return $claim;
            });
    
        return response()->json([
            'draw' => intval($request->get('draw')), // Added draw parameter
            'data' => $claimRequests,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
           
             // `recordsFiltered` is same as `recordsTotal` since no separate filtering count is used
        ]);
    }


    public function approve(Request $request,RefundClaim $refundClaim)
    {
       $status = $request->input('status');  
       $rejectionReason = $request->input('rejection_comment'); 

       $validStatuses = ['approved', 'rejected', 'pending'];

        if (!in_array($status, $validStatuses)) {
            return response()->json(['error' => 'Invalid status'], 400);
        }
       $refundClaim->status = $status;

       if ($status === 'approved') {
        $refundClaim->approved_date = now(); // Store the current date
        } else {
            $refundClaim->approved_date = null; // Remove the date if status is not approved
        }

        if ($status === 'rejected') {
            $refundClaim->rejection_comment = $rejectionReason;
        } else {
            $refundClaim->rejection_comment = null; // Clear rejection comment if not rejected
        }

        $refundClaim->save();
        
        $refundClaim->load('user');
        $this->sendClaimNotification($refundClaim);
        return response()->json($refundClaim);
    }
}
