<?php

namespace App\Http\Controllers;

use App\Models\LeadActivity;
use Illuminate\Http\Request;

class LeadActivityController extends Controller
{
    public function getLeadActivities($id)
    {
        // Retrieve activities by client_id
        $activities = LeadActivity::where('client_id', $id)
            ->orderBy('status_changed_date', 'asc')
            ->get();

        return response()->json($activities);
    }
}
