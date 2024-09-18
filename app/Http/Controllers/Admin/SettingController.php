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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function getGeneralSettings()
    {
        $setting = GeneralSetting::first();
        $setting->logo = $setting->logo ?
            Storage::disk('public')->url('uploads/' . $setting->logo) :
            asset('images/Frontlogo.png');

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
            if ($setting->logo) {
                if (Storage::drive('public')->exists('uploads/' . $setting->logo)) {
                    Storage::drive('public')->delete('uploads/' . $setting->logo);
                }
            }

            $image = $request->file('logo');
            $name = $image->getClientOriginalName();
            if (!Storage::disk('public')->exists('uploads')) {
                Storage::disk('public')->makeDirectory('uploads');
            }

            if (Storage::disk('public')->putFileAs("uploads", $image, $name)) {
                $input['logo'] = $name;
            }
        }

        $setting->update($input);

        return response()->json([
            'message' => 'General Setting updated successfully',
        ]);
    }

    public function getAccountDetails()
    {
        $account = Auth::user();
        $account->avatar = $account->avatar ?
            Storage::disk('public')->url('uploads/admin/' . $account->avatar) :
            asset('images/man.png');

        return response()->json([
            'account' => $account,
        ]);
    }

    public function saveAccountDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins,email,' . Auth::user()->id],
            'two_factor_enabled' => ['nullable', 'boolean']
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $input = $request->all();

        $admin = Admin::find(Auth::user()->id);
        if ($request->hasfile('avatar')) {
            if ($admin->avatar) {
                if (Storage::drive('public')->exists('uploads/admin/' . $admin->avatar)) {
                    Storage::drive('public')->delete('uploads/admin/' . $admin->avatar);
                }
            }

            $image = $request->file('avatar');
            $name = $image->getClientOriginalName();
            if (!Storage::disk('public')->exists('uploads/admin')) {
                Storage::disk('public')->makeDirectory('uploads/admin');
            }

            if (Storage::disk('public')->putFileAs("uploads/admin", $image, $name)) {
                $input['avatar'] = $name;
            }

        }
        if ($request->has('twostepverification')) {
            $input['two_factor_enabled'] = $request->input('twostepverification') == 'true';
        }

        $admin->update($input);

        return response()->json([
            'data' => $request->all(),
            'message' => 'Account details updated successfully',
        ]);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'min:6'],
            'password' => ['required', 'min:6', 'confirmed']
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $admin = Admin::find(Auth::user()->id);
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

    public function changeBankDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_type' => ['required', 'string'],
            'full_name' => ['required_if:payment_type,money_transfer'],
            'bank_name' => ['required_if:payment_type,money_transfer'],
            'bank_number' => ['required_if:payment_type,money_transfer'],
            'branch_number' => ['required_if:payment_type,money_transfer'],
            'account_number' => ['required_if:payment_type,money_transfer'],
        ], [
            'payment_type.required' => 'The payment type is required.',
            'full_name.required_if' => 'The full name is required.',
            'bank_name.required_if' => 'The bank name is required.',
            'bank_number.required_if' => 'The bank number is required.',
            'branch_number.required_if' => 'The branch number is required.',
            'account_number.required_if' => 'The account number is required.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $admin = Admin::find(Auth::user()->id);
        $admin->payment_type = $request->input('payment_type');
        $admin->full_name = $request->input('full_name');
        $admin->bank_name = $request->input('bank_name');
        $admin->bank_number = $request->input('bank_number');
        $admin->branch_number = $request->input('branch_number');
        $admin->account_number = $request->input('account_number');

        $admin->save();

        return response()->json(['message' => 'Bank details updated successfully'], 200);
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
