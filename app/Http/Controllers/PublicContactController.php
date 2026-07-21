<?php

namespace App\Http\Controllers;

use App\Models\Mairie;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Support\Referentiel;
use Illuminate\Http\Request;

/**
 * Page publique « Contacter votre Mairie » : une personne extérieure
 * envoie une demande (ticket) à la mairie de son choix.
 */
class PublicContactController extends Controller
{
    public function create()
    {
        return view('contact.mairie', [
            'mairies'  => Mairie::where('afficher_contact', true)->orderBy('nom')->get(),
            'services' => Referentiel::SERVICES,
        ]);
    }

    public function store(Request $request)
    {
        // Champs tous obligatoires ; TrimStrings retire les espaces → « min:2 »
        // rejette les valeurs vides ou d'un seul caractère.
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

        // La mairie choisie doit être publiquement contactable
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

        // Le message initial (description) devient le premier message du fil
        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => null,
            'corps'     => $data['message'],
        ]);

        return redirect()->route('contact.mairie')
            ->with('ticket_ok', $ticket->reference);
    }
}
