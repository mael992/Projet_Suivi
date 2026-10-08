<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    public const AUTEUR_DEMANDEUR = 'demandeur';
    public const AUTEUR_ASSISTANT = 'assistant';
    public const AUTEUR_ADMIN     = 'admin';

    protected $fillable = ['support_demande_id', 'auteur', 'corps'];

    public function demande()
    {
        return $this->belongsTo(SupportDemande::class, 'support_demande_id');
    }

    public function estDuDemandeur(): bool
    {
        return $this->auteur === self::AUTEUR_DEMANDEUR;
    }

    /**
     * Signature affichée. Côté équipe, toujours « Admin » : on ne sait
     * jamais quel admin a répondu (confidentialité).
     */
    public function signature(bool $vueAdmin = false): string
    {
        return match ($this->auteur) {
            self::AUTEUR_ADMIN     => __('Admin'),
            self::AUTEUR_ASSISTANT => __('Assistant'),
            default                => $vueAdmin ? $this->demande->libelleDemandeur() : __('Vous'),
        };
    }
}
