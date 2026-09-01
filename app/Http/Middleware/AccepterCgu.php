<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tant que les conditions générales d'utilisation ne sont pas acceptées,
 * l'utilisateur connecté est redirigé vers la page des CGU.
 */
class AccepterCgu
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Le changement de mot de passe imposé passe AVANT les CGU : sans cette
        // exception, les deux middlewares se renvoient la balle indéfiniment.
        if ($user && $user->must_change_password) {
            return $next($request);
        }

        $routesLibres = [
            'cgu', 'cgu.accepter', 'logout', 'lang.switch',
            'password.force-change', 'password.force-change.update',
            'password.request', 'password.email', 'password.reset', 'password.store', 'password.update',
        ];

        if ($user && ! $user->cgu_acceptees_at && ! $request->routeIs(...$routesLibres)) {
            return redirect()->route('cgu');
        }

        return $next($request);
    }
}
