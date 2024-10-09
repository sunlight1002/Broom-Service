<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Phase;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PhaseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $phases = Phase::all();
        return response()->json($phases);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'phase_name' => 'required|string|max:255',
        ]);

        $phase = Phase::create([
            'phase_name' => $validated['phase_name'],
        ]);
       return response()->json(['message' => 'Phase created successfully!', 'phase' => $phase], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = Auth::user();

        if ($user->role === 'admin' || $user->role == 'superadmin') {
            // Admin can see all phases and tasks
            $phases = Phase::with(['tasks' => function ($query) {
                $query->orderBy('sort_order');} ,
                'tasks.comments.commentable', 'tasks.workers:id,firstname',
                'tasks.users:id,name'])
                ->find($id);
        } else {
            // Workers and users can only see their respective phases and tasks
            $user_id = $user->id;
            $userType = get_class($user);

            $phases = Phase::whereHas('tasks', function($query) use ($user_id, $userType) {

                $query->whereHas('workers', function($query) use ($user_id, $userType) {
                    $query->where('assignable_id', $user_id);
                    $query->where('assignable_type', User::class);
                })->orWhereHas('users', function($query) use ($user_id) {
                    $query->where('assignable_id', $user_id);
                    $query->where('assignable_type', Admin::class);
                });

            })->with(['tasks' => function($query) use ($user_id, $userType) {
                $query->whereHas('workers', function($query) use ($user_id, $userType) {
                    $query->where('assignable_id', $user_id);
                    $query->where('assignable_type', User::class);
                })->orWhereHas('users', function($query) use ($user_id) {
                    $query->where('assignable_id', $user_id);
                    $query->where('assignable_type', Admin::class);
                })->with(['comments.commentable', 'workers:id,firstname', 'users:id,name'])
                ->orderBy('sort_order');
            }])->find($id);
        }

        return response()->json(['phases' => $phases]);

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
        try {
            $validated = $request->validate([
                'phase_name' => 'required|string|max:255',
            ]);

            $phase = Phase::find($id);

            if (!$phase) {
                return response()->json(['message' => 'Phase not found'], 404);
            }

            $phase->update(['phase_name' => $validated['phase_name']]);

            return response()->json(['message' => 'Phase has been updated'], 200);

        } catch (\Exception $e) {
            return response()->json([
                'errors' => [ 'An error occurred while updating the phase'],
                'exception' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $phase = Phase::find($id);

        if ($phase) {
            // Delete the tasks and their comments
            foreach ($phase->tasks as $task) {
                $task->comments()->delete(); 
                $task->delete(); 
            }

            $phase->delete(); 

            return response()->json(['message' => 'Phase and its tasks deleted successfully!']);
        } else {
            return response()->json(['message' => 'Phase not found'], 404);
        }
    }
}
