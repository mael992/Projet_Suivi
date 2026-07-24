<?php

namespace App\Console\Commands;

use App\Mail\TacheEcheance;
use App\Models\Tache;
use App\Support\Referentiel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Rappel e-mail le jour de l'échéance (date butoir) d'une tâche non terminée,
 * envoyé au responsable et, s'il y a une substitution, au substitut.
 */
class RappelerTaches extends Command
{
    protected $signature   = 'mgds:rappeler-taches';
    protected $description = "Rappelle par e-mail les tâches à réaliser le jour de leur échéance";

    public function handle(): int
    {
        $taches = Tache::with(['assigne', 'substitut', 'mairie'])
            ->whereDate('date_butoir', now()->toDateString())
            ->where('statut', '!=', Referentiel::STATUT_FAIT)
            ->get();

        foreach ($taches as $tache) {
            // Le responsable, et le substitut si la tâche lui a été confiée
            $destinataires = collect([$tache->assigne]);
            if ($tache->prise_en_charge === 'substitution' && $tache->substitut) {
                $destinataires->push($tache->substitut);
            }

            foreach ($destinataires->filter(fn ($u) => $u && $u->email)->unique('id') as $destinataire) {
                try {
                    Mail::to($destinataire->email)->send(new TacheEcheance($tache, $destinataire));
                    $this->info("Rappel échéance {$tache->reference} → {$destinataire->username}");
                } catch (\Exception $e) {
                    report($e);
                }
            }
        }

        return self::SUCCESS;
    }
}
