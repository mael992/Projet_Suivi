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
        $mairies = Mairie::where('afficher_contact', true)->orderBy('nom')->get();

        // Services proposés par mairie (ceux qui ont au moins un destinataire)
        $servicesParMairie = [];
        foreach ($mairies as $m) {
            $servicesParMairie[$m->id] = $m->servicesContactables();
        }

        return view('contact.mairie', [
            'mairies'           => $mairies,
            'services'          => Referentiel::SERVICES,
            'servicesParMairie' => $servicesParMairie,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'mairie_id'           => 'required|exists:mairies,id',
            'service'             => 'nullable|integer|in:' . implode(',', array_keys(Referentiel::SERVICES)),
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
            'service'             => $data['service'] ?? null,
            'nom'                 => $data['nom'],
            'prenom'              => $data['prenom'],
            'telephone_indicatif' => ($data['telephone_indicatif'] ?? '') ?: '+33',
            'telephone'           => $data['telephone'],
            'email'               => $data['email'],
            'sujet'               => $data['sujet'],
            'photos'              => $photos ?: null,
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

        // Jeton simple en session pour autoriser la réponse
        session(['ticket_suivi_' . $ticket->id => true]);

        return view('contact.ticket', compact('ticket'));
    }

    public function repondreCitoyen(Request $request, Ticket $ticket)
    {
        abort_unless(session('ticket_suivi_' . $ticket->id) === true, 403);

        $data = $request->validate([
            'corps' => 'required|string|min:2|max:5000',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => null,
            'corps'     => $data['corps'],
        ]);
        $ticket->touch();

        $this->notifierMairie($ticket);

        return redirect()->route('contact.mairie')->with('ticket_ok', $ticket->reference);
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
