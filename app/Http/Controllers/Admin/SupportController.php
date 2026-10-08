<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SupportNouveauMessage;
use App\Models\SupportDemande;
use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Onglet « Message Support » de l'administration : les demandes au
 * support technique. Les réponses partent signées « Admin », sans dire
 * quel admin a répondu.
 */
class SupportController extends Controller
{
    public function index(Request $request)
    {
        $dossier = $request->query('dossier', SupportDemande::STATUT_RECEPTION);
        if (! array_key_exists($dossier, SupportDemande::STATUTS)) {
            $dossier = SupportDemande::STATUT_RECEPTION;
        }

        $compteurs = [];
        foreach (array_keys(SupportDemande::STATUTS) as $statut) {
            $compteurs[$statut] = SupportDemande::where('statut', $statut)->count();
        }

        $demandes = SupportDemande::with('user.mairie')
            ->where('statut', $dossier)
            ->latest('updated_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.messages.index', compact('demandes', 'dossier', 'compteurs'));
    }

    public function show(SupportDemande $demande)
    {
        $demande->load('messages', 'user.mairie');

        return view('admin.messages.show', compact('demande'));
    }

    public function repondre(Request $request, SupportDemande $demande)
    {
        abort_if($demande->estCloture(), 403, 'Cette demande est clôturée.');

        $data = $request->validate([
            'corps' => 'required|string|min:2|max:5000',
        ]);

        $demande->ajouterMessage(SupportMessage::AUTEUR_ADMIN, $data['corps']);

        if ($email = $demande->emailDemandeur()) {
            try {
                Mail::to($email)->send(new SupportNouveauMessage($demande, pourAdmin: false));
            } catch (\Exception $e) {
                report($e);
            }
        }

        return redirect()->route('admin.messages.show', $demande)
            ->with('success', __('Réponse envoyée.'));
    }

    public function cloturer(SupportDemande $demande)
    {
        if (! $demande->estCloture()) {
            $demande->update(['statut' => SupportDemande::STATUT_CLOTURE, 'cloture_at' => now()]);
        }

        return redirect()->route('admin.messages.index')
            ->with('success', __('Demande clôturée.'));
    }
}
