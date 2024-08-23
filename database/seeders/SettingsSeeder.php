<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('settings')->insert([
            ['key' => 'overtime_rate_9to10_hours', 'value' => 1.25],
            ['key' => 'overtime_rate_11to12_hours', 'value' => 1.50],
            ['key' => 'holiday_rate_forTwo_hours', 'value' => 1.75],
            ['key' => 'holiday_rate_three_hours', 'value' => 2.00],
            ['key' => 'rosh_hashanah_pay', 'value' => 234.56],
            ['key' => 'public_sector_holiday_pay', 'value' => 300.00],
            ['key' => 'bonus_after_one_year_perHour', 'value' => 0.35],
            ['key' => 'bonus_after_six_years_perHour', 'value' => 0.46],
            ['key' => 'deduction_foreignworker', 'value' => 134.46],
            ['key' => 'recovery_fee_year_ofservice', 'value' => 423 ],
            ['key' => 'driving_fee_perDay', 'value' => 13 ],
            ['key' => 'driving_fee_perMonth', 'value' => 236 ],
        ]);
    }
}
