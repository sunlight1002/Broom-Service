<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Holiday;
use Illuminate\Support\Facades\Validator;

class HolidayController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $columns = ['id', 'holiday_name', 'start_date', 'end_date'];

        $length = $request->get('length', 10);
        $start = $request->get('start', 0); 
        $column = $request->get('column', 0); 
        $dir = $request->get('dir', 'asc'); 

        $query = Holiday::select('id', 'holiday_name', 'start_date', 'end_date');

        // Search filter
        if ($search = $request->get('search')) {
            $query->where(function ($query) use ($search) {
                $query->where('holiday_name', 'like', "%{$search}%")
                    ->orWhere('start_date', 'like', "%{$search}%")
                    ->orWhere('end_date', 'like', "%{$search}%");
            });
        }

        // Sorting
        $query->orderBy($columns[$column], $dir);
        

        // Pagination
        $totalRecords = $query->count();
        $holidays = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => intval($request->get('draw')),
            'data' => $holidays,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
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
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'holiday_name' => 'required|string|max:255',
        ]);

        $holiday = Holiday::create($request->all());
        return response()->json($holiday, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $holiday = Holiday::findOrFail($id);
        return response()->json($holiday);
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
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'holiday_name' => 'required|string|max:255',
        ]);

        $holiday = Holiday::findOrFail($id);
        $holiday->update($request->all());

        return response()->json($holiday);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->delete();

        return response()->json(null, 204);
    }
}
