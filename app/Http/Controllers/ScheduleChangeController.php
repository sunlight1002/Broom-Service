<?php

namespace App\Http\Controllers;

use App\Models\ScheduleChange;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\WhatsappNotificationEvent;
use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;

class ScheduleChangeController extends Controller
{
    /**
     * Store a newly created schedule change in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */

     public function index(Request $request)
     {
         $columns = [
             'id',
             'user_type',
             'user_id',
             'status',
             'reason',
             'comments',
             'created_at',
             'updated_at'
         ];
     
         $length = $request->get('length', 10); // Number of records per page
         $start = $request->get('start', 0); // Starting index
         $order = $request->get('order', []); // Ordering data
         $columnIndex = $order[0]['column'] ?? 0; // Column index for sorting
         $dir = $order[0]['dir'] ?? 'desc'; // Sort direction (asc/desc)
     
         // Base query for ScheduleChange
         $query = ScheduleChange::with('user');
     
         // Search functionality
         if ($search = $request->get('search')['value'] ?? null) {
             $query->where(function ($query) use ($search, $columns) {
                 foreach ($columns as $column) {
                     $query->orWhere($column, 'like', "%{$search}%");
                 }
             });
         }
     
         // Filter by user type and status
         $userType = $request->get('type', null); // Default to null (no filter)
         $status = $request->get('status', null); // Get status filter (null by default)
     
         $query->where(function ($query) use ($userType, $status) {
             if ($userType) {
                 if ($userType === 'Client') {
                     $query->whereHas('user', function ($q) {
                         $q->where('user_type', 'App\Models\Client');
                     });
                 } elseif ($userType === 'Worker') {
                     $query->whereHas('user', function ($q) {
                         $q->where('user_type', 'App\Models\User');
                     });
                 }
             }
             if ($status && $status !== 'All') {
                 $query->where('status', $status);
             }
         });
     
         // Select specified columns
         $query->select($columns);
     
         // Ordering
         $query->orderBy($columns[$columnIndex] ?? 'id', $dir);
     
         // Pagination
         $totalRecords = $query->count();
         $scheduleChanges = $query->skip($start)->take($length)->get();
     
         // Transform the data (if needed)
         $scheduleChanges = $scheduleChanges->map(function ($change) {
             $user = $change->user;
             $userType = '';
             if ($user instanceof \App\Models\Client) {
                 $userType = 'Client';
             } elseif ($user instanceof \App\Models\User) {
                 $userType = 'Worker';
             }
     
             return [
                 'id' => $change->id,
                 'user_type' => $userType,
                 'user_fullname' => $user->firstname . ' ' . $user->lastname,
                 'status' => $change->status,
                 'reason' => $change->reason,
                 'comments' => $change->comments,
                 'created_at' => $change->created_at,
             ];
         });
     
         // Response
         return response()->json([
             'filter' => $request->filter,
             'draw' => intval($request->get('draw')),
             'data' => $scheduleChanges,
             'recordsTotal' => $totalRecords,
             'recordsFiltered' => $totalRecords,
         ]);
     }
     
     
     
     
  
     
    //  public function getAllScheduleChanges(Request $request)
    // {
    //     $query = ScheduleChange::query();

    //     if ($request->has('user_type')) {
    //         $query->where('user_type', $request->user_type);
    //     }

    //     if ($request->has('user_id')) {
    //         $query->where('user_id', $request->user_id);
    //     }

    //     $scheduleChanges = $query->paginate(10); // Paginate results

    //     return response()->json([
    //         'message' => 'Filtered schedule changes fetched successfully.',
    //         'data' => $scheduleChanges
    //     ], 200);
    // }



    public function requestToChange(Request $request)
    {
        $request->validate([
            'client_id' => 'required|integer',
            'text' => 'required|string',
        ]);
    
        // Get the authenticated user
        $user = Auth::user();
        
        // Dynamically set user_type based on the authenticated user class
        $userType = get_class($user);

        \Log::info($userType);
    
        $userId = null;
    
        // Check if the user is a Client or Worker and set the user_id accordingly
        if ($user instanceof \App\Models\Client) {
            $userId = $user->id;
        } elseif ($user instanceof \App\Models\User) {
            $userId = $user->id;
        } else {
            return response()->json(['error' => 'Invalid user type.'], 400);
        }
    
        $scheduleChange = new ScheduleChange();
        $scheduleChange->user_type = $userType;  
        $scheduleChange->user_id = $userId;      
        $scheduleChange->comments = $request->text;  
        $scheduleChange->save();
    
        $clientData = [];
    
        if ($userType === \App\Models\Client::class) {
            $client = Client::find($request->client_id);
            if (!$client) {
                return response()->json([
                    'message' => 'Client not found'
                ], 404);
            }
    
            $clientData = [
                'type' => WhatsappMessageTemplateEnum::NOTIFY_TEAM_REQUEST_TO_CHANGE_SCHEDULE_CLIENT,
                'notificationData' => [
                    'client' => $client->toArray(),
                    'request_details' => $request->text,  
                ],
            ];
    
        } elseif ($userType === \App\Models\User::class) {
            $worker = User::find($request->client_id); 
            if (!$worker) {
                return response()->json([
                    'message' => 'Worker not found'
                ], 404);
            }
    
            $clientData = [
                'type' => WhatsappMessageTemplateEnum::NOTIFY_TEAM_REQUEST_TO_CHANGE_SCHEDULE_WORKER,
                'notificationData' => [
                    'worker' => $worker->toArray(),
                    'request_details' => $request->text,
                ],
            ];
        } else {
            return response()->json([
                'message' => 'Invalid user type, must be Client or Worker'
            ], 400);
        }
    
        $res = event(new WhatsappNotificationEvent($clientData));
    
        // Return the success response
        return response()->json([
            'message' => 'Request sent successfully.'
        ], 200);
    }
     
    public function updateScheduleChange(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string',   // Ensure status is provided and is a string
            // 'comments' => 'nullable|string', // Comments are optional
        ]);

        // Find the ScheduleChange record by ID
        $scheduleChange = ScheduleChange::find($id);

        if (!$scheduleChange) {
            return response()->json([
                'message' => 'Schedule Change record not found.'
            ], 404);
        }

        // Update the record with the request data
        $scheduleChange->status = $request->status;
        // $scheduleChange->comments = $request->comments;
        $scheduleChange->save();

        // Return a success response
        return response()->json([
            'message' => 'Schedule updated successfully.',
            'data' => $scheduleChange
        ], 200);
    }


    public function getScheduleChange($id)
    {
        // Find the ScheduleChange record by its ID
        $scheduleChange = ScheduleChange::with('user')->find($id);

        // Check if the record exists
        if (!$scheduleChange) {
            return response()->json([
                'message' => 'Schedule change not found',
            ], 404);
        }

        // Return the record as a response
        return response()->json([
            'scheduleChange' => $scheduleChange,
        ], 200);
    }


}
