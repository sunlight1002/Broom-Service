<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoices::query()
            ->with('client')
            ->where('client_id', Auth::id())
            ->latest()
            ->paginate(20);

        return response()->json([
            'lng' => Auth::user()->lng,
            'invoices' => $invoices,
        ]);
    }
}
