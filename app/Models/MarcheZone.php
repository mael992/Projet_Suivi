<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Zone de marché posée sur la vue aérienne de la ville
 * (place, rue, trottoir, parking…). Chaque zone porte la configuration
 * de son marché : type, disposition des exposants, écart, obstacles.
 */
class MarcheZone extends Model
{
    public const TYPES = [
        'place'    => 'Place',
        'rue'      => 'Rue',
        'trottoir' => 'Trottoir',
        'parking'  => 'Parking',
        'autre'    => 'Autre',
    ];

    public const TYPES_MARCHE = [
        'hebdomadaire' => 'Marché hebdomadaire',
        'nocturne'     => 'Marché nocturne',
        'brocante'     => 'Brocante / Vide-grenier',
        'noel'         => 'Marché de Noël',
        'foire'        => 'Foire / Fête foraine',
    ];

    protected $fillable = [
        'mairie_id',
        'nom',
        'type',
        'pos_x',
        'pos_y',
        'largeur_pct',
        'hauteur_pct',
        'rotation',
        'couleur',
        'marche_type',
        'longueur_m',
        'largeur_m',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'pos_x'       => 'float',
            'pos_y'       => 'float',
            'largeur_pct' => 'float',
            'hauteur_pct' => 'float',
            'rotation'    => 'float',
            'longueur_m'  => 'float',
            'largeur_m'   => 'float',
            'config'      => 'array',
        ];
    }

    public function mairie()
    {
        return $this->belongsTo(Mairie::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getMarcheTypeLabelAttribute(): string
    {
        return self::TYPES_MARCHE[$this->marche_type] ?? '—';
    }
}
