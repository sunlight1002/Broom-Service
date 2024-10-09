<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TaskManagement;
use App\Models\Admin;
use App\Models\Phase;
use App\Models\User;
use App\Models\TaskComment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tasks = TaskManagement::with([
            'phase', 
            'comments', 
            'workers:id,firstname',
            'users:id,name'
        ])
        ->orderBy('sort_order')
        ->get();
        
        return response()->json($tasks);
    }

    public function showWorkerTasks($workerId)
    {
        // Fetch tasks assigned to the specific worker
        $tasks = TaskManagement::with('phase')->whereHas('taskWorker', function($query) use ($workerId) {
            $query->where('assignable_id', $workerId)
                  ->where('assignable_type', User::class); // Adjust User::class if it's Admin or another class
        })
        ->with([
            'comments',
        ])
        ->orderBy('sort_order')
        ->get();
        
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
            'phase_id' => 'required|exists:phase,id',
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
    
        $user = Auth::user();
        $userType = get_class($user);
        \Log::info('Authenticated User:', ['user' => $user, 'user_type' => $userType]);
    
        // Create a new task
        $task = new TaskManagement($request->all());
        $task->user_id = $user->id;
        $task->user_type = $userType;  
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
            'workers:id,firstname',
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

        return response()->json(['message' => 'Task has been deleted'], 204); 
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
        if ($comment->commentable_id !== $user->id || $comment->commentable_type !== get_class($user)) {
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
}

