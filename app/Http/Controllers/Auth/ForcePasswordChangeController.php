<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\DoubleAuthentification as A2F;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ForcePasswordChangeController extends Controller
{
    public function show()
    {
        return view('auth.force-password-change');
    }

    public function update(Request $request)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Double authentification : déjà fait si le code vient d'être saisi à
        // la connexion ; redemandé depuis un appareil de confiance
        if (! A2F::confirmeRecemment($request)) {
            return A2F::exigerConfirmation($request, route('password.force-change'));
        }

        $user = auth()->user();

        $user->password                   = Hash::make($request->password);
        $user->temp_password              = null;
        $user->temp_password_expires_at   = null;
        $user->must_change_password       = false;
        $user->save();
        A2F::oublierAppareils($user);

        // Le provisoire libre-service a joué son rôle
        $user->consommerPasswordProvisoire();
        $request->session()->forget('mdp_provisoire_utilise');

        ActivityLogger::auth('PASSWORD_CHANGED', 'Mot de passe provisoire remplacé avec succès');

        return redirect()->intended(
            $user->isAdmin() ? route('dashboard') : route('home')
        )->with('success', __('messages.password_changed_success'));
    }
}
