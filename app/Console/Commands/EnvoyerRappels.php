<?php

namespace App\Console\Commands;

use App\Mail\NoteRappel;
use App\Mail\RappelCalendrier;
use App\Models\Note;
use App\Models\Rappel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Envoie les rappels du Pense-bête (email le jour J) :
 * rappels du calendrier + notes avec rappel programmé.
 */
class EnvoyerRappels extends Command
{
    protected $signature   = 'mgds:envoyer-rappels';
    protected $description = 'Envoie les emails de rappel du Pense-bête (calendrier + notes, jour J)';

    public function handle(): int
    {
        // Rappels du calendrier
        $rappels = Rappel::with('utilisateur')
            ->whereDate('date_rappel', now()->toDateString())
            ->where('envoye', false)
            ->get();

        foreach ($rappels as $rappel) {
            if (! $rappel->utilisateur?->email) {
                continue;
            }

            try {
                Mail::to($rappel->utilisateur->email)->send(new RappelCalendrier($rappel));
                $rappel->update(['envoye' => true]);
                $this->info("Rappel #{$rappel->id} envoyé à {$rappel->utilisateur->username}.");
            } catch (\Exception $e) {
                report($e);
                $this->error("Rappel #{$rappel->id} : échec d'envoi.");
            }
        }

        // Notes avec rappel programmé
        $notes = Note::with('utilisateur')
            ->where('notifier', true)
            ->whereDate('date_notification', now()->toDateString())
            ->where('notifiee', false)
            ->get();

        foreach ($notes as $note) {
            if (! $note->utilisateur?->email) {
                continue;
            }

            try {
                Mail::to($note->utilisateur->email)->send(new NoteRappel($note));
                $note->update(['notifiee' => true]);
                $this->info("Note #{$note->id} rappelée à {$note->utilisateur->username}.");
            } catch (\Exception $e) {
                report($e);
                $this->error("Note #{$note->id} : échec d'envoi.");
            }
        }

        return self::SUCCESS;
    }
}
