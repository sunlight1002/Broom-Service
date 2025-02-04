<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use App\Models\Expanse;
use App\Models\Client;
use App\Models\Expenses;
use App\Models\Setting;
use App\Enums\SettingKeyEnum;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ExpanseController extends Controller
{

    public function index(Request $request)
    {
        $columns = [
            'supplier_name',
            'supplier_vat_id',
            'expense_type_name',
            'expense_docnum',
            'expense_sum',
            'upload_file',
        ];
    
        $length = $request->get('length', 10); // Number of records per page
        $start = $request->get('start', 0); // Starting index
        $order = $request->get('order', []); // Ordering data
        $columnIndex = $order[0]['column'] ?? 0; // Column index for sorting
        $dir = $order[0]['dir'] ?? 'desc'; // Sort direction (asc/desc)
    
        // Base query for Expenses
        $query = Expenses::query();
    
        // Search functionality
        if ($search = $request->get('search')['value'] ?? null) {
            $query->where(function ($query) use ($search, $columns) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', "%{$search}%");
                }
            });
        }
    
        // Sorting
        if (isset($columns[$columnIndex])) {
            $query->orderBy($columns[$columnIndex], $dir);
        }
    
        // Get total records before filtering
        $totalRecords = Expenses::count();
    
        // Get filtered records
        $filteredRecords = $query->count();
    
        // Apply pagination
        $expenses = $query->skip($start)->take($length)->get();
    
        // Response
        return response()->json([
            'filter' => $request->filter,
            'draw' => intval($request->get('draw')),
            'data' => $expenses,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
        ]);
    }
    
    
    public function expanseStore(Request $request)
    {
        $data = $request->all();
        \Log::info($data);
        $request->validate([
            'supplier_name' => 'required',
            'supplier_vat_id' => 'required',
            'expense_type_name' => 'required',
            'expense_sum' => 'required',
            'expense_docnum' => 'required',
        ]);
    
        // Initialize scan variable (in case no file is uploaded)
        $scanFile = null;
    
        // Handle invoice file upload
        if ($request->hasFile('scan')) {
            $scanFile = $request->file('scan');
    
            // Ensure the directory exists
            if (!Storage::disk('public')->exists('uploads/expanses')) {
                Storage::disk('public')->makeDirectory('uploads/expanses');
            }
    
            // Generate unique filename with extension
            $filename = "_invoice_" . time() . "." . $scanFile->getClientOriginalExtension();
    
            // Store the file in the 'public/uploads/expanses' folder
            Storage::disk('public')->putFileAs("uploads/expanses", $scanFile, $filename);
    
            // Convert file to Base64
            $scanFile = base64_encode(file_get_contents($scanFile->getRealPath()));
        }
    
        // Send the expense to iCount
        $icountResponse = $this->expanseIcount($data, $scanFile);
    
        Expenses::create([
            'supplier_id' => $data['supplier_id'] ?? null,
            'supplier_name' => $data['supplier_name'] ?? null,
            'supplier_vat_id' => $data['supplier_vat_id'] ?? null,
            'expense_id' => $icountResponse['expense_id'] ?? null,
            'expense_type_id' => $data['expense_type_id'] ?? null,
            'expense_type_name' => $data['expense_type_name'] ?? null,
            'expense_docnum' => $data['expense_docnum'] ?? null, 
            'expense_sum' => $data['expense_sum'] ?? null,
            'upload_file' => $filename ?? null
        ]);

        return response()->json([
            'message' => 'Document has been created successfully!',
            'icount_response' => $icountResponse,
        ]);
    }

    public function getExpenseTypes() {
        $iCountCompanyID = Setting::where('key', SettingKeyEnum::ICOUNT_COMPANY_ID)->value('value');
        $iCountUsername = Setting::where('key', SettingKeyEnum::ICOUNT_USERNAME)->value('value');
        $iCountPassword = Setting::where('key', SettingKeyEnum::ICOUNT_PASSWORD)->value('value');
    
        $url = 'https://api.icount.co.il/api/v3.php/expense_type/get_list';
    
        // Prepare API request data
        $requestData = [
            'cid' => $iCountCompanyID,
            'user' => $iCountUsername,
            'pass' => $iCountPassword,
            // 'list_type' => true
        ];
    
        // Send the request to iCount API
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $requestData);
    
        // Decode JSON response
        $responseData = $response->json();
        $httpCode = $response->status();
    
        // Handle errors
        if ($httpCode != 200) {
            throw new Exception("Error: Failed to create expense in iCount. Response: " . json_encode($responseData));
        }
    
        return $responseData;
    }

    public function getExpenseDoctypes() {
        $iCountCompanyID = Setting::where('key', SettingKeyEnum::ICOUNT_COMPANY_ID)->value('value');
        $iCountUsername = Setting::where('key', SettingKeyEnum::ICOUNT_USERNAME)->value('value');
        $iCountPassword = Setting::where('key', SettingKeyEnum::ICOUNT_PASSWORD)->value('value');
    
        $url = 'https://api.icount.co.il/api/v3.php/expense/doctypes';
    
        // Prepare API request data
        $requestData = [
            'cid' => $iCountCompanyID,
            'user' => $iCountUsername,
            'pass' => $iCountPassword,
        ];
    
        // Send the request to iCount API
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $requestData);
    
        // Decode JSON response
        $responseData = $response->json();
        $httpCode = $response->status();
    
        // Handle errors
        if ($httpCode != 200) {
            throw new Exception("Error: Failed to create expense in iCount. Response: " . json_encode($responseData));
        }
    
        return $responseData;
    }

    public function getSuplierList() {
        $iCountCompanyID = Setting::where('key', SettingKeyEnum::ICOUNT_COMPANY_ID)->value('value');
        $iCountUsername = Setting::where('key', SettingKeyEnum::ICOUNT_USERNAME)->value('value');
        $iCountPassword = Setting::where('key', SettingKeyEnum::ICOUNT_PASSWORD)->value('value');
    
        $url = 'https://api.icount.co.il/api/v3.php/supplier/get_list';
    
        // Prepare API request data
        $requestData = [
            'cid' => $iCountCompanyID,
            'user' => $iCountUsername,
            'pass' => $iCountPassword,
        ];
    
        // Send the request to iCount API
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $requestData);
    
        // Decode JSON response
        $responseData = $response->json();
        $httpCode = $response->status();
    
        // Handle errors
        if ($httpCode != 200) {
            throw new Exception("Error: Failed to create expense in iCount. Response: " . json_encode($responseData));
        }
    
        return $responseData;
    }
    
    private function expanseIcount($data, $scanFile = null)
    {
        $iCountCompanyID = Setting::where('key', SettingKeyEnum::ICOUNT_COMPANY_ID)->value('value');
        $iCountUsername = Setting::where('key', SettingKeyEnum::ICOUNT_USERNAME)->value('value');
        $iCountPassword = Setting::where('key', SettingKeyEnum::ICOUNT_PASSWORD)->value('value');
    
        $url = 'https://api.icount.co.il/api/v3.php/expense/create';
    
        // Prepare API request data
        $requestData = [
            'cid' => $iCountCompanyID,
            'user' => $iCountUsername,
            'pass' => $iCountPassword,
            'supplier_id' => $data['supplier_id'],
            'expense_type_id' => $data['expense_type_id'],
            'expense_doctype' => $data['expense_type_name'],
            'expense_sum' => $data['expense_sum'],
            'expense_docnum' => $data['expense_docnum']
        ];
    
        // Attach scan file if available
        if ($scanFile) {
            $requestData['scan'] = $scanFile;
        }
    
        // Send the request to iCount API
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $requestData);
    
        // Decode JSON response
        $responseData = $response->json();
        $httpCode = $response->status();
    
        // Handle errors
        if ($httpCode != 200) {
            throw new Exception("Error: Failed to create expense in iCount. Response: " . json_encode($responseData));
        }
    
        return $responseData;
    }
    

}
