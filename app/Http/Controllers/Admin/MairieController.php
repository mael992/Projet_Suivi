<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mairie;
use App\Models\MairieObservateur;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

/**
 * Gestionnaire des accès mairie (côté admin) :
 * liste des mairies, abonnements, et observateurs qui reçoivent une
 * copie de tous les e-mails de leur mairie.
 */
class MairieController extends Controller
{
    public function index()
    {
        $mairies = Mairie::withCount(['users', 'observateurs'])->orderBy('nom')->get();

        return view('admin.mairies.index', compact('mairies'));
    }

    public function create()
    {
        return view('admin.mairies.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom'                 => 'required|string|max:255|unique:mairies,nom',
            'email'               => 'required|email',
            'telephone_indicatif' => 'nullable|string|max:8',
            'telephone'           => 'nullable|string|max:20',
            'date_fin_abonnement' => 'required|date',
        ]);

        $data['telephone_indicatif'] = $data['telephone_indicatif'] ?: '+33';

        $mairie = Mairie::create($data);

        ActivityLogger::log('MAIRIE', 'CREATE', "Mairie créée : \"{$mairie->nom}\" (abonnement jusqu'au {$mairie->date_fin_abonnement->format('d/m/Y')})");

        return redirect()->route('mairies.edit', $mairie)->with('success', 'Mairie créée. Vous pouvez maintenant ajouter des observateurs.');
    }

    public function edit(Mairie $mairie)
    {
        $mairie->load('observateurs');

        return view('admin.mairies.edit', compact('mairie'));
    }

    public function update(Request $request, Mairie $mairie)
    {
        $data = $request->validate([
            'nom'                 => 'required|string|max:255|unique:mairies,nom,' . $mairie->id,
            'email'               => 'required|email',
            'telephone_indicatif' => 'nullable|string|max:8',
            'telephone'           => 'nullable|string|max:20',
            'date_fin_abonnement' => 'required|date',
        ]);

        $data['telephone_indicatif'] = $data['telephone_indicatif'] ?: '+33';

        $mairie->update($data);

        ActivityLogger::log('MAIRIE', 'UPDATE', "Mairie modifiée : \"{$mairie->nom}\"");

        return redirect()->route('mairies.index')->with('success', 'Mairie mise à jour.');
    }

    public function destroy(Mairie $mairie)
    {
        $nom = $mairie->nom;
        $mairie->delete();

        ActivityLogger::log('MAIRIE', 'DELETE', "Mairie supprimée : \"{$nom}\" (utilisateurs et tâches associés supprimés)");

        return redirect()->route('mairies.index')->with('success', "Mairie « {$nom} » supprimée.");
    }

    // ── Observateurs (copie de tous les mails, sans limite) ─────

    public function storeObservateur(Request $request, Mairie $mairie)
    {
        $data = $request->validate([
            'nom'   => 'nullable|string|max:255',
            'email' => 'required|email',
        ]);

        if ($mairie->observateurs()->where('email', $data['email'])->exists()) {
            return back()->withErrors(['email' => 'Cette adresse est déjà observatrice de cette mairie.']);
        }

        $mairie->observateurs()->create($data);

        ActivityLogger::log('MAIRIE', 'OBSERVATEUR', "Observateur ajouté à \"{$mairie->nom}\" : {$data['email']}");

        return redirect()->route('mairies.edit', $mairie)->with('success', 'Observateur ajouté.');
    }

    public function destroyObservateur(Mairie $mairie, MairieObservateur $observateur)
    {
        abort_if($observateur->mairie_id !== $mairie->id, 404);

        $observateur->delete();

        ActivityLogger::log('MAIRIE', 'OBSERVATEUR', "Observateur retiré de \"{$mairie->nom}\" : {$observateur->email}");

        return redirect()->route('mairies.edit', $mairie)->with('success', 'Observateur retiré.');
    }
}
