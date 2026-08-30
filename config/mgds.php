<?php

/*
 * Coordonnées du support MGDS affichées dans les emails d'abonnement.
 * Définies via .env (jamais en dur : le dépôt est public).
 */
return [
    'support_phone' => env('MGDS_SUPPORT_PHONE', ''),
    'support_email' => env('MGDS_SUPPORT_EMAIL', ''),

    /*
     * Identité de l'éditeur, reprise sur les devis (mentions obligatoires).
     * À renseigner dans le .env : sans SIRET ni forme juridique, un devis
     * n'est pas conforme.
     */
    'editeur' => [
        'nom'            => env('MGDS_EDITEUR_NOM', 'MGDS'),
        'forme'          => env('MGDS_EDITEUR_FORME', ''),          // SARL, SAS, EI…
        'adresse'        => env('MGDS_EDITEUR_ADRESSE', ''),
        'code_postal'    => env('MGDS_EDITEUR_CP', ''),
        'ville'          => env('MGDS_EDITEUR_VILLE', ''),
        'siret'          => env('MGDS_EDITEUR_SIRET', ''),
        'rcs'            => env('MGDS_EDITEUR_RCS', ''),            // ou Répertoire des métiers
        'tva'            => env('MGDS_EDITEUR_TVA', ''),            // n° TVA intracommunautaire
        'tva_applicable' => env('MGDS_EDITEUR_TVA_APPLICABLE', true),
    ],
];
