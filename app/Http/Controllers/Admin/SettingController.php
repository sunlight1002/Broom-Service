<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SettingKeyEnum;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\GeneralSetting;
use App\Models\Countries;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function getGeneralSettings()
    {
        $setting = GeneralSetting::first();
        $setting->logo  = $setting->logo ? asset('storage/uploads/' . $setting->logo) : asset('images/Frontlogo.png');

        return response()->json([
            'setting' => $setting,
        ]);
    }

    public function saveGeneralSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'         => ['required', 'string', 'max:255'],
            'facebook'      => ['required'],
            'twitter'       => ['required'],
            'instagram'     => ['required'],
            'linkedin'      => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $setting = GeneralSetting::first();

        $input = $request->all();

        if ($request->hasfile('logo')) {
            $image = $request->file('logo');
            $name = $image->getClientOriginalName();
            $image->storeAs('uploads/', $name, 'public');

            $input['logo'] = $name;
        }

        GeneralSetting::where('id', $setting->id)->update($input);

        return response()->json([
            'message' => 'General Setting updated successfully',
        ], 200);
    }

    public function getAccountDetails()
    {
        $account = Auth::user();
        $account->avatar = $account->avatar ? asset('storage/uploads/admin/' . $account->avatar) : asset('images/man.png');

        return response()->json([
            'account' => $account,
        ]);
    }

    public function saveAccountDetails(Request $request)
    {
        $admin = Auth::user();
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins,email,' . $admin->id],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $input = $request->all();
        if ($request->hasfile('avatar')) {
            $image = $request->file('avatar');
            $name = $image->getClientOriginalName();
            $image->storeAs('uploads/admin/', $name, 'public');

            $input['avatar'] = $name;
        }

        $admin = Admin::where('id', $admin->id)->update($input);

        return response()->json([
            'message' => 'Account details updated successfully',
        ]);
    }

    public function changePassword(Request $request)
    {
        $id = Auth::user()->id;

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'min:6'],
            'password' => ['required', 'min:6', 'confirmed']
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $admin = Admin::find($id);
        if (Hash::check($request->get('current_password'), $admin->password)) {
            $admin->password = Hash::make($request->password);
            $admin->save();

            return response()->json([
                'message' => 'Password changed successfully',
            ]);
        } else {
            return response()->json([
                'errors' => [
                    'current_password' => 'Current password is incorrect.'
                ]
            ]);
        }

        return response()->json([
            'message' => 'Password changed successfully',
        ]);
    }

    public function getCountries()
    {
        $countries = Countries::get();

        return response()->json([
            'countries' => $countries
        ]);
    }

    public function allSettings()
    {
        $settings = Setting::query()
            ->select('key', 'value')
            ->get()
            ->pluck('value', 'key')
            ->toArray();

        return response()->json($settings);
    }

    public function updateSettings(Request $request)
    {
        if ($request->for == 'zcredit') {
            $validator = Validator::make($request->all(), [
                'zcredit_key' => 'required',
                'zcredit_terminal_number' => 'required',
                'zcredit_terminal_pass' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->messages()]);
            }

            Setting::updateOrCreate(
                ['key' => SettingKeyEnum::ZCREDIT_KEY],
                ['value' => $request->zcredit_key]
            );

            Setting::updateOrCreate(
                ['key' => SettingKeyEnum::ZCREDIT_TERMINAL_NUMBER],
                ['value' => $request->zcredit_terminal_number]
            );

            Setting::updateOrCreate(
                ['key' => SettingKeyEnum::ZCREDIT_TERMINAL_PASS],
                ['value' => $request->zcredit_terminal_pass]
            );
        }

        if ($request->for == 'icount') {
            $validator = Validator::make($request->all(), [
                'icount_company_id' => 'required',
                'icount_username' => 'required',
                'icount_password' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->messages()]);
            }

            Setting::updateOrCreate(
                ['key' => SettingKeyEnum::ICOUNT_COMPANY_ID],
                ['value' => $request->icount_company_id]
            );

            Setting::updateOrCreate(
                ['key' => SettingKeyEnum::ICOUNT_USERNAME],
                ['value' => $request->icount_username]
            );

            Setting::updateOrCreate(
                ['key' => SettingKeyEnum::ICOUNT_PASSWORD],
                ['value' => $request->icount_password]
            );
        }

        return response()->json([
            'success' => 'Settings has been updated'
        ]);
    }
}
