<?php

namespace App\Models;

use App\Support\Referentiel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Absence d'un agent sur une période. Pendant celle-ci, son binôme reprend
 * ses tâches (voir User::idsRemplaces()).
 */
class Absence extends Model
{
    protected $fillable = [
        'mairie_id',
        'user_id',
        'motif',
        'date_debut',
        'date_fin',
        'justificatif',
        'cree_par',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin'   => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mairie()
    {
        return $this->belongsTo(Mairie::class);
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'cree_par');
    }

    // ── Périodes ─────────────────────────────────────────────────

    /** Absences couvrant aujourd'hui (bornes incluses). */
    public function scopeEnCours(Builder $query): Builder
    {
        $auj = now()->startOfDay()->toDateString();

        return $query->whereDate('date_debut', '<=', $auj)->whereDate('date_fin', '>=', $auj);
    }

    /** Absences qui commencent plus tard. */
    public function scopeAVenir(Builder $query): Builder
    {
        return $query->whereDate('date_debut', '>', now()->startOfDay()->toDateString());
    }

    /** Absences entièrement passées : l'onglet « Historique ». */
    public function scopeTerminees(Builder $query): Builder
    {
        return $query->whereDate('date_fin', '<', now()->startOfDay()->toDateString());
    }

    public function estEnCours(): bool
    {
        $auj = now()->startOfDay();

        return $this->date_debut->lte($auj) && $this->date_fin->gte($auj);
    }

    public function motifLabel(): string
    {
        return Referentiel::motifAbsenceLabel($this->motif);
    }

    /** « du 12/07/2026 au 19/07/2026 », ou une seule date si la période dure un jour. */
    public function periodeLabel(): string
    {
        return $this->date_debut->equalTo($this->date_fin)
            ? $this->date_debut->format('d/m/Y')
            : 'du ' . $this->date_debut->format('d/m/Y') . ' au ' . $this->date_fin->format('d/m/Y');
    }
}
