<?php

namespace App\Http\Controllers;

use App\Models\Mairie;
use App\Models\MarcheCode;
use App\Models\MarcheDemande;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

/**
 * Accès public au marché :
 *  - « Je souhaite rejoindre votre marché » (si la commune l'autorise) ;
 *  - consultation du plan 2D avec le code remis aux exposants.
 */
class MarchePublicController extends Controller
{
    public function index()
    {
        return view('marche.public', [
            'mairies' => Mairie::where('marche_inscription_ouverte', true)->orderBy('nom')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'mairie_id'           => 'required|exists:mairies,id',
            'prenom'              => 'required|string|min:2|max:100',
            'nom'                 => 'required|string|min:2|max:100',
            'societe'             => 'nullable|string|max:150',
            'activite'            => 'required|string|min:2|max:100',
            'telephone_indicatif' => 'nullable|string|max:8',
            'telephone'           => 'required|string|min:6|max:20',
            'email'               => 'required|email|max:255',
            'longueur_souhaitee'  => 'nullable|numeric|min:1|max:50',
            'message'             => 'nullable|string|max:2000',
        ]);

        // La commune doit avoir ouvert les inscriptions
        $mairie = Mairie::where('id', $data['mairie_id'])
            ->where('marche_inscription_ouverte', true)
            ->firstOrFail();

        $data['telephone_indicatif'] = ($data['telephone_indicatif'] ?? '') ?: '+33';

        MarcheDemande::create($data);

        ActivityLogger::log('MARCHE', 'DEMANDE', "Demande d'inscription au marché reçue ({$mairie->nom}, {$data['activite']})");

        return redirect()->route('marche.public')
            ->with('demande_ok', 'Votre demande a bien été transmise à la mairie. Vous serez recontacté par e-mail.');
    }

    /** L'exposant saisit son code : le plan du marché s'affiche. */
    public function plan(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:8',
        ]);

        $code = MarcheCode::with('zone.mairie')
            ->where('code', strtoupper(trim($data['code'])))
            ->first();

        if (! $code) {
            return back()->withErrors(['code' => 'Ce code est inconnu. Vérifiez la saisie.'])->withInput();
        }

        if (! $code->estValide()) {
            return back()->withErrors(['code' => 'Ce code a expiré : il n\'était valable que jusqu\'au ' . $code->valable_le->format('d/m/Y') . '.'])->withInput();
        }

        return view('marche.plan-public', [
            'code' => $code,
            'zone' => $code->zone,
        ]);
    }
}
