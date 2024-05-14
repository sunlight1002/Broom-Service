<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManpowerCompany;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ManpowerCompaniesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $companies = ManpowerCompany::query()
            ->latest()
            ->paginate(20);

        return response()->json([
            'companies' => $companies,
        ]);
    }

    public function allCompanies()
    {
        $companies = ManpowerCompany::all();

        return response()->json([
            'companies' => $companies,
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
            'name' => ['required', 'unique:manpower_companies'],
            'file' => ['required', 'file'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $data = $request->all();
        if (!Storage::drive('public')->exists('manpower-companies/contract')) {
            Storage::drive('public')->makeDirectory('manpower-companies/contract');
        }

        $file = $request->file('file');

        $file_name = Str::uuid()->toString() . '.pdf';
        if (!Storage::disk('public')->putFileAs("manpower-companies/contract", $file, $file_name)) {
            return response()->json([
                'message' => "Can't save PDF"
            ], 403);
        }

        $data['contract_filename'] = $file_name;
        ManpowerCompany::create($data);

        return response()->json([
            'message' => 'Company has been created'
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ManpowerCompany  $company
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'unique:manpower_companies,name,' . $id],
            'file' => ['nullable', 'file'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $company = ManpowerCompany::find($id);

        if (!$company) {
            return response()->json([
                'message' => 'Company not found'
            ], 404);
        }

        $data = $request->all();
        if (!Storage::drive('public')->exists('manpower-companies/contract')) {
            Storage::drive('public')->makeDirectory('manpower-companies/contract');
        }

        if ($request->hasFile('file')) {
            if (
                $company->contract_filename &&
                Storage::drive('public')->exists('manpower-companies/contract/' . $company->contract_filename)
            ) {
                Storage::drive('public')->delete('manpower-companies/contract/' . $company->contract_filename);
            }

            $file = $request->file('file');

            $file_name = Str::uuid()->toString() . '.pdf';
            if (!Storage::disk('public')->putFileAs("manpower-companies/contract", $file, $file_name)) {
                return response()->json([
                    'message' => "Can't save PDF"
                ], 403);
            }

            $data['contract_filename'] = $file_name;
        } else {
            $data['contract_filename'] = $company->contract_filename;
        }

        $company->update($data);

        return response()->json([
            'message' => 'Company has been updated'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ManpowerCompany  $company
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (User::where('manpower_company_id', $id)->exists()) {
            return response()->json([
                'message' => "Company can't be deleted"
            ], 403);
        }

        $company = ManpowerCompany::find($id);
        $company->delete();

        return response()->json([
            'message' => "Company has been deleted"
        ]);
    }
}
