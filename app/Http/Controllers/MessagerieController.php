<?php

namespace App\Http\Controllers;

use App\Mail\NouveauMessageTicket;
use App\Models\Mairie;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Centre de Messagerie : messages externes reçus par la mairie, classés en
 * dossiers (Réception, Réponse, Clôturé, Réouverture demandée).
 * - Agents de la mairie : consultent, répondent, clôturent, traitent les
 *   demandes de réouverture (selon les services qu'ils reçoivent).
 * - Admin : consulte tout, avec tri par mairie (lecture seule).
 * Un message envoyé ne peut être ni modifié ni supprimé.
 */
class MessagerieController extends Controller
{
    public function index(Request $request)
    {
        $user  = auth()->user();
        $admin = $user->isAdmin();

        $dossier = $request->input('dossier', Ticket::STATUT_RECEPTION);
        if (! array_key_exists($dossier, Ticket::STATUTS)) {
            $dossier = Ticket::STATUT_RECEPTION;
        }

        $requete = Ticket::with(['messages.auteur', 'mairie'])
            ->where('type', 'externe')
            ->visiblesPar($user)
            ->where('statut', $dossier);

        // Recherche : sujet, e-mail, nom, prénom, référence
        if ($request->filled('q')) {
            $q = $request->input('q');
            $requete->where(function ($sub) use ($q) {
                $sub->where('sujet', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('nom', 'like', "%{$q}%")
                    ->orWhere('prenom', 'like', "%{$q}%")
                    ->orWhere('reference', 'like', "%{$q}%");
            });
        }

        $mairies = collect();
        $filtre  = 'tout';

        if ($admin) {
            $mairies = Mairie::orderBy('nom')->get();
            $filtre  = $request->input('mairie', 'tout');
            if ($filtre !== 'tout') {
                $requete->where('mairie_id', (int) $filtre);
            }
        } else {
            abort_unless($user->mairie !== null, 403);
        }

        // Tri : plus récent d'abord (défaut) ou plus ancien
        $tri = $request->input('tri') === 'ancien' ? 'asc' : 'desc';
        $requete->orderBy('updated_at', $tri);

        return view('messagerie.index', [
            'tickets'      => $requete->get(),
            'admin'        => $admin,
            'peutRepondre' => ! $admin,
            'mairies'      => $mairies,
            'filtre'       => $filtre,
            'dossier'      => $dossier,
            'tri'          => $tri === 'asc' ? 'ancien' : 'recent',
            'compteurs'    => Ticket::compteursPour($user),
        ]);
    }

    /** Un agent de la mairie répond au ticket (message ajouté, jamais modifiable). */
    public function repondre(Request $request, Ticket $ticket)
    {
        $this->verifierAgent($ticket);
        abort_unless($ticket->peutEcrire(), 403, 'Cette conversation est clôturée.');

        $data = $request->validate([
            'corps'      => 'required|string|min:1|max:5000',
            'fichiers'   => 'nullable|array|max:3',
            'fichiers.*' => 'file|max:8192|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt',
        ]);

        $fichiers = [];
        foreach ($request->file('fichiers', []) as $fichier) {
            $fichiers[] = $fichier->store('tickets', 'public');
        }

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'corps'     => $data['corps'],
            'fichiers'  => $fichiers ?: null,
        ]);

        $ticket->majStatutApresMessage(deLaMairie: true);

        $this->notifierCitoyen($ticket);

        return back()->with('success', 'Réponse envoyée.');
    }

    /** La mairie clôture la conversation (lecture seule, réouverture possible 15 jours). */
    public function cloturer(Ticket $ticket)
    {
        $this->verifierAgent($ticket);

        $ticket->update([
            'statut'                  => Ticket::STATUT_CLOTURE,
            'cloture_at'              => now(),
            'cloture_par'             => 'mairie',
            'reouverture_demandee_at' => null,
            'reouverture_motif'       => null,
        ]);

        ActivityLogger::log('MESSAGERIE', 'CLOTURE', "Ticket {$ticket->reference} clôturé par la mairie");
        $this->notifierCitoyen($ticket, cloture: true);

        return back()->with('success', 'Conversation clôturée.');
    }

    /** La mairie accepte la demande de réouverture du citoyen. */
    public function accepterReouverture(Ticket $ticket)
    {
        $this->verifierAgent($ticket);
        abort_unless($ticket->statut === Ticket::STATUT_REOUVERTURE, 403);

        $ticket->update([
            'statut'                  => Ticket::STATUT_RECEPTION,
            'cloture_at'              => null,
            'cloture_par'             => null,
            'reouverture_demandee_at' => null,
        ]);

        ActivityLogger::log('MESSAGERIE', 'REOUVERTURE', "Ticket {$ticket->reference} rouvert");
        $this->notifierCitoyen($ticket);

        return back()->with('success', 'Conversation rouverte.');
    }

    /** La mairie refuse la réouverture : retour au dossier « Clôturé ». */
    public function refuserReouverture(Ticket $ticket)
    {
        $this->verifierAgent($ticket);
        abort_unless($ticket->statut === Ticket::STATUT_REOUVERTURE, 403);

        $ticket->update([
            'statut'                  => Ticket::STATUT_CLOTURE,
            'reouverture_demandee_at' => null,
        ]);

        ActivityLogger::log('MESSAGERIE', 'REOUVERTURE', "Réouverture refusée pour le ticket {$ticket->reference}");
        $this->notifierCitoyen($ticket, cloture: true);

        return back()->with('success', 'Demande de réouverture refusée.');
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function verifierAgent(Ticket $ticket): void
    {
        $user = auth()->user();
        abort_if($user->isAdmin(), 403); // admin en lecture seule
        abort_unless($user->mairie_id === $ticket->mairie_id, 403);
        // Agir sur un message : être destinataire du service, ou disposer de
        // la visibilité globale sur les messages de la mairie
        abort_unless($user->voitTousLesMessages() || $user->recoitCommunication($ticket->service), 403);
    }

    private function notifierCitoyen(Ticket $ticket, bool $cloture = false): void
    {
        try {
            Mail::to($ticket->email)->send(new NouveauMessageTicket($ticket, pourCitoyen: true, cloture: $cloture));
        } catch (\Exception $e) {
            report($e);
        }
    }
}
