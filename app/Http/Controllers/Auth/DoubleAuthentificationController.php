<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\DoubleAuthentification as A2F;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Pages de saisie du code de double authentification (A2F) :
 *  - connexion (après identifiant + mot de passe) ;
 *  - modification importante du compte (utilisateur connecté) ;
 *  - « J'ai déjà un ticket » (habitant, sans compte).
 */
class DoubleAuthentificationController extends Controller
{
    // ── Connexion ────────────────────────────────────────────────

    public function connexion(Request $request): View|RedirectResponse
    {
        $attente = A2F::enAttente($request, A2F::BUT_CONNEXION);
        if (! $attente) {
            return redirect()->route('login')->withErrors(['username' => __('Code expiré ou trop d\'essais : merci de vous reconnecter.')]);
        }

        return $this->page($attente, [
            'titre'      => __('Vérification de connexion'),
            'action'     => route('a2f.connexion.verifier'),
            'renvoi'     => route('a2f.connexion.renvoyer'),
            'annulation' => route('login'),
            'confiance'  => true,
        ]);
    }

    public function verifierConnexion(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|string|max:20']);

        $donnees = A2F::verifier($request, A2F::BUT_CONNEXION, $request->input('code'));
        $user    = $donnees ? User::find($donnees['user_id']) : null;

        if (! $user) {
            return $this->codeRefuse($request, A2F::BUT_CONNEXION, 'a2f.connexion', 'login');
        }

        Auth::login($user, (bool) $donnees['remember']);
        $request->session()->regenerate();

        // Mot de passe provisoire : le changement reste exigé pour cette session
        if ($donnees['provisoire']) {
            $request->session()->put('mdp_provisoire_utilise', true);
        }

        // Le code vient d'être saisi : pas de second code pour une modification immédiate
        A2F::marquerConfirme($request);

        if ($request->boolean('confiance')) {
            A2F::faireConfiance($request, $user);
        }

        ActivityLogger::auth('LOGIN', 'Connexion réussie (code A2F)', $user->username . ' (id:' . $user->id . ')');

        return redirect()->intended(route('apps'));
    }

    public function renvoyerConnexion(Request $request): RedirectResponse
    {
        return $this->renvoyer($request, A2F::BUT_CONNEXION, 'a2f.connexion', 'login');
    }

    // ── Modification importante ──────────────────────────────────

    public function action(Request $request): View|RedirectResponse
    {
        $attente = A2F::enAttente($request, A2F::BUT_ACTION);
        if (! $attente) {
            return redirect()->route('profile.edit')->withErrors(['a2f' => __('Code expiré ou trop d\'essais : merci de recommencer votre modification.')]);
        }

        return $this->page($attente, [
            'titre'      => __('Confirmer une modification importante'),
            'action'     => route('a2f.action.verifier'),
            'renvoi'     => route('a2f.action.renvoyer'),
            'annulation' => $attente['donnees']['retour'] ?? route('profile.edit'),
            'confiance'  => false,
        ]);
    }

    public function verifierAction(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|string|max:20']);

        $donnees = A2F::verifier($request, A2F::BUT_ACTION, $request->input('code'));
        if ($donnees === null) {
            return $this->codeRefuse($request, A2F::BUT_ACTION, 'a2f.action', 'profile.edit');
        }

        A2F::marquerConfirme($request);
        ActivityLogger::auth('A2F', 'Code A2F validé pour une modification importante', $request->user()->username);

        return redirect($donnees['retour'] ?? route('profile.edit'))
            ->with('a2f_ok', __('Code vérifié : vous pouvez maintenant valider votre modification (pendant :minutes minutes).', ['minutes' => A2F::MINUTES_CONFIRMATION]));
    }

    public function renvoyerAction(Request $request): RedirectResponse
    {
        return $this->renvoyer($request, A2F::BUT_ACTION, 'a2f.action', 'profile.edit');
    }

    // ── « J'ai déjà un ticket » ──────────────────────────────────

    public function ticket(Request $request): View|RedirectResponse
    {
        $attente = A2F::enAttente($request, A2F::BUT_TICKET);
        if (! $attente) {
            return redirect()->route('contact.mairie')->withErrors(['ticket' => __('Code expiré ou trop d\'essais : merci de saisir à nouveau votre numéro de ticket.')]);
        }

        return $this->page($attente, [
            'titre'      => __('Vérification de votre adresse e-mail'),
            'action'     => route('contact.ticket.code.verifier'),
            'renvoi'     => route('contact.ticket.code.renvoyer'),
            'annulation' => route('contact.mairie'),
            'confiance'  => false,
        ]);
    }

    public function verifierTicket(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|string|max:20']);

        $donnees = A2F::verifier($request, A2F::BUT_TICKET, $request->input('code'));
        $ticket  = $donnees ? Ticket::find($donnees['ticket_id']) : null;

        if (! $ticket) {
            return $this->codeRefuse($request, A2F::BUT_TICKET, 'contact.ticket.code', 'contact.mairie');
        }

        // Autorise la consultation et la réponse pour cette session
        session(['ticket_suivi_' . $ticket->id => true]);

        return redirect()->route('contact.ticket.voir', $ticket);
    }

    public function renvoyerTicket(Request $request): RedirectResponse
    {
        return $this->renvoyer($request, A2F::BUT_TICKET, 'contact.ticket.code', 'contact.mairie');
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function page(array $attente, array $options): View
    {
        return view('auth.a2f', $options + [
            'email'   => A2F::emailMasque($attente['email']),
            'minutes' => A2F::MINUTES_CODE,
        ]);
    }

    /** Mauvais code : on reste sur la page tant qu'il reste des essais. */
    private function codeRefuse(Request $request, string $but, string $page, string $depart): RedirectResponse
    {
        if (A2F::enAttente($request, $but)) {
            return redirect()->route($page)->withErrors(['code' => __('Code incorrect. Vérifiez le code reçu par e-mail.')]);
        }

        A2F::oublier($request, $but);

        return redirect()->route($depart)->withErrors([
            $but === A2F::BUT_TICKET ? 'ticket' : ($but === A2F::BUT_CONNEXION ? 'username' : 'a2f')
                => __('Code expiré ou trop d\'essais : merci de recommencer.'),
        ]);
    }

    private function renvoyer(Request $request, string $but, string $page, string $depart): RedirectResponse
    {
        if (! A2F::renvoyerCode($request, $but)) {
            return redirect()->route($depart);
        }

        return redirect()->route($page)->with('a2f_ok', __('Un nouveau code vous a été envoyé.'));
    }
}
