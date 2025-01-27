<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Add FreeSerif font
        TCPDF_FONTS::addTTFfont(public_path('fonts/FreeSerif.ttf'), 'TrueTypeUnicode', '', 32);
        // Add other variations if necessary

        // Password::createUrlFromToken(function ($token) {
        //     return url('client/reset-password' . $token);
        // });
    
        // Password::createUrlFromToken(function ($token) {
        //     return url('user/reset-password' . $token);
        // });
    }
}
