<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarcheAxe extends Model
{
    protected $table = 'marche_axes';

    protected $fillable = [
        'marche_plan_id',
        'nom',
        'longueur',
    ];

    protected function casts(): array
    {
        return [
            'longueur' => 'float',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(MarchePlan::class, 'marche_plan_id');
    }

    public function emplacements()
    {
        return $this->hasMany(MarcheEmplacement::class, 'marche_axe_id')->orderBy('position');
    }

    /** Mètres encore libres sur l'axe (peut être négatif si dépassement) */
    public function longueurRestante(): float
    {
        return round($this->longueur - (float) $this->emplacements->sum('longueur'), 2);
    }

    /** Fin du dernier stand (position de départ pour le prochain) */
    public function finDernierStand(): float
    {
        $dernier = $this->emplacements->sortBy('position')->last();

        return $dernier ? (float) $dernier->position + (float) $dernier->longueur : 0.0;
    }
}
