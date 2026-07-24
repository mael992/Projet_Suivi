<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mairie extends Model
{
    protected $fillable = [
        'nom',
        'code_postal',
        'afficher_contact',
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
            'afficher_contact'    => 'boolean',
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

    /** Jours restants avant la fin d'abonnement (négatif si dépassé, null si non défini) */
    public function joursRestantsAbonnement(): ?int
    {
        if ($this->date_fin_abonnement === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->date_fin_abonnement->copy()->startOfDay(), false);
    }

    /**
     * Badge d'abonnement pour l'admin :
     *  - expiré                → « Expiré »
     *  - 7 jours ou moins      → « Bientôt »
     *  - 8 jours ou plus       → « Accès »
     */
    public function badgeAbonnement(): ?array
    {
        $jours = $this->joursRestantsAbonnement();
        if ($jours === null) {
            return null;
        }

        if ($this->abonnementExpire()) {
            return ['label' => 'Expiré', 'couleur' => 'danger'];
        }

        return $jours <= 7
            ? ['label' => 'Bientôt', 'couleur' => 'warning text-dark']
            : ['label' => 'Accès', 'couleur' => 'success'];
    }

    /** Adresses e-mail qui reçoivent une copie de toutes les notifications */
    public function emailsObservateurs(): array
    {
        return $this->observateurs()->pluck('email')->all();
    }

    /** Utilisateurs de la mairie qui reçoivent les messages du service donné. */
    public function destinatairesCommunication(?int $service)
    {
        return $this->users()
            ->where('role', 'user')
            ->whereNotNull('email')
            ->get()
            ->filter(fn ($u) => $u->recoitCommunication($service))
            ->values();
    }

    /** Services (clés) proposés sur « Contacter votre Mairie » : au moins un destinataire. */
    public function servicesContactables(): array
    {
        $users    = $this->users()->where('role', 'user')->get();
        $services = [];

        foreach (array_keys(\App\Support\Referentiel::SERVICES) as $s) {
            foreach ($users as $u) {
                if ($u->recoitCommunication($s)) {
                    $services[] = $s;
                    break;
                }
            }
        }

        return $services;
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
