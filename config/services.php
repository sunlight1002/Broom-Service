<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'whatsapp_api' => [
        'code' => env('WHATSAPP_API_CODE'),
        'secret' => env('WHATSAPP_API_SECRET'),
        'auth_token' => env('WHATSAPP_AUTH_TOKEN'),
        'from_id' => env('WHATSAPP_FROM_NUMBER_ID'),
        'meeting_schedule' => env('WHATSAPP_TEMPLATE_METTING_SCHEDULE'),
    ],

    'app' => [
        'lead_token' => env('LEAD_TOKEN'),
        'old_contract' => env('OLD_CONTRACT'),
        'notify_failed_process_to' => env('NOTIFY_FAILED_PROCESS_TO'),
        'tax_percentage' => env('TAX_PERCENTAGE'),
        'currency' => env('CURRENCY'),
    ],

    'zcredit' => [
        'key' => env('ZCREDIT_KEY'),
        'terminalnumber' => env('ZCREDIT_TERMINALNUMBER'),
        'terminalpassword' => env('ZCREDIT_TERMINALPASSWORD'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'calendar_id' => env('GOOGLE_CALENDAR_ID'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
    ],

    'facebook' => [
        'app_id' => env('FB_APP_ID'),
        'app_secret' => env('FB_APP_SECRET'),
        'app_scope_id' => env('FB_APP_SCOPE_ID'),
        'account_id' => env('FB_ACCOUNT_ID'),
        'access_token' => env('FB_ACCESS_TOKEN'),
        'webhook_token' => env('FB_WEBHOOK_TOKEN'),
    ]
];
