<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mairie extends Model
{
    protected $fillable = [
        'nom',
        'email',
        'telephone_indicatif',
        'telephone',
        'date_fin_abonnement',
    ];

    protected function casts(): array
    {
        return [
            'date_fin_abonnement' => 'date',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function taches()
    {
        return $this->hasMany(Tache::class);
    }

    public function observateurs()
    {
        return $this->hasMany(MairieObservateur::class);
    }

    public function standards()
    {
        return $this->hasMany(Standard::class);
    }

    /** Abonnement expiré → connexion refusée pour les utilisateurs de cette mairie */
    public function abonnementExpire(): bool
    {
        return $this->date_fin_abonnement !== null
            && $this->date_fin_abonnement->isPast();
    }

    /** Adresses e-mail qui reçoivent une copie de toutes les notifications */
    public function emailsObservateurs(): array
    {
        return $this->observateurs()->pluck('email')->all();
    }
}
