<?php

namespace App\Console\Commands;

use App\Mail\RappelCalendrier;
use App\Models\Rappel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Envoie les rappels du calendrier Pense-bête (email le jour J).
 */
class EnvoyerRappels extends Command
{
    protected $signature   = 'mgds:envoyer-rappels';
    protected $description = 'Envoie les emails de rappel du calendrier Pense-bête (jour J)';

    public function handle(): int
    {
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

        return self::SUCCESS;
    }
}
