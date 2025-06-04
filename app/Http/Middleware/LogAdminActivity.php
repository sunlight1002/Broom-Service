<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Jobs\LogAdminLocation;

class LogAdminActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (Auth::guard('admin-api')->check()) {
            $admin = Auth::guard('admin-api')->user();
            $cacheKey = 'admin_activity_logged_' . $admin->id;

            // Dispatch only if not recently logged
            if (!Cache::has($cacheKey)) {
                $ip = $request->ip();

                // Fallback for localhost IPs
                if (in_array($ip, ['127.0.0.1', '::1'])) {
                    $ip = '24.48.0.1'; // Example IP for testing
                }

                LogAdminLocation::dispatch($admin->id, $ip);

                // Cache to prevent re-dispatching for 5 minutes
                Cache::put($cacheKey, true, now()->addMinutes(5));
            }
        }

        return $response;
    }
}
