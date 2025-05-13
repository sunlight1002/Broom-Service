<?php

namespace App\Http\Controllers;

use App\Models\LeadActivity;
use App\Models\Admin;
use Illuminate\Http\Request;

class LeadActivityController extends Controller
{
    public function getLeadActivities($id)
    {
        // Retrieve activities by client_id
        $activities = LeadActivity::where('client_id', $id)
            ->orderBy('status_changed_date', 'asc')
            ->get();

        foreach ($activities as $activity) {
            if($activity->changed_by){
                $admin = Admin::find($activity->changed_by);
                $activity->changed_by = $admin->name;
            }
        }
        

        return response()->json($activities);
    }
}
