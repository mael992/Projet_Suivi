<?php

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Standard;
use App\Services\ActivityLogger;
use App\Support\Referentiel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Fiche contact de la mairie : annuaire automatique (alimenté par la
 * gestion des utilisateurs) + numéros de standard.
 */
class ContactFicheController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Côté admin : tri par mairie (Tout + chaque mairie), lecture globale
        if ($user->isAdmin()) {
            $mairies = \App\Models\Mairie::orderBy('nom')->get();
            $filtre  = $request->input('mairie', 'tout');

            $blocs = $filtre !== 'tout'
                ? collect([$this->blocMairie(\App\Models\Mairie::findOrFail($filtre))])
                : $mairies->map(fn ($m) => $this->blocMairie($m));

            return view('gestion.contacts.index', [
                'admin'      => true,
                'mairies'    => $mairies,
                'filtre'     => $filtre,
                'blocs'      => $blocs,
                'mairieEdit' => $filtre !== 'tout' ? (int) $filtre : null,
            ]);
        }

        abort_unless($user->aDroit('contacts_lecture'), 403);
        $mairie = $user->mairie;
        abort_unless($mairie !== null, 403);

        return view('gestion.contacts.index', [
            'admin'      => false,
            'blocs'      => collect([$this->blocMairie($mairie)]),
            'mairieEdit' => $mairie->id,
        ]);
    }

    /** Téléchargement de la fiche contact en PDF. */
    public function pdf(Request $request)
    {
        $user = auth()->user();

        $mairie = $user->isAdmin()
            ? \App\Models\Mairie::findOrFail($request->input('mairie'))
            : $user->mairie;

        abort_unless($mairie !== null, 403);
        abort_unless($user->isAdmin() || $user->aDroit('contacts_lecture'), 403);

        $bloc      = $this->blocMairie($mairie);
        $contacts  = $bloc['contacts'];
        $standards = $bloc['standards'];

        $pdf = Pdf::loadView('pdf.contacts', compact('mairie', 'contacts', 'standards'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('Fiche_Contact_' . Str::slug($mairie->nom) . '.pdf');
    }

    public function storeStandard(Request $request)
    {
        abort_unless(auth()->user()->aDroit('contacts_modification'), 403);

        $mairie = $this->mairieCible($request);
        abort_unless($mairie !== null, 403);

        $data = $request->validate([
            'service'             => 'required|integer|in:' . implode(',', array_keys(Referentiel::SERVICES)),
            'telephone_indicatif' => 'nullable|string|max:8',
            'telephone'           => 'required|string|max:20',
            'email'               => 'nullable|email|max:255',
        ]);

        $mairie->standards()->create([
            'service'             => (int) $data['service'],
            'telephone_indicatif' => $data['telephone_indicatif'] ?: '+33',
            'telephone'           => $data['telephone'],
            'email'               => $data['email'] ?? null,
        ]);

        ActivityLogger::log('CONTACT', 'CREATE', "Numéro de standard ajouté ({$mairie->nom}, service " . Referentiel::serviceLabel((int) $data['service']) . ')');

        return $this->retour($mairie)->with('success', 'Numéro de standard ajouté.');
    }

    public function updateStandard(Request $request, Standard $standard)
    {
        abort_unless(auth()->user()->aDroit('contacts_modification'), 403);
        $this->verifierProprietaire($standard);

        $data = $request->validate([
            'telephone_indicatif' => 'nullable|string|max:8',
            'telephone'           => 'required|string|max:20',
            'email'               => 'nullable|email|max:255',
        ]);

        $standard->update([
            'telephone_indicatif' => $data['telephone_indicatif'] ?: '+33',
            'telephone'           => $data['telephone'],
            'email'               => $data['email'] ?? null,
        ]);

        ActivityLogger::log('CONTACT', 'UPDATE', 'Numéro de standard modifié (service ' . $standard->service_label . ')');

        return $this->retour($standard->mairie)->with('success', 'Numéro de standard modifié.');
    }

    public function destroyStandard(Standard $standard)
    {
        abort_unless(auth()->user()->aDroit('contacts_modification'), 403);
        $this->verifierProprietaire($standard);
        $mairie = $standard->mairie;

        $standard->delete();

        return $this->retour($mairie)->with('success', 'Numéro de standard supprimé.');
    }

    // ── Helpers ──────────────────────────────────────────────────

    /** Mairie ciblée : celle de l'utilisateur, ou celle passée en paramètre pour un admin. */
    private function mairieCible(Request $request): ?\App\Models\Mairie
    {
        $user = auth()->user();

        return $user->isAdmin()
            ? \App\Models\Mairie::find($request->input('mairie_id'))
            : $user->mairie;
    }

    /** Le standard appartient bien à la mairie de l'utilisateur (admin : toute mairie). */
    private function verifierProprietaire(Standard $standard): void
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $standard->mairie_id === $user->mairie_id, 403);
    }

    /** Redirection vers la fiche, en conservant le filtre mairie côté admin. */
    private function retour(?\App\Models\Mairie $mairie)
    {
        $params = (auth()->user()->isAdmin() && $mairie) ? ['mairie' => $mairie->id] : [];

        return redirect()->route('gestion.contacts.index', $params);
    }

    /** Annuaire complet d'une mairie (contacts triés + numéros de standard). */
    private function blocMairie(\App\Models\Mairie $mairie): array
    {
        $contacts = $mairie->users()
            ->where('role', 'user')
            ->get()
            ->sortBy([
                fn ($a, $b) => ($a->service ?? 99) <=> ($b->service ?? 99),
                fn ($a, $b) => ($a->grade ?? 99) <=> ($b->grade ?? 99),
                fn ($a, $b) => strcasecmp(Str::ascii($a->nom . $a->prenom), Str::ascii($b->nom . $b->prenom)),
            ])
            ->values();

        return [
            'mairie'    => $mairie,
            'contacts'  => $contacts,
            'standards' => $mairie->standards()->orderBy('service')->get(),
        ];
    }
}
