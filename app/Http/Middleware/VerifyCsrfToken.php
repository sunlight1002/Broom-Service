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
        '/webhook_active_workers',
        '/webhook_active_clients',
        '/webhook_worker_lead',
        '/webhook_active_worker_monday',
        '/webhook_active_client_monday',
        'twilio/voice/webhook',
        'zcredit/callback',
        'newlead',
        'facebook/webhook',
        'icount/webhook',
        'twilio/*',
    ];
}
