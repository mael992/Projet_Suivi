<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Demande d'un commerçant souhaitant rejoindre le marché d'une commune.
 */
class MarcheDemande extends Model
{
    public const STATUT_ATTENTE  = 'en_attente';
    public const STATUT_ACCEPTEE = 'acceptee';
    public const STATUT_REFUSEE  = 'refusee';

    public const STATUTS = [
        self::STATUT_ATTENTE  => 'En attente',
        self::STATUT_ACCEPTEE => 'Acceptée',
        self::STATUT_REFUSEE  => 'Refusée',
    ];

    protected $fillable = [
        'mairie_id', 'prenom', 'nom', 'societe', 'activite',
        'telephone_indicatif', 'telephone', 'email',
        'longueur_souhaitee', 'message', 'statut', 'reponse',
    ];

    protected function casts(): array
    {
        return ['longueur_souhaitee' => 'float'];
    }

    public function mairie()
    {
        return $this->belongsTo(Mairie::class);
    }

    public function getNomCompletAttribute(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }
}
