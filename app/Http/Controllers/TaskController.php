<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TaskManagement;
use App\Models\Admin;
use App\Models\Phase;
use App\Models\User;
use App\Models\ServiceSchedule;
use App\Models\ManageTime;
use App\Models\TaskComment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Traits\JobSchedule; // Import the trait

class TaskController extends Controller
{
    use JobSchedule; // Declare the trait
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = TaskManagement::with([
            'phase',
            'comments',
            'workers:id,firstname,lastname',
            'users:id,name'
        ]);

        // Filtering
        if ($request->has('status') && $request->status && $request->status !== 'All') {
            $query->whereRaw('LOWER(TRIM(status)) = ?', [strtolower(trim($request->status))]);
        }
        
        // Handle worker_id filtering
        if ($request->has('worker_id')) {
            $workerIds = $request->input('worker_id');
            if (is_string($workerIds)) {
                $workerIds = [$workerIds];
            }
            if (is_array($workerIds)) {
                $workerIds = array_filter($workerIds);
                if (!empty($workerIds)) {
                    $query->whereHas('workers', function ($q) use ($workerIds) {
                        $q->whereIn('users.id', $workerIds);
                    });
                }
            }
        }
        
        // Handle user_id filtering
        if ($request->has('user_id')) {
            $userIds = $request->input('user_id');
            if (is_string($userIds)) {
                $userIds = [$userIds];
            }
            if (is_array($userIds)) {
                $userIds = array_filter($userIds);
                if (!empty($userIds)) {
                    $query->whereHas('users', function ($q) use ($userIds) {
                        $q->whereIn('admins.id', $userIds);
                    });
                }
            }
        }
        
        if ($request->has('due_date_start') && $request->due_date_start) {
            $query->where('due_date', '>=', $request->due_date_start);
        }
        
