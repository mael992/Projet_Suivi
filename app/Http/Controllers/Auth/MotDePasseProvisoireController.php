<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\MotDePasseProvisoire;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Bouton « Recevoir un mot de passe provisoire » de la page Mot de passe
 * oublié. Le mot de passe habituel n'est jamais modifié ici : le provisoire
 * s'y ajoute le temps de sa validité, puis disparaît de lui-même.
 */
class MotDePasseProvisoireController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower($request->string('email')->toString())])->first();

        // La mairie sans abonnement valide ne peut de toute façon pas se connecter
        if ($user && ! ($user->mairie && $user->mairie->abonnementExpire())) {
            $clair = $user->genererPasswordProvisoire();

            try {
                Mail::to($user->email)->send(new MotDePasseProvisoire($user, $clair));
                ActivityLogger::auth(
                    'TEMP_PASSWORD_SENT',
                    'Mot de passe provisoire envoyé par e-mail',
                    $user->username . ' (id:' . $user->id . ')'
                );
            } catch (\Exception $e) {
                report($e);
            }
        }

        // Réponse identique dans tous les cas : l'écran public ne doit pas
        // révéler quelles adresses correspondent à un compte.
        return back()->with('status', __('messages.temp_password_sent'));
    }
}
