<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Rappel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Pense-bête intelligent : onglet Calendrier (rappels datés + email le jour J)
 * et onglet Notes (style boîte mail, avec sous-dossiers).
 * Chaque utilisateur ne voit que SES rappels et SES notes.
 */
class PenseBeteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Mois affiché par le calendrier (?mois=YYYY-MM)
        try {
            $mois = Carbon::createFromFormat('Y-m', $request->input('mois', now()->format('Y-m')))->startOfMonth();
        } catch (\Exception) {
            $mois = now()->startOfMonth();
        }

        $rappelsDuMois = $user->hasMany(Rappel::class)
            ->whereBetween('date_rappel', [$mois->copy()->startOfMonth(), $mois->copy()->endOfMonth()])
            ->orderBy('date_rappel')
            ->get()
            ->groupBy(fn ($r) => $r->date_rappel->format('Y-m-d'));

        // Recherche : mot-clé et/ou plage de dates
        $resultats = collect();
        $enRecherche = $request->filled('q') || $request->filled('du') || $request->filled('au');
        if ($enRecherche) {
            $resultats = $user->hasMany(Rappel::class)
                ->when($request->filled('q'), fn ($q) => $q->where('texte', 'like', '%' . $request->q . '%'))
                ->when($request->filled('du'), fn ($q) => $q->whereDate('date_rappel', '>=', $request->du))
                ->when($request->filled('au'), fn ($q) => $q->whereDate('date_rappel', '<=', $request->au))
                ->orderBy('date_rappel')
                ->get();
        }

        // Tri des notes : par date de création (récent → ancien) ou alphabétique
        $tri   = $request->input('tri') === 'alpha' ? 'alpha' : 'date';
        $notes = $user->hasMany(Note::class)
            ->orderBy('dossier')
            ->when($tri === 'alpha', fn ($q) => $q->orderBy('titre'), fn ($q) => $q->orderByDesc('created_at'))
            ->get();

        // Compteurs « aujourd'hui » pour les bulles bleues
        $auj             = now()->toDateString();
        $badgeCalendrier = $user->hasMany(Rappel::class)->whereDate('date_rappel', $auj)->count();
        $badgeNotes      = $user->hasMany(Note::class)->where('notifier', true)->whereDate('date_notification', $auj)->count();

        return view('pensebete.index', [
            'mois'            => $mois,
            'rappelsDuMois'   => $rappelsDuMois,
            'resultats'       => $resultats,
            'enRecherche'     => $enRecherche,
            'notes'           => $notes,
            'tri'             => $tri,
            'dossiers'        => $notes->pluck('dossier')->filter()->unique()->sort()->values(),
            'badgeCalendrier' => $badgeCalendrier,
            'badgeNotes'      => $badgeNotes,
        ]);
    }

    // ── Calendrier ───────────────────────────────────────────────

    public function storeRappel(Request $request)
    {
        $data = $request->validate([
            'date_rappel' => 'required|date',
            'texte'       => 'nullable|string|max:3000',
            'fichier'     => 'nullable|file|max:8192|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt',
        ]);

        $rappel = new Rappel([
            'date_rappel' => $data['date_rappel'],
            'texte'       => $data['texte'] ?? null,
        ]);
        $rappel->user_id = auth()->id();

        if ($request->hasFile('fichier')) {
            $rappel->fichier = $request->file('fichier')->store('pensebete', 'public');
        }

        $rappel->save();

        return redirect()->route('pensebete.index', ['mois' => $rappel->date_rappel->format('Y-m')])
            ->with('success', 'Rappel enregistré pour le ' . $rappel->date_rappel->format('d/m/Y') . '. Un email vous sera envoyé ce jour-là.');
    }

    public function destroyRappel(Rappel $rappel)
    {
        abort_unless($rappel->user_id === auth()->id(), 403);

        if ($rappel->fichier) {
            Storage::disk('public')->delete($rappel->fichier);
        }
        $rappel->delete();

        return redirect()->route('pensebete.index')->with('success', 'Rappel supprimé.');
    }

    // ── Notes ────────────────────────────────────────────────────

    public function storeNote(Request $request)
    {
        $data = $this->validerNote($request);

        $note = new Note([
            'dossier'           => $data['dossier'] ?? null,
            'titre'             => $data['titre'],
            'contenu'           => $data['contenu'] ?? null,
            'notifier'          => $data['notifier'],
            'date_notification' => $data['notifier'] ? $data['date_notification'] : null,
        ]);
        $note->user_id = auth()->id();

        if ($request->hasFile('image')) {
            $note->image = $request->file('image')->store('pensebete', 'public');
        }

        $note->save();

        return redirect()->route('pensebete.index', ['onglet' => 'notes'])->with('success', 'Note ajoutée.');
    }

    public function updateNote(Request $request, Note $note)
    {
        abort_unless($note->user_id === auth()->id(), 403);

        $data = $this->validerNote($request);

        $note->dossier           = $data['dossier'] ?? null;
        $note->titre             = $data['titre'];
        $note->contenu           = $data['contenu'] ?? null;
        $note->notifier          = $data['notifier'];
        $note->date_notification = $data['notifier'] ? $data['date_notification'] : null;
        if (! $data['notifier']) {
            $note->notifiee = false;
        }

        if ($request->hasFile('image')) {
            if ($note->image) {
                Storage::disk('public')->delete($note->image);
            }
            $note->image = $request->file('image')->store('pensebete', 'public');
        }

        $note->save();

        return redirect()->route('pensebete.index', ['onglet' => 'notes'])->with('success', 'Note modifiée.');
    }

    public function destroyNote(Note $note)
    {
        abort_unless($note->user_id === auth()->id(), 403);

        if ($note->image) {
            Storage::disk('public')->delete($note->image);
        }
        $note->delete();

        return redirect()->route('pensebete.index', ['onglet' => 'notes'])->with('success', 'Note supprimée.');
    }

    private function validerNote(Request $request): array
    {
        $data = $request->validate([
            'titre'             => 'required|string|max:150',
            'dossier'           => 'nullable|string|max:100',
            'contenu'           => 'nullable|string|max:10000',
            'image'             => 'nullable|image|max:8192',
            'notifier'          => 'nullable|boolean',
            'date_notification' => 'nullable|required_if:notifier,1|date',
        ]);

        $data['notifier'] = $request->boolean('notifier');

        return $data;
    }
}
