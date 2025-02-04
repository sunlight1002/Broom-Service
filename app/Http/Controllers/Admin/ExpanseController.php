<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use App\Models\Expanse;
use App\Models\Client;
use App\Models\Setting;
use App\Enums\SettingKeyEnum;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ExpanseController extends Controller
{
    
    public function expanseStore(Request $request)
    {
        $data = $request->all();
        \Log::info($data);
    
        // Initialize scan variable (in case no file is uploaded)
        $scanFile = null;
    
        // Handle invoice file upload
        if ($request->hasFile('invoice')) {
            $invoice = $request->file('invoice');
    
            // Ensure the directory exists
            if (!Storage::disk('public')->exists('uploads/expanses')) {
                Storage::disk('public')->makeDirectory('uploads/expanses');
            }
    
            // Generate unique filename with extension
            $filename = "_invoice_" . time() . "." . $invoice->getClientOriginalExtension();
    
            // Store the file in the 'public/uploads/expanses' folder
            Storage::disk('public')->putFileAs("uploads/expanses", $invoice, $filename);
    
            // Convert file to Base64
            $scanFile = base64_encode(file_get_contents($invoice->getRealPath()));
        }
    
        // Send the expense to iCount
        $icountResponse = $this->expanseIcount($data, $scanFile);
    
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
            'expense_doctype' => $data['expense_doctype'],
            'expense_sum' => $data['expense_sum'],
        ];
    
        // Attach scan file if available
        if ($scanFile) {
            $requestData['scan'] = $scanFile;
        }
        \Log::info($requestData);

    
        // Send the request to iCount API
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $requestData);
    
        // Decode JSON response
        $responseData = $response->json();
        \Log::info($responseData);
        $httpCode = $response->status();
    
        // Handle errors
        if ($httpCode != 200) {
            throw new Exception("Error: Failed to create expense in iCount. Response: " . json_encode($responseData));
        }
    
        return $responseData;
    }
    

}
