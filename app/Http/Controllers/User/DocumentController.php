<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        if($worker->is_exist){
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
}
