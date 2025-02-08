<?php

namespace App\Http\Middleware;

use Closure;

class TelescopeBasicAuth
{
    public function handle($request, Closure $next)
    {
        $username = env('TELESCOPE_BASIC_AUTH_USER', 'admin');
        $password = env('TELESCOPE_BASIC_AUTH_PASSWORD', 'secret');

        if (
            $request->getUser() !== $username ||
            $request->getPassword() !== $password
        ) {
            $headers = ['WWW-Authenticate' => 'Basic'];
            return response('Unauthorized.', 401, $headers);
        }

        return $next($request);
    }
}
