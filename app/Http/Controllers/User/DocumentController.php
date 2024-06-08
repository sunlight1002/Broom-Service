<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
}
