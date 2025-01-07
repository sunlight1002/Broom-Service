<?php

declare(strict_types=1);

namespace App\Enums;

final class SettingKeyEnum extends AbstractEnum
{
    public const ZCREDIT_TERMINAL_NUMBER = 'zcredit_terminal_number';
    public const ZCREDIT_TERMINAL_PASS = 'zcredit_terminal_pass';
    public const ZCREDIT_KEY = 'zcredit_key';
    public const ICOUNT_COMPANY_ID = 'icount_company_id';
    public const ICOUNT_USERNAME = 'icount_username';
    public const ICOUNT_PASSWORD = 'icount_password';
    public const ICOUNT_X_SECRET = 'icount_x_secret';
    public const GOOGLE_ACCESS_TOKEN = 'google_access_token';
    public const GOOGLE_REFRESH_TOKEN = 'google_refresh_token';
    public const GOOGLE_CALENDAR_ID = 'google_calendar_id';
    public const GOOGLE_SHEET_ID = 'google_sheet_id';
    public const OVERTIME_RATE_9TO10_HOURS = 'overtime_rate_9to10_hours';
    public const OVERTIME_RATE_11TO12_HOURS = 'overtime_rate_11to12_hours';
    public const HOLIDAY_RATE_FORTWO_HOURS = 'holiday_rate_forTwo_hours';
    public const HOLIDAY_RATE_THREE_HOURS = 'holiday_rate_three_hours';
    public const ROSH_HASHANAH_PAY = 'rosh_hashanah_pay';
    public const PUBLIC_SECTOR_HOLIDAY_PAY = 'public_sector_holiday_pay';
    public const BONUS_AFTER_ONE_YEAR = 'bonus_after_one_year_perHour';
    public const BONUS_AFTER_SIX_YEARS = 'bonus_after_six_years_perHour';
    public const DEDUCTION_FOREIGNWORKER = 'deduction_foreignworker';
    public const RECOVERY_FEE = 'recovery_fee_year_ofservice';
    public const DRIVING_FEE_PERMONTH = 'driving_fee_perMonth';
    public const DRIVING_FEE_PERDAY = 'driving_fee_perDay';
}
