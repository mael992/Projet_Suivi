<?php

namespace App\Support;

/**
 * Référentiel MGDS : services (équipes) des mairies et grades des utilisateurs.
 * Le numéro de service sert de "secteur" dans les références (ex: 12-0, 12-1…).
 */
class Referentiel
{
    // ── Services / Équipes ───────────────────────────────────────
    public const SERVICE_MAIRE = 0;

    public const SERVICES = [
        0  => 'M. / Mme le Maire',
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
    public const SERVICES_VUE_GLOBALE = [0, 1, 2];

    /**
     * Statuts (grades) autorisés pour un service donné — couplage du
     * formulaire de création d'utilisateur.
     */
    public static function gradesAutorises(?int $service): array
    {
        return match ($service) {
            self::SERVICE_MAIRE => [self::GRADE_MAIRE],
            1                   => [self::GRADE_DIR_CABINET, self::GRADE_EMPLOYE], // Cabinet du maire
            2                   => [self::GRADE_DGS, self::GRADE_EMPLOYE],         // DGS
            default             => [self::GRADE_EMPLOYE],
        };
    }

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

    // ── Droits d'application ─────────────────────────────────────
    // Chaque droit se coche indépendamment (l'ordre ci-dessous ne sert
    // qu'à l'affichage). Voir DROITS_IMPLIQUES pour les seules cascades.
    public const DROITS = [
        'gestion_utilisateurs'  => 'Gestion des utilisateurs',
        'contacts_modification' => 'Fiche Contact — modification',
        'contacts_lecture'      => 'Fiche Contact — lecture',
        'marche_gestion'        => 'Marché — gestion',
        'taches_gestion'        => 'Tableau des suivis — gestion',
    ];

    // Logo de l'application correspondant à chaque droit (mêmes icônes que le hub)
    public const DROITS_ICONES = [
        'gestion_utilisateurs'  => '👥',
        'contacts_modification' => '📇',
        'contacts_lecture'      => '📇',
        'marche_gestion'        => '🛍️',
        'taches_gestion'        => '📊',
    ];

    public static function droitIcone(?string $droit): string
    {
        return self::DROITS_ICONES[$droit] ?? '🔧';
    }

    /**
     * Droits automatiquement accordés par un droit coché.
     *
     * Deux règles seulement, et la seconde ne sort jamais de son application :
     *  - « Gestion des utilisateurs » donne accès à tout ;
     *  - « modification » implique la « lecture » de la même application.
     *
     * Une case cochée sur le Marché n'ouvre donc rien sur le Tableau des
     * suivis, et pouvoir écrire dans les fiches contact n'autorise pas à
     * créer des tâches.
     */
    public const DROITS_IMPLIQUES = [
        'gestion_utilisateurs'  => [
            'contacts_modification',
            'contacts_lecture',
            'marche_gestion',
            'taches_gestion',
        ],
        'contacts_modification' => ['contacts_lecture'],
    ];

    /**
     * Droits cochés + droits impliqués, dans l'ordre du référentiel.
     * Les clés inconnues sont ignorées.
     */
    public static function expanserDroits(array $droits): array
    {
        $accordes = [];

        foreach ($droits as $droit) {
            if (! isset(self::DROITS[$droit])) {
                continue;
            }

            $accordes[$droit] = true;

            foreach (self::DROITS_IMPLIQUES[$droit] ?? [] as $implique) {
                $accordes[$implique] = true;
            }
        }

        return array_values(array_intersect(array_keys(self::DROITS), array_keys($accordes)));
    }

    /**
     * Droits par défaut selon le grade (modifiables ensuite par utilisateur).
     * Un employé n'a aucun droit d'application par défaut : il voit
     * simplement ses propres tâches (droit de base, non géré ici).
     */
    public static function droitsDefaut(?int $grade): array
    {
        // Maire, Directeur de Cabinet et DGS : accès complet par défaut.
        return match ($grade) {
            self::GRADE_MAIRE, self::GRADE_DIR_CABINET, self::GRADE_DGS => ['gestion_utilisateurs'],
            default                                                     => [],
        };
    }

    // ── Motifs d'absence ─────────────────────────────────────────
    public const MOTIFS_ABSENCE = [
        'signalee'      => 'Absence signalée',
        'non_justifiee' => 'Absence non justifiée',
        'arret_travail' => 'Arrêt de travail',
        'arret_maladie' => 'Arrêt maladie',
        'vacances'      => 'Vacances',
        'rendez_vous'   => 'Rendez-vous',
        'formation'     => 'Formation',
    ];

    public static function motifAbsenceLabel(?string $motif): string
    {
        return self::MOTIFS_ABSENCE[$motif] ?? '—';
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

    /** Services effectifs d'une mairie (personnalisés) ou référentiel par défaut. */
    public static function servicesPour($mairie = null): array
    {
        return $mairie ? $mairie->libellesServices() : self::SERVICES;
    }

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
