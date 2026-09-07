<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Estimation établie par une mairie pour un commerçant de son marché.
 * Les montants sont calculés à partir des lignes (désignation, quantité,
 * prix unitaire HT) et du taux de TVA.
 */
class Devis extends Model
{
    protected $table = 'devis';

    public const STATUTS = [
        'brouillon' => 'Brouillon',
        'envoye'    => 'Envoyé',
        'accepte'   => 'Accepté',
        'refuse'    => 'Refusé',
    ];

    protected $fillable = [
        'mairie_id', 'commercant_id', 'reference', 'client_nom', 'client_adresse', 'client_email',
        'date_devis', 'validite_jours', 'lieu_execution', 'delai_execution',
        'conditions', 'modalites_paiement', 'lignes', 'taux_tva', 'statut',
    ];

    protected function casts(): array
    {
        return [
            'date_devis' => 'date',
            'lignes'     => 'array',
            'taux_tva'   => 'float',
        ];
    }

    public function mairie()
    {
        return $this->belongsTo(Mairie::class);
    }

    public function commercant()
    {
        return $this->belongsTo(Commercant::class);
    }

    public function totalHt(): float
    {
        return collect($this->lignes ?? [])
            ->sum(fn ($l) => ((float) ($l['quantite'] ?? 0)) * ((float) ($l['prix_unitaire'] ?? 0)));
    }

    public function montantTva(): float
    {
        return round($this->totalHt() * $this->taux_tva / 100, 2);
    }

    public function totalTtc(): float
    {
        return round($this->totalHt() + $this->montantTva(), 2);
    }

    /** Date limite de validité de l'offre. */
    public function valableJusquau()
    {
        return $this->date_devis->copy()->addDays($this->validite_jours);
    }

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    /** Référence annuelle : DEV-2026-0001 */
    public static function genererReference(): string
    {
        $annee = now()->format('Y');
        $rang  = static::where('reference', 'like', "DEV-{$annee}-%")->count() + 1;

        return sprintf('DEV-%s-%04d', $annee, $rang);
    }
}
