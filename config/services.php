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
        'callback-url' => env('ZCREDIT_CALLBACK_URL'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'calendar_id' => env('GOOGLE_CALENDAR_ID'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
        'map_key' => env('GOOGLE_MAP_KEY'),
        'translate_key' => env('GOOGLE_TRANSLATE_API_KEY'),
    ],

    'facebook' => [
        'app_id' => env('FB_APP_ID'),
        'app_secret' => env('FB_APP_SECRET'),
        'app_scope_id' => env('FB_APP_SCOPE_ID'),
        'account_id' => env('FB_ACCOUNT_ID'),
        'access_token' => env('FB_ACCESS_TOKEN'),
        'msg_access_token' => env('FB_MSG_ACCESS_TOKEN'),
        'webhook_token' => env('FB_WEBHOOK_TOKEN'),
        'page_id' => env('FB_PAGE_ID'),
        'business_id' => env('FB_BUSINESS_ID'),
    ],

    'insta' => [
        'insta_id' => env('INSTA_BUSINESS_ID'),
        'insta_name' => env('INSTA_USERNAME'),
    ],

    'whapi' => [
        'url' => env('WHAPI_API_URL'),
        'token' => env('WHAPI_API_TOKEN'),
        'client_token' => env('CLIENT_WHAPI_API_TOKEN'),
        'worker_token' => env('WORKER_WHAPI_API_TOKEN'),
        'worker_job_token' => env('WORKER_WHAPI_JOB_API_TOKEN'),
    ],

    'whatsapp_groups' => [
        'payment_status' => env('PAYMENT_STATUS_WA_GROUP_ID'),
        'changes_cancellation' => env('CHANGES_CANCELLATION_WA_GROUP_ID'),
        'lead_client' => env('LEAD_CLIENT_WA_GROUP_ID'),
        'workers_availability' => env('WORKERS_AVAILABILITY_WA_GROUP_ID'),
        'reviews_of_clients' => env('REVIEWS_OF_CLIENTS_WA_GROUP_ID'),
        'problem_with_workers' => env('PROBLEM_WITH_WORKERS_WA_GROUP_ID'),
        'notification_test' => env('NOTIFICATION_TEST_GROUP'),
        'relevant_with_workers' => env('RELEVANT_WITH_WORKERS_WA_GROUP_ID'),
        'problem_with_payments' => env('PROBLEM_WITH_PAYMENTS_WA_GROUP_ID'),
        'urgent' => env('URGENT_WA_GROUP_ID'),
    ],

    'twilio' => [
        'twilio_id' => env('TWILIO_SID'),
        'twilio_token' => env('TWILIO_AUTH_TOKEN'),
        'twilio_number' => env('TWILIO_NUMBER'),
        'twilio_whatsapp_number' => env('TWILIO_WHATSAPP_NUMBER'),
        'webhook' => env('TWILIO_WEBHOOK_URL')
    ],

    'short_url' => [
        'domain' => env('SHORT_URL_DOMAIN'),
        'worker' => env('WORKER_SHORT_URL'),
        'admin' => env('ADMIN_SHORT_URL'),
        'client' => env('CLIENT_SHORT_URL'),
    ],

    'mail' => [
        'default' => env('DEFAULT_MAIL'),
    ]

];
