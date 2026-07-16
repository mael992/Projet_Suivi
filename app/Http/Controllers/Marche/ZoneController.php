<?php

namespace App\Http\Controllers\Marche;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Marche\Concerns\ResolveMairie;
use App\Models\MarcheZone;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Vue aérienne de la ville : zones de marché (place, rue, trottoir…)
 * puis configuration du marché de chaque zone (disposition des exposants,
 * écart modulable, obstacles, aperçu 3D).
 */
class ZoneController extends Controller
{
    use ResolveMairie;

    /** Page d'entrée du Marché : vue aérienne + zones */
    public function ville(Request $request)
    {
        $mairie = $this->mairieCourante($request);
        $zones  = $mairie->zonesMarche()->orderBy('nom')->get();

        return view('marche.ville', [
            'mairie'  => $mairie,
            'zones'   => $zones,
            'mairies' => $this->mairiesPourSelecteur(),
        ]);
    }

    /** Changer l'image de la vue aérienne */
    public function storeImage(Request $request)
    {
        $mairie = $this->mairieCourante($request);
        $this->verifierEdition();

        $request->validate(['image' => 'required|image|max:8192']);

        if ($mairie->vue_aerienne) {
            Storage::disk('public')->delete($mairie->vue_aerienne);
        }

        $mairie->update(['vue_aerienne' => $request->file('image')->store('villes', 'public')]);

        return redirect()->route('marche.ville', $this->paramMairie($request))
            ->with('success', 'Vue aérienne mise à jour.');
    }

    public function store(Request $request)
    {
        $mairie = $this->mairieCourante($request);
        $this->verifierEdition();

        $data = $request->validate([
            'nom'     => 'required|string|max:100',
            'type'    => 'required|in:' . implode(',', array_keys(MarcheZone::TYPES)),
            'couleur' => 'nullable|string|max:20',
        ]);

        $zone = $mairie->zonesMarche()->create([
            'nom'     => $data['nom'],
            'type'    => $data['type'],
            'couleur' => $data['couleur'] ?: '#2e86de',
        ]);

        ActivityLogger::log('MARCHE', 'CREATE', "Zone de marché créée : {$zone->nom} ({$mairie->nom})");

        return redirect()->route('marche.ville', $this->paramMairie($request))
            ->with('success', "Zone « {$zone->nom} » ajoutée. Placez-la sur la vue aérienne.");
    }

    /** Sauvegarde en bloc des positions (drag / resize / rotation) */
    public function positions(Request $request)
    {
        $mairie = $this->mairieCourante($request);
        $this->verifierEdition();

        $data = $request->validate([
            'zones'                => 'required|array',
            'zones.*.id'           => 'required|integer',
            'zones.*.pos_x'        => 'required|numeric|min:0|max:100',
            'zones.*.pos_y'        => 'required|numeric|min:0|max:100',
            'zones.*.largeur_pct'  => 'required|numeric|min:1|max:100',
            'zones.*.hauteur_pct'  => 'required|numeric|min:1|max:100',
            'zones.*.rotation'     => 'required|numeric|min:-360|max:360',
        ]);

        foreach ($data['zones'] as $z) {
            MarcheZone::where('id', $z['id'])
                ->where('mairie_id', $mairie->id)
                ->update([
                    'pos_x'       => $z['pos_x'],
                    'pos_y'       => $z['pos_y'],
                    'largeur_pct' => $z['largeur_pct'],
                    'hauteur_pct' => $z['hauteur_pct'],
                    'rotation'    => $z['rotation'],
                ]);
        }

        return response()->json(['ok' => true]);
    }

    public function update(Request $request, MarcheZone $zone)
    {
        $this->verifierZone($request, $zone);
        $this->verifierEdition();

        $data = $request->validate([
            'nom'     => 'required|string|max:100',
            'type'    => 'required|in:' . implode(',', array_keys(MarcheZone::TYPES)),
            'couleur' => 'nullable|string|max:20',
        ]);

        $zone->update($data);

        return back()->with('success', 'Zone mise à jour.');
    }

    public function destroy(Request $request, MarcheZone $zone)
    {
        $this->verifierZone($request, $zone);
        $this->verifierEdition();

        $nom = $zone->nom;
        $zone->delete();

        ActivityLogger::log('MARCHE', 'DELETE', "Zone de marché supprimée : {$nom}");

        return redirect()->route('marche.ville', $this->paramMairie($request))
            ->with('success', "Zone « {$nom} » supprimée.");
    }

    /** Page de la zone : type de marché, disposition, obstacles, 3D */
    public function show(Request $request, MarcheZone $zone)
    {
        $this->verifierZone($request, $zone);

        return view('marche.zone', [
            'mairie'  => $zone->mairie,
            'zone'    => $zone,
            'mairies' => $this->mairiesPourSelecteur(),
        ]);
    }

    /** Sauvegarde de la configuration du marché de la zone (JSON) */
    public function saveConfig(Request $request, MarcheZone $zone)
    {
        $this->verifierZone($request, $zone);
        $this->verifierEdition();

        $data = $request->validate([
            'marche_type'          => 'nullable|in:' . implode(',', array_keys(MarcheZone::TYPES_MARCHE)),
            'longueur_m'           => 'required|numeric|min:5|max:2000',
            'largeur_m'            => 'required|numeric|min:3|max:2000',
            'disposition'          => 'required|in:rangee,double,grille,u,peripherie',
            'ecart'                => 'required|numeric|min:0|max:20',
            'taille_stand'         => 'required|numeric|min:1|max:12',
            'allee'                => 'required|numeric|min:2|max:30',
            'degagement'           => 'required|numeric|min:0|max:15',
            'obstacles'            => 'array',
            'obstacles.*.type'     => 'required|in:arbre,fontaine,poteau,temporaire',
            'obstacles.*.x'        => 'required|numeric|min:0',
            'obstacles.*.y'        => 'required|numeric|min:0',
        ]);

        $zone->update([
            'marche_type' => $data['marche_type'] ?? null,
            'longueur_m'  => $data['longueur_m'],
            'largeur_m'   => $data['largeur_m'],
            'config'      => [
                'disposition'  => $data['disposition'],
                'ecart'        => (float) $data['ecart'],
                'taille_stand' => (float) $data['taille_stand'],
                'allee'        => (float) $data['allee'],
                'degagement'   => (float) $data['degagement'],
                'obstacles'    => array_values($data['obstacles'] ?? []),
            ],
        ]);

        ActivityLogger::log('MARCHE', 'UPDATE', "Configuration du marché enregistrée : zone {$zone->nom}");

        return response()->json(['ok' => true]);
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function mairiesPourSelecteur()
    {
        return auth()->user()->isAdmin()
            ? \App\Models\Mairie::orderBy('nom')->get(['id', 'nom'])
            : collect();
    }

    private function verifierZone(Request $request, MarcheZone $zone): void
    {
        $mairie = $this->mairieCourante($request);
        abort_if($zone->mairie_id !== $mairie->id, 403);
    }

    private function paramMairie(Request $request): array
    {
        return $request->filled('mairie') ? ['mairie' => $request->input('mairie')] : [];
    }
}
