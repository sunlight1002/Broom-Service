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
}
