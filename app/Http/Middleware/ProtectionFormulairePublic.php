<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protection anti-robots des formulaires publics (sans service extérieur) :
 *  - limite d'envois par adresse IP (tous les modes) ;
 *  - mode « complet » : champ piège invisible qu'un humain laisse vide, et
 *    délai minimum entre l'affichage du formulaire et son envoi (un robot
 *    remplit et envoie en une fraction de seconde).
 *
 * Les deux champs cachés sont ajoutés par le composant <x-anti-robot />.
 */
class ProtectionFormulairePublic
{
    /** Nom du champ piège : doit rester vide. */
    public const CHAMP_PIEGE = 'champ_controle';

    /** Nom du champ portant l'heure d'affichage (chiffrée). */
    public const CHAMP_HEURE = 'formulaire_affiche_a';

    /** Délai minimum, en secondes, entre l'affichage et l'envoi. */
    public const DELAI_MIN = 3;

    /** Envois autorisés par minute et par IP, selon le mode. */
    private const LIMITES = [
        'complet' => 5,
        'limite'  => 10,
    ];

    public function handle(Request $request, Closure $next, string $mode = 'complet'): Response
    {
        $cle = 'formulaire-public:' . $request->route()?->getName() . ':' . $request->ip();

        if (RateLimiter::tooManyAttempts($cle, self::LIMITES[$mode] ?? self::LIMITES['complet'])) {
            return $this->refuser(__('Trop d\'envois en peu de temps. Merci de patienter une minute avant de réessayer.'));
        }
        RateLimiter::hit($cle, 60);

        if ($mode === 'complet') {
            // Un humain ne voit pas ce champ : s'il est rempli, c'est un robot
            if (filled($request->input(self::CHAMP_PIEGE))) {
                return $this->refuser(__('Votre envoi n\'a pas pu être vérifié. Merci de réessayer.'));
            }

            $affiche = $this->heureAffichage($request);
            if ($affiche === null) {
                return $this->refuser(__('Votre envoi n\'a pas pu être vérifié. Merci de réessayer.'));
            }
            if (now()->timestamp - $affiche < self::DELAI_MIN) {
                return $this->refuser(__('Merci de patienter quelques secondes avant d\'envoyer le formulaire.'));
            }
        }

        return $next($request);
    }

    /** Heure d'affichage du formulaire, ou null si absente ou falsifiée. */
    private function heureAffichage(Request $request): ?int
    {
        $valeur = $request->input(self::CHAMP_HEURE);
        if (! is_string($valeur) || $valeur === '') {
            return null;
        }

        try {
            $heure = decrypt($valeur);
        } catch (DecryptException) {
            return null;
        }

        return is_int($heure) ? $heure : null;
    }

    /** Retour au formulaire avec la saisie conservée (hors fichiers). */
    private function refuser(string $message): Response
    {
        return back()
            ->withErrors(['anti_robot' => $message])
            ->withInput(request()->except([self::CHAMP_PIEGE, self::CHAMP_HEURE]));
    }
}
