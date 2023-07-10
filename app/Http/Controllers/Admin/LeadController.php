<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Lead;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $q = $request->q;
        $result = Lead::query();

        $result->where('name',    'like', '%' . $q . '%');
        $result->orWhere('email',   'like', '%' . $q . '%');
        $result->orWhere('phone',       'like', '%' . $q . '%');
        $result->orWhere('meta', 'like', '%' . $q . '%');

        $result = $result->orderBy('id', 'desc')->paginate(20);

        return response()->json([
            'leads'       => $result,
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

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
            'name' => ['required', 'string', 'max:255'],
            'phone'     => ['required'],
            'email'     => ['required'],
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }
        $lead                = new Lead;
        $lead->name          = $request->name;
        $lead->phone         = $request->phone;
        $lead->email         = $request->email;
        $lead->lead_status   = $request->lead_status;
        $lead->meta          = $request->meta;
        $lead->save();

        return response()->json([
            'message'       => 'Lead created successfully',            
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
         $lead                = Lead::find($id);
        return response()->json([
            'lead'        => $lead,            
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
            'name' => ['required', 'string', 'max:255'],
            'phone'     => ['required'],
            'email'     => ['required'],
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }
        $lead                = Lead::find($id);
        $lead->name          = $request->name;
        $lead->phone         = $request->phone;
        $lead->email         = $request->email;
        $lead->lead_status   = $request->lead_status;
        $lead->meta          = $request->meta;
        $lead->save();

        return response()->json([
            'message'       => 'Lead updated successfully',            
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
         Lead::find($id)->delete();
        return response()->json([
            'message'     => "Lead has been deleted"         
        ], 200);
    }
}
