<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Holiday;
use Carbon\Carbon;
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
        $columns = ['id', 'holiday_name', 'start_date', 'end_date', 'full_day', 'half_day', 'first_half', 'second_half'];

        $length = $request->get('length', 10);
        $start = $request->get('start', 0);
        $column = $request->get('column', 0);
        $dir = $request->get('dir', 'asc');

        $query = Holiday::select('id', 'holiday_name', 'start_date', 'end_date', 'full_day', 'half_day', 'first_half', 'second_half');

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
            'day_type' => 'required|in:fullDay,halfDay',  
            'half_type' => 'nullable|in:firstHalf,secondHalf',
        ]);
    
        $data = [
            'holiday_name' => $request->holiday_name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'full_day' => false,
            'half_day' => false,
            'first_half' => false,
            'second_half' => false,
        ];
    
        if ($request->day_type == 'fullDay') {
            $data['full_day'] = true;
        } elseif ($request->day_type == 'halfDay') {
            $data['half_day'] = true;
    
            if ($request->half_type == 'firstHalf') {
                $data['first_half'] = true;
            } elseif ($request->half_type == 'secondHalf') {
                $data['second_half'] = true;
            }
        }
    
        $holiday = Holiday::create($data);
        return response()->json($holiday, 201);
    }
    

    public function getAll()
    {
        $holidays = Holiday::select('id', 'start_date', 'end_date', 'holiday_name')
            ->get()
            ->map(function ($holiday) {
                // Calculate all dates between start_date and end_date
                $start = Carbon::parse($holiday->start_date);
                $end = Carbon::parse($holiday->end_date);
                $allDates = [];
    
                while ($start->lte($end)) {
                    $allDates[] = $start->format('Y-m-d');
                    $start->addDay();
                }
    
                // Return holiday data with allDates field
                return [
                    'id' => $holiday->id,
                    'name' => $holiday->holiday_name,
                    'start_date' => $holiday->start_date,
                    'end_date' => $holiday->end_date,
                    'all_dates' => $allDates,
                ];
            });
    
        return response()->json($holidays, 200);
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
