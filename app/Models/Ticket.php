<?php

namespace App\Models;

use App\Support\Referentiel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    // Dossiers du Centre de Messagerie
    public const STATUT_RECEPTION   = 'reception';              // nouveau message reçu, à traiter
    public const STATUT_REPONSE     = 'reponse';                // la mairie a répondu au dernier message
    public const STATUT_CLOTURE     = 'cloture';                // conversation clôturée
    public const STATUT_REOUVERTURE = 'reouverture_demandee';   // le citoyen demande la réouverture

    public const STATUTS = [
        self::STATUT_RECEPTION   => 'Réception',
        self::STATUT_REPONSE     => 'Réponse',
        self::STATUT_CLOTURE     => 'Clôturé',
        self::STATUT_REOUVERTURE => 'Réouverture demandée',
    ];

    /**
     * Dossier « Transféré » : ce n'est pas un statut mais un tri. Une demande
     * transférée garde son statut réel (Réception, Réponse, Clôturé…) ; ce
     * dossier la fait simplement apparaître à part pour la repérer d'un œil.
     */
    public const DOSSIER_TRANSFERE = 'transfere';

    /** Boîte de réception des demandes d'adhésion au marché. */
    public const DOSSIER_ADHESION = 'adhesion_marche';

    /**
     * Types de conversation. « externe » = un habitant écrit à sa mairie ;
     * « marche » = un commerçant demande à rejoindre le marché. Les deux
     * partagent la mécanique de conversation mais pas la boîte de réception.
     */
    public const TYPE_EXTERNE = 'externe';
    public const TYPE_MARCHE  = 'marche';

    /** Délai pendant lequel une réouverture peut être demandée (jours). */
    public const JOURS_REOUVERTURE = 15;

    /** Durée de conservation en lecture seule après clôture (mois). */
    public const MOIS_CONSERVATION = 6;

    protected $fillable = [
        'mairie_id', 'marche_demande_id', 'reference', 'type', 'service',
        'nom', 'prenom', 'telephone_indicatif', 'telephone', 'email',
        'sujet', 'photos', 'statut', 'confidentiel', 'confidents',
        'transfere_services', 'transfere_users', 'transfere_par', 'transfere_at',
        'cloture_at', 'cloture_par', 'reouverture_demandee_at', 'reouverture_motif',
    ];

    protected function casts(): array
    {
        return [
            'service'                 => 'integer',
            'photos'                  => 'array',
            'confidentiel'            => 'boolean',
            'confidents'              => 'array',
            'transfere_services'      => 'array',
            'transfere_users'         => 'array',
            'transfere_at'            => 'datetime',
            'cloture_at'              => 'datetime',
            'reouverture_demandee_at' => 'datetime',
        ];
    }

    public function demandeMarche()
    {
        return $this->belongsTo(MarcheDemande::class, 'marche_demande_id');
    }

    public function mairie()
    {
        return $this->belongsTo(Mairie::class);
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at');
    }

    /** Le « facteur » : personne qui a transféré la demande. */
    public function facteur()
    {
        return $this->belongsTo(User::class, 'transfere_par');
    }

    public function estTransfere(): bool
    {
        return $this->transfere_at !== null;
    }

    /** Numéros des services destinataires du transfert. */
    public function servicesTransfert(): array
    {
        return array_map('intval', $this->transfere_services ?? []);
    }

    /** Identifiants des personnes destinataires du transfert. */
    public function idsTransfert(): array
    {
        return array_map('intval', $this->transfere_users ?? []);
    }

    /** Personnes à qui la demande a été transférée. */
    public function destinatairesTransfert()
    {
        $ids = $this->idsTransfert();

        return $ids ? User::whereIn('id', $ids)->orderBy('nom')->orderBy('prenom')->get() : collect();
    }

    /** Récapitulatif « vers qui » : services et personnes, en clair. */
    public function libelleTransfert(): string
    {
        $cibles = array_map(fn ($s) => $this->mairie?->libelleService($s) ?? (string) $s, $this->servicesTransfert());

        foreach ($this->destinatairesTransfert() as $agent) {
            $cibles[] = $agent->username;
        }

        return implode(', ', $cibles);
    }

    // ── Libellés ─────────────────────────────────────────────────

    public function getServiceLabelAttribute(): string
    {
        // Attention : le service 0 (Maire) est valide → comparaison stricte à null
        if ($this->service === null) {
            return 'Je ne sais pas';
        }

        return $this->mairie
            ? $this->mairie->libelleService($this->service)
            : Referentiel::serviceLabel($this->service);
    }

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function getNomCompletAttribute(): string
    {
        return trim($this->nom . ' ' . $this->prenom);
    }

    public function getTelephoneCompletAttribute(): string
    {
        return '(' . $this->telephone_indicatif . ') ' . $this->telephone;
    }

    // ── Cycle de vie ─────────────────────────────────────────────

    public function estCloture(): bool
    {
        return in_array($this->statut, [self::STATUT_CLOTURE, self::STATUT_REOUVERTURE], true);
    }

    /** Une réouverture peut-elle encore être demandée (15 jours après la clôture) ? */
    public function reouverturePossible(): bool
    {
        return $this->statut === self::STATUT_CLOTURE
            && $this->cloture_at !== null
            && $this->cloture_at->copy()->addDays(self::JOURS_REOUVERTURE)->isFuture();
    }

    /** Jours restants pour demander une réouverture. */
    public function joursRestantsReouverture(): int
    {
        if ($this->cloture_at === null) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($this->cloture_at->copy()->addDays(self::JOURS_REOUVERTURE), false));
    }

    /** Écriture possible (mairie ou citoyen) : uniquement hors clôture. */
    public function peutEcrire(): bool
    {
        return ! $this->estCloture();
    }

    /** Conversation conservée en lecture seule (6 mois après la clôture). */
    public function conservationExpiree(): bool
    {
        return $this->cloture_at !== null
            && $this->cloture_at->copy()->addMonths(self::MOIS_CONSERVATION)->isPast();
    }

    /** Le dernier message vient-il de la personne extérieure (mairie doit répondre) ? */
    public function attendReponseMairie(): bool
    {
        $dernier = $this->messages()->latest('created_at')->first();

        return $dernier !== null && $dernier->user_id === null;
    }

    /** Recalcule le dossier après un nouveau message. */
    public function majStatutApresMessage(bool $deLaMairie): void
    {
        $this->statut = $deLaMairie ? self::STATUT_REPONSE : self::STATUT_RECEPTION;
        $this->save();
    }

    // ── Requêtes ─────────────────────────────────────────────────

    /** Restreint aux tickets visibles par l'utilisateur (mairie + services reçus). */
    public function scopeVisiblesPar(Builder $query, User $user): Builder
    {
        // La confidentialité prime sur tout, y compris l'admin et la direction
        $query->where(fn (Builder $q) => $q
            ->where('confidentiel', false)
            ->orWhere(fn (Builder $c) => $c
                ->where('confidentiel', true)
                ->whereJsonContains('confidents', $user->id)
            )
        );

        if ($user->isAdmin()) {
            return $query;
        }

        $query->where('mairie_id', $user->mairie_id);

        // Visibilité globale (case « voir tous les messages »)
        if ($user->voitTousLesMessages()) {
            return $query;
        }

        $cats        = $user->categoriesCommunication();
        $numServices = array_values(array_filter($cats, fn ($c) => $c !== 'inconnu'));
        $inconnu     = in_array('inconnu', $cats, true);

        return $query->where(function (Builder $q) use ($numServices, $inconnu, $user) {
            if ($numServices) {
                $q->whereIn('service', $numServices);
                // Demandes transférées vers un service que l'utilisateur reçoit
                foreach ($numServices as $s) {
                    $q->orWhereJsonContains('transfere_services', (int) $s);
                }
            }
            if ($inconnu) {
                $q->orWhereNull('service');
            }

            // Transfert nominatif, et le « facteur » garde la main sur ce qu'il a transféré
            $q->orWhereJsonContains('transfere_users', $user->id)
              ->orWhere('transfere_par', $user->id);

            if (! $numServices && ! $inconnu) {
                // Sans service coché, on ne voit que ce qui nous est adressé
                $q->orWhereRaw('1 = 0');
            }
        });
    }

    /**
     * Droit d'agir (répondre, clôturer, transférer, traiter une réouverture).
     *
     * Règle : qui voit la demande peut la traiter. Auparavant on exigeait
     * d'être destinataire du SERVICE d'origine ; depuis que l'habitant ne
     * choisit plus de service, une demande transférée nominativement
     * n'appartenait à aucun service et son destinataire recevait une
     * erreur 403 alors qu'il la voyait dans sa liste.
     */
    public function peutEtreGerePar(User $user): bool
    {
        if ($user->isAdmin()) {
            return false; // l'admin reste en lecture seule
        }

        if ($user->mairie_id !== $this->mairie_id) {
            return false;
        }

        // Les demandes d'adhésion au marché suivent le droit de leur
        // application, pas les cases de communication extérieure.
        if ($this->type === self::TYPE_MARCHE) {
            return $user->aDroit('marche_gestion');
        }

        return static::whereKey($this->id)->visiblesPar($user)->exists();
    }

    /**
     * Boîte de réception des demandes d'adhésion au marché : réservée aux
     * personnes qui ont le droit sur l'application Marché.
     */
    public static function adhesionsMarchePour(User $user): Builder
    {
        $query = static::where('type', self::TYPE_MARCHE);

        return $user->isAdmin() ? $query : $query->where('mairie_id', $user->mairie_id);
    }

    /** L'utilisateur a-t-il accès à la boîte « Demande adhésion marché » ? */
    public static function voitAdhesionsMarche(User $user): bool
    {
        return $user->isAdmin() || $user->aDroit('marche_gestion');
    }

    /**
     * Demandes d'adhésion au marché qui attendent une décision.
     * Sert la pastille bleue du hub : c'est une réception, comme un message.
     */
    public static function adhesionsEnAttentePour(User $user): int
    {
        if (! self::voitAdhesionsMarche($user)) {
            return 0;
        }

        return self::adhesionsMarchePour($user)
            ->whereIn('statut', [self::STATUT_RECEPTION, self::STATUT_REOUVERTURE])
            ->count();
    }

    /**
     * Dossiers de travail (Réception, Réponse, Clôturé, Réouverture).
     *
     * Une demande transférée n'y figure plus que pour ses destinataires :
     * celui qui l'a transférée la retrouve dans « Transféré », et pas en
     * double dans sa Réception — sauf s'il s'est mis lui-même parmi les
     * destinataires, auquel cas elle réapparaît des deux côtés.
     */
    public function scopeDossiersDeTravail(Builder $query, User $user): Builder
    {
        $services = array_values(array_filter(
            $user->categoriesCommunication(),
            fn ($c) => $c !== 'inconnu',
        ));

        return $query->where(function (Builder $q) use ($user, $services) {
            // Ce que je n'ai pas transféré moi-même ne bouge pas
            $q->where(fn (Builder $s) => $s
                ->whereNull('transfere_at')
                ->orWhere('transfere_par', '!=', $user->id))
              // Transférée par moi, mais je me suis mis dans les destinataires
              ->orWhereJsonContains('transfere_users', $user->id);

            foreach ($services as $service) {
                $q->orWhereJsonContains('transfere_services', (int) $service);
            }
        });
    }

    /**
     * Dossier « Transféré » : les demandes transférées encore en cours.
     * Une fois clôturée, la demande vit dans « Clôturé » et disparaît d'ici,
     * y compris pour la personne qui l'avait transférée.
     */
    public function scopeDossierTransfere(Builder $query): Builder
    {
        return $query->whereNotNull('transfere_at')
            ->where('statut', '!=', self::STATUT_CLOTURE);
    }

    /** Compteurs de notification par dossier (Réception + Réouverture demandée). */
    public static function compteursPour(User $user): array
    {
        $base    = static::where('type', self::TYPE_EXTERNE)->visiblesPar($user);
        $travail = (clone $base)->dossiersDeTravail($user);

        if (self::voitAdhesionsMarche($user)) {
            $adhesions = self::adhesionsMarchePour($user)
                ->where('statut', '!=', self::STATUT_CLOTURE)->count();
        }

        return [
            self::DOSSIER_ADHESION   => $adhesions ?? 0,
            self::STATUT_RECEPTION   => (clone $travail)->where('statut', self::STATUT_RECEPTION)->count(),
            self::STATUT_REPONSE     => (clone $travail)->where('statut', self::STATUT_REPONSE)->count(),
            self::STATUT_CLOTURE     => (clone $travail)->where('statut', self::STATUT_CLOTURE)->count(),
            self::STATUT_REOUVERTURE => (clone $travail)->where('statut', self::STATUT_REOUVERTURE)->count(),
            // Dossier transversal : les demandes transférées encore ouvertes
            self::DOSSIER_TRANSFERE  => (clone $base)->dossierTransfere()->count(),
        ];
    }

    /** Badge du hub : messages reçus à traiter + demandes de réouverture. */
    public static function enAttentePour(User $user): int
    {
        return static::where('type', 'externe')
            ->visiblesPar($user)
            ->dossiersDeTravail($user)
            ->whereIn('statut', [self::STATUT_RECEPTION, self::STATUT_REOUVERTURE])
            ->count();
    }

    /**
     * Référence « mairie-numéro » : la numérotation repart à 1 pour chaque
     * mairie, préfixée par son numéro (ex. 1-1, 1-2, 2-1…).
     */
    public static function genererReference(int $mairieId): string
    {
        $dernier = static::where('mairie_id', $mairieId)
            ->get(['reference'])
            ->map(fn ($t) => (int) (str_contains($t->reference, '-')
                ? substr($t->reference, strpos($t->reference, '-') + 1)
                : $t->reference))
            ->max();

        return $mairieId . '-' . (($dernier ?? 0) + 1);
    }
}
