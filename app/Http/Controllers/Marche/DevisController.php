<?php

namespace App\Http\Controllers\Marche;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Marche\Concerns\ResolveMairie;
use App\Models\Commercant;
use App\Models\Devis;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Estimations du Marché : une mairie chiffre une prestation pour un
 * commerçant (emplacement, branchement, services annexes…).
 *
 * Ce n'est pas un devis d'abonnement adressé à la mairie — cette lecture-là
 * a été abandonnée : l'onglet vit désormais dans l'application Marché.
 */
class DevisController extends Controller
{
    use ResolveMairie;

    public function index(Request $request)
    {
        $mairie = $this->mairieCourante($request);

        return view('marche.devis.index', [
            'mairie'      => $mairie,
            'devis'       => Devis::with('commercant')->where('mairie_id', $mairie->id)
                                ->orderByDesc('date_devis')->orderByDesc('id')->get(),
            'commercants' => Commercant::where('mairie_id', $mairie->id)
                                ->orderBy('nom')->orderBy('prenom')->get(),
            'editeur'     => config('mgds.editeur'),
            'mairies'     => $this->mairiesPourSelecteur(),
        ]);
    }

    public function store(Request $request)
    {
        $mairie = $this->mairieCourante($request);
        $this->verifierEdition();

        // Les lignes laissées vides sont retirées avant validation
        $request->merge([
            'lignes' => array_values(array_filter(
                $request->input('lignes', []),
                fn ($l) => trim($l['designation'] ?? '') !== '',
            )),
        ]);

        $data = $request->validate([
            'commercant_id'           => 'nullable|exists:commercants,id',
            'client_nom'              => 'required|string|max:255',
            'client_adresse'          => 'nullable|string|max:255',
            'client_email'            => 'nullable|email|max:255',
            'date_devis'              => 'required|date',
            'validite_jours'          => 'required|integer|min:1|max:365',
            'lieu_execution'          => 'nullable|string|max:255',
            'delai_execution'         => 'nullable|string|max:255',
            'conditions'              => 'nullable|string|max:2000',
            'modalites_paiement'      => 'nullable|string|max:1000',
            'taux_tva'                => 'required|numeric|min:0|max:30',
            'lignes'                  => 'required|array|min:1',
            'lignes.*.designation'    => 'required|string|max:255',
            'lignes.*.quantite'       => 'required|numeric|min:0',
            'lignes.*.prix_unitaire'  => 'required|numeric|min:0',
        ]);

        if ($data['commercant_id'] ?? null) {
            $commercant = Commercant::findOrFail($data['commercant_id']);
            abort_unless($commercant->mairie_id === $mairie->id, 403);
        }

        $devis = Devis::create($data + [
            'mairie_id' => $mairie->id,
            'reference' => Devis::genererReference(),
        ]);

        ActivityLogger::log('DEVIS', 'CREATE', "Estimation {$devis->reference} créée pour « {$devis->client_nom} » (" . number_format($devis->totalTtc(), 2, ',', ' ') . ' € TTC)');

        return redirect()->route('marche.devis.index', $this->paramMairie($request))
            ->with('success', "Estimation {$devis->reference} créée.");
    }

    public function pdf(Request $request, Devis $devis)
    {
        $this->verifierDevis($request, $devis);

        return Pdf::loadView('pdf.devis', [
            'devis'   => $devis,
            'editeur' => config('mgds.editeur'),
        ])->setPaper('a4', 'portrait')
          ->download('Estimation_' . $devis->reference . '.pdf');
    }

    public function statut(Request $request, Devis $devis)
    {
        $this->verifierDevis($request, $devis);
        $this->verifierEdition();

        $data = $request->validate([
            'statut' => 'required|in:' . implode(',', array_keys(Devis::STATUTS)),
        ]);

        $devis->update($data);

        ActivityLogger::log('DEVIS', 'UPDATE', "Estimation {$devis->reference} : statut « {$data['statut']} »");

        return back()->with('success', 'Statut mis à jour.');
    }

    public function destroy(Request $request, Devis $devis)
    {
        $this->verifierDevis($request, $devis);
        $this->verifierEdition();

        $reference = $devis->reference;
        $devis->delete();

        ActivityLogger::log('DEVIS', 'DELETE', "Estimation {$reference} supprimée");

        return redirect()->route('marche.devis.index', $this->paramMairie($request))
            ->with('success', "Estimation {$reference} supprimée.");
    }

    private function verifierDevis(Request $request, Devis $devis): void
    {
        $mairie = $this->mairieCourante($request);
        abort_unless($devis->mairie_id === $mairie->id, 403);
    }
}
