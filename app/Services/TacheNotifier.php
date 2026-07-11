<?php

namespace App\Services;

use App\Mail\TacheAssignee;
use App\Mail\TacheCloturee;
use App\Mail\TacheCreee;
use App\Models\Tache;
use App\Models\User;
use App\Support\Referentiel;
use Illuminate\Support\Facades\Mail;

/**
 * Notifications e-mail liées aux tâches.
 *
 * Les observateurs de la mairie (gestionnaire des accès mairie, côté admin)
 * reçoivent une copie de TOUS les mails, y compris les passages en "fait".
 */
class TacheNotifier
{
    /** Création d'une tâche : responsables du service (+ employé si déjà assigné). */
    public static function notifierCreation(Tache $tache): void
    {
        $responsables = static::responsablesDuService($tache);

        foreach ($responsables as $responsable) {
            static::envoyer($tache, new TacheCreee($tache, $responsable, pourResponsable: true));
        }

        if ($tache->assigne && $tache->assigne->email) {
            static::envoyer($tache, new TacheCreee($tache, $tache->assigne, pourResponsable: false));
        }

        // Si personne n'a pu être notifié, les observateurs reçoivent quand même l'info
        if ($responsables->isEmpty() && ! ($tache->assigne && $tache->assigne->email)) {
            static::envoyerAuxObservateursSeuls($tache, new TacheCreee($tache, null, pourResponsable: true));
        }
    }

    /** Une tâche existante vient d'être affectée à un employé. */
    public static function notifierAssignation(Tache $tache): void
    {
        if ($tache->assigne && $tache->assigne->email) {
            static::envoyer($tache, new TacheAssignee($tache, $tache->assigne));
        }

        foreach (static::responsablesDuService($tache) as $responsable) {
            static::envoyer($tache, new TacheAssignee($tache, $responsable));
        }
    }

    /** La tâche est passée au statut "fait". */
    public static function notifierCloture(Tache $tache): void
    {
        $destinataires = static::responsablesDuService($tache);

        if ($tache->createur && $tache->createur->email) {
            $destinataires->push($tache->createur);
        }

        $destinataires = $destinataires->unique('id')->filter(fn ($u) => $u->email);

        foreach ($destinataires as $dest) {
            static::envoyer($tache, new TacheCloturee($tache, $dest));
        }

        if ($destinataires->isEmpty()) {
            static::envoyerAuxObservateursSeuls($tache, new TacheCloturee($tache, null));
        }
    }

    // ── Internes ─────────────────────────────────────────────────

    private static function responsablesDuService(Tache $tache)
    {
        return User::where('mairie_id', $tache->mairie_id)
            ->where('service', $tache->service)
            ->whereIn('grade', [Referentiel::GRADE_DIR_CABINET, Referentiel::GRADE_DGS])
            ->whereNotNull('email')
            ->get();
    }

    private static function envoyer(Tache $tache, $mailable): void
    {
        $observateurs = $tache->mairie?->emailsObservateurs() ?? [];

        try {
            $mail = Mail::to($mailable->destinataire->email);
            if (! empty($observateurs)) {
                $mail->bcc($observateurs);
            }
            $mail->send($mailable);
        } catch (\Exception $e) {
            // Ne jamais bloquer l'application si l'envoi échoue
            report($e);
        }
    }

    private static function envoyerAuxObservateursSeuls(Tache $tache, $mailable): void
    {
        $observateurs = $tache->mairie?->emailsObservateurs() ?? [];

        if (empty($observateurs)) {
            return;
        }

        try {
            Mail::to($observateurs)->send($mailable);
        } catch (\Exception $e) {
            report($e);
        }
    }
}
