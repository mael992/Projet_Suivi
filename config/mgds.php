<?php

/*
 * Coordonnées du support MGDS affichées dans les emails d'abonnement.
 * Définies via .env (jamais en dur : le dépôt est public).
 */
return [
    'support_phone' => env('MGDS_SUPPORT_PHONE', ''),
    'support_email' => env('MGDS_SUPPORT_EMAIL', ''),
];
