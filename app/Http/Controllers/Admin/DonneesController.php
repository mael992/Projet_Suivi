<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mairie;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * RGPD : une mairie peut récupérer l'intégralité de ses données (export ZIP)
 * ou en demander la destruction définitive, avec attestation à l'appui.
 */
class DonneesController extends Controller
{
    public function index()
    {
        return view('admin.donnees.index', [
            'mairies' => Mairie::withCount(['users', 'taches'])->orderBy('nom')->get(),
        ]);
    }

    /** Export complet des données d'une mairie (JSON + fichiers) au format ZIP. */
    public function exporter(Mairie $mairie)
    {
        $donnees = $this->collecter($mairie);
        $nom     = 'MGDS_export_' . Str::slug($mairie->nom) . '_' . now()->format('Y-m-d_H-i');

        // Sans l'extension PHP zip, on fournit l'export en JSON : les données
        // sont complètes, seuls les fichiers joints ne sont pas empaquetés.
        if (! class_exists(\ZipArchive::class)) {
            ActivityLogger::log('RGPD', 'EXPORT', "Export JSON des données de la mairie « {$mairie->nom} » (zip indisponible)");

            return response()->streamDownload(function () use ($donnees, $mairie) {
                echo json_encode([
                    'mairie'    => $mairie->nom,
                    'genere_le' => now()->toDateTimeString(),
                    'donnees'   => $donnees,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }, $nom . '.json', ['Content-Type' => 'application/json']);
        }

        $chemin = storage_path('app/' . $nom . '.zip');

        $zip = new ZipArchive();
        $zip->open($chemin, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($donnees as $table => $lignes) {
            $zip->addFromString("donnees/{$table}.json", json_encode($lignes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        // Fichiers déposés (photos de tâches, pièces jointes, plans…)
        foreach ($this->fichiers($mairie) as $relatif) {
            $absolu = storage_path('app/public/' . $relatif);
            if (is_file($absolu)) {
                $zip->addFile($absolu, 'fichiers/' . $relatif);
            }
        }

        $zip->addFromString('LISEZ-MOI.txt', $this->lisezMoi($mairie, $donnees));
        $zip->close();

        ActivityLogger::log('RGPD', 'EXPORT', "Export complet des données de la mairie « {$mairie->nom} »");

        return response()->download($chemin, $nom . '.zip')->deleteFileAfterSend(true);
    }

    /**
     * Destruction définitive des données d'une mairie, avec attestation PDF.
     * La confirmation exige de saisir exactement le nom de la mairie.
     */
    public function detruire(Request $request, Mairie $mairie)
    {
        $request->validate([
            'confirmation' => 'required|string',
        ]);

        if (trim($request->input('confirmation')) !== $mairie->nom) {
            return back()->withErrors([
                'confirmation' => 'Le nom saisi ne correspond pas exactement à la mairie : destruction annulée.',
            ]);
        }

        $donnees = $this->collecter($mairie);
        $volumes = collect($donnees)->map(fn ($l) => count($l))->all();
        $nomMairie = $mairie->nom;
        $codePostal = $mairie->code_postal;

        // Attestation générée AVANT suppression (elle en récapitule le contenu)
        $pdf = Pdf::loadView('pdf.attestation-destruction', [
            'nomMairie'  => $nomMairie,
            'codePostal' => $codePostal,
            'volumes'    => $volumes,
            'genereLe'   => now(),
            'reference'  => 'DESTR-' . $mairie->id . '-' . now()->format('YmdHis'),
            'operateur'  => auth()->user()?->username,
        ])->setPaper('a4', 'portrait');

        // Fichiers puis données : la cascade supprime tâches, tickets, utilisateurs…
        foreach ($this->fichiers($mairie) as $relatif) {
            @unlink(storage_path('app/public/' . $relatif));
        }
        $mairie->delete();

        ActivityLogger::log('RGPD', 'DESTRUCTION', "Destruction définitive des données de la mairie « {$nomMairie} » ({$codePostal})");

        return $pdf->download('MGDS_attestation_destruction_' . Str::slug($nomMairie) . '.pdf');
    }

    // ── Helpers ──────────────────────────────────────────────────

    /** Rassemble toutes les données rattachées à la mairie. */
    private function collecter(Mairie $mairie): array
    {
        $ids = fn ($table, $colonne = 'mairie_id') => \DB::table($table)->where($colonne, $mairie->id);

        $zonesIds  = \DB::table('marche_zones')->where('mairie_id', $mairie->id)->pluck('id');
        $ticketIds = \DB::table('tickets')->where('mairie_id', $mairie->id)->pluck('id');

        return [
            'mairie'            => [$mairie->toArray()],
            'utilisateurs'      => $ids('users')->get()->map(fn ($u) => collect((array) $u)->except(['password', 'remember_token', 'temp_password'])->all())->all(),
            'services'          => $ids('mairie_services')->get()->all(),
            'observateurs'      => $ids('mairie_observateurs')->get()->all(),
            'standards'         => $ids('standards')->get()->all(),
            'taches'            => $ids('taches')->get()->all(),
            'commercants'       => $ids('commercants')->get()->all(),
            'zones_marche'      => $ids('marche_zones')->get()->all(),
            'codes_marche'      => \DB::table('marche_codes')->whereIn('marche_zone_id', $zonesIds)->get()->all(),
            'demandes_marche'   => $ids('marche_demandes')->get()->all(),
            'tickets'           => $ids('tickets')->get()->all(),
            'messages_tickets'  => \DB::table('ticket_messages')->whereIn('ticket_id', $ticketIds)->get()->all(),
        ];
    }

    /** Chemins (relatifs au disque public) des fichiers de la mairie. */
    private function fichiers(Mairie $mairie): array
    {
        $fichiers = [];

        if ($mairie->vue_aerienne) {
            $fichiers[] = $mairie->vue_aerienne;
        }

        foreach (\DB::table('taches')->where('mairie_id', $mairie->id)->get(['photo_avant', 'photo_apres']) as $t) {
            $fichiers[] = $t->photo_avant;
            $fichiers[] = $t->photo_apres;
        }

        $ticketIds = \DB::table('tickets')->where('mairie_id', $mairie->id)->pluck('id');

        foreach (\DB::table('tickets')->where('mairie_id', $mairie->id)->pluck('photos') as $json) {
            foreach (json_decode($json ?? '[]', true) ?: [] as $photo) {
                $fichiers[] = $photo;
            }
        }
        foreach (\DB::table('ticket_messages')->whereIn('ticket_id', $ticketIds)->pluck('fichiers') as $json) {
            foreach (json_decode($json ?? '[]', true) ?: [] as $f) {
                $fichiers[] = $f;
            }
        }

        return array_values(array_filter(array_unique($fichiers)));
    }

    private function lisezMoi(Mairie $mairie, array $donnees): string
    {
        $lignes = [
            "Export des données — MGDS",
            "Mairie : {$mairie->nom} ({$mairie->code_postal})",
            'Généré le ' . now()->format('d/m/Y à H:i'),
            '',
            'Contenu :',
        ];

        foreach ($donnees as $table => $valeurs) {
            $lignes[] = sprintf('  - donnees/%s.json : %d enregistrement(s)', $table, count($valeurs));
        }

        $lignes[] = '  - fichiers/ : photos, pièces jointes et plans déposés';
        $lignes[] = '';
        $lignes[] = 'Les mots de passe ne sont jamais exportés.';

        return implode("\n", $lignes);
    }
}
