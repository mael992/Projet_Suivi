<?php

namespace App\Models;

use App\Support\Referentiel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'prenom',
        'nom',
        'username',
        'email',
        'password',
        'temp_password',
        'temp_password_expires_at',
        'must_change_password',
        'role',
        'mairie_id',
        'service',
        'grade',
        'droit',
        'communication',
        'voit_tous_messages',
        'cgu_acceptees_at',
        'binome_id',
        'absent',
        'absent_du',
        'absent_au',
        'absence_motif',
        'fonction',
        'reference',
        'telephone_indicatif',
        'telephone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'                 => 'hashed',
            'temp_password_expires_at' => 'datetime',
            'must_change_password'     => 'boolean',
            'service'                  => 'integer',
            'grade'                    => 'integer',
            'communication'            => 'array',
            'voit_tous_messages'       => 'boolean',
            'cgu_acceptees_at'         => 'datetime',
            'absent'                   => 'boolean',
            'absent_du'                => 'date',
            'absent_au'                => 'date',
        ];
    }

    // ── Relations ────────────────────────────────────────────────

    public function mairie()
    {
        return $this->belongsTo(Mairie::class);
    }

    public function taches()
    {
        return $this->hasMany(Tache::class);
    }

    // ── Rôles & permissions ──────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Droit d'application le plus fort de l'utilisateur :
     *  - null           → droit par défaut du grade
     *  - 'aucun'        → aucun droit (explicitement retiré)
     *  - clé de droit   → ce droit et tous les plus faibles
     */
    public function droitActuel(): string
    {
        if ($this->droit === null) {
            return Referentiel::droitDefaut($this->grade);
        }

        return $this->droit === Referentiel::DROIT_AUCUN ? '' : $this->droit;
    }

    /**
     * Système hiérarchique : posséder un droit donne tous les droits
     * plus faibles (situés à sa droite dans Referentiel::DROITS).
     */
    public function aDroit(string $droit): bool
    {
        return $this->isAdmin()
            || Referentiel::rangDroit($this->droitActuel()) <= Referentiel::rangDroit($droit);
    }

    /** Peuvent créer / modifier / supprimer des tâches */
    public function peutGererTaches(): bool
    {
        return $this->aDroit('taches_gestion');
    }

    /** Accès à la Gestion de la Mairie (utilisateurs, avancement) */
    public function peutGererMairie(): bool
    {
        return ! $this->isAdmin() && $this->aDroit('gestion_utilisateurs');
    }

    /**
     * Catégories de messages externes que l'utilisateur reçoit. L'habitant ne
     * choisit plus de service sur « Contacter votre Mairie » : toutes les
     * demandes arrivent sans service, d'où l'unique catégorie « inconnu »
     * pilotée par la case « Réceptionner les messages extérieurs ».
     */
    public function categoriesCommunication(): array
    {
        return $this->communication ?? [];
    }

    /** Reçoit les messages extérieurs arrivant sur « Contacter votre Mairie ». */
    public function receptionneMessagesExternes(): bool
    {
        return in_array('inconnu', $this->categoriesCommunication(), true);
    }

    /**
     * Visibilité en lecture sur TOUS les messages de la mairie, sans être
     * destinataire. La direction (Maire, Directeur de Cabinet, DGS) en
     * dispose d'office : c'est un statut, pas une case à cocher.
     */
    public function voitTousLesMessages(): bool
    {
        return $this->estDirection() || (bool) $this->voit_tous_messages;
    }

    /** L'utilisateur reçoit-il les messages du service donné (null = « Je ne sais pas ») ? */
    public function recoitCommunication(?int $service): bool
    {
        // Le service 0 (Maire) est une valeur valide : comparaison stricte à null
        $categorie = $service !== null ? (string) $service : 'inconnu';

        return in_array($categorie, $this->categoriesCommunication(), true);
    }

    /** Binôme : personne qui reprend le travail pendant une absence. */
    public function binome()
    {
        return $this->belongsTo(User::class, 'binome_id');
    }

    /** Personnes dont cet utilisateur est le binôme. */
    public function remplaces()
    {
        return $this->hasMany(User::class, 'binome_id');
    }

    /** Absence en cours aujourd'hui (dates incluses). */
    public function estAbsent(): bool
    {
        if (! $this->absent) {
            return false;
        }

        $auj = now()->startOfDay();

        return (! $this->absent_du || $this->absent_du->lte($auj))
            && (! $this->absent_au || $this->absent_au->gte($auj));
    }

    /** Ids des personnes actuellement absentes que cet utilisateur remplace. */
    public function idsRemplaces(): array
    {
        return $this->remplaces()
            ->where('absent', true)
            ->get()
            ->filter(fn ($u) => $u->estAbsent())
            ->pluck('id')
            ->all();
    }

    /** Maire, Directeur de Cabinet ou DGS : « mini-admins » de leur mairie */
    public function estDirection(): bool
    {
        return in_array($this->grade, [
            Referentiel::GRADE_MAIRE,
            Referentiel::GRADE_DIR_CABINET,
            Referentiel::GRADE_DGS,
        ], true);
    }

    /** Cabinet du maire / DGS / Maire : voient toutes les tâches de la mairie */
    public function voitTousLesServices(): bool
    {
        return $this->isAdmin()
            || $this->estDirection()
            || in_array($this->service, Referentiel::SERVICES_VUE_GLOBALE, true);
    }

    /** Directeur de Cabinet / DGS : voient toutes les tâches de leur service */
    public function voitSonService(): bool
    {
        return in_array($this->grade, [Referentiel::GRADE_DIR_CABINET, Referentiel::GRADE_DGS], true);
    }

    // ── Attributs pratiques ──────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return trim(($this->prenom ?? '') . ' ' . ($this->nom ?? '')) ?: $this->username;
    }

    public function getServiceLabelAttribute(): string
    {
        // Nom personnalisé par la mairie s'il existe, sinon référentiel par défaut
        return $this->mairie
            ? $this->mairie->libelleService($this->service)
            : Referentiel::serviceLabel($this->service);
    }

    public function getGradeLabelAttribute(): string
    {
        return Referentiel::gradeLabel($this->grade);
    }

    public function getTelephoneCompletAttribute(): string
    {
        return $this->telephone
            ? '(' . $this->telephone_indicatif . ') ' . $this->telephone
            : '—';
    }

    /** Le mot de passe provisoire a-t-il expiré ? */
    public function tempPasswordExpired(): bool
    {
        return $this->temp_password_expires_at !== null
            && $this->temp_password_expires_at->isPast();
    }

    // ── Génération automatique ───────────────────────────────────

    /**
     * Identifiant unique prenom.nom — si déjà pris (même dans une autre
     * mairie), on ajoute +1 : prenom.nom1, prenom.nom2, …
     */
    public static function genererUsername(string $prenom, string $nom, ?int $ignoreId = null): string
    {
        $base = Str::slug($prenom, '') . '.' . Str::slug($nom, '');
        $base = Str::lower($base);

        $username = $base;
        $i = 0;
        while (static::where('username', $username)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $i++;
            $username = $base . $i;
        }

        return $username;
    }

    /**
     * Référence automatique "service-numéro" (numérotation par mairie
     * et par service, en partant de 0).
     */
    public static function genererReference(int $mairieId, int $service): string
    {
        $count = static::where('mairie_id', $mairieId)
            ->where('service', $service)
            ->count();

        return $service . '-' . ($count + 1);
    }
}
