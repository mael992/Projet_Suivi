<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Question de la Boîte de dialogue (entraide entre mairies).
 * Les questions sont partagées entre toutes les mairies.
 */
class DialogueQuestion extends Model
{
    public const SECTIONS = [
        'tableau-suivis' => 'Tableau des suivis',
        'marche'         => 'Marché',
        'fiche-contact'  => 'Fiche Contact',
        'administration' => 'Paramètres Administration',
    ];

    /** Droit d'application requis pour voir chaque section (null = tout le monde, 'admin' = admin) */
    public const SECTIONS_ACCES = [
        'tableau-suivis' => null,
        'marche'         => 'marche_gestion',
        'fiche-contact'  => 'contacts_lecture',
        'administration' => 'admin',
    ];

    protected $fillable = ['user_id', 'section', 'texte', 'fermee_at'];

    protected function casts(): array
    {
        return ['fermee_at' => 'datetime'];
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reponses()
    {
        return $this->hasMany(DialogueReponse::class)->orderBy('created_at');
    }

    public function estFermee(): bool
    {
        return $this->fermee_at !== null;
    }

    /** Sections visibles pour un utilisateur selon ses droits d'application. */
    public static function sectionsVisiblesPour(User $user): array
    {
        $visibles = [];

        foreach (array_keys(self::SECTIONS) as $cle) {
            $acces = self::SECTIONS_ACCES[$cle];

            if ($acces === null) {
                $visibles[] = $cle;
            } elseif ($acces === 'admin') {
                if ($user->isAdmin()) {
                    $visibles[] = $cle;
                }
            } elseif ($user->aDroit($acces)) {
                $visibles[] = $cle;
            }
        }

        return $visibles;
    }

    /** Nombre de questions non répondues (ouvertes, sans réponse) dans les sections visibles. */
    public static function nonReponduesPour(User $user): int
    {
        return self::whereIn('section', self::sectionsVisiblesPour($user))
            ->whereNull('fermee_at')
            ->whereDoesntHave('reponses')
            ->count();
    }
}
