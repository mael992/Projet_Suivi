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
        'vue_aerienne',
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

    public function zonesMarche()
    {
        return $this->hasMany(MarcheZone::class);
    }

    /** Image de la vue aérienne (sinon plan de démonstration neutre) */
    public function vueAerienneUrl(): string
    {
        return $this->vue_aerienne
            ? asset('storage/' . $this->vue_aerienne)
            : asset('images/fond-plan-exemple.png');
    }

    /**
     * Abonnement expiré → connexion refusée pour les utilisateurs de cette mairie.
     * La date de fin est INCLUSE : le 14 juillet, un abonnement finissant
     * le 14 juillet fonctionne encore toute la journée.
     */
    public function abonnementExpire(): bool
    {
        return $this->date_fin_abonnement !== null
            && $this->date_fin_abonnement->copy()->endOfDay()->isPast();
    }

    /** Adresses e-mail qui reçoivent une copie de toutes les notifications */
    public function emailsObservateurs(): array
    {
        return $this->observateurs()->pluck('email')->all();
    }

    /**
     * Destinataires des emails d'abonnement : les personnes qui dirigent
     * la mairie (Maire, Directeur de Cabinet, DGS) + les observateurs
     * + l'adresse de la mairie elle-même.
     */
    public function emailsDirection(): array
    {
        $dirigeants = $this->users()
            ->whereIn('grade', [
                \App\Support\Referentiel::GRADE_MAIRE,
                \App\Support\Referentiel::GRADE_DIR_CABINET,
                \App\Support\Referentiel::GRADE_DGS,
            ])
            ->whereNotNull('email')
            ->pluck('email')
            ->all();

        return array_values(array_unique(array_filter(array_merge(
            $dirigeants,
            $this->emailsObservateurs(),
            [$this->email],
        ))));
    }
}
