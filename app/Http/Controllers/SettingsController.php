<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function getSettings()
    {
        $settings = Setting::all()->pluck('value', 'key');
        
        $overtimeRate125 = $settings['overtime_rate_9to10_hours'] ?? null;
        $overtimeRate150 = $settings['overtime_rate_11to12_hours'] ?? null;
        $holidayPay175 = $settings['holiday_rate_forTwo_hours'] ?? null;
        $holidayPay200 = $settings['holiday_rate_three_hours'] ?? null;
        $bonusAfterOneYear = $settings['bonus_after_one_year_perHour'] ?? null;
        $bonusAfterSixYears = $settings['bonus_after_six_years_perHour'] ?? null;
        $publicHolidayBonus = $settings['rosh_hashanah_pay'] ?? null;
        $workerDeduction = (float)($settings['deduction_foreignworker'] ?? 0);
        $recoveryFee = $settings['recovery_fee_year_ofservice'] ?? null;
        $drivingFeeDay = $settings['driving_fee_perDay'] ?? null;
        $drivingFeeMonth = (float)($settings['driving_fee_perMonth'] ?? 0);
        
        return response()->json([
            'overtimeRate125' => $overtimeRate125,
            'overtimeRate150' => $overtimeRate150,
            'holidayPay175' => $holidayPay175,
            'holidayPay200' => $holidayPay200,
            'bonusAfterOneYear' => $bonusAfterOneYear,
            'bonusAfterSixYears' => $bonusAfterSixYears,
            'publicHolidayBonus' => $publicHolidayBonus,
            'workerDeduction' => $workerDeduction,
            'recoveryFee' => $recoveryFee,
            'drivingFeeDay' => $drivingFeeDay,
            'drivingFeeMonth' => $drivingFeeMonth,
        ]);
    }

    public function saveSettings(Request $request)
    {
        // Define validation rules
        $validator = Validator::make($request->all(), [
            'overtimeRate125' => ['required', 'numeric', 'min:0'],
            'overtimeRate150' => ['required', 'numeric', 'min:0'],
            'holidayPay175' => ['required', 'numeric', 'min:0'],
            'holidayPay200' => ['required', 'numeric', 'min:0'],
            'bonusAfterOneYear' => ['required', 'numeric', 'min:0'],
            'bonusAfterSixYears' => ['required', 'numeric', 'min:0'],
            'publicHolidayBonus' => ['required', 'numeric', 'min:0'],
            'workerDeduction' => ['required', 'numeric', 'min:0'],
            'recoveryFee' => ['required', 'numeric', 'min:0'],
            'drivingFeeDay' => ['required', 'numeric', 'min:0'],
            'drivingFeeMonth' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 422);
        }

        $settings = $request->only([
            'overtimeRate125',
            'overtimeRate150',
            'holidayPay175',
            'holidayPay200',
            'bonusAfterOneYear',
            'bonusAfterSixYears',
            'publicHolidayBonus',
            'workerDeduction',
            'recoveryFee',
            'drivingFeeDay',
            'drivingFeeMonth',
        ]);

        // Mapping the request data to the corresponding settings keys
        $keyMapping = [
            'overtimeRate125' => 'overtime_rate_9to10_hours',
            'overtimeRate150' => 'overtime_rate_11to12_hours',
            'holidayPay175' => 'holiday_rate_forTwo_hours',
            'holidayPay200' => 'holiday_rate_three_hours',
            'bonusAfterOneYear' => 'bonus_after_one_year_perHour',
            'bonusAfterSixYears' => 'bonus_after_six_years_perHour',
            'publicHolidayBonus' => 'rosh_hashanah_pay',
            'workerDeduction' => 'deduction_foreignworker',
            'recoveryFee' => 'recovery_fee_year_ofservice',
            'drivingFeeDay' => 'driving_fee_perDay',
            'drivingFeeMonth' => 'driving_fee_perMonth',
        ];

        foreach ($settings as $key => $value) {
            $dbKey = $keyMapping[$key] ?? null;
            if ($dbKey) {
                Setting::updateOrCreate(
                    ['key' => $dbKey],
                    ['value' => $value]
                );
            }
        }

        return response()->json(['success' => 'Settings saved successfully.'], 200);
    }
}
