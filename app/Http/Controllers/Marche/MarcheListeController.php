<?php

namespace App\Http\Controllers\Marche;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Marche\Concerns\ResolveMairie;
use App\Models\Marche;
use App\Models\MarcheZone;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

/**
 * Navigation « option 2 » du Marché : liste de marchés datés, puis liste des
 * endroits de chacun (rue, place, trottoir…). Le plan 2D/3D reste celui de
 * la zone, atteint depuis l'endroit.
 *
 * Cette présentation cohabite avec la vue aérienne (« option 1 ») le temps
 * de trancher entre les deux.
 */
class MarcheListeController extends Controller
{
    use ResolveMairie;

    /** Niveau 1 : les marchés de la commune. */
    public function index(Request $request)
    {
        $mairie = $this->mairieCourante($request);

        return view('marche.liste.index', [
            'mairie'  => $mairie,
            'marches' => Marche::where('mairie_id', $mairie->id)
                ->withCount('endroits')
                ->orderByRaw('date_deroulement IS NULL')
                ->orderBy('date_deroulement')
                ->get(),
            'mairies' => $this->mairiesPourSelecteur(),
        ]);
    }

    /** Niveau 2 : les endroits d'un marché. */
    public function show(Request $request, Marche $marche)
    {
        $mairie = $this->mairieCourante($request);
        $this->verifierMarche($marche, $mairie);

        return view('marche.liste.show', [
            'mairie'   => $mairie,
            'marche'   => $marche,
            'endroits' => $marche->endroits()->orderBy('nom')->get(),
            'mairies'  => $this->mairiesPourSelecteur(),
        ]);
    }

    public function store(Request $request)
    {
        $mairie = $this->mairieCourante($request);
        $this->verifierEdition();

        $data = $request->validate([
            'nom'              => 'required|string|max:120',
            'date_deroulement' => 'nullable|date',
        ]);

        $marche = Marche::create($data + ['mairie_id' => $mairie->id]);

        ActivityLogger::log('MARCHE', 'CREATE', "Marché créé : {$marche->nom}");

        return redirect()->route('marche.liste.show', array_merge(['marche' => $marche->id], $this->paramMairie($request)))
            ->with('success', 'Marché créé.');
    }

    public function update(Request $request, Marche $marche)
    {
        $mairie = $this->mairieCourante($request);
        $this->verifierMarche($marche, $mairie);
        $this->verifierEdition();

        $marche->update($request->validate([
            'nom'              => 'required|string|max:120',
            'date_deroulement' => 'nullable|date',
        ]));

        ActivityLogger::log('MARCHE', 'UPDATE', "Marché modifié : {$marche->nom}");

        return back()->with('success', 'Marché mis à jour.');
    }

    public function destroy(Request $request, Marche $marche)
    {
        $mairie = $this->mairieCourante($request);
        $this->verifierMarche($marche, $mairie);
        $this->verifierEdition();

        // Les endroits survivent au marché : ils redeviennent de simples
        // zones de la vue aérienne plutôt que d'être supprimés avec leur plan.
        $marche->endroits()->update(['marche_id' => null]);
        $nom = $marche->nom;
        $marche->delete();

        ActivityLogger::log('MARCHE', 'DELETE', "Marché supprimé : {$nom}");

        return redirect()->route('marche.liste.index', $this->paramMairie($request))
            ->with('success', 'Marché supprimé — ses endroits restent disponibles sur la vue aérienne.');
    }

    /** Ajoute un endroit (= une zone) au marché. */
    public function storeEndroit(Request $request, Marche $marche)
    {
        $mairie = $this->mairieCourante($request);
        $this->verifierMarche($marche, $mairie);
        $this->verifierEdition();

        $data = $request->validate([
            'nom'  => 'required|string|max:100',
            'type' => 'required|in:' . implode(',', array_keys(MarcheZone::TYPES)),
        ]);

        MarcheZone::create($data + [
            'mairie_id' => $mairie->id,
            'marche_id' => $marche->id,
        ]);

        ActivityLogger::log('MARCHE', 'CREATE', "Endroit ajouté au marché {$marche->nom} : {$data['nom']}");

        return back()->with('success', 'Endroit ajouté.');
    }

    /** Détache un endroit du marché sans toucher à son plan. */
    public function detacherEndroit(Request $request, Marche $marche, MarcheZone $zone)
    {
        $mairie = $this->mairieCourante($request);
        $this->verifierMarche($marche, $mairie);
        $this->verifierEdition();
        abort_unless($zone->marche_id === $marche->id, 404);

        $zone->update(['marche_id' => null]);

        return back()->with('success', 'Endroit retiré du marché (son plan est conservé).');
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function verifierMarche(Marche $marche, $mairie): void
    {
        abort_unless($marche->mairie_id === $mairie->id, 403);
    }
}
