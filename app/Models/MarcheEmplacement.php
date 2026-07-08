<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarcheEmplacement extends Model
{
    protected $table = 'marche_emplacements';

    protected $fillable = [
        'marche_axe_id',
        'marche_plan_id',
        'commercant_id',
        'position',
        'longueur',
        'montant',
        'label',
        'pos_x',
        'pos_y',
        'largeur_pct',
        'hauteur_pct',
        'couleur',
        'electricite',
    ];

    protected function casts(): array
    {
        return [
            'position'    => 'float',
            'longueur'    => 'float',
            'montant'     => 'float',
            'pos_x'       => 'float',
            'pos_y'       => 'float',
            'largeur_pct' => 'float',
            'hauteur_pct' => 'float',
            'electricite' => 'boolean',
        ];
    }

    public function axe()
    {
        return $this->belongsTo(MarcheAxe::class, 'marche_axe_id');
    }

    /** Emplacement posé directement sur le fond de plan (2D) */
    public function plan()
    {
        return $this->belongsTo(MarchePlan::class, 'marche_plan_id');
    }

    /** Plan de rattachement, que l'emplacement soit 2D ou sur un axe */
    public function planParent(): ?MarchePlan
    {
        return $this->plan ?? $this->axe?->plan;
    }

    public function commercant()
    {
        return $this->belongsTo(Commercant::class);
    }

    public function getFinAttribute(): float
    {
        return round($this->position + $this->longueur, 2);
    }
}
