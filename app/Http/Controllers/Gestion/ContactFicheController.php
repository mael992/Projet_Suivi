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
    public function index()
    {
        [$mairie, $contacts, $standards] = $this->donnees();

        return view('gestion.contacts.index', compact('mairie', 'contacts', 'standards'));
    }

    /** Téléchargement de la fiche contact en PDF. */
    public function pdf()
    {
        [$mairie, $contacts, $standards] = $this->donnees();

        $pdf = Pdf::loadView('pdf.contacts', compact('mairie', 'contacts', 'standards'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('Fiche_Contact_' . Str::slug($mairie->nom) . '.pdf');
    }

    public function storeStandard(Request $request)
    {
        $mairie = auth()->user()->mairie;
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

        return redirect()->route('gestion.contacts.index')->with('success', 'Numéro de standard ajouté.');
    }

    public function updateStandard(Request $request, Standard $standard)
    {
        abort_if($standard->mairie_id !== auth()->user()->mairie_id, 403);

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

        return redirect()->route('gestion.contacts.index')->with('success', 'Numéro de standard modifié.');
    }

    public function destroyStandard(Standard $standard)
    {
        abort_if($standard->mairie_id !== auth()->user()->mairie_id, 403);

        $standard->delete();

        return redirect()->route('gestion.contacts.index')->with('success', 'Numéro de standard supprimé.');
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function donnees(): array
    {
        $mairie = auth()->user()->mairie;
        abort_unless($mairie !== null, 403);

        // Annuaire automatisé depuis la gestion des utilisateurs, trié par service puis grade
        $contacts = $mairie->users()
            ->where('role', 'user')
            ->get()
            ->sortBy([
                fn ($a, $b) => ($a->service ?? 99) <=> ($b->service ?? 99),
                fn ($a, $b) => ($a->grade ?? 99) <=> ($b->grade ?? 99),
                fn ($a, $b) => strcasecmp(Str::ascii($a->nom . $a->prenom), Str::ascii($b->nom . $b->prenom)),
            ])
            ->values();

        $standards = $mairie->standards()->orderBy('service')->get();

        return [$mairie, $contacts, $standards];
    }
}
