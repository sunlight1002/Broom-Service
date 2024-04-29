<?php

namespace App\Http\Middleware;

use App\Models\Client;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class SetLanguage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            if (get_class(Auth::user()) == Client::class) {
                App::setLocale(Auth::user()->lng);
            } else if (get_class(Auth::user()) == User::class) {
                App::setLocale(Auth::user()->lng);
            } else {
                App::setLocale('en');
            }
        } else {
            App::setLocale('en');
        }

        return $next($request);
    }
}
