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

    /** Délai pendant lequel une réouverture peut être demandée (jours). */
    public const JOURS_REOUVERTURE = 15;

    /** Durée de conservation en lecture seule après clôture (mois). */
    public const MOIS_CONSERVATION = 6;

    protected $fillable = [
        'mairie_id', 'reference', 'type', 'service',
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

        return $user->mairie_id === $this->mairie_id
            && static::whereKey($this->id)->visiblesPar($user)->exists();
    }

    /** Compteurs de notification par dossier (Réception + Réouverture demandée). */
    public static function compteursPour(User $user): array
    {
        $base = static::where('type', 'externe')->visiblesPar($user);

        return [
            self::STATUT_RECEPTION   => (clone $base)->where('statut', self::STATUT_RECEPTION)->count(),
            self::STATUT_REPONSE     => (clone $base)->where('statut', self::STATUT_REPONSE)->count(),
            self::STATUT_CLOTURE     => (clone $base)->where('statut', self::STATUT_CLOTURE)->count(),
            self::STATUT_REOUVERTURE => (clone $base)->where('statut', self::STATUT_REOUVERTURE)->count(),
            // Dossier transversal : les demandes transférées, tous statuts confondus
            self::DOSSIER_TRANSFERE  => (clone $base)->whereNotNull('transfere_at')->count(),
        ];
    }

    /** Badge du hub : messages reçus à traiter + demandes de réouverture. */
    public static function enAttentePour(User $user): int
    {
        return static::where('type', 'externe')
            ->visiblesPar($user)
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
