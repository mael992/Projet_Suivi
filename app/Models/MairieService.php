<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Service (équipe) propre à une mairie : renomme, désactive ou ajoute
 * un service par rapport au référentiel par défaut.
 */
class MairieService extends Model
{
    protected $fillable = ['mairie_id', 'numero', 'nom', 'actif'];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'actif'  => 'boolean',
        ];
    }

    public function mairie()
    {
        return $this->belongsTo(Mairie::class);
    }
}
