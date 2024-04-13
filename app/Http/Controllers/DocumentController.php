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
    public function documents(request $request, $id)
    {
        $userWithDoc = User::query()
            ->where('id', $id)
            ->with(['documents','documents.document_type' => function ($query) {
                return $query->select(['id','name']);
            }])->first();

        return response()->json([
            'user' => $userWithDoc
        ]);
    }
    public function save(Request $request)
    {
        $data = $request->all();
        $user = User::find($data['id']);
        if( $request->file('visa') ||  $request->file('passport')){
            $visa_file = $request->file('visa');
            if ($request->hasFile('visa')) {
                if (!Storage::disk('public')->exists('uploads/documents')) {
                    Storage::disk('public')->makeDirectory('uploads/documents');
                }
                $tmp_file_name = $user->id . "_visa_" . date('s') . "_" . $visa_file->getClientOriginalName();
                if (Storage::disk('public')->putFileAs("uploads/documents", $visa_file, $tmp_file_name)) {
                    $user->visa = $tmp_file_name;
                    $user->save();
                }           
            }
            $pasport_file = $request->file('passport');
            if ($request->hasFile('passport')) {
                if (!Storage::disk('public')->exists('uploads/documents')) {
                    Storage::disk('public')->makeDirectory('uploads/documents');
                }
                $tmp_file_name = $user->id . "_passport_" . date('s') . "_" . $pasport_file->getClientOriginalName();
                if (Storage::disk('public')->putFileAs("uploads/documents", $pasport_file, $tmp_file_name)) {
                    $user->passport = $tmp_file_name;
                    $user->save();
                }           
            }
        }else{
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
            $user->documents()->create([
                'document_type_id' => $data['doc_id'],
                'file' => $file_name,
            ]);
        }

        return response()->json([
            'message' => 'Document has been created successfully!'
        ]);
    }    
    public function remove($id, $user_id)
    {
        if(in_array($id, ['visa', 'passport'])){
            $userobj = User::find($user_id);
            if(!empty($userobj)){
                if (Storage::drive('public')->exists('uploads/documents/' . $userobj->file)) {
                    Storage::drive('public')->delete('uploads/documents/' . $userobj->file);
                }
                $userobj->$id = null;
                $userobj->save();
            }
        }else{
            $docObj = Document::find($id);
            if(!empty($docObj)){
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
    public function getDocumentTypes(request $request)
    {
        $documentTypes = DocumentType::get();

        return response()->json([
            'documentTypes' => $documentTypes
        ]);
    }    
}
