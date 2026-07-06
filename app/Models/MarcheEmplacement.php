<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarcheEmplacement extends Model
{
    protected $table = 'marche_emplacements';

    protected $fillable = [
        'marche_axe_id',
        'commercant_id',
        'position',
        'longueur',
        'montant',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'float',
            'longueur' => 'float',
            'montant'  => 'float',
        ];
    }

    public function axe()
    {
        return $this->belongsTo(MarcheAxe::class, 'marche_axe_id');
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
