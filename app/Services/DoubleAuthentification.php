<?php

namespace App\Services;

use App\Mail\CodeVerification;
use App\Models\AppareilConfiance;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Double authentification (A2F) par code à 6 chiffres envoyé par e-mail.
 *
 *  - Connexion : code demandé à chaque connexion, sauf depuis un appareil de
 *    confiance (même navigateur, même adresse IP, moins de 30 jours).
 *  - Modification importante (mot de passe, e-mail, suppression du compte,
 *    e-mail ou mot de passe d'un autre compte) : code demandé à chaque fois,
 *    même depuis un appareil de confiance. Un code validé vaut 10 minutes.
 *  - « J'ai déjà un ticket » : code envoyé à l'e-mail du ticket.
 *
 * Le code attendu est gardé en session, sous forme d'empreinte seulement,
 * avec une durée de vie et un nombre d'essais limités.
 */
class DoubleAuthentification
{
    public const BUT_CONNEXION = 'connexion';
    public const BUT_ACTION    = 'action';
    public const BUT_TICKET    = 'ticket';

    public const MINUTES_CODE         = 10;
    public const ESSAIS_MAX           = 5;
    public const JOURS_CONFIANCE      = 30;
    public const MINUTES_CONFIRMATION = 10;

    public const COOKIE_APPAREIL = 'mgds_appareil';

    // ── Codes ────────────────────────────────────────────────────

    /**
     * Tire un nouveau code (l'ancien ne vaut plus rien) et l'envoie.
     * Renvoie false si l'e-mail n'a pas pu partir.
     */
    public static function envoyerCode(Request $request, string $but, string $email, array $donnees = []): bool
    {
        $code = (string) random_int(100000, 999999);

        $request->session()->put(self::cle($but), [
            'empreinte' => self::empreinte($code),
            'expire'    => now()->addMinutes(self::MINUTES_CODE)->timestamp,
            'essais'    => 0,
            'email'     => $email,
            'donnees'   => $donnees,
        ]);

        try {
            Mail::to($email)->send(new CodeVerification($code, $but));
        } catch (\Exception $e) {
            report($e);

            return false;
        }

        return true;
    }

    /** Renvoie un nouveau code à la même adresse, si un code est en attente. */
    public static function renvoyerCode(Request $request, string $but): bool
    {
        $attente = $request->session()->get(self::cle($but));
        if (! $attente) {
            return false;
        }

        return self::envoyerCode($request, $but, $attente['email'], $attente['donnees']);
    }

    /** Code en attente (encore valable) pour ce but, ou null. */
    public static function enAttente(Request $request, string $but): ?array
    {
        $attente = $request->session()->get(self::cle($but));

        if (! $attente || $attente['expire'] < now()->timestamp || $attente['essais'] >= self::ESSAIS_MAX) {
            return null;
        }

        return $attente;
    }

    /**
     * Vérifie le code saisi. Bon code : il est consommé et on renvoie les
     * données mises de côté. Mauvais code : un essai de moins ; au bout de
     * 5 erreurs, il faut redemander un code.
     */
    public static function verifier(Request $request, string $but, string $saisi): ?array
    {
        $attente = self::enAttente($request, $but);
        if (! $attente) {
            return null;
        }

        $saisi = preg_replace('/\D/', '', $saisi);

        if (! hash_equals($attente['empreinte'], self::empreinte($saisi))) {
            $attente['essais']++;
            $request->session()->put(self::cle($but), $attente);

            return null;
        }

        $request->session()->forget(self::cle($but));

        return $attente['donnees'];
    }

    public static function oublier(Request $request, string $but): void
    {
        $request->session()->forget(self::cle($but));
    }

    /** Adresse masquée pour l'affichage : « ma***@exemple.fr ». */
    public static function emailMasque(string $email): string
    {
        [$nom, $domaine] = array_pad(explode('@', $email, 2), 2, '');

        return Str::substr($nom, 0, 2) . str_repeat('*', max(3, Str::length($nom) - 2)) . '@' . $domaine;
    }

    // ── Modifications importantes ────────────────────────────────

    /** Un code a été validé il y a moins de 10 minutes dans cette session. */
    public static function confirmeRecemment(Request $request): bool
    {
        $confirme = (int) $request->session()->get('a2f_confirme_at', 0);

        return $confirme > now()->subMinutes(self::MINUTES_CONFIRMATION)->timestamp;
    }

    public static function marquerConfirme(Request $request): void
    {
        $request->session()->put('a2f_confirme_at', now()->timestamp);
    }

    /**
     * Envoie un code à l'utilisateur connecté et l'emmène sur la page de
     * saisie ; une fois le code validé, il revient sur $retour pour refaire
     * sa modification.
     */
    public static function exigerConfirmation(Request $request, string $retour): RedirectResponse
    {
        $user = $request->user();

        if (! $user->email) {
            return redirect($retour)->withErrors([
                'a2f' => __('Aucune adresse e-mail n\'est enregistrée sur votre compte : le code de vérification ne peut pas être envoyé. Contactez votre administrateur.'),
            ]);
        }

        if (! self::envoyerCode($request, self::BUT_ACTION, $user->email, ['retour' => $retour])) {
            return redirect($retour)->withErrors(['a2f' => __('Le code de vérification n\'a pas pu être envoyé. Merci de réessayer dans quelques instants.')]);
        }

        return redirect()->route('a2f.action');
    }

    // ── Appareils de confiance ───────────────────────────────────

    /** Même navigateur, même adresse IP, même compte, moins de 30 jours. */
    public static function appareilReconnu(Request $request, User $user): bool
    {
        $jeton = $request->cookie(self::COOKIE_APPAREIL);
        if (! is_string($jeton) || strlen($jeton) !== 64) {
            return false;
        }

        $appareil = AppareilConfiance::where('jeton_hash', hash('sha256', $jeton))->first();

        return $appareil !== null
            && $appareil->user_id === $user->id
            && $appareil->expire_at->isFuture()
            && $appareil->ip === $request->ip()
            && hash_equals($appareil->navigateur_hash, self::navigateur($request));
    }

    public static function faireConfiance(Request $request, User $user): void
    {
        // Un seul enregistrement par navigateur : l'ancien (autre IP…) est remplacé
        $ancien = $request->cookie(self::COOKIE_APPAREIL);
        if (is_string($ancien) && $ancien !== '') {
            AppareilConfiance::where('jeton_hash', hash('sha256', $ancien))->delete();
        }
        AppareilConfiance::where('expire_at', '<', now())->delete();

        $jeton = Str::random(64);

        AppareilConfiance::create([
            'user_id'         => $user->id,
            'jeton_hash'      => hash('sha256', $jeton),
            'ip'              => (string) $request->ip(),
            'navigateur_hash' => self::navigateur($request),
            'expire_at'       => now()->addDays(self::JOURS_CONFIANCE),
        ]);

        Cookie::queue(Cookie::make(
            self::COOKIE_APPAREIL, $jeton, self::JOURS_CONFIANCE * 24 * 60,
            null, null, null, true, false, 'lax',
        ));
    }

    /** Mot de passe ou e-mail changé : plus aucun appareil de confiance. */
    public static function oublierAppareils(User $user): void
    {
        AppareilConfiance::where('user_id', $user->id)->delete();
    }

    // ── Helpers ──────────────────────────────────────────────────

    private static function cle(string $but): string
    {
        return 'a2f.' . $but;
    }

    private static function empreinte(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    private static function navigateur(Request $request): string
    {
        return hash('sha256', (string) $request->userAgent());
    }
}
