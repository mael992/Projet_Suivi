<?php

namespace App\Http\Controllers;

use App\Models\Mairie;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;

/**
 * Centre de Messagerie : boîte de réception des messages externes d'une
 * mairie (tickets de la page « Contacter votre Mairie »).
 * - Agents de la mairie : consultent et répondent aux tickets de leur mairie.
 * - Admin : consulte tous les tickets, avec tri par mairie (lecture seule).
 * Un message envoyé ne peut être ni modifié ni supprimé.
 */
class MessagerieController extends Controller
{
    public function index(Request $request)
    {
        $user  = auth()->user();
        $admin = $user->isAdmin();

        $requete = Ticket::with(['messages.auteur', 'mairie'])
            ->where('type', 'externe')
            ->orderByDesc('updated_at');

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
            $requete->where('mairie_id', $user->mairie_id);
        }

        return view('messagerie.index', [
            'tickets'      => $requete->get(),
            'admin'        => $admin,
            'peutRepondre' => ! $admin, // admin (et observateurs) : lecture seule
            'mairies'      => $mairies,
            'filtre'       => $filtre,
        ]);
    }

    /** Un agent de la mairie répond au ticket (message ajouté, jamais modifiable). */
    public function repondre(Request $request, Ticket $ticket)
    {
        $user = auth()->user();
        abort_if($user->isAdmin(), 403); // admin en lecture seule
        abort_unless($user->mairie_id === $ticket->mairie_id, 403);

        $data = $request->validate([
            'corps' => 'required|string|min:1|max:5000',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $user->id,
            'corps'     => $data['corps'],
        ]);

        $ticket->touch();

        return redirect()->route('messagerie.index')->with('success', 'Réponse envoyée.');
    }
}
