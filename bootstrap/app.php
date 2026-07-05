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
        // Le trafic arrive via le proxy Cloudflare / tunnel cloudflared
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin'   => \App\Http\Middleware\AdminMiddleware::class,
            'gestion' => \App\Http\Middleware\GestionMairieMiddleware::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\ForcePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Rediriger vers l'accueil en cas de CSRF expiré (419) au lieu d'afficher "Page Expired"
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            return redirect()->route('home')->with('info', 'Votre session a expiré. Veuillez vous reconnecter.');
        });
    })->create();
