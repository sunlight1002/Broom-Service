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
        ])->get();
        
        return response()->json($tasks);
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
            'status'   => 'required|string',
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
        \Log::info('Authenticated User:', ['user' => $user,'user_type' => $userType]);

        $task = new TaskManagement($request->all());
        $task->user_id =  $user->id;
        $task->user_type = $userType;  
        
        $task->save();
        
        if ($request->has('worker_ids')) {
            foreach ($request->worker_ids as $workerId) {
                $syncData[$workerId] = ['assignable_type' => User::class];
            }
        }
    
        if ($request->has('user_ids')) {
            foreach ($request->user_ids as $userId) {
                $syncData[$userId] = ['assignable_type' => Admin::class];
            }
        }

        $task->workers()->syncWithoutDetaching($syncData);
        $task->users()->syncWithoutDetaching($syncData);

        return response()->json(['message' => 'Tasks created successfully!','task' => $task], 201);
    }
    
    public function sort(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $sortedIDs = $request->input('ids');

    $tasks = TaskManagement::whereIn('id', $sortedIDs)->get();
    $tasksGroupedByPhase = $tasks->groupBy('phase_id');

    foreach ($tasksGroupedByPhase as $phaseID => $tasksInPhase) {
        $sortedInPhase = $tasksInPhase->keyBy('id');

        foreach ($sortedIDs as $sortOrder => $id) {
            if ($sortedInPhase->has($id) && $sortedInPhase->get($id)->phase_id == $phaseID) {
                $task = $sortedInPhase->get($id);
                $task->sort_order = $sortOrder + 1; 
                $task->save();
            }
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
        $validator = Validator::make($request->all(), [
            'phase_id' => 'required|exists:phase,id',
            'task_name' => 'required|string',
            'status'   => 'required|string',
            'priority' => 'required|string|in:high,medium,low',
            'due_date' => 'nullable|date',
            'worker_ids' => 'nullable|array',
            'worker_ids.*' => 'integer|exists:users,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:admins,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        if (!Auth::check()) {
            return response()->json(['error' => 'User is not authenticated'], 401);
        }

        $user = Auth::user();
        $userType = get_class($user);
        \Log::info('Authenticated User:', ['user' => $user,'user_type' => $userType]);

        $task = TaskManagement::find($id);
        $task->update($request->all());
        $task->user_id =  $user->id;
        $task->user_type = $userType;  
        $task->save();
        

        if ($request->has('worker_ids')) {
            // Detach existing workers not in the new list
            $existingWorkerIds = $task->workers->pluck('id')->toArray();
            $newWorkerIds = $request->worker_ids;
            $workersToDetach = array_diff($existingWorkerIds, $newWorkerIds);
            
            // Detach workers no longer assigned
            $task->workers()->whereIn('assignable_id', $workersToDetach)->delete();
    
            // Attach new workers
            foreach ($newWorkerIds as $workerId) {
                $task->workers()->syncWithoutDetaching([
                    $workerId => ['assignable_type' => User::class]
                ]);
            }
        }
    
        // Update users (admins)
        if ($request->has('user_ids')) {
            // Detach existing users not in the new list
            $existingUserIds = $task->users->pluck('id')->toArray();
            $newUserIds = $request->user_ids;
            $usersToDetach = array_diff($existingUserIds, $newUserIds);
            
            // Detach users no longer assigned
            $task->users()->whereIn('assignable_id', $usersToDetach)->delete();
    
            // Attach new users
            foreach ($newUserIds as $userId) {
                $task->users()->syncWithoutDetaching([
                    $userId => ['assignable_type' => Admin::class]
                ]);
            }
        }
    
        
        return response()->json(['message' => 'Task has been updated', 'task' => $task]);
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
            !$task->workers->contains($user->id) &&
            !$task->users->contains($user->id)
        ) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $comment = new TaskComment([
             'task_management_id' => $taskId,
             'commentable_id' => $user->id,
             'commentable_type' => get_class($user),
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

    public function getComments($taskId)
    {
        $task = TaskManagement::with('comments.commentable')->findOrFail($taskId);
        return response()->json($task->comments);
    }
}

