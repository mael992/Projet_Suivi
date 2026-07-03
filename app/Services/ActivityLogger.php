<?php

namespace App\Services;

use Illuminate\Support\Facades\Request;

/**
 * Service de journalisation des actions utilisateurs.
 *
 * Législation FR/RGPD (CNIL / ANSSI) :
 *   - Conservation minimale des logs de connexion et d'accès : 6 mois
 *   - Recommandation ANSSI pour les systèmes sensibles : 12 mois
 *   → Nous appliquons 6 mois (configurable via LOG_RETENTION_MONTHS)
 *
 * Fichiers produits :
 *   storage/logs/activity/activity-YYYY-MM.log  (log courant, un par mois)
 *   storage/logs/activity/backups/              (sauvegardes toutes les 48h)
 */
class ActivityLogger
{
    // ── Catégories ──────────────────────────────────────────────
    const CAT_AUTH     = 'AUTH';
    const CAT_USER     = 'USER';
    const CAT_TICKET   = 'TICKET';
    const CAT_INCIDENT = 'INCIDENT';
    const CAT_CHANTIER = 'CHANTIER';
    const CAT_SYSTEM   = 'SYSTEM';

    // ── Durée de conservation (mois) ────────────────────────────
    const RETENTION_MONTHS = 6;

    // ────────────────────────────────────────────────────────────

    /**
     * Écrit une ligne dans le fichier de log du mois courant.
     */
    public static function log(string $category, string $action, string $detail, ?string $actor = null): void
    {
        $dir  = storage_path('logs/activity');
        $file = $dir . '/activity-' . now()->format('Y-m') . '.log';

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $actor = $actor ?? static::resolveActor();
        $ip    = Request::ip() ?? '—';
        $line  = sprintf(
            "[%s] [%s] [%s] %s | Acteur: %s | IP: %s\n",
            now()->format('Y-m-d H:i:s'),
            $category,
            strtoupper($action),
            $detail,
            $actor,
            $ip
        );

        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    // ── Raccourcis thématiques ───────────────────────────────────

    public static function auth(string $action, string $detail, ?string $actor = null): void
    {
        static::log(self::CAT_AUTH, $action, $detail, $actor);
    }

    public static function user(string $action, string $detail, ?string $actor = null): void
    {
        static::log(self::CAT_USER, $action, $detail, $actor);
    }

    public static function ticket(string $action, string $detail, ?string $actor = null): void
    {
        static::log(self::CAT_TICKET, $action, $detail, $actor);
    }

    public static function incident(string $action, string $detail, ?string $actor = null): void
    {
        static::log(self::CAT_INCIDENT, $action, $detail, $actor);
    }

    public static function chantier(string $action, string $detail, ?string $actor = null): void
    {
        static::log(self::CAT_CHANTIER, $action, $detail, $actor);
    }

    public static function system(string $action, string $detail): void
    {
        static::log(self::CAT_SYSTEM, $action, $detail, 'SYSTEM');
    }

    // ── Utilitaires internes ─────────────────────────────────────

    private static function resolveActor(): string
    {
        if (auth()->check()) {
            return auth()->user()->username . ' (id:' . auth()->id() . ')';
        }
        return 'anonyme';
    }

    /**
     * Liste tous les fichiers de log du mois courant et des mois précédents
     * dans la limite de RETENTION_MONTHS.
     */
    public static function listLogFiles(): array
    {
        $dir = storage_path('logs/activity');
        if (!is_dir($dir)) return [];

        $files = glob($dir . '/activity-*.log') ?: [];
        rsort($files);
        return $files;
    }
}
