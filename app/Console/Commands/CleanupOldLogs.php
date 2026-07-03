<?php

namespace App\Console\Commands;

use App\Services\ActivityLogger;
use Illuminate\Console\Command;

/**
 * Supprime les fichiers de logs (logs + sauvegardes) de plus de 6 mois.
 *
 * Législation appliquée :
 *   CNIL / RGPD — conservation minimale des logs de connexion : 6 mois
 *   Référence : délibération CNIL n°2021-122 et recommandations ANSSI
 *
 * Conservation : ActivityLogger::RETENTION_MONTHS mois (défaut : 6)
 */
class CleanupOldLogs extends Command
{
    protected $signature   = 'logs:cleanup';
    protected $description = 'Supprime les logs d\'activité de plus de ' . ActivityLogger::RETENTION_MONTHS . ' mois (conformité CNIL/RGPD)';

    public function handle(): int
    {
        $srcDir    = storage_path('logs/activity');
        $backupDir = $srcDir . '/backups';
        $limit     = now()->subMonths(ActivityLogger::RETENTION_MONTHS);
        $deleted   = 0;

        // ── Nettoyer les logs mensuels ───────────────────────────
        $files = glob($srcDir . '/activity-*.log') ?: [];
        foreach ($files as $file) {
            // Nom : activity-YYYY-MM.log
            if (preg_match('/activity-(\d{4}-\d{2})\.log$/', $file, $m)) {
                $fileDate = \Carbon\Carbon::createFromFormat('Y-m', $m[1])->endOfMonth();
                if ($fileDate->lt($limit)) {
                    unlink($file);
                    $deleted++;
                    $this->line("Supprimé : " . basename($file));
                }
            }
        }

        // ── Nettoyer les dossiers de sauvegarde ─────────────────
        if (is_dir($backupDir)) {
            $backups = glob($backupDir . '/*', GLOB_ONLYDIR) ?: [];
            foreach ($backups as $dir) {
                // Nom : YYYY-MM-DD_HH-II-SS
                if (preg_match('/(\d{4}-\d{2}-\d{2})_/', basename($dir), $m)) {
                    $dirDate = \Carbon\Carbon::parse($m[1]);
                    if ($dirDate->lt($limit)) {
                        static::deleteDirectory($dir);
                        $deleted++;
                        $this->line("Supprimé (backup) : " . basename($dir));
                    }
                }
            }
        }

        ActivityLogger::system('CLEANUP', "Nettoyage CNIL/RGPD : {$deleted} entrée(s) supprimée(s) (>{$limit->format('Y-m-d')})");
        $this->info("Nettoyage terminé : {$deleted} entrée(s) supprimée(s).");

        return Command::SUCCESS;
    }

    private static function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? static::deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
