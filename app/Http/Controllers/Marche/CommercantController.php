<?php

namespace App\Http\Controllers\Marche;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Marche\Concerns\ResolveMairie;
use App\Models\Commercant;
use App\Models\Mairie;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class CommercantController extends Controller
{
    use ResolveMairie;

    public function index(Request $request)
    {
        $mairie = $this->mairieCourante($request);

        $commercants = Commercant::where('mairie_id', $mairie->id)
            ->withCount('emplacements')
            ->orderBy('nom')->orderBy('prenom')
            ->get();

        $mairies = $request->user()->isAdmin() ? Mairie::orderBy('nom')->get(['id', 'nom']) : collect();

        return view('marche.commercants.index', compact('mairie', 'commercants', 'mairies'));
    }

    public function create(Request $request)
    {
        $mairie = $this->mairieCourante($request);
        $this->verifierEdition();

        $mairies = $request->user()->isAdmin() ? Mairie::orderBy('nom')->get(['id', 'nom']) : collect();

        return view('marche.commercants.create', compact('mairie', 'mairies'));
    }

    public function store(Request $request)
    {
        $mairie = $this->mairieCourante($request);
        $this->verifierEdition();

        $data = $this->valider($request);

        $commercant = Commercant::create(['mairie_id' => $mairie->id] + $data);

        ActivityLogger::log('MARCHE', 'CREATE', "Commerçant ajouté : {$commercant->full_name} ({$commercant->activite}, {$mairie->nom})");

        return $this->retour($request, "Commerçant « {$commercant->full_name} » ajouté.");
    }

    public function update(Request $request, Commercant $commercant)
    {
        $this->verifierCommercant($request, $commercant);
        $this->verifierEdition();

        $commercant->update($this->valider($request));

        return $this->retour($request, 'Commerçant mis à jour.');
    }

    public function destroy(Request $request, Commercant $commercant)
    {
        $this->verifierCommercant($request, $commercant);
        $this->verifierEdition();

        $nom = $commercant->full_name;
        $commercant->delete();

        ActivityLogger::log('MARCHE', 'DELETE', "Commerçant supprimé : {$nom} (historique de venues supprimé)");

        return $this->retour($request, "Commerçant « {$nom} » supprimé.");
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function valider(Request $request): array
    {
        return $request->validate([
            'nom'                 => 'required|string|max:255',
            'prenom'              => 'nullable|string|max:255',
            'activite'            => 'required|string|max:255',
            'telephone_indicatif' => 'nullable|string|max:8',
            'telephone'           => 'nullable|string|max:20',
            'email'               => 'nullable|email',
            'longueur_defaut'     => 'required|numeric|min:0.5|max:999',
        ]);
    }

    private function verifierCommercant(Request $request, Commercant $commercant): void
    {
        abort_if($commercant->mairie_id !== $this->mairieCourante($request)->id, 403);
    }

    private function retour(Request $request, string $message)
    {
        $params = $request->user()->isAdmin() && $request->filled('mairie')
            ? ['mairie' => $request->integer('mairie')]
            : [];

        return redirect()->route('marche.commercants', $params)->with('success', $message);
    }
}
