<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SkillController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $filter                     = [];
        $filter['skill']            = $request->skill;       
        $filter['status']           = $request->status;

        $skills              = Skill::query();

        if(isset($filter['skill'])){
            $skills          = $skills->where('skill', 'LIKE', '%'.$filter['skill'].'%');
        }
       
        if(isset($filter['status'])){
            $skills          = $skills->where('status', $filter['status']);
        }

        $skills              = $skills->orderBy('id', 'desc')->paginate(20);

        return response()->json([
            'skills'         => $skills,        
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
            'skill'       => ['required', 'string', 'max:255'],           
            'status'      => ['required'],           
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $input                  = $request->all();        
        $skill               = Skill::create($input);

        return response()->json([
            'message'       => 'Skill created successfully',            
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $skill                = Skill::find($id);

        return response()->json([
            'skill'       => $skill,        
        ], 200);
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
            'skill' => ['required', 'string', 'max:255'],           
            'status'      => ['required'],           
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $input                  = $request->all();        
        $skill                  = Skill::where('id', $id)->update($input);

        return response()->json([
            'message'       => 'Skill updated successfully',            
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Skill::find($id)->delete();
        return response()->json([
            'message'     => "Skill has been deleted"         
        ], 200);
    }
}
