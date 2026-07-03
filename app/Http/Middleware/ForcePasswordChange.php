<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->must_change_password) {

            // Mot de passe provisoire expiré → déconnexion forcée
            if ($user->tempPasswordExpired() && !$request->routeIs('logout')) {
                ActivityLogger::auth('TEMP_PASSWORD_EXPIRED', "Mot de passe provisoire expiré — déconnexion forcée", $user->username . ' (id:' . $user->id . ')');
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->withErrors(['email' => __('messages.temp_password_expired')]);
            }

            // Pas encore expiré → rediriger vers la page de changement
            if (
                !$request->routeIs('password.force-change') &&
                !$request->routeIs('password.force-change.update') &&
                !$request->routeIs('logout')
            ) {
                return redirect()->route('password.force-change');
            }
        }

        return $next($request);
    }
}
