<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\Document;
use App\Models\Form;
use App\Models\Admin;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Services\GeminiVisaExtractorService;
class DocumentController extends Controller
{
    public function documents($id)
    {
        $worker = User::find($id);

        if (!$worker) {
            return response()->json([
                'error' => 'Worker not found'
            ], 404);
        }

        $documents = $worker->documents()
            ->with(['document_type' => function ($query) {
                return $query->select(['id', 'name', 'slug']);
            }])
            ->where('userable_type', 'App\Models\User')
            ->get();

        return response()->json([
            'documents' => $documents
        ]);
    }

    public function adminDocuments($id)
    {
        $user = Admin::find($id);
    
        if (!$user) {
            return response()->json([
                'error' => 'Admin not found'
            ], 404);
        }
    
        $documents = $user->documents()
            ->with(['document_type' => function ($query) {
                return $query->select(['id', 'name']);
            }])
            ->where('userable_type', Admin::class)
            ->get();
    
        return response()->json([
            'documents' => $documents
        ]);
    }
    

    public function save(Request $request)
    {
        $data = $request->all();
        $user = User::find($data['id']);
        $aiExtractor = new GeminiVisaExtractorService();
        
        if ($request->file('visa') || $request->file('passport') || $request->file('id_card')) {
            
            // Handle visa file upload
            if ($request->hasFile('visa')) {
                $visa_file = $request->file('visa');
                
                if (!Storage::disk('public')->exists('uploads/documents')) {
                    Storage::disk('public')->makeDirectory('uploads/documents');
                }
                
                $tmp_file_name = $user->id . "_visa_" . date('s') . "_" . $visa_file->getClientOriginalName();
                
                if (Storage::disk('public')->putFileAs("uploads/documents", $visa_file, $tmp_file_name)) {
                    $user->visa = $tmp_file_name;
                    $user->save();

                    $fullPath = Storage::disk('public')->path('uploads/documents/' . $tmp_file_name);
                    $aiResult = $aiExtractor->extractExpiryDateAndNumber($fullPath);
                    if ($aiResult['expiry_date'] || $aiResult['number']) {
                        $user->renewal_visa = $aiResult['expiry_date'];
                        $user->id_number = $aiResult['number'];
                        $user->save();
                        if ($user->renewal_visa) {
                            $expiry = \Carbon\Carbon::parse($user->renewal_visa);
                            if ($expiry->isPast()) {
                                $user->save();
                            }
                        }
                    }
                }
            }
    
            // Handle passport file upload with delete check
            if ($request->hasFile('passport')) {
                // Delete existing passport file if present
                if ($user->passport && Storage::disk('public')->exists('uploads/documents/' . $user->passport)) {
                    Storage::disk('public')->delete('uploads/documents/' . $user->passport);
                }
    
                $pasport_file = $request->file('passport');
                $tmp_file_name = $user->id . "_passport_" . date('s') . "_" . $pasport_file->getClientOriginalName();
                
                if (Storage::disk('public')->putFileAs("uploads/documents", $pasport_file, $tmp_file_name)) {
                    $user->passport_card = $tmp_file_name;
                    $user->save();
                }
            }
    
            // Handle ID card file upload with delete check
            if ($request->hasFile('id_card')) {
                // Delete existing ID card file if present
                if ($user->id_card && Storage::disk('public')->exists('uploads/documents/' . $user->id_card)) {
                    Storage::disk('public')->delete('uploads/documents/' . $user->id_card);
                }
    
                $id_card = $request->file('id_card');
                $tmp_file_name = $user->id . "_id_card_" . date('s') . "_" . $id_card->getClientOriginalName();
                
                if (Storage::disk('public')->putFileAs("uploads/documents", $id_card, $tmp_file_name)) {
                    $user->id_card = $tmp_file_name;
                    $user->save();
                }
            }
            
        } else {
            // Handle other document uploads
            $validator = Validator::make($request->all(), [
                'id'             => ['required'],
                'doc_id'         => ['required'],
                'file'           => ['required', 'file', 'mimes:pdf,jpeg,png'], // Add accepted file types
                'other_doc_name' => ['nullable', 'string', 'max:255'], // Validate the optional other_doc_name field
            ], [], [
                'doc_id' => 'Document type',
            ]);
        
            $validator->sometimes('other_doc_name', 'required|string|max:255', function ($input) {
                return $input->doc_id == 9; // Apply 'required' rule if doc_id is 9
            });
        
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->messages()], 422);
            }
        
            $file = $request->file('file');
            $file_name = '';
            if ($request->hasFile('file')) {
                if (!Storage::disk('public')->exists('uploads/documents')) {
                    Storage::disk('public')->makeDirectory('uploads/documents');
                }
                $tmp_file_name = $user->id . "_" . date('s') . "_" . $file->getClientOriginalName();
                if (Storage::disk('public')->putFileAs("uploads/documents", $file, $tmp_file_name)) {
                    $file_name = $tmp_file_name;
                }
            }
        
            $docType = DocumentType::find($data['doc_id']);
        
            // Use `other_doc_name` if provided, otherwise use the document type name
            $documentName = $data['other_doc_name'] ?? $docType->name;
        
            $user->documents()->create([
                'document_type_id' => $data['doc_id'],
                'name'             => $documentName,
                'date'             => $data['date'] ?? null,
                'file'             => $file_name,
            ]);
        }
        
        return response()->json([
            'message' => 'Document1111 has been created successfully!'
        ]);
        
    }

    public function AdminDocssave(Request $request)
    {
        $data = $request->all();
        \Log::info($data);
        $user = Auth::user();
        
            $validator = Validator::make($request->all(), [
                'id'             => ['required'],
                'doc_id'         => ['required'],
                'file'           => ['required', 'file', 'mimes:pdf,jpeg,png'], // Add accepted file types
                'other_doc_name' => ['nullable', 'string', 'max:255'], // Validate the optional other_doc_name field
            ], [], [
                'doc_id' => 'Document type',
            ]);
        
            $validator->sometimes('other_doc_name', 'required|string|max:255', function ($input) {
                return $input->doc_id == 9; // Apply 'required' rule if doc_id is 9
            });
        
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->messages()], 422);
            }
        
            $file = $request->file('file');
            $file_name = '';
            if ($request->hasFile('file')) {
                if (!Storage::disk('public')->exists('uploads/documents')) {
                    Storage::disk('public')->makeDirectory('uploads/documents');
                }
                $tmp_file_name = $user->id . "_" . date('s') . "_" . $file->getClientOriginalName();
                if (Storage::disk('public')->putFileAs("uploads/documents", $file, $tmp_file_name)) {
                    $file_name = $tmp_file_name;
                }
            }
        
            $docType = DocumentType::find($data['doc_id']);
        
            // Use `other_doc_name` if provided, otherwise use the document type name
            $documentName = $data['other_doc_name'] ?? $docType->name;
        
            $user->documents()->create([
                'document_type_id' => $data['doc_id'],
                'userable_type'      => get_class($user),
                'name'             => $documentName,
                'file'             => $file_name,
            ]);
        
        return response()->json([
            'message' => 'Document has been created successfully!'
        ]);
        
    }
    

    public function remove($id, $user_id)
    {
        if (in_array($id, ['visa', 'passport_card', 'id_card'])) {
            $userobj = User::find($user_id);
            if (!empty($userobj)) {
                if (Storage::drive('public')->exists('uploads/documents/' . $userobj->file)) {
                    Storage::drive('public')->delete('uploads/documents/' . $userobj->file);
                }
                $userobj->$id = null;
                $userobj->save();
            }
        } else {
            $docObj = Document::find($id);
            if (!empty($docObj)) {
                if (Storage::drive('public')->exists('uploads/documents/' . $docObj->file)) {
                    Storage::drive('public')->delete('uploads/documents/' . $docObj->file);
                }
                $docObj->delete();
            }
        }
        return response()->json([
            'message' => 'Document has been deleted successfully!'
        ]);
    }


    public function adminRemoveDoc($id, $user_id)
    {

        $docObj = Document::where('id', $id)
                ->where('userable_id', $user_id)
                ->where('userable_type', 'App\Models\Admin')
                ->first();

        if (!empty($docObj)) {
            if (Storage::drive('public')->exists('uploads/documents/' . $docObj->file)) {
                Storage::drive('public')->delete('uploads/documents/' . $docObj->file);
            }
            $docObj->delete();
        }
        return response()->json([
            'message' => 'Document has been deleted successfully!'
        ]);
    }

    public function getDocumentTypes(Request $request)
    {
        $documentTypes = DocumentType::get();

        return response()->json([
            'documentTypes' => $documentTypes
        ]);
    }

    public function resetForm($form_id)
    {
        // Find the form by ID
        $form = Form::find($form_id);
    
        if ($form) {
            // Ensure data is an array
            $data = is_string($form->data) ? json_decode($form->data, true) : $form->data;
    
            // List of keys to empty
            $fieldsToEmpty = [
                'signature', 'signatureDate', 'signature1', 'signature2', 'signature3',
                'signatureDate1', 'signatureDate2', 'signatureDate3', 'signatureDate4'
            ];
    
            // Empty the specified fields if they exist
            foreach ($fieldsToEmpty as $field) {
                if (array_key_exists($field, $data)) {
                    $data[$field] = null;
                }
            }
    
            // Update the form record
            $form->update([
                'pdf_name' => null,
                'submitted_at' => null,
                'data' => $data, // Save directly if Laravel handles JSON casting
            ]);
    
            return response()->json([
                'message' => 'Document has been reset successfully!',
            ]);
        }
    
        return response()->json([
            'message' => 'Form not found!',
        ], 404);
    }

    public function getWorkerDocuments($workerId)
    {
        $worker = User::with(['documents.documentType'])->findOrFail($workerId);

        return response()->json([
            'documents' => $worker->documents,
            'worker' => $worker,
        ]);
}
    
}
