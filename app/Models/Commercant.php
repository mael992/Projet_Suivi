<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commercant extends Model
{
    protected $fillable = [
        'mairie_id',
        'nom',
        'prenom',
        'activite',
        'telephone_indicatif',
        'telephone',
        'email',
        'longueur_defaut',
    ];

    protected function casts(): array
    {
        return [
            'longueur_defaut' => 'float',
        ];
    }

    public function mairie()
    {
        return $this->belongsTo(Mairie::class);
    }

    /** Chaque emplacement sur un plan daté = une venue */
    public function emplacements()
    {
        return $this->hasMany(MarcheEmplacement::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->prenom ?? '') . ' ' . $this->nom);
    }
}
