<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\Document;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function documents($id)
    {
        $worker = User::find($id);

        $documents = $worker->documents()
            ->with(['document_type' => function ($query) {
                return $query->select(['id', 'name']);
            }])
            ->get();

        return response()->json([
            'documents' => $documents
        ]);
    }

    public function save(Request $request)
{
    $data = $request->all();
    $user = User::find($data['id']);
    
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
            if ($user->passport && Storage::disk('public')->exists('uploads/documents/' . $user->passport)) {
                Storage::disk('public')->delete('uploads/documents/' . $user->passport);
            }

            $pasport_file = $request->file('passport');
            $tmp_file_name = $user->id . "_passport_" . date('s') . "_" . $pasport_file->getClientOriginalName();
            
            if (Storage::disk('public')->putFileAs("uploads/documents", $pasport_file, $tmp_file_name)) {
                $user->passport = $tmp_file_name;
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
            'id'        => ['required'],
            'doc_id'    => ['required'],
            'file'      => ['required'],
        ], [], [
            'doc_id'    => 'Document type',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
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

        $user->documents()->create([
            'document_type_id' => $data['doc_id'],
            'name' => $docType->name,
            'file' => $file_name,
        ]);
    }

    return response()->json([
        'message' => 'Document has been created successfully!'
    ]);
}

    public function remove($id, $user_id)
    {
        if (in_array($id, ['visa', 'passport', 'id_card'])) {
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

    public function getDocumentTypes(Request $request)
    {
        $documentTypes = DocumentType::get();

        return response()->json([
            'documentTypes' => $documentTypes
        ]);
    }
}
