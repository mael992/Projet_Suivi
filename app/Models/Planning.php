<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Feuille d'heures d'une semaine (numéro ISO) pour une mairie.
 */
class Planning extends Model
{
    protected $fillable = [
        'mairie_id',
        'annee',
        'semaine',
        'cree_par',
    ];

    protected function casts(): array
    {
        return [
            'annee'   => 'integer',
            'semaine' => 'integer',
        ];
    }

    public function mairie()
    {
        return $this->belongsTo(Mairie::class);
    }

    public function lignes()
    {
        return $this->hasMany(PlanningLigne::class);
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'cree_par');
    }

    // ── Dates de la semaine ──────────────────────────────────────

    public function lundi(): CarbonImmutable
    {
        return CarbonImmutable::now()->setISODate($this->annee, $this->semaine)->startOfDay();
    }

    public function dimanche(): CarbonImmutable
    {
        return $this->lundi()->addDays(6);
    }

    /** Les sept dates de la semaine, indexées 1 (lundi) → 7 (dimanche). */
    public function dates(): array
    {
        $lundi = $this->lundi();

        return collect(range(1, 7))
            ->mapWithKeys(fn (int $jour) => [$jour => $lundi->addDays($jour - 1)])
            ->all();
    }

    public function libelle(): string
    {
        return 'Semaine ' . $this->semaine . ' — ' . $this->annee;
    }

    public function periodeLabel(): string
    {
        return 'du ' . $this->lundi()->format('d/m/Y') . ' au ' . $this->dimanche()->format('d/m/Y');
    }

    /** Semaine ISO en cours, pour proposer un défaut à la création. */
    public static function semaineCourante(): array
    {
        $auj = CarbonImmutable::now();

        return ['annee' => (int) $auj->isoWeekYear, 'semaine' => (int) $auj->isoWeek];
    }
}
