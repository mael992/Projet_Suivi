<?php

namespace App\Http\Controllers;

use App\Mail\NouveauMessageTicket;
use App\Models\Mairie;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Support\Referentiel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Page publique « Contacter votre Mairie » : une personne extérieure
 * envoie une demande (ticket) à la mairie de son choix, puis peut suivre
 * son ticket (numéro + e-mail).
 */
class PublicContactController extends Controller
{
    public function create()
    {
        // L'habitant choisit uniquement sa commune : c'est la mairie qui
        // oriente ensuite la demande vers le bon service (« facteur »).
        return view('contact.mairie', [
            'mairies' => Mairie::where('afficher_contact', true)->orderBy('nom')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'mairie_id'           => 'required|exists:mairies,id',
            // Plus de choix de service côté habitant : la mairie oriente elle-même
            'nom'                 => 'required|string|min:2|max:100',
            'prenom'              => 'required|string|min:2|max:100',
            'telephone_indicatif' => 'nullable|string|max:8',
            'telephone'           => 'required|string|min:6|max:20',
            'email'               => 'required|email|max:255',
            'sujet'               => 'required|string|min:2|max:150',
            'message'             => 'required|string|min:2|max:5000',
            'photos'              => 'nullable|array|max:2',
            'photos.*'            => 'image|max:8192',
        ]);

        $mairie = Mairie::where('id', $data['mairie_id'])->where('afficher_contact', true)->firstOrFail();

        $photos = [];
        foreach ($request->file('photos', []) as $photo) {
            $photos[] = $photo->store('tickets', 'public');
        }

        $ticket = Ticket::create([
            'mairie_id'           => $mairie->id,
            'reference'           => Ticket::genererReference($mairie->id),
            'type'                => 'externe',
            'service'             => null,
            'nom'                 => $data['nom'],
            'prenom'              => $data['prenom'],
            'telephone_indicatif' => ($data['telephone_indicatif'] ?? '') ?: '+33',
            'telephone'           => $data['telephone'],
            'email'               => $data['email'],
            'sujet'               => $data['sujet'],
            'photos'              => $photos ?: null,
            // Toujours explicite : le ticket arrive dans le dossier « Réception »
            'statut'              => Ticket::STATUT_RECEPTION,
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => null,
            'corps'     => $data['message'],
        ]);

        $this->notifierMairie($ticket);

        return redirect()->route('contact.mairie')->with('ticket_ok', $ticket->reference);
    }

    // ── Suivi d'un ticket existant (« J'ai déjà un ticket ») ─────

    public function suivi(Request $request)
    {
        $data = $request->validate([
            'reference' => 'required|string',
            'email'     => 'required|email',
        ]);

        $ticket = Ticket::where('reference', $data['reference'])
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($data['email'])])
            ->with('messages.auteur')
            ->first();

        if (! $ticket) {
            return back()->withErrors(['ticket' => 'Les données saisies sont erronées. Merci de vérifier votre numéro de ticket et votre adresse e-mail.'])
                ->withInput();
        }

        // Jeton simple en session pour autoriser la consultation et la réponse
        session(['ticket_suivi_' . $ticket->id => true]);

        // Redirection en GET : la page reste rafraîchissable (F5, auto-refresh)
        return redirect()->route('contact.ticket.voir', $ticket);
    }

    /** Affiche la conversation d'un ticket déjà authentifié en session. */
    public function voirTicket(Ticket $ticket)
    {
        abort_unless(session('ticket_suivi_' . $ticket->id) === true, 403);

        $ticket->load('messages.auteur');

        return view('contact.ticket', compact('ticket'));
    }

    public function repondreCitoyen(Request $request, Ticket $ticket)
    {
        abort_unless(session('ticket_suivi_' . $ticket->id) === true, 403);

        $data = $request->validate([
            'corps'      => 'required|string|min:2|max:5000',
            'fichiers'   => 'nullable|array|max:3',
            'fichiers.*' => 'file|max:8192|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt',
        ]);

        $fichiers = [];
        foreach ($request->file('fichiers', []) as $fichier) {
            $fichiers[] = $fichier->store('tickets', 'public');
        }

        abort_unless($ticket->peutEcrire(), 403, 'Cette conversation est clôturée.');

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => null,
            'corps'     => $data['corps'],
            'fichiers'  => $fichiers ?: null,
        ]);
        $ticket->majStatutApresMessage(deLaMairie: false);

        $this->notifierMairie($ticket);

        // On reste sur la conversation : le citoyen voit son message envoyé
        return redirect()->route('contact.ticket.voir', $ticket)
            ->with('success', 'Votre message a bien été envoyé à la mairie.');
    }

    /** Le citoyen clôture lui-même sa demande (il n'a plus besoin d'aide). */
    public function cloturerCitoyen(Ticket $ticket)
    {
        abort_unless(session('ticket_suivi_' . $ticket->id) === true, 403);
        abort_unless($ticket->peutEcrire(), 403);

        $ticket->update([
            'statut'      => Ticket::STATUT_CLOTURE,
            'cloture_at'  => now(),
            'cloture_par' => 'citoyen',
        ]);

        return redirect()->route('contact.mairie')
            ->with('ticket_ok', $ticket->reference)
            ->with('ticket_message', 'Votre demande a été clôturée. Vous pouvez encore demander sa réouverture pendant ' . Ticket::JOURS_REOUVERTURE . ' jours.');
    }

    /** Le citoyen demande la réouverture d'une conversation clôturée (15 jours max). */
    public function demanderReouverture(Request $request, Ticket $ticket)
    {
        abort_unless(session('ticket_suivi_' . $ticket->id) === true, 403);

        if (! $ticket->reouverturePossible()) {
            return back()->withErrors(['reouverture' => 'Le délai de ' . Ticket::JOURS_REOUVERTURE . ' jours est dépassé : cette conversation ne peut plus être rouverte.']);
        }

        $data = $request->validate([
            'motif' => 'required|string|min:2|max:1000',
        ]);

        $ticket->update([
            'statut'                  => Ticket::STATUT_REOUVERTURE,
            'reouverture_demandee_at' => now(),
            'reouverture_motif'       => $data['motif'],
        ]);

        $this->notifierMairie($ticket);

        return redirect()->route('contact.mairie')
            ->with('ticket_ok', $ticket->reference)
            ->with('ticket_message', 'Votre demande de réouverture a été transmise à la mairie.');
    }

    // ── Helpers ──────────────────────────────────────────────────

    /** Prévient par e-mail les agents de la mairie qui reçoivent ce service. */
    private function notifierMairie(Ticket $ticket): void
    {
        foreach ($ticket->mairie->destinatairesCommunication($ticket->service) as $agent) {
            try {
                Mail::to($agent->email)->send(new NouveauMessageTicket($ticket, pourCitoyen: false));
            } catch (\Exception $e) {
                report($e);
            }
        }
    }
}
