<?php

namespace App\Traits;

use App\Models\Invoices;
use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\Log;

trait ICountDocument
{
    private function closeDoc($docnum, $type)
    {
        Log::info('close doc.' . $docnum . '-' . $type);
        $closeDocResponse = $this->closeICountDocument($docnum, $type);

        if (!$closeDocResponse["status"]) {
            throw new Exception($closeDocResponse["reason"], 500);
        }

        if ($type == 'invoice') {
            Invoices::where('invoice_id', $docnum)->update(['invoice_icount_status' => 'Closed']);
        }

        if ($type == 'order') {
            Order::where('order_id', $docnum)->update(['status' => 'Closed']);
        }

        return response()->json(['message' => 'Doc closed successfully!']);
    }
}
