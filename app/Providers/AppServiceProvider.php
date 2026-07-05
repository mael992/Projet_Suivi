<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Derrière Cloudflare (SSL Flexible), le serveur reçoit du HTTP :
        // on force le schéma HTTPS en production pour que les URLs, les
        // formulaires et les cookies de session soient corrects.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
