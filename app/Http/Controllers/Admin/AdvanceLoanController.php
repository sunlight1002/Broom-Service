<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdvanceLoan;
use App\Models\AdvanceLoanTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class AdvanceLoanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = auth()->user();    
        $query = AdvanceLoan::with('worker')
                    ->where('worker_id', $user->id);
    
        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function($q) use ($request) {
                $q->where('type', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('status', 'LIKE', '%' . $request->search . '%');
            });
        }
    
        // Apply status filter if provided
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }
    
        $advanceLoans = $query->orderBy('created_at', 'desc')
            ->paginate($request->length);
    
        $data = $advanceLoans->map(function ($advanceLoan) {
            $latestTransaction = AdvanceLoanTransaction::where('advance_loan_id', $advanceLoan->id)
                ->orderBy('transaction_date', 'desc')
                ->first();
    
            $totalPaidAmount = AdvanceLoanTransaction::where('advance_loan_id', $advanceLoan->id)
                ->where('type', 'credit')
                ->sum('amount');
    
            $latestPendingAmount = $latestTransaction ? $latestTransaction->pending_amount : 0;
            
            return [
                'id' => $advanceLoan->id,
                'worker_name' => $advanceLoan->worker->firstname . ' ' . $advanceLoan->worker->lastname,
                'type' => $advanceLoan->type,
                'amount' => $advanceLoan->amount,
                'monthly_payment' => $advanceLoan->monthly_payment,
                'loan_start_date' => $advanceLoan->loan_start_date,
                'status' => $advanceLoan->status,
                'created_at' => $advanceLoan->created_at->format('Y-m-d'),
                'latest_pending_amount' => $latestPendingAmount,
                'total_paid_amount' => $totalPaidAmount,
            ];
        });
    
        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => $advanceLoans->total(),
            'recordsFiltered' => $advanceLoans->total(),
            'data' => $data,
        ]);
    }
    

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'worker_id' => 'exists:users,id',
            'type' => 'required',
            'amount' => 'required|numeric|min:0',
            'monthly_payment' => 'nullable|required_if:type,loan|numeric|min:0',
            'loan_start_date' => 'nullable|required_if:type,loan|date',
        ]);

        $advanceLoan = AdvanceLoan::create([
            'worker_id' => $request->worker_id,
            'type' => $request->type,
            'amount' => $request->amount,
            'monthly_payment' => $request->monthly_payment,
            'loan_start_date' => $request->loan_start_date,
        ]);

        AdvanceLoanTransaction::create([
            'advance_loan_id' => $advanceLoan->id, // The ID of the advance/loan
            'worker_id' => $request->worker_id,
            'type' => 'debit', // Assuming 'debit' is the type of transaction for creating a loan/advance
            'amount' => $request->amount,
            'pending_amount' => $request->amount,
            'transaction_date' => now(),
        ]);


        return response()->json(['message' => 'Advance/Loan recorded successfully', 'data' => $advanceLoan], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($worker_id)
    {
        $worker = User::findOrFail($worker_id);
    
        $advanceLoans = AdvanceLoan::with('worker:id,firstname,lastname')
            ->orderBy('created_at', 'desc')
            ->where('worker_id', $worker_id)
            ->get()
            ->map(function ($advanceLoan) {
                $latestTransaction = AdvanceLoanTransaction::where('advance_loan_id', $advanceLoan->id)
                    ->orderBy('transaction_date', 'desc')
                    ->first();

                $totalPaidAmount = AdvanceLoanTransaction::where('advance_loan_id', $advanceLoan->id)
                ->where('type', 'credit')
                ->sum('amount');

                    $latestPendingAmount = $latestTransaction ? $latestTransaction->pending_amount : 0;
                return [
                    'id' => $advanceLoan->id,
                    'worker_name' => $advanceLoan->worker->firstname . ' ' . $advanceLoan->worker->lastname,
                    'type' => $advanceLoan->type,
                    'amount' => $advanceLoan->amount,
                    'monthly_payment' => $advanceLoan->monthly_payment,
                    'loan_start_date' => $advanceLoan->loan_start_date,
                    'status' => $advanceLoan->status,
                    'created_at' => $advanceLoan->created_at->format('Y-m-d'),
                    'latest_pending_amount' => $latestPendingAmount,
                    'total_paid_amount' => $totalPaidAmount,
                ];
            });
        
    
        return response()->json($advanceLoans);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'worker_id' => 'exists:users,id',
            'type' => 'required',
            'amount' => 'required|numeric|min:0',
            'monthly_payment' => 'nullable|required_if:type,loan|numeric|min:0',
            'loan_start_date' => 'nullable|required_if:type,loan|date',
        ]);
    
        $advanceLoan = AdvanceLoan::findOrFail($id);
    
        $hasCreditEntry = AdvanceLoanTransaction::where('advance_loan_id', $id)
                            ->where('type', 'credit')
                            ->exists();
    
        if ($hasCreditEntry) {
            return response()->json([
                'message' => 'There are credit entries for this advance/loan. Can not upadte it.',
                'data' => $advanceLoan
            ], 400);
        }    
        $advanceLoan->update([
            'worker_id' => $request->worker_id,
            'type' => $request->type,
            'amount' => $request->amount,
            'monthly_payment' => $request->monthly_payment,
            'loan_start_date' => $request->loan_start_date,
        ]);
    
        $debitTransaction = AdvanceLoanTransaction::where('advance_loan_id', $id)
                            ->where('type', 'debit')
                            ->first();

        if ($debitTransaction) {
            $debitTransaction->update([
                'amount' => $request->amount,
                'pending_amount' => $request->amount,
                'transaction_date' => now(),
            ]);
        }
    
        return response()->json(['message' => 'Advance/Loan updated successfully', 'data' => $advanceLoan], 200);
    }
    

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $advanceLoan = AdvanceLoan::findOrFail($id);

        $hasCreditEntry = AdvanceLoanTransaction::where('advance_loan_id', $id)
            ->where('type', 'credit')
            ->exists();
    
        if ($hasCreditEntry) {
            return response()->json([
                'message' => 'There are credit entries for this advance/loan. Cannot delete it.',
                'data' => $advanceLoan
            ], 400);
        }
    
        AdvanceLoanTransaction::where('advance_loan_id', $advanceLoan->id)->delete();
        $advanceLoan->delete();
    
        return response()->json(['message' => 'Advance/Loan deleted successfully']);
    }


}

