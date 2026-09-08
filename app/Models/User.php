<?php

namespace App\Models;

use App\Support\Referentiel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
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
        'droits',
        'communication',
        'voit_tous_messages',
        'cgu_acceptees_at',
        'binome_id',
        'fonction',
        'reference',
        'telephone_indicatif',
        'telephone',
    ];

    protected $hidden = [
        'password',
        'password_provisoire',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'                 => 'hashed',
            'temp_password_expires_at' => 'datetime',
            'password_provisoire_expires_at' => 'datetime',
            'must_change_password'     => 'boolean',
            'service'                  => 'integer',
            'grade'                    => 'integer',
            'communication'            => 'array',
            'droits'                   => 'array',
            'voit_tous_messages'       => 'boolean',
            'cgu_acceptees_at'         => 'datetime',
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
     * Droits réellement cochés sur la fiche, sans les droits impliqués :
     *  - null → droits par défaut du grade
     *  - []   → aucun droit (explicitement retiré)
     */
    public function droitsCoches(): array
    {
        return $this->droits ?? Referentiel::droitsDefaut($this->grade);
    }

    /** Droits effectifs : les cases cochées, plus ce qu'elles impliquent. */
    public function droitsActuels(): array
    {
        return Referentiel::expanserDroits($this->droitsCoches());
    }

    /**
     * Chaque droit est indépendant : seules les implications déclarées dans
     * Referentiel::DROITS_IMPLIQUES en accordent d'autres.
     */
    public function aDroit(string $droit): bool
    {
        return $this->isAdmin() || in_array($droit, $this->droitsActuels(), true);
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

    /** Absences déclarées pour cette personne (passées, en cours, à venir). */
    public function absences()
    {
        return $this->hasMany(Absence::class);
    }

    /** Absence couvrant aujourd'hui (dates incluses). */
    public function estAbsent(): bool
    {
        return $this->absences()->enCours()->exists();
    }

    /** L'absence du jour, pour afficher le motif et la période. */
    public function absenceEnCours(): ?Absence
    {
        return $this->absences()->enCours()->orderBy('date_debut')->first();
    }

    /** Ids des personnes actuellement absentes que cet utilisateur remplace. */
    public function idsRemplaces(): array
    {
        return $this->remplaces()
            ->whereHas('absences', fn ($q) => $q->enCours())
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

    /**
     * Voit toutes les tâches de la mairie.
     *
     * C'est le GRADE qui l'accorde, pas le service : appartenir au Cabinet du
     * maire ou à la DGS ne suffit pas. Un employé de ces services y voyait
     * auparavant toutes les tâches de la commune sans être responsable de
     * quoi que ce soit.
     */
    public function voitTousLesServices(): bool
    {
        return $this->isAdmin() || $this->estDirection();
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

    /** Validité du mot de passe provisoire remis à l'ouverture d'un compte. */
    public const HEURES_TEMP_PASSWORD = 48;

    /**
     * Mot de passe provisoire d'ouverture de compte : le système le tire au
     * sort, personne ne le choisit. La mairie n'a donc pas à inventer un mot
     * de passe (et à le réutiliser d'un agent à l'autre) ; il figure sur le
     * courrier d'identifiants et doit être changé à la première connexion.
     *
     * Sans symboles : il est recopié à la main depuis un courrier papier.
     */
    public static function genererMotDePasseProvisoire(): string
    {
        return Str::password(12, symbols: false, spaces: false);
    }

    /** Applique un mot de passe provisoire fraîchement tiré et le renvoie en clair. */
    public function attribuerMotDePasseProvisoire(): string
    {
        $clair = self::genererMotDePasseProvisoire();

        $this->forceFill([
            'password'                 => Hash::make($clair),
            'temp_password'            => $clair,
            'temp_password_expires_at' => now()->addHours(self::HEURES_TEMP_PASSWORD),
            'must_change_password'     => true,
        ]);

        return $clair;
    }

    // ── Mot de passe provisoire en libre-service ─────────────────

    /** Durée de validité du mot de passe provisoire envoyé par e-mail. */
    public const HEURES_PASSWORD_PROVISOIRE = 2;

    /**
     * Génère un mot de passe provisoire, le stocke haché et renvoie sa
     * version en clair (à envoyer par e-mail, jamais conservée).
     *
     * Le mot de passe habituel n'est PAS modifié : si la personne ignore
     * l'e-mail, elle continue de se connecter comme avant et le provisoire
     * cesse simplement de fonctionner.
     */
    public function genererPasswordProvisoire(): string
    {
        $clair = Str::password(12, symbols: false, spaces: false);

        $this->forceFill([
            'password_provisoire'            => Hash::make($clair),
            'password_provisoire_expires_at' => now()->addHours(self::HEURES_PASSWORD_PROVISOIRE),
        ])->save();

        return $clair;
    }

    /** Un mot de passe provisoire est-il en cours de validité ? */
    public function passwordProvisoireActif(): bool
    {
        return $this->password_provisoire !== null
            && $this->password_provisoire_expires_at !== null
            && $this->password_provisoire_expires_at->isFuture();
    }

    /** Le mot de passe saisi correspond-il au provisoire encore valide ? */
    public function passwordProvisoireCorrespond(string $clair): bool
    {
        return $this->passwordProvisoireActif()
            && Hash::check($clair, $this->password_provisoire);
    }

    /** Usage unique : le provisoire est effacé dès qu'il a servi (ou expiré). */
    public function consommerPasswordProvisoire(): void
    {
        $this->forceFill([
            'password_provisoire'            => null,
            'password_provisoire_expires_at' => null,
        ])->save();
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
