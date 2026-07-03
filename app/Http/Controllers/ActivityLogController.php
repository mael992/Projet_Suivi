<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $dir   = storage_path('logs/activity');
        $files = glob($dir . '/activity-*.log') ?: [];
        rsort($files); // plus récent en premier

        // Fichier sélectionné (par défaut : le plus récent)
        $selected = $request->query('file');
        if (!$selected || !file_exists($dir . '/' . basename($selected))) {
            $selected = $files ? basename($files[0]) : null;
        }

        $lines   = [];
        $content = '';

        if ($selected && file_exists($dir . '/' . $selected)) {
            $raw   = file_get_contents($dir . '/' . $selected);
            $lines = array_filter(array_reverse(explode("\n", $raw))); // plus récent en haut
        }

        // Filtre par catégorie
        $filterCat = $request->query('cat', '');
        if ($filterCat) {
            $lines = array_filter($lines, fn($l) => str_contains($l, "[{$filterCat}]"));
        }

        // Filtre texte libre
        $filterText = $request->query('q', '');
        if ($filterText) {
            $lines = array_filter($lines, fn($l) => stripos($l, $filterText) !== false);
        }

        // Liste des sauvegardes
        $backupDir = $dir . '/backups';
        $backups   = [];
        if (is_dir($backupDir)) {
            $dirs = glob($backupDir . '/*', GLOB_ONLYDIR) ?: [];
            rsort($dirs);
            $backups = array_map('basename', $dirs);
        }

        return view('admin.logs.index', compact(
            'files', 'selected', 'lines', 'filterCat', 'filterText', 'backups'
        ));
    }

    public function download(Request $request)
    {
        $file = basename($request->query('file', ''));
        $path = storage_path('logs/activity/' . $file);

        if (!$file || !file_exists($path)) {
            abort(404);
        }

        return response()->download($path);
    }
}
