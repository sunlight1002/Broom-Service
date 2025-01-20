<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'webhook_fb',
        '/webhook_worker',
        '/webhook_active_worker',
        '/webhook_active_client',
        'twilio/voice/webhook',
        'zcredit/callback',
        'newlead',
        'facebook/webhook',
        'icount/webhook',
        'twilio/*',
    ];
}
