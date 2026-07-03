<?php

namespace App\Console\Commands;

use App\Services\ActivityLogger;
use Illuminate\Console\Command;

/**
 * Sauvegarde les logs d'activité dans un sous-dossier horodaté.
 * Planifié toutes les 48h.
 */
class BackupActivityLogs extends Command
{
    protected $signature   = 'logs:backup';
    protected $description = 'Sauvegarde les fichiers de logs d\'activité (toutes les 48h)';

    public function handle(): int
    {
        $srcDir    = storage_path('logs/activity');
        $backupDir = $srcDir . '/backups/' . now()->format('Y-m-d_H-i-s');

        if (!is_dir($srcDir)) {
            $this->info('Aucun dossier de logs à sauvegarder.');
            return Command::SUCCESS;
        }

        $files = glob($srcDir . '/activity-*.log') ?: [];

        if (empty($files)) {
            $this->info('Aucun fichier de log à sauvegarder.');
            return Command::SUCCESS;
        }

        mkdir($backupDir, 0775, true);

        $count = 0;
        foreach ($files as $file) {
            $dest = $backupDir . '/' . basename($file);
            copy($file, $dest);
            $count++;
        }

        ActivityLogger::system('BACKUP', "Sauvegarde créée : {$backupDir} ({$count} fichier(s))");
        $this->info("Sauvegarde créée : {$backupDir} ({$count} fichier(s))");

        return Command::SUCCESS;
    }
}
