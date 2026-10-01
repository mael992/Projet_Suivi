<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Le trafic arrive via le proxy Cloudflare / tunnel cloudflared : on lui
        // fait confiance pour le schéma (https) et l'hôte, mais PAS pour l'IP du
        // visiteur via X-Forwarded-For (falsifiable). L'IP réelle est reprise de
        // l'en-tête Cloudflare « CF-Connecting-IP » par SetCloudflareClientIp.
        $middleware->trustProxies(at: '*', headers:
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
        );

        // Doit s'exécuter avant toute lecture de request()->ip()
        $middleware->prepend(\App\Http\Middleware\SetCloudflareClientIp::class);

        $middleware->alias([
            'admin'   => \App\Http\Middleware\AdminMiddleware::class,
            'gestion' => \App\Http\Middleware\GestionMairieMiddleware::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\ForcePasswordChange::class,
            \App\Http\Middleware\AccepterCgu::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Rediriger vers l'accueil en cas de CSRF expiré (419) au lieu d'afficher "Page Expired"
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            return redirect()->route('home')->with('info', 'Votre session a expiré. Veuillez vous reconnecter.');
        });
    })->create();
