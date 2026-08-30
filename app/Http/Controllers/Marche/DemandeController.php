<?php

namespace App\Http\Controllers\Marche;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Marche\Concerns\ResolveMairie;
use App\Models\Commercant;
use App\Models\MarcheCode;
use App\Models\MarcheDemande;
use App\Models\MarcheZone;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

/**
 * Demandes des commerçants souhaitant rejoindre le marché, et codes
 * d'accès au plan remis aux exposants.
 */
class DemandeController extends Controller
{
    use ResolveMairie;

    public function index(Request $request)
    {
        $mairie = $this->mairieCourante($request);

        return view('marche.demandes', [
            'mairie'   => $mairie,
            'demandes' => MarcheDemande::where('mairie_id', $mairie->id)
                ->orderByRaw("CASE statut WHEN 'en_attente' THEN 0 ELSE 1 END")
                ->orderByDesc('created_at')
                ->get(),
            'zones'    => $mairie->zonesMarche()->orderBy('nom')->get(),
            'codes'    => MarcheCode::whereIn('marche_zone_id', $mairie->zonesMarche()->pluck('id'))
                ->orderByDesc('valable_le')
                ->get(),
            'mairies'  => auth()->user()->isAdmin() ? \App\Models\Mairie::orderBy('nom')->get() : collect(),
        ]);
    }

    /** Ouvre ou ferme les inscriptions en ligne pour la commune. */
    public function basculerInscriptions(Request $request)
    {
        $mairie = $this->mairieCourante($request);
        $this->verifierEdition();

        $mairie->update(['marche_inscription_ouverte' => ! $mairie->marche_inscription_ouverte]);

        return back()->with('success', $mairie->marche_inscription_ouverte
            ? 'Les commerçants peuvent désormais demander à rejoindre votre marché.'
            : 'Les inscriptions en ligne sont fermées.');
    }

    /** Accepte la demande : le commerçant entre au registre. */
    public function accepter(Request $request, MarcheDemande $demande)
    {
        $this->verifierDemande($request, $demande);
        $this->verifierEdition();

        $demande->update(['statut' => MarcheDemande::STATUT_ACCEPTEE]);

        // Création du commerçant s'il n'existe pas déjà
        Commercant::firstOrCreate(
            ['mairie_id' => $demande->mairie_id, 'email' => $demande->email],
            [
                'prenom'              => $demande->prenom,
                'nom'                 => $demande->nom,
                'activite'            => $demande->activite,
                'telephone_indicatif' => $demande->telephone_indicatif,
                'telephone'           => $demande->telephone,
            ],
        );

        ActivityLogger::log('MARCHE', 'DEMANDE', "Demande acceptée : {$demande->nom_complet} ({$demande->activite})");

        return back()->with('success', "Demande de {$demande->nom_complet} acceptée : le commerçant a été ajouté au registre.");
    }

    public function refuser(Request $request, MarcheDemande $demande)
    {
        $this->verifierDemande($request, $demande);
        $this->verifierEdition();

        $data = $request->validate(['reponse' => 'nullable|string|max:1000']);

        $demande->update([
            'statut'  => MarcheDemande::STATUT_REFUSEE,
            'reponse' => $data['reponse'] ?? null,
        ]);

        return back()->with('success', "Demande de {$demande->nom_complet} refusée.");
    }

    /** Génère un code d'accès au plan, valable jusqu'au jour du marché. */
    public function genererCode(Request $request)
    {
        $mairie = $this->mairieCourante($request);
        $this->verifierEdition();

        $data = $request->validate([
            'marche_zone_id' => 'required|exists:marche_zones,id',
            'valable_le'     => 'required|date|after_or_equal:today',
        ]);

        $zone = MarcheZone::findOrFail($data['marche_zone_id']);
        abort_unless($zone->mairie_id === $mairie->id, 403);

        $code = MarcheCode::create([
            'marche_zone_id' => $zone->id,
            'code'           => MarcheCode::genererCode(),
            'valable_le'     => $data['valable_le'],
        ]);

        return back()->with('success', "Code « {$code->code} » créé pour le marché du " . $code->valable_le->format('d/m/Y') . '.');
    }

    public function supprimerCode(Request $request, MarcheCode $code)
    {
        $mairie = $this->mairieCourante($request);
        abort_unless($code->zone->mairie_id === $mairie->id, 403);
        $this->verifierEdition();

        $code->delete();

        return back()->with('success', 'Code supprimé.');
    }

    private function verifierDemande(Request $request, MarcheDemande $demande): void
    {
        abort_unless($demande->mairie_id === $this->mairieCourante($request)->id, 403);
    }
}
