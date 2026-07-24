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
            'code_postal'         => 'required|digits:5',
            'afficher_contact'    => 'nullable|boolean',
            'email'               => 'required|email',
            'telephone_indicatif' => 'nullable|string|max:8',
            'telephone'           => 'nullable|string|max:20',
            'date_fin_abonnement' => 'required|date',
        ]);

        $data['telephone_indicatif'] = $data['telephone_indicatif'] ?: '+33';
        $data['afficher_contact']    = $request->boolean('afficher_contact');

        $mairie = Mairie::create($data);

        ActivityLogger::log('MAIRIE', 'CREATE', "Mairie créée : \"{$mairie->nom}\" ({$mairie->code_postal}) — abonnement pris jusqu'au {$mairie->date_fin_abonnement->format('d/m/Y')}");

        return redirect()->route('mairies.edit', $mairie)->with('success', 'Mairie créée. Vous pouvez maintenant ajouter des observateurs.');
    }

    public function edit(Mairie $mairie)
    {
        $mairie->load('observateurs');

        // Liste déroulante des observateurs : utilisateurs (pas d'admin) ayant un
        // e-mail, et qui ne sont pas déjà observateurs d'une mairie (une seule mairie).
        $dejaObservateurs = MairieObservateur::pluck('email')->all();

        $utilisateurs = \App\Models\User::where('role', 'user')
            ->whereNotNull('email')
            ->whereNotIn('email', $dejaObservateurs)
            ->with('mairie:id,nom')
            ->orderBy('username')
            ->get(['id', 'username', 'prenom', 'nom', 'email', 'mairie_id']);

        return view('admin.mairies.edit', compact('mairie', 'utilisateurs'));
    }

    public function update(Request $request, Mairie $mairie)
    {
        $data = $request->validate([
            'nom'                 => 'required|string|max:255|unique:mairies,nom,' . $mairie->id,
            'code_postal'         => 'required|digits:5',
            'afficher_contact'    => 'nullable|boolean',
            'email'               => 'required|email',
            'telephone_indicatif' => 'nullable|string|max:8',
            'telephone'           => 'nullable|string|max:20',
            'date_fin_abonnement' => 'required|date',
        ]);

        $ancienneDate = $mairie->date_fin_abonnement?->format('d/m/Y');

        $data['telephone_indicatif'] = $data['telephone_indicatif'] ?: '+33';
        $data['afficher_contact']    = $request->boolean('afficher_contact');

        $mairie->update($data);

        $nouvelleDate = $mairie->date_fin_abonnement->format('d/m/Y');
        $detail = $ancienneDate !== $nouvelleDate
            ? "Mairie modifiée : \"{$mairie->nom}\" — abonnement prolongé jusqu'au {$nouvelleDate} (avant : {$ancienneDate})"
            : "Mairie modifiée : \"{$mairie->nom}\"";

        ActivityLogger::log('MAIRIE', 'UPDATE', $detail);

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

        // Un observateur ne peut appartenir qu'à une seule mairie
        if (MairieObservateur::where('email', $data['email'])->exists()) {
            return back()->withErrors(['email' => 'Cette adresse est déjà observatrice d\'une mairie (une seule mairie autorisée).']);
        }

        // Un compte administrateur ne peut pas être observateur
        if (\App\Models\User::where('email', $data['email'])->where('role', 'admin')->exists()) {
            return back()->withErrors(['email' => 'Un administrateur ne peut pas être observateur.']);
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
