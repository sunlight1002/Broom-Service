<?php

namespace App\Http\Controllers;

use App\Models\LeadActivity;
use App\Models\Admin;
use App\Models\Client;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LeadActivityController extends Controller
{
    public function getLeadActivities($id)
    {
        $client = Client::with('leadActivities')->where('id', $id)->first();
        $activities = $client->leadActivities()->orderBy('status_changed_date', 'asc')->get();

        foreach ($activities as $activity) {
            if($activity->changed_by){
                $admin = Admin::find($activity->changed_by);
                $activity->changed_by = $admin->name;
            }
            $activity->created_date = $client->created_at;
            
        }
        

        return response()->json($activities);
    }
}
