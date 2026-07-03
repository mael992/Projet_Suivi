<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Accès à la "Gestion de la Mairie" : seuls les responsables et
 * sous-responsables (et les admins) y ont droit.
 */
class GestionMairieMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (! $user || (! $user->isAdmin() && ! $user->peutGererMairie())) {
            abort(403);
        }

        return $next($request);
    }
}
