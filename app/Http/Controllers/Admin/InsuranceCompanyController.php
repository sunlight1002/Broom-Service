<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InsuranceCompany;
use Illuminate\Http\Request;

class InsuranceCompanyController extends Controller
{
    public function index()
    {
        $insurance_companies = InsuranceCompany::first();
        
        return response()->json([
            'insurance_companies' => $insurance_companies
        ],200);
    }

    public function updateOrCreate(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required',
        ]);

        $data = $request->all();
        $insurance_companies = InsuranceCompany::first();
        if ($insurance_companies) {
            $insurance_companies->update($data);
        } else {
            InsuranceCompany::create($data);
        }

        return response()->json([
            'insurance_companies' => InsuranceCompany::first()
        ], 200);
    }
}
