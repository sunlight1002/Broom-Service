<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Contract;

class CvvController extends Controller
{
    // Search clients and return their card details from contract form_data
    public function search(Request $request)
    {
        $query = $request->input('client', '');
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $search = $request->input('search.value', '');
        
        $clients = Client::query();
        
        if (!empty($query)) {
            $clients = $clients->where(function($q) use ($query) {
                $q->where('firstname', 'like', "%$query%")
                  ->orWhere('lastname', 'like', "%$query%")
                  ->orWhere('email', 'like', "%$query%");
            });
        }
        
        if (!empty($search)) {
            $clients = $clients->where(function($q) use ($search) {
                $q->where('firstname', 'like', "%$search%")
                  ->orWhere('lastname', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }
        
        $totalRecords = $clients->count();
        
        $clients = $clients->skip($start)->take($length)->get();
        
        $data = [];
        $totalDisplayRecords = 0;
        
        foreach ($clients as $client) {
            $contracts = Contract::where('client_id', $client->id)->get();
            $cards = [];
            
            foreach ($contracts as $contract) {
                if ($contract->form_data) {
                    // Handle form_data - it might be already an array or a JSON string
                    $formData = is_array($contract->form_data) 
                        ? $contract->form_data 
                        : json_decode($contract->form_data, true);
                    
                    // Look for card information in form_data
                    if ($formData && isset($formData['card_type']) && isset($formData['cvv'])) {
                        $cards[] = [
                            'card_type' => $formData['card_type'] ?? 'N/A',
                            'cvv' => $formData['cvv'] ?? 'N/A',
                            'contract_id' => $contract->id,
                        ];
                    }
                }
            }
            
            // If client has no cards, add an empty card entry
            if (empty($cards)) {
                $cards[] = [
                    'card_type' => 'N/A',
                    'cvv' => 'N/A',
                    'contract_id' => null,
                ];
            }
            
            $data[] = [
                'client_id' => $client->id,
                'client_name' => $client->firstname . ' ' . $client->lastname,
                'client_email' => $client->email,
                'cards' => $cards,
                'created_at' => $client->created_at
            ];
            $totalDisplayRecords++;
        }
        
        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalDisplayRecords,
            'data' => $data
        ]);
    }
} 