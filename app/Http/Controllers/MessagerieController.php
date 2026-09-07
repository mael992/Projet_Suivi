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

        $dossiersValides = array_merge(
            array_keys(Ticket::STATUTS),
            [Ticket::DOSSIER_TRANSFERE, Ticket::DOSSIER_ADHESION],
        );

        $dossier = $request->input('dossier', Ticket::STATUT_RECEPTION);
        if (! in_array($dossier, $dossiersValides, true)) {
            $dossier = Ticket::STATUT_RECEPTION;
        }

        if ($dossier === Ticket::DOSSIER_ADHESION) {
            // Boîte réservée à l'application Marché : ni service, ni transfert
            abort_unless(Ticket::voitAdhesionsMarche($user), 403);

            $requete = Ticket::adhesionsMarchePour($user)
                ->with(['messages.auteur', 'mairie', 'demandeMarche']);
        } else {
            $requete = Ticket::with(['messages.auteur', 'mairie'])
                ->where('type', Ticket::TYPE_EXTERNE)
                ->visiblesPar($user);

            // « Transféré » n'est pas un statut : c'est un tri transversal.
            // Les dossiers de travail, eux, ne gardent une demande transférée
            // que pour ses destinataires.
            $dossier === Ticket::DOSSIER_TRANSFERE
                ? $requete->dossierTransfere()
                : $requete->where('statut', $dossier)->dossiersDeTravail($user);
        }

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

        $tickets = $requete->get();

        // Agents joignables par mairie, pour le transfert nominatif
        $agentsMairie = \App\Models\User::where('role', 'user')
            ->whereIn('mairie_id', $tickets->pluck('mairie_id')->unique()->all() ?: [0])
            ->orderBy('nom')->orderBy('prenom')
            ->get()
            ->groupBy('mairie_id');

        return view('messagerie.index', [
            'tickets'      => $tickets,
            'agentsMairie' => $agentsMairie,
            'admin'        => $admin,
            'peutRepondre' => ! $admin,
            'mairies'      => $mairies,
            'filtre'       => $filtre,
            'dossier'      => $dossier,
            'tri'          => $tri === 'asc' ? 'ancien' : 'recent',
            'compteurs'    => Ticket::compteursPour($user),
            'voitAdhesions' => Ticket::voitAdhesionsMarche($user),
        ]);
    }

    /**
     * La mairie accepte la demande d'adhésion : le candidat rejoint le
     * registre des commerçants, et la conversation se clôture.
     */
    public function accepterAdhesion(Ticket $ticket)
    {
        $demande = $this->demandeDuTicket($ticket);

        $demande->update(['statut' => \App\Models\MarcheDemande::STATUT_ACCEPTEE]);

        \App\Models\Commercant::firstOrCreate(
            ['mairie_id' => $demande->mairie_id, 'email' => $demande->email],
            [
                'prenom'              => $demande->prenom,
                'nom'                 => $demande->nom,
                'activite'            => $demande->activite,
                'telephone_indicatif' => $demande->telephone_indicatif,
                'telephone'           => $demande->telephone,
                'longueur_defaut'     => $demande->longueur_souhaitee,
            ],
        );

        $this->cloturerAdhesion($ticket, "Demande acceptée : {$demande->nom_complet} rejoint le registre des commerçants.");

        ActivityLogger::log('MARCHE', 'DEMANDE', "Adhésion acceptée : {$demande->nom_complet} ({$demande->activite})");

        return back()->with('success', "Demande acceptée : {$demande->nom_complet} a été ajouté au registre.");
    }

    /** La mairie refuse la demande, avec un motif transmis au candidat. */
    public function refuserAdhesion(Request $request, Ticket $ticket)
    {
        $demande = $this->demandeDuTicket($ticket);

        $data = $request->validate(['reponse' => 'nullable|string|max:1000']);

        $demande->update([
            'statut'  => \App\Models\MarcheDemande::STATUT_REFUSEE,
            'reponse' => $data['reponse'] ?? null,
        ]);

        $this->cloturerAdhesion(
            $ticket,
            trim("Demande refusée. " . ($data['reponse'] ?? '')),
        );

        ActivityLogger::log('MARCHE', 'DEMANDE', "Adhésion refusée : {$demande->nom_complet}");

        return back()->with('success', "Demande de {$demande->nom_complet} refusée.");
    }

    /** Trace la décision dans la conversation puis la clôture. */
    private function cloturerAdhesion(Ticket $ticket, string $corps): void
    {
        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'corps'     => $corps,
        ]);

        $ticket->update([
            'statut'      => Ticket::STATUT_CLOTURE,
            'cloture_at'  => now(),
            'cloture_par' => 'mairie',
        ]);

        $this->notifierCitoyen($ticket);
    }

    private function demandeDuTicket(Ticket $ticket): \App\Models\MarcheDemande
    {
        $user = auth()->user();

        abort_unless($ticket->type === Ticket::TYPE_MARCHE, 404);
        abort_unless($ticket->peutEtreGerePar($user), 403);
        abort_unless($ticket->demandeMarche !== null, 404);

        return $ticket->demandeMarche;
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

    /**
     * Transfert « facteur » : le destinataire redistribue la demande à un ou
     * plusieurs services et/ou à une ou plusieurs personnes, en une seule
     * fois. Il en garde la visibilité et le suivi.
     */
    public function transferer(Request $request, Ticket $ticket)
    {
        $this->verifierAgent($ticket);
        $user = auth()->user();

        $data = $request->validate([
            'services'   => 'nullable|array',
            'services.*' => 'integer',
            'users'      => 'nullable|array',
            'users.*'    => 'integer|exists:users,id',
        ]);

        $services = array_values(array_unique(array_map('intval', $data['services'] ?? [])));
        $userIds  = array_values(array_unique(array_map('intval', $data['users'] ?? [])));

        if (! $services && ! $userIds) {
            return back()->withErrors(['transfert' => 'Choisissez au moins un service ou une personne.']);
        }

        // Les services doivent exister dans cette mairie…
        $connus = $ticket->mairie->libellesServices();
        foreach ($services as $service) {
            abort_unless(array_key_exists($service, $connus), 422);
        }

        // …et les personnes appartenir à cette mairie
        $destinataires = \App\Models\User::whereIn('id', $userIds)->get();
        foreach ($destinataires as $destinataire) {
            abort_unless($destinataire->mairie_id === $ticket->mairie_id, 403);
        }

        $ticket->update([
            'transfere_services' => $services ?: null,
            'transfere_users'    => $userIds ?: null,
            'transfere_par'      => $user->id,
            'transfere_at'       => now(),
            'statut'             => Ticket::STATUT_RECEPTION,
        ]);

        // Un même agent peut être visé nominativement ET via son service :
        // on ne le prévient qu'une fois.
        $aPrevenir = $destinataires->all();
        foreach ($services as $service) {
            foreach ($ticket->mairie->destinatairesCommunication($service) as $agent) {
                $aPrevenir[] = $agent;
            }
        }

        foreach (collect($aPrevenir)->filter->email->unique('id') as $agent) {
            try {
                Mail::to($agent->email)->send(new NouveauMessageTicket($ticket, pourCitoyen: false));
            } catch (\Exception $e) {
                report($e);
            }
        }

        $vers = $ticket->fresh()->libelleTransfert();

        ActivityLogger::log('MESSAGERIE', 'TRANSFERT', "Ticket {$ticket->reference} transféré vers « {$vers} » par {$user->username}");

        return back()->with('success', "Demande transférée vers « {$vers} ».");
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function verifierAgent(Ticket $ticket): void
    {
        // Qui voit la demande peut la traiter : destinataire du service,
        // destinataire d'un transfert, « facteur » qui l'a transmise, ou
        // visibilité globale. L'admin reste en lecture seule.
        abort_unless($ticket->peutEtreGerePar(auth()->user()), 403);
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