        if ($request->has('due_date_end') && $request->due_date_end) {
            $query->where('due_date', '<=', $request->due_date_end);
        }
        
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            // Handle DataTables search object format
            if (is_array($search) && isset($search['value'])) {
                $search = $search['value'];
            }
            if (is_string($search) && !empty(trim($search))) {
                $query->where(function ($q) use ($search) {
                    $q->where('task_name', 'like', "%$search%")
                      ->orWhere('description', 'like', "%$search%");
                });
            }
        }

        // DataTables sorting
        $sortBy = 'due_date';
        $sortOrder = 'desc';
        $allowedSortColumns = ['task_name', 'status', 'due_date', 'priority', 'created_at'];
        if ($request->has('order') && $request->has('columns')) {
            $orderArr = $request->input('order');
            $columnsArr = $request->input('columns');
            if (is_array($orderArr) && count($orderArr) > 0) {
                $orderColIdx = $orderArr[0]['column'] ?? null;
                $orderDir = $orderArr[0]['dir'] ?? 'desc';
                if ($orderColIdx !== null && isset($columnsArr[$orderColIdx]['data'])) {
                    $colName = $columnsArr[$orderColIdx]['data'];
                    if (in_array($colName, $allowedSortColumns)) {
                        $sortBy = $colName;
                        $sortOrder = strtolower($orderDir) === 'asc' ? 'asc' : 'desc';
                    }
                }
            }
        } else {
            // Fallback to legacy sort_by/sort_order
            $sortBy = $request->get('sort_by', 'due_date');
            $sortOrder = $request->get('sort_order', 'desc');
            if (!in_array($sortBy, $allowedSortColumns)) {
                $sortBy = 'due_date';
            }
            $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';
        }
        $query->orderBy($sortBy, $sortOrder);

        // DataTables pagination
        $perPage = $request->get('length', $request->get('per_page', 10));
        $start = $request->get('start', 0);
        $page = $perPage > 0 ? intval($start / $perPage) + 1 : 1;
        $tasks = $query->paginate($perPage, ['*'], 'page', $page);

        // DataTables response format
        if ($request->has('draw')) {
            return response()->json([
                'draw' => intval($request->get('draw')),
                'recordsTotal' => $tasks->total(),
                'recordsFiltered' => $tasks->total(),
                'data' => $tasks->items(),
            ]);
        }
        return response()->json($tasks);
    }

    public function showWorkerTasks($workerId, Request $request)
    {
        // Build query for tasks assigned to the specific worker
        $query = TaskManagement::with(['phase', 'comments.commentable', 'workers:id,firstname,lastname', 'users:id,name'])
            ->whereHas('workers', function($q) use ($workerId) {
                $q->where('users.id', $workerId);
            });

        // Apply status filter
        if ($request->has('status') && $request->status !== '' && $request->status !== null && $request->status !== 'All') {
            $query->where('status', $request->status);
        }

        // Apply search filter
        if ($request->has('search') && $request->search !== '' && $request->search !== null) {
            $search = $request->search;
            // Handle DataTables search object format
            if (is_array($search) && isset($search['value'])) {
                $search = $search['value'];
            }
            if (is_string($search) && !empty(trim($search))) {
                $query->where(function($q) use ($search) {
                    $q->where('task_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
        }

        // Apply date range filter
        if ($request->has('due_date_start') && $request->due_date_start !== '' && $request->due_date_start !== null) {
            $query->where('due_date', '>=', $request->due_date_start);
        }
        if ($request->has('due_date_end') && $request->due_date_end !== '' && $request->due_date_end !== null) {
            $query->where('due_date', '<=', $request->due_date_end);
        }

        // DataTables sorting
        $sortBy = 'due_date';
        $sortOrder = 'desc';
        $allowedSortColumns = ['task_name', 'status', 'due_date', 'priority', 'created_at'];
        if ($request->has('order') && $request->has('columns')) {
            $orderArr = $request->input('order');
            $columnsArr = $request->input('columns');
            if (is_array($orderArr) && count($orderArr) > 0) {
                $orderColIdx = $orderArr[0]['column'] ?? null;
                $orderDir = $orderArr[0]['dir'] ?? 'desc';
                if ($orderColIdx !== null && isset($columnsArr[$orderColIdx]['data'])) {
                    $colName = $columnsArr[$orderColIdx]['data'];
                    if (in_array($colName, $allowedSortColumns)) {
                        $sortBy = $colName;
                        $sortOrder = strtolower($orderDir) === 'asc' ? 'asc' : 'desc';
                    }
                }
            }
        } else {
            $sortBy = $request->get('sort_by', 'due_date');
            $sortOrder = $request->get('sort_order', 'desc');
            if (!in_array($sortBy, $allowedSortColumns)) {
                $sortBy = 'due_date';
            }
            $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';
        }
        $query->orderBy($sortBy, $sortOrder);

        // DataTables pagination
        $perPage = $request->get('length', $request->get('per_page', 10));
        $start = $request->get('start', 0);
        $page = $perPage > 0 ? intval($start / $perPage) + 1 : 1;
        $tasks = $query->paginate($perPage, ['*'], 'page', $page);

        // DataTables response format
        if ($request->has('draw')) {
            return response()->json([
                'draw' => intval($request->get('draw')),
                'recordsTotal' => $tasks->total(),
                'recordsFiltered' => $tasks->total(),
                'data' => $tasks->items(),
            ]);
        }
        return response()->json($tasks);
    }
    

    public function updateSortOrder(Request $request)
    {
        $sortedTasks = $request->tasks; // Expecting an array of task IDs and their new sort order

        foreach ($sortedTasks as $task) {
            TaskManagement::where('id', $task['id'])
                ->update(['sort_order' => $task['sort_order']]);
        }

        return response()->json(['message' => 'Tasks sorted successfully']);
    }

    // Method to move a task to a different phase
    public function moveTaskToPhase(Request $request, TaskManagement $task)
    {
        $task->phase_id = $request->phase_id; // New phase ID
        $task->save();

        return response()->json(['message' => 'Task moved to phase successfully']);
    }

    public function tasksByPhase(Request $request)
    {
        $request->validate([
            'per_page' => 'integer|min:1|max:100',
            'page' => 'integer|min:1'
        ]);

        $perPage = $request->input('per_page', 10); // Default items per page
        $page = $request->input('page', 1);

        $user = Auth::user();
        $user_id = $user->id;
        $userType = get_class($user);

        // Get phase ID from tasks assigned to the user
        $task = TaskManagement::whereHas('workers', function ($query) use ($user_id, $userType) {
            $query->where('assignable_id', $user_id)
                  ->where('assignable_type', $userType);
        })->orWhereHas('users', function ($query) use ($user_id) {
            $query->where('assignable_id', $user_id)
                  ->where('assignable_type', Admin::class);
        })->first();

        if (!$task) {
            return response()->json(['message' => 'No tasks found for the user.'], 404);
        }

        $phaseId = $task->phase_id;

        if ($user->role === 'admin' || $user->role === 'superadmin') {
            // Admins and superadmins can see all phases and tasks
            $tasks = TaskManagement::with([
                    'phase',
                    'comments.commentable',
                    'workers:id,firstname',
                    'users:id,name'
                ])
                ->orderBy('sort_order')
                ->paginate($perPage, ['*'], 'page', $page);
        } else {
            // Workers and users can only see their respective phases and tasks
            $tasks = TaskManagement::where('phase_id', $phaseId)
                ->whereHas('workers', function ($query) use ($user_id, $userType) {
                    $query->where('assignable_id', $user_id)
                        ->where('assignable_type', $userType);
                })
                ->orWhereHas('users', function ($query) use ($user_id) {
                    $query->where('assignable_id', $user_id)
                        ->where('assignable_type', Admin::class);
                })
                ->with([
                    'phase',
                    'comments.commentable',
                    'workers:id,firstname',
                    'users:id,name'
                ])
                ->orderBy('sort_order')
                ->paginate($perPage, ['*'], 'page', $page);
        }

        // Get phase name by ID
        $phaseName = Phase::where('id', $phaseId)->value('phase_name');

        return response()->json([
            'phase_name' => $phaseName,
            'tasks' => $tasks
        ]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_name' => 'required|string',
            'status' => 'required|string',
            'priority' => 'required|string|in:high,medium,low',
            'due_date' => 'nullable|date',
            'worker_ids' => 'nullable|array',
            'worker_ids.*' => 'integer|exists:users,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:admins,id',
            "user_type" => 'string|in:admins'
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 400);
        }
    
        if (!Auth::check()) {
            return response()->json(['error' => 'User is not authenticated'], 401);
        }

        if(isset($request->frequency_id)){
            $service = ServiceSchedule::where('id', $request->frequency_id)
                        ->where('status', 1)
                        ->first();
        }

        $manageTime = ManageTime::first();
        $workingWeekDays = json_decode($manageTime->days);
        $repeat_value = $service->period;

        $start_date = Carbon::parse($request->due_date);
        $preferredWeekDay = strtolower($start_date->format('l'));
        $next_start_date = $this->scheduleNextJobDate($request->due_date, $repeat_value, $preferredWeekDay, $workingWeekDays);


        $user = Auth::user();
        $userType = get_class($user);
    
        // Create a new task
        $task = new TaskManagement($request->all());
        $task->user_id = $user->id;
        $task->user_type = $userType;
        $task->frequency_id = $request->frequency_id != 1 ? $request->frequency_id : null;
        $task->next_start_date = $next_start_date;  
        $task->save();
    
        // Handle worker_ids sync
        if ($request->has('worker_ids')) {
            $workerSyncData = [];
            foreach ($request->worker_ids as $workerId) {
                $workerSyncData[$workerId] = ['assignable_type' => User::class];
            }
            $task->workers()->syncWithoutDetaching($workerSyncData);
        }
    
        // Handle user_ids sync
        if ($request->has('user_ids')) {
            $userSyncData = [];
            foreach ($request->user_ids as $userId) {
                $userSyncData[$userId] = ['assignable_type' => Admin::class];
            }
            $task->users()->syncWithoutDetaching($userSyncData);
        }
    
        return response()->json(['message' => 'Tasks created successfully!', 'task' => $task], 201);
    }
    
    
    public function sort(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);
    
        $sortedIDs = $request->input('ids');
    
        // Ensure that the IDs are unique and in the correct order
        $sortedIDs = array_values(array_unique($sortedIDs));
    
        // Retrieve tasks based on the sorted IDs
        $tasks = TaskManagement::whereIn('id', $sortedIDs)->get();
    
        // Update sort_order based on the new position
        foreach ($sortedIDs as $sortOrder => $id) {
            $task = $tasks->find($id);
            if ($task) {
                $task->sort_order = $sortOrder + 1; // Sort order starts from 1
                $task->save();
            }
        }
    
        return response()->json(['success' => true, 'message' => 'Sorting updated successfully']);
    }
    
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        $tasks = TaskManagement::with([
            'phase', 
            'comments', 
            'workers:id,firstname,lastname',
            'users:id,name' 
        ])->find($id);
        
        return response()->json($tasks);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'phase_id' => 'required|exists:phase,id',
            'task_name' => 'required|string',
            'status' => 'required|string',
            'priority' => 'required|string|in:high,medium,low',
            'due_date' => 'nullable|date',
            'worker_ids' => 'nullable|array',
            'worker_ids.*' => 'integer|exists:users,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:admins,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 400);
        }
    
        // Check if the user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'User is not authenticated'], 401);
        }
    
        // Get authenticated user and log info
        $user = Auth::user();
        $userType = get_class($user);
        \Log::info('Authenticated User:', ['user' => $user, 'user_type' => $userType]);
    
        // Find the task by ID and update with the request data
        $task = TaskManagement::find($id);
        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }
        
        // Update task fields
        $task->fill($request->all());
        $task->user_id = $user->id;
        $task->user_type = $userType;
        $task->save();
    
        // Handle worker_ids sync: detach and attach workers as needed
        if ($request->has('worker_ids')) {
            $workerSyncData = [];
            foreach ($request->worker_ids as $workerId) {
                $workerSyncData[$workerId] = ['assignable_type' => User::class];
            }
            $task->workers()->sync($workerSyncData);  // Sync workers with new data
        } else {
            // If no worker_ids are provided, detach all workers
            $task->workers()->detach();
        }
    
        // Handle user_ids sync: detach and attach users (admins) as needed
        if ($request->has('user_ids')) {
            $userSyncData = [];
            foreach ($request->user_ids as $userId) {
                $userSyncData[$userId] = ['assignable_type' => Admin::class];
            }
            $task->users()->sync($userSyncData);  // Sync users (admins) with new data
        } else {
            // If no user_ids are provided, detach all users
            $task->users()->detach();
        }
    
        return response()->json(['message' => 'Task has been updated successfully!', 'task' => $task], 200);
    }
    

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $task = TaskManagement::findOrFail($id);

        $task->workers()->detach();
        $task->users()->detach();

        $task->delete();

        return response()->json(['message' => 'Task has been deleted'], 200); 
    }

    //comments handle 

    public function addComment(Request $request, $taskId)
    {
        $userType = $request->type;

        \Log::info($request->all());

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 400);
        }

        $task = TaskManagement::with(['workers', 'users'])->findOrFail($taskId);
        $user = Auth::user();
      
        // Check if the user is allowed to comment
        if (
            $task->user_id !== $user->id &&  
            !$task->workers->contains('id', $user->id) &&
            !$task->users->contains('id', $user->id)
        ) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        

        $comment = new TaskComment([
             'task_management_id' => $taskId,
             'commentable_id' => $user->id,
             'commentable_type' => $userType == 'worker' ? "App\\Models\\User" : get_class($user),
            'comment' => $request->comment,
        ]);
        $comment->save();

        return response()->json(['message' => 'Comment added successfully!', 'comment' => $comment], 201);
    }

    public function addWorkerComment(Request $request, $taskId)
    {
        $userType = $request->type;

        \Log::info($request->all());

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 400);
        }

        $task = TaskManagement::with(['workers', 'users'])->findOrFail($taskId);
        $user = Auth::user();
      
        // Check if the user is allowed to comment
        if (
            $task->user_id !== $user->id &&  
            !$task->workers->contains('id', $user->id) &&
            !$task->users->contains('id', $user->id)
        ) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        

        $comment = new TaskComment([
             'task_management_id' => $taskId,
             'commentable_id' => $user->id,
             'commentable_type' => $userType == 'worker' ? "App\\Models\\User" : get_class($user),
            'comment' => $request->comment,
        ]);
        $comment->save();

        return response()->json(['message' => 'Comment added successfully!', 'comment' => $comment], 201);
    }

    public function updateComment(Request $request, $taskId, $commentId)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 400);
        }

        $task = TaskManagement::with(['workers', 'users'])->findOrFail($taskId);
        $comment = TaskComment::findOrFail($commentId);
        $user = Auth::user();

        // Check if the user is allowed to update the comment
        // For workers, comments are saved with commentable_type as "App\Models\User"
        // For admins, comments are saved with commentable_type as "App\Models\Admin"
        $isWorkerComment = $comment->commentable_type === "App\\Models\\User";
        $isAdminComment = $comment->commentable_type === "App\\Models\\Admin";
        
        if ($comment->commentable_id !== $user->id || 
            ($isWorkerComment && get_class($user) !== "App\\Models\\User") ||
            ($isAdminComment && get_class($user) !== "App\\Models\\Admin")) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $comment->update(['comment' => $request->comment]);

        return response()->json(['message' => 'Comment updated successfully!', 'comment' => $comment], 200);
    }

    public function deleteComment($commentId)
    {
        
        $comment = TaskComment::findOrFail($commentId);
        $user = Auth::user();

        // Check if the user is allowed to delete the comment
        if ($comment->commentable_id !== $user->id || $comment->commentable_type !== get_class($user)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully!'], 200);
    }

    public function deleteWorkerComment($commentId)
    {
        
        $comment = TaskComment::findOrFail($commentId);

        // Check if the user is allowed to delete the comment
        if ($comment->commentable_type !== "App\\Models\\User") {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully!'], 200);
    }

    public function changeWorkerStatus(Request $request)
    {

        // Retrieve the task/comment using the provided ID
        $task = TaskManagement::with(['workers', 'users'])->findOrFail($request->id);
        $user = Auth::user();
      
        // Check if the user is allowed to comment
        if (
            $task->user_id !== $user->id &&  
            !$task->workers->contains('id', $user->id) &&
            !$task->users->contains('id', $user->id)
        ) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    
        // Update the status of the comment/task
        $task->status = $request->status;
        $task->save(); // Save the changes to the database
    
        // Return a success response
        return response()->json(['message' => 'Status updated successfully', 'status' => $task->status]);
    }
    

    public function getComments($taskId)
    {
        $task = TaskManagement::with('comments.commentable')->findOrFail($taskId);
        return response()->json($task->comments);
    }

    /**
     * Get team members that workers can access
     *
     * @return \Illuminate\Http\Response
     */
    public function getWorkerTeamMembers()
    {
        $user = Auth::user();
        
        // Get team members (admins) that are assigned to tasks with this worker
        $teamMembers = Admin::whereHas('taskWorkers', function ($query) use ($user) {
            $query->whereHas('task', function ($taskQuery) use ($user) {
                $taskQuery->whereHas('workers', function ($workerQuery) use ($user) {
                    $workerQuery->where('assignable_id', $user->id)
                               ->where('assignable_type', User::class);
                });
            });
        })
        ->select('id', 'name')
        ->distinct()
        ->orderBy('name')
        ->get()
        ->map(function ($admin) {
            return [
                'value' => $admin->id,
                'label' => $admin->name
            ];
        });

        return response()->json($teamMembers);
    }
}

