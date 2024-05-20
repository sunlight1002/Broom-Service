<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use TCPDF_FONTS;

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
    }
}
