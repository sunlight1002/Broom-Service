<?php

namespace App\Http\Controllers;

use App\Models\ScheduleChange;
use App\Models\Client;
use App\Models\User;
use App\Models\WhatsAppBotActiveClientState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\WhatsappNotificationEvent;
use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use Illuminate\Support\Facades\DB;

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
                 'user_id' => $change->user_id,
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
     
     public function addScheduleRequest(Request $request)
     {
         $request->validate([
             'client_ids' => 'array',
             'client_ids.*' => 'exists:clients,id',
             'worker_ids' => 'array',
             'worker_ids.*' => 'exists:users,id',
             'reason' => 'required|string',
             'comment' => 'required|string',
         ]);
     
         $scheduleChanges = [];
     
         DB::beginTransaction();
         try {
             if (!empty($request->client_ids)) {
                 foreach ($request->client_ids as $clientId) {
                     $scheduleChanges[] = [
                         'user_type' => Client::class,
                         'user_id' => $clientId,
                         'reason' => $request->reason,
                         'comments' => $request->comment,
                         'status' => 'pending', // You can modify the default status
                         'created_at' => now(),
                         'updated_at' => now(),
                     ];
                 }
             }
     
             if (!empty($request->worker_ids)) {
                 foreach ($request->worker_ids as $workerId) {
                     $scheduleChanges[] = [
                         'user_type' => User::class,
                         'user_id' => $workerId,
                         'reason' => $request->reason,
                         'comments' => $request->comment,
                         'status' => 'pending',
                         'created_at' => now(),
                         'updated_at' => now(),
                     ];
                 }
             }
     
             // Bulk insert to optimize performance
             if (!empty($scheduleChanges)) {
                 ScheduleChange::insert($scheduleChanges);
             }
     
             DB::commit();
             return response()->json(['message' => 'Schedule change requests created successfully'], 201);
         } catch (\Exception $e) {
             DB::rollBack();
             return response()->json(['error' => 'Failed to create schedule change requests', 'details' => $e->getMessage()], 500);
         }
     }

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

    public function sendMessageToUser(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
            'reason' => 'required|string',
        ]);
    
        $scheduleChange = ScheduleChange::with('user')
            ->where('id', $id)
            ->first();
    
        if (!$scheduleChange) {
            return response()->json([
                'message' => 'Schedule change not found',
            ], 404);
        }
    
        // Get existing team responses and decode them as an array
        $existingResponses = $scheduleChange->team_response ? json_decode($scheduleChange->team_response, true) : [];
    
        // Ensure it's an array
        if (!is_array($existingResponses)) {
            $existingResponses = [];
        }
    
        // Append new response
        $newResponse = [
            'reason' => $request->reason,
            'message' => $request->message,
            'timestamp' => now()->toDateTimeString(),
        ];
    
        $existingResponses[] = $newResponse;
    
        // Update the team_response field with the new array
        $scheduleChange->team_response = json_encode($existingResponses);
        $scheduleChange->save();

        $message = [
            "en" => "Hello :client_name,
Following your request regarding :team_reason, the team has reviewed it and provided the following response:
':team_message'

Do you want to add anything else to this request?
    • If yes, reply with the number 1.
    • If no, no further action is needed.
Thank you for your cooperation.

Best regards,
The Broom Service Team 🌹",

            "heb" => "שלום :client_name,
בהמשך לבקשתך בנוגע ל**:team_reason**, הצוות שלנו בדק את הפנייה והשיב:
':team_message'

האם תרצה להוסיף משהו נוסף לבקשה זו?
    • אם כן, השב עם הספרה 1.
    • אם לא, אין צורך בפעולה נוספת.
תודה על שיתוף הפעולה.

בברכה,
צוות ברום סרוויס 🌹"
        ];
        // \Log::info($scheduleChange);

        $lng = $scheduleChange->user->lng;
        \Log::info($lng);
        $from = $scheduleChange->user->phone;
        $team_reason = $request->reason;
        $team_message = $request->message;
        $clientName = "*" . ($scheduleChange->user->firstname ?? '') . ' ' . ($scheduleChange->user->lastname ?? '') . "*";

        $nextMessage = $message[$lng];
        $personalizedMessage = str_replace([':client_name', ':team_reason', ':team_message'], [$clientName, $team_reason, $team_message], $nextMessage);
        \Log::info($personalizedMessage);
        sendClientWhatsappMessage($from, ['name' => '', 'message' => $personalizedMessage]);

        
        $clientState = WhatsAppBotActiveClientState::where('from', $from)->first();
        if ($clientState) {
            $clientState->menu_option = 'not_recognized->team_send_message';
            $clientState->lng = $lng;
            $clientState->save();
        }else{
            WhatsAppBotActiveClientState::create([
                'client_id' => $scheduleChange->user->id,
                'from' => $from,
                'lng' => $lng,
                'menu_option' => 'not_recognized->team_send_message',
            ]);
        }
    
        return response()->json([
            'message' => 'Response added successfully',
            'team_response' => $existingResponses
        ]);
    }
    

}
