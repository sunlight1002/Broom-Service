<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class LogAdminActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (Auth::guard('admin-api')->check()) {
            $admin = Auth::guard('admin-api')->user();
            $adminModel = \App\Models\Admin::find($admin->id);

            $ip = $request->ip();

            if (in_array($ip, ['127.0.0.1', '::1'])) {
                $ip = '24.48.0.1'; // IP (Canada)
            }

            $country = 'Unknown';
            try {
                $raw = Http::timeout(3)->get("http://ip-api.com/php/{$ip}")->body();

                $geo = @unserialize($raw);

                if ($geo && is_array($geo) && ($geo['status'] ?? '') === 'success') {
                    $country = $geo['country'] ?? 'Unknown';
                }
            } catch (\Exception $e) {
            }

            $adminModel->update([
                'ip' => $ip,
                'country' => $country,
                'last_activity_date' => now(),
            ]);
        }

        return $response;
    }
}
