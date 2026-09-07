<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Ligne de planning : la semaine d'un agent.
 *
 * Une journée absente l'emporte sur tout le reste : ses heures ne comptent
 * pas, même si des créneaux avaient été saisis avant que l'absence ne soit
 * déclarée. C'est ce qui fait qu'une absence de dernière minute se voit
 * immédiatement dans le planning, sans le retoucher.
 */
class PlanningLigne extends Model
{
    /** Nombre de plages horaires proposées par journée. */
    public const CRENEAUX_PAR_JOUR = 2;

    protected $fillable = [
        'planning_id',
        'user_id',
        'jours',
        'duree_contrat',
        'signe_at',
    ];

    protected function casts(): array
    {
        return [
            'jours'         => 'array',
            'duree_contrat' => 'integer',
            'signe_at'      => 'datetime',
        ];
    }

    public function planning()
    {
        return $this->belongsTo(Planning::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── Journées ─────────────────────────────────────────────────

    /** Réglages saisis pour un jour (1 = lundi … 7 = dimanche). */
    public function journee(int $jour): array
    {
        $donnees = $this->jours[(string) $jour] ?? [];

        return [
            'repos'    => (bool) ($donnees['repos'] ?? false),
            'creneaux' => $donnees['creneaux'] ?? [],
            // Retard constaté ce jour-là, en minutes (0 = à l'heure)
            'retard'   => (int) ($donnees['retard'] ?? 0),
        ];
    }

    /** Absence couvrant cette date, relue à chaque affichage. */
    public function absencePour(CarbonInterface $date): ?Absence
    {
        return $this->user?->absences
            ->first(fn (Absence $a) => $a->date_debut->lte($date) && $a->date_fin->gte($date));
    }

    /** Minutes travaillées ce jour-là : zéro si repos ou absence. */
    public function minutesJour(int $jour, ?CarbonInterface $date = null): int
    {
        if ($date && $this->absencePour($date)) {
            return 0;
        }

        $journee = $this->journee($jour);

        if ($journee['repos']) {
            return 0;
        }

        $total = 0;
        foreach ($journee['creneaux'] as $creneau) {
            $total += self::minutesEntre($creneau[0] ?? null, $creneau[1] ?? null);
        }

        // Le retard se déduit des heures effectivement faites
        return max(0, $total - $journee['retard']);
    }

    /** Total de la semaine, absences et repos déduits. */
    public function totalMinutes(array $dates = []): int
    {
        $total = 0;
        foreach (range(1, 7) as $jour) {
            $total += $this->minutesJour($jour, $dates[$jour] ?? null);
        }

        return $total;
    }

    /** Écart au contrat : positif = heures en plus, négatif = heures en moins. */
    public function variationMinutes(array $dates = []): ?int
    {
        return $this->duree_contrat === null
            ? null
            : $this->totalMinutes($dates) - $this->duree_contrat;
    }

    /** Retard cumulé sur la semaine, en minutes. */
    public function retardMinutes(array $dates = []): int
    {
        $total = 0;
        foreach (range(1, 7) as $jour) {
            // Un jour d'absence ou de repos ne compte pas de retard
            if (($dates[$jour] ?? null) && $this->absencePour($dates[$jour])) {
                continue;
            }
            if ($this->journee($jour)['repos']) {
                continue;
            }
            $total += $this->journee($jour)['retard'];
        }

        return $total;
    }

    public function estSigne(): bool
    {
        return $this->signe_at !== null;
    }

    /**
     * Durée due telle qu'on la saisit : en heures, en chiffres seulement
     * (35 ou 35.5). Le « HH:MM » se tapait mal et prêtait à confusion avec
     * les créneaux horaires.
     */
    public function dureeContratSaisie(): string
    {
        if ($this->duree_contrat === null) {
            return '';
        }

        $heures = $this->duree_contrat / 60;

        return rtrim(rtrim(number_format($heures, 2, '.', ''), '0'), '.');
    }

    /** Convertit une saisie en heures (35, 35.5…) en minutes. */
    public static function heuresEnMinutes(mixed $heures): ?int
    {
        return $heures === null || $heures === ''
            ? null
            : (int) round(((float) $heures) * 60);
    }

    // ── Formatage ────────────────────────────────────────────────

    /** 450 → « 7h30 », -90 → « -1h30 », 0 → « 0h00 ». */
    public static function formatMinutes(?int $minutes): string
    {
        if ($minutes === null) {
            return '—';
        }

        $signe   = $minutes < 0 ? '-' : '';
        $minutes = abs($minutes);

        return $signe . intdiv($minutes, 60) . 'h' . str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT);
    }

    /** Minutes entre deux « HH:MM ». Une plage incomplète ou à l'envers vaut 0. */
    public static function minutesEntre(?string $debut, ?string $fin): int
    {
        if (! $debut || ! $fin) {
            return 0;
        }

        [$hDebut, $mDebut] = array_pad(array_map('intval', explode(':', $debut)), 2, 0);
        [$hFin, $mFin]     = array_pad(array_map('intval', explode(':', $fin)), 2, 0);

        return max(0, ($hFin * 60 + $mFin) - ($hDebut * 60 + $mDebut));
    }
}
