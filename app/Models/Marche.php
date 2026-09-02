<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Marché daté de la commune (« option 2 » de navigation) : il regroupe les
 * endroits où il se tient — rue, place, trottoir… — chacun menant au plan.
 */
class Marche extends Model
{
    protected $table = 'marches';

    protected $fillable = [
        'mairie_id',
        'nom',
        'date_deroulement',
    ];

    protected function casts(): array
    {
        return [
            'date_deroulement' => 'date',
        ];
    }

    public function mairie()
    {
        return $this->belongsTo(Mairie::class);
    }

    /** Endroits du marché : ce sont les zones existantes, rattachées ici. */
    public function endroits()
    {
        return $this->hasMany(MarcheZone::class);
    }

    public function dateLabel(): string
    {
        return $this->date_deroulement?->format('d/m/Y') ?? '—';
    }

    /** Un marché dont la date est passée n'est plus à préparer. */
    public function estPasse(): bool
    {
        return $this->date_deroulement !== null
            && $this->date_deroulement->lt(now()->startOfDay());
    }
}
