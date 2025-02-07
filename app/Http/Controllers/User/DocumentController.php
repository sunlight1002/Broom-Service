<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\WorkerLeads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
{
    public function documents()
    {
        $worker = Auth::user();

        $documents = $worker->documents()
            ->with(['document_type' => function ($query) {
                return $query->select(['id', 'name']);
            }])
            ->get();

        return response()->json([
            'documents' => $documents
        ]);
    }

    public function forms()
    {
        $worker = Auth::user();

        if ($worker->is_exist) {
            return response()->json([
                'exist_user_forms' => $worker
            ]);
        }
        $forms = $worker->forms()
            ->get(['id', 'type', 'pdf_name', 'submitted_at']);

        return response()->json([
            'forms' => $forms
        ]);
    }

    public function getDocumentTypes(Request $request)
    {
        $documentTypes = DocumentType::get();

        return response()->json([
            'documentTypes' => $documentTypes
        ]);
    }

    public function upload(Request $request)
    {
        $data = $request->all();
        $worker = User::find(Auth::id());

        $file = $request->file('file');

        if ($request->hasFile('file')) {
            if (!Storage::disk('public')->exists('uploads/documents')) {
                Storage::disk('public')->makeDirectory('uploads/documents');
            }
            $tmp_file_name = $worker->id . "_" . date('s') . "_" . $file->getClientOriginalName();
            if (Storage::disk('public')->putFileAs("uploads/documents", $file, $tmp_file_name)) {
                $file_name = $tmp_file_name;
            }
        }

        $worker->documents()->create([
            'document_type_id' => $data['type'],
            'name' => $data['name'],
            'file' => $file_name,
        ]);

        return response()->json(['success' => true]);
    }

    public function save(Request $request)
    {
        $data = $request->all();
        $user = $request->type == "worker" ? User::find($data['id']) : WorkerLeads::find($data['id']);        
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
                }
            }
    
            // Handle passport file upload with delete check
            if ($request->hasFile('passport')) {
                // Delete existing passport file if present
                if ($user->passport_card && Storage::disk('public')->exists('uploads/documents/' . $user->passport_card)) {
                    Storage::disk('public')->delete('uploads/documents/' . $user->passport_card);
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
                'file'             => $file_name,
            ]);
        }
        
        return response()->json([
            'message' => 'Document has been created successfully!'
        ]);
        
    }
}
