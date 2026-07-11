<?php

namespace App\Support;

/**
 * Référentiel MGDS : services (équipes) des mairies et grades des utilisateurs.
 * Le numéro de service sert de "secteur" dans les références (ex: 12-0, 12-1…).
 */
class Referentiel
{
    // ── Services / Équipes ───────────────────────────────────────
    public const SERVICES = [
        1  => 'Cabinet du maire',
        2  => 'Direction Générale des Services',
        3  => 'Événements & Vie associative',
        4  => 'Service Financier',
        5  => 'Service Gestion des Ressources Humaines',
        6  => 'Pôle Culture - Patrimoine',
        7  => 'Urbanisme',
        8  => 'Accueil / Citoyenneté',
        9  => 'Service État Civil / Funéraire - Élections',
        10 => 'Pôle Éducation',
        11 => 'Pôle Solidarité',
        12 => 'Service Technique / Centre Technique Municipal (CTM)',
        13 => 'Pôle Sécurité / Police Municipale',
    ];

    // Services qui voient toutes les tâches de la mairie
    public const SERVICES_VUE_GLOBALE = [1, 2];

    // ── Grades (statuts utilisateur) ─────────────────────────────
    public const GRADE_MAIRE        = 1;
    public const GRADE_DIR_CABINET  = 2;
    public const GRADE_DGS          = 3;
    public const GRADE_EMPLOYE      = 4;

    public const GRADES = [
        self::GRADE_MAIRE       => 'M. / Mme le Maire',
        self::GRADE_DIR_CABINET => 'Directeur de Cabinet',
        self::GRADE_DGS         => 'Directrice Générale des Services',
        self::GRADE_EMPLOYE     => 'Employé',
    ];

    // Grades autorisés à créer / modifier / supprimer des tâches
    public const GRADES_CREATION_TACHE = [
        self::GRADE_MAIRE,
        self::GRADE_DIR_CABINET,
        self::GRADE_DGS,
    ];

    // ── Droits d'application (du plus fort au plus faible) ───────
    // Système hiérarchique : posséder un droit donne automatiquement
    // tous les droits situés à sa droite dans cette liste.
    public const DROITS = [
        'gestion_utilisateurs'  => 'Gestion des utilisateurs',
        'contacts_modification' => 'Fiche Contact — modification',
        'contacts_lecture'      => 'Fiche Contact — lecture',
        'marche_gestion'        => 'Marché — gestion',
        'taches_gestion'        => 'Tableau des suivis — gestion',
        'taches_lecture'        => 'Tableau des suivis — lecture',
    ];

    /** Position du droit dans la hiérarchie (0 = le plus fort). */
    public static function rangDroit(?string $droit): int
    {
        $rang = array_search($droit, array_keys(self::DROITS), true);

        return $rang === false ? PHP_INT_MAX : $rang;
    }

    /** Droit par défaut selon le grade (modifiable ensuite par utilisateur). */
    public static function droitDefaut(?int $grade): string
    {
        return match ($grade) {
            self::GRADE_MAIRE                        => 'gestion_utilisateurs',
            self::GRADE_DIR_CABINET, self::GRADE_DGS => 'contacts_modification',
            default                                  => 'taches_lecture',
        };
    }

    // ── Statuts des tâches ───────────────────────────────────────
    public const STATUT_OUVERT   = 'ouvert';
    public const STATUT_EN_COURS = 'en_cours';
    public const STATUT_FAIT     = 'fait';

    public const STATUTS = [
        self::STATUT_OUVERT   => 'Ouvert',
        self::STATUT_EN_COURS => 'En cours',
        self::STATUT_FAIT     => 'Fait',
    ];

    // ── Indicatifs téléphoniques proposés ────────────────────────
    public const INDICATIFS = ['+33', '+32', '+41', '+352', '+377', '+44', '+34', '+39', '+49'];

    public static function serviceLabel(?int $service): string
    {
        return self::SERVICES[$service] ?? '—';
    }

    public static function gradeLabel(?int $grade): string
    {
        return self::GRADES[$grade] ?? '—';
    }

    public static function statutLabel(?string $statut): string
    {
        return self::STATUTS[$statut] ?? '—';
    }
}
