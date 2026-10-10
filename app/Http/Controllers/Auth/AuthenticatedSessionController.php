<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ActivityLogger;
use App\Services\DoubleAuthentification as A2F;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * Double authentification : après le mot de passe, un code est envoyé par
     * e-mail, sauf depuis un appareil de confiance (même navigateur, même
     * adresse IP, moins de 30 jours). La session n'est ouverte qu'une fois
     * le code saisi.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();

        if (A2F::appareilReconnu($request, $user)) {
            $request->session()->regenerate();

            ActivityLogger::auth('LOGIN', 'Connexion réussie (appareil de confiance)', $user->username . ' (id:' . $user->id . ')');

            return redirect()->intended(route('apps'));
        }

        // Mot de passe correct, mais pas encore de session : on attend le code
        $provisoire = (bool) $request->session()->pull('mdp_provisoire_utilise', false);
        Auth::guard('web')->logout();
        $request->session()->regenerate();

        if (! $user->email) {
            throw ValidationException::withMessages([
                'username' => __('Aucune adresse e-mail n\'est enregistrée sur votre compte : le code de vérification ne peut pas être envoyé. Contactez votre administrateur.'),
            ]);
        }

        $envoye = A2F::envoyerCode($request, A2F::BUT_CONNEXION, $user->email, [
            'user_id'    => $user->id,
            'remember'   => $request->boolean('remember'),
            'provisoire' => $provisoire,
        ]);

        if (! $envoye) {
            A2F::oublier($request, A2F::BUT_CONNEXION);

            throw ValidationException::withMessages([
                'username' => __('Le code de vérification n\'a pas pu être envoyé. Merci de réessayer dans quelques instants.'),
            ]);
        }

        ActivityLogger::auth('A2F', 'Mot de passe correct, code de connexion envoyé', $user->username . ' (id:' . $user->id . ')');

        return redirect()->route('a2f.connexion');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $username = auth()->user()?->username ?? '?';
        ActivityLogger::auth('LOGOUT', 'Déconnexion', $username);

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
