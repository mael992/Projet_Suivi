<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tickets:cleanup')->daily();

// ── Abonnements des mairies ─────────────────────────────────────
// Chaque jour à 00h01 : email « dernier jour » (jour J) + « désabonné » (J+1)
Schedule::command('mgds:notifier-abonnements')->dailyAt('00:01');

// ── Pense-bête : rappels du calendrier (email le jour J) ────────
Schedule::command('mgds:envoyer-rappels')->dailyAt('00:05');

// ── Tâches : rappel le jour de l'échéance (tâches non terminées) ──
Schedule::command('mgds:rappeler-taches')->dailyAt('07:00');

// ── Logs d'activité ─────────────────────────────────────────────
// Sauvegarde toutes les 48h (tous les 2 jours à 02:00)
Schedule::command('logs:backup')->cron('0 2 */2 * *');
// Nettoyage CNIL/RGPD : suppression des logs > 6 mois (1er du mois à 03:00)
Schedule::command('logs:cleanup')->monthlyOn(1, '03:00');
