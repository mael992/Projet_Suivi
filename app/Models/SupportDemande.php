<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Demande au Support technique MGDS, ouverte via l'assistant (questionnaire
 * à étapes) de la page Contact. Réservée aux admins : aucune mairie ne la voit.
 */
class SupportDemande extends Model
{
    public const STATUT_RECEPTION = 'reception';   // en attente d'une réponse de l'équipe
    public const STATUT_REPONSE   = 'reponse';     // l'équipe a répondu
    public const STATUT_CLOTURE   = 'cloture';

    public const STATUTS = [
        self::STATUT_RECEPTION => 'Réception',
        self::STATUT_REPONSE   => 'Réponse',
        self::STATUT_CLOTURE   => 'Clôturé',
    ];

    /** Choix de l'assistant pour un agent connecté. */
    public const CHOIX_CONNECTE = [
        'compte'    => 'Mon compte',
        'collegue'  => 'Un collègue de la même mairie',
        'plusieurs' => 'Plusieurs personnes',
        'mairie'    => 'Une mairie',
    ];

    /** Choix de l'assistant pour une personne sans compte (avant connexion). */
    public const CHOIX_PUBLIC = [
        'connexion'       => 'Je n\'arrive pas à me connecter',
        'trouver_mairie'  => 'Je ne trouve pas ma mairie',
        'question_mairie' => 'Je veux poser une question à ma mairie',
        'autre'           => 'Autre problème',
    ];

    /** Choix pour lesquels l'assistant demande une précision. */
    public const QUESTIONS_PRECISION = [
        'collegue'       => 'Pour quelle personne ?',
        'plusieurs'      => 'Merci d\'indiquer les personnes concernées.',
        'mairie'         => 'Quelle mairie ?',
        'trouver_mairie' => 'Quelle est votre commune ?',
    ];

    protected $fillable = [
        'reference', 'jeton', 'user_id', 'avec_compte', 'nom', 'prenom', 'email',
        'concerne', 'precision', 'statut', 'cloture_at',
    ];

    protected $hidden = ['jeton'];

    protected function casts(): array
    {
        return [
            'avec_compte' => 'boolean',
            'cloture_at'  => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(SupportMessage::class)->orderBy('created_at')->orderBy('id');
    }

    // ── Libellés ─────────────────────────────────────────────────

    public static function libelleChoix(string $choix): string
    {
        return self::CHOIX_CONNECTE[$choix] ?? self::CHOIX_PUBLIC[$choix] ?? $choix;
    }

    public function getConcerneLabelAttribute(): string
    {
        return __(self::libelleChoix($this->concerne));
    }

    public function getStatutLabelAttribute(): string
    {
        return __(self::STATUTS[$this->statut] ?? $this->statut);
    }

    /** Qui écrit : l'agent (identifiant, mairie) ou « sans compte MGDS ». */
    public function libelleDemandeur(): string
    {
        if (! $this->avec_compte) {
            return trim($this->prenom . ' ' . $this->nom) . ' (' . __('sans compte MGDS') . ')';
        }

        if (! $this->user) {
            return __('Compte supprimé');
        }

        return $this->user->username . ($this->user->mairie ? ' — ' . $this->user->mairie->nom : '');
    }

    /**
     * Où suivre la conversation : le Centre de messagerie (onglet « Message
     * Support ») pour une demande ouverte depuis un compte, comme pour
     * l'équipe MGDS ; le lien secret pour une personne sans compte.
     */
    public function lienMessagerie(): string
    {
        return route('messagerie.index', ['onglet' => 'support', 'demande' => $this->id]);
    }

    public function lienSuivi(): string
    {
        return $this->avec_compte ? $this->lienMessagerie() : route('support.suivi', $this->jeton);
    }

    /** Adresse à prévenir quand l'équipe répond. */
    public function emailDemandeur(): ?string
    {
        return $this->avec_compte ? $this->user?->email : $this->email;
    }

    // ── Cycle de vie ─────────────────────────────────────────────

    public function estCloture(): bool
    {
        return $this->statut === self::STATUT_CLOTURE;
    }

    /** Ajoute un message et recalcule le statut. */
    public function ajouterMessage(string $auteur, string $corps): SupportMessage
    {
        $message = $this->messages()->create(['auteur' => $auteur, 'corps' => $corps]);

        if ($auteur !== SupportMessage::AUTEUR_ASSISTANT) {
            $this->update([
                'statut' => $auteur === SupportMessage::AUTEUR_ADMIN ? self::STATUT_REPONSE : self::STATUT_RECEPTION,
            ]);
        }

        return $message;
    }

    /** Badge admin : demandes qui attendent une réponse de l'équipe. */
    public static function enAttente(): int
    {
        return static::where('statut', self::STATUT_RECEPTION)->count();
    }

    public static function genererJeton(): string
    {
        return Str::random(64);
    }
}
