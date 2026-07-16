<?php

namespace App\Console\Commands;

use App\Mail\AbonnementDernierJour;
use App\Mail\AbonnementExpire;
use App\Models\Mairie;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Emails automatiques d'abonnement, lancés chaque jour à 00h01 :
 * - le jour J (date de fin incluse)  : « dernier jour, l'accès se termine ce soir »
 * - le lendemain (J+1)               : « votre mairie est désabonnée »
 */
class NotifierAbonnements extends Command
{
    protected $signature   = 'mgds:notifier-abonnements';
    protected $description = "Envoie les emails de fin d'abonnement aux mairies (dernier jour + expiration)";

    public function handle(): int
    {
        $aujourdhui = now()->toDateString();
        $hier       = now()->subDay()->toDateString();

        // Dernier jour d'abonnement (date de fin incluse)
        foreach (Mairie::whereDate('date_fin_abonnement', $aujourdhui)->get() as $mairie) {
            $this->envoyer($mairie, new AbonnementDernierJour($mairie), 'dernier jour');
        }

        // Abonnement terminé depuis hier
        foreach (Mairie::whereDate('date_fin_abonnement', $hier)->get() as $mairie) {
            $this->envoyer($mairie, new AbonnementExpire($mairie), 'expiré');
        }

        return self::SUCCESS;
    }

    private function envoyer(Mairie $mairie, $mailable, string $type): void
    {
        $destinataires = $mairie->emailsDirection();

        if (empty($destinataires)) {
            $this->warn("{$mairie->nom} : aucun destinataire ({$type}).");

            return;
        }

        try {
            Mail::to($destinataires)->send($mailable);
            ActivityLogger::log('ABONNEMENT', 'MAIL', "Email abonnement « {$type} » envoyé ({$mairie->nom}, " . count($destinataires) . ' destinataire(s))');
            $this->info("{$mairie->nom} : email « {$type} » envoyé à " . count($destinataires) . ' destinataire(s).');
        } catch (\Exception $e) {
            report($e);
            $this->error("{$mairie->nom} : échec d'envoi ({$type}).");
        }
    }
}
