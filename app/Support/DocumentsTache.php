<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Documents joints aux tâches : PDF, Word, tableurs, images…
 *
 * Stockés sur le disque privé et servis uniquement par une route qui vérifie
 * la visibilité de la tâche : certaines tâches sont confidentielles, un lien
 * public vers leurs pièces jointes les exposerait.
 */
class DocumentsTache
{
    public const DISQUE = 'local';

    public const MAX_FICHIERS = 5;

    /** Taille maximale d'un fichier, en kilo-octets (10 Mo). */
    public const MAX_KO = 10240;

    public const EXTENSIONS = ['pdf', 'doc', 'docx', 'odt', 'xls', 'xlsx', 'ods', 'txt', 'jpg', 'jpeg', 'png', 'webp'];

    /** Règles de validation d'un champ de dépôt (ex. « fichiers »). */
    public static function regles(string $champ): array
    {
        return [
            $champ        => 'nullable|array|max:' . self::MAX_FICHIERS,
            $champ . '.*' => 'file|max:' . self::MAX_KO . '|mimes:' . implode(',', self::EXTENSIONS),
        ];
    }

    /**
     * Enregistre les fichiers envoyés et renvoie les entrées à stocker.
     *
     * @param  UploadedFile[]  $fichiers
     */
    public static function enregistrer(array $fichiers): array
    {
        $entrees = [];

        foreach ($fichiers as $fichier) {
            $entrees[] = [
                'chemin' => $fichier->store('taches', self::DISQUE),
                'nom'    => $fichier->getClientOriginalName(),
                'taille' => $fichier->getSize(),
            ];
        }

        return $entrees;
    }

    /** Supprime du disque les fichiers d'une liste. */
    public static function supprimer(?array $entrees): void
    {
        foreach ($entrees ?? [] as $entree) {
            if (! empty($entree['chemin'])) {
                Storage::disk(self::DISQUE)->delete($entree['chemin']);
            }
        }
    }

    /** « 1,2 Mo », « 340 Ko ». */
    public static function tailleLisible(?int $octets): string
    {
        if (! $octets) {
            return '—';
        }

        return $octets >= 1048576
            ? number_format($octets / 1048576, 1, ',', ' ') . ' Mo'
            : max(1, (int) round($octets / 1024)) . ' Ko';
    }

    /** Icône selon l'extension du nom d'origine. */
    public static function icone(string $nom): string
    {
        return match (strtolower(pathinfo($nom, PATHINFO_EXTENSION))) {
            'pdf'                 => '📕',
            'doc', 'docx', 'odt'  => '📘',
            'xls', 'xlsx', 'ods'  => '📗',
            'jpg', 'jpeg', 'png', 'webp' => '🖼️',
            default               => '📄',
        };
    }
}
