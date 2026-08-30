<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Devis;
use App\Models\Mairie;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Devis d'abonnement MGDS adressés aux mairies, avec les mentions
 * obligatoires d'un devis de prestation de service.
 */
class DevisController extends Controller
{
    public function index()
    {
        return view('admin.devis.index', [
            'devis'   => Devis::with('mairie')->orderByDesc('date_devis')->orderByDesc('id')->get(),
            'mairies' => Mairie::orderBy('nom')->get(),
            'editeur' => config('mgds.editeur'),
        ]);
    }

    public function store(Request $request)
    {
        // Les lignes laissées vides sont retirées avant validation
        $request->merge([
            'lignes' => array_values(array_filter(
                $request->input('lignes', []),
                fn ($l) => trim($l['designation'] ?? '') !== '',
            )),
        ]);

        $data = $request->validate([
            'mairie_id'               => 'nullable|exists:mairies,id',
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

        $data['reference'] = Devis::genererReference();

        $devis = Devis::create($data);

        ActivityLogger::log('DEVIS', 'CREATE', "Devis {$devis->reference} créé pour « {$devis->client_nom} » (" . number_format($devis->totalTtc(), 2, ',', ' ') . ' € TTC)');

        return redirect()->route('admin.devis.index')->with('success', "Devis {$devis->reference} créé.");
    }

    public function pdf(Devis $devis)
    {
        $pdf = Pdf::loadView('pdf.devis', [
            'devis'   => $devis,
            'editeur' => config('mgds.editeur'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Devis_' . $devis->reference . '_' . Str::slug($devis->client_nom) . '.pdf');
    }

    public function statut(Request $request, Devis $devis)
    {
        $data = $request->validate([
            'statut' => 'required|in:' . implode(',', array_keys(Devis::STATUTS)),
        ]);

        $devis->update($data);

        return back()->with('success', "Devis {$devis->reference} : " . Devis::STATUTS[$data['statut']] . '.');
    }

    public function destroy(Devis $devis)
    {
        $reference = $devis->reference;
        $devis->delete();

        return back()->with('success', "Devis {$reference} supprimé.");
    }
}
