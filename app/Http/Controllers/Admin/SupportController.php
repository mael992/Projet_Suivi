<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SupportNouveauMessage;
use App\Models\SupportDemande;
use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Réponses de l'équipe MGDS aux demandes au support technique. Les
 * conversations se lisent dans Centre de messagerie / Message Support ; les
 * réponses partent signées « Admin », sans dire quel admin a répondu.
 */
class SupportController extends Controller
{
    /** Ancienne page de Paramètres administratifs : tout est dans la messagerie. */
    public function index(Request $request)
    {
        $dossier = $request->query('dossier');

        return redirect()->route('messagerie.index', array_filter([
            'onglet'  => 'support',
            'support' => array_key_exists((string) $dossier, SupportDemande::STATUTS) ? $dossier : null,
        ]));
    }

    public function show(SupportDemande $demande)
    {
        return redirect($demande->lienMessagerie());
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

        return redirect($demande->lienMessagerie())
            ->with('success', __('Réponse envoyée.'));
    }

    public function cloturer(SupportDemande $demande)
    {
        if (! $demande->estCloture()) {
            $demande->update(['statut' => SupportDemande::STATUT_CLOTURE, 'cloture_at' => now()]);
        }

        return redirect($demande->lienMessagerie())
            ->with('success', __('Demande clôturée.'));
    }
}
