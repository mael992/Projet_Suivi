<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\Planning;
use App\Models\PlanningLigne;
use App\Models\User;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Application Planning.
 *
 *  - Côté mairie (droit « planning_gestion ») : créer les semaines, remplir
 *    la grille agents × 7 jours, exporter en PDF.
 *  - Côté agent : consulter sa propre semaine et la signer.
 *
 * Les absences ne sont jamais recopiées dans le planning : elles sont
 * relues à l'affichage, donc une absence déclarée après coup neutralise
 * aussitôt les journées concernées.
 */
class PlanningController extends Controller
{
    public function index()
    {
        $user   = auth()->user();
        $mairie = $user->mairie;
        abort_unless($mairie, 403);

        $plannings = Planning::where('mairie_id', $mairie->id)
            ->withCount('lignes')
            ->orderByDesc('annee')->orderByDesc('semaine')
            ->get();

        return view('planning.index', [
            'mairie'    => $mairie,
            'plannings' => $plannings,
            'peutGerer' => $user->aDroit('planning_gestion'),
            'defaut'    => Planning::semaineCourante(),
        ]);
    }

    public function store(Request $request)
    {
        $mairie = $this->mairieGeree();

        $data = $request->validate([
            'annee'   => 'required|integer|min:2020|max:2100',
            'semaine' => 'required|integer|min:1|max:53',
        ]);

        $existant = Planning::where('mairie_id', $mairie->id)
            ->where('annee', $data['annee'])->where('semaine', $data['semaine'])->first();

        if ($existant) {
            return redirect()->route('planning.show', $existant)
                ->with('success', 'Cette semaine existait déjà.');
        }

        $planning = Planning::create($data + [
            'mairie_id' => $mairie->id,
            'cree_par'  => auth()->id(),
        ]);

        // Une ligne par agent de la mairie, prête à être remplie
        foreach ($this->agents($mairie->id) as $agent) {
            PlanningLigne::create(['planning_id' => $planning->id, 'user_id' => $agent->id]);
        }

        ActivityLogger::log('PLANNING', 'CREATE', "Planning créé : {$planning->libelle()}");

        return redirect()->route('planning.show', $planning)->with('success', 'Planning créé.');
    }

    public function show(Planning $planning)
    {
        $user = auth()->user();
        $this->verifierMairie($planning);

        $peutGerer = $user->aDroit('planning_gestion');

        // Un agent sans droit ne voit que sa propre ligne
        $lignes = $this->lignesAvecAbsences($planning, $peutGerer ? null : $user->id);

        abort_if($lignes->isEmpty() && ! $peutGerer, 403);

        return view('planning.show', [
            'planning'  => $planning,
            'lignes'    => $lignes,
            'dates'     => $planning->dates(),
            'peutGerer' => $peutGerer,
            'moi'       => $user,
        ]);
    }

    /** Enregistrement de la grille complète. */
    public function update(Request $request, Planning $planning)
    {
        $this->verifierMairie($planning);
        $this->verifierGestion();

        $data = $request->validate([
            'lignes'                          => 'required|array',
            // Durée due sur la semaine, saisie en « HH:MM » (ex. 35:00)
            'lignes.*.duree_contrat'          => ['nullable', 'regex:/^\d{1,2}:[0-5]\d$/'],
            'lignes.*.jours'                  => 'nullable|array',
            'lignes.*.jours.*.repos'          => 'nullable',
            'lignes.*.jours.*.creneaux'       => 'nullable|array',
            'lignes.*.jours.*.creneaux.*.*'   => 'nullable|date_format:H:i',
        ]);

        foreach ($planning->lignes as $ligne) {
            $saisie = $data['lignes'][$ligne->id] ?? null;
            if ($saisie === null) {
                continue;
            }

            $ligne->update([
                'jours'         => $this->normaliserJours($saisie['jours'] ?? []),
                'duree_contrat' => PlanningLigne::minutesEntre('00:00', $saisie['duree_contrat'] ?? null) ?: null,
                // Modifier la semaine invalide la signature déjà donnée
                'signe_at'      => null,
            ]);
        }

        ActivityLogger::log('PLANNING', 'UPDATE', "Planning enregistré : {$planning->libelle()}");

        return redirect()->route('planning.show', $planning)->with('success', 'Planning enregistré.');
    }

    public function destroy(Planning $planning)
    {
        $this->verifierMairie($planning);
        $this->verifierGestion();

        $libelle = $planning->libelle();
        $planning->delete();

        ActivityLogger::log('PLANNING', 'DELETE', "Planning supprimé : {$libelle}");

        return redirect()->route('planning.index')->with('success', 'Planning supprimé.');
    }

    /** L'agent signe sa propre semaine — personne ne signe à sa place. */
    public function signer(Planning $planning)
    {
        $this->verifierMairie($planning);

        $ligne = $planning->lignes()->where('user_id', auth()->id())->first();
        abort_unless($ligne, 403, 'Vous n\'avez pas de ligne dans ce planning.');

        $ligne->update(['signe_at' => now()]);

        ActivityLogger::log('PLANNING', 'UPDATE', "Planning signé : {$planning->libelle()}");

        return back()->with('success', 'Planning signé.');
    }

    public function pdf(Planning $planning)
    {
        $this->verifierMairie($planning);
        $this->verifierGestion();

        $pdf = Pdf::loadView('pdf.planning', [
            'planning' => $planning,
            'lignes'   => $this->lignesAvecAbsences($planning),
            'dates'    => $planning->dates(),
            'genereLe' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Planning_S' . $planning->semaine . '_' . $planning->annee . '.pdf');
    }

    // ── Helpers ──────────────────────────────────────────────────

    /**
     * Lignes du planning avec, pour chaque agent, les seules absences qui
     * chevauchent la semaine : une requête au lieu d'une par journée.
     */
    private function lignesAvecAbsences(Planning $planning, ?int $userId = null)
    {
        $lundi    = $planning->lundi()->toDateString();
        $dimanche = $planning->dimanche()->toDateString();

        return $planning->lignes()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->with(['user' => fn ($q) => $q->with([
                'absences' => fn ($a) => $a
                    ->whereDate('date_debut', '<=', $dimanche)
                    ->whereDate('date_fin', '>=', $lundi),
            ])])
            ->get()
            ->sortBy(fn (PlanningLigne $l) => [$l->user?->nom, $l->user?->prenom])
            ->values();
    }

    /** Ne garde que des journées bien formées : 7 jours, créneaux « HH:MM ». */
    private function normaliserJours(array $saisie): array
    {
        $jours = [];

        foreach (range(1, 7) as $jour) {
            $brut     = $saisie[$jour] ?? $saisie[(string) $jour] ?? [];
            $creneaux = [];

            foreach (array_slice($brut['creneaux'] ?? [], 0, PlanningLigne::CRENEAUX_PAR_JOUR) as $creneau) {
                $debut = $creneau[0] ?? null;
                $fin   = $creneau[1] ?? null;

                if ($debut && $fin) {
                    $creneaux[] = [$debut, $fin];
                }
            }

            $jours[(string) $jour] = [
                'repos'    => (bool) ($brut['repos'] ?? false),
                'creneaux' => $creneaux,
            ];
        }

        return $jours;
    }

    private function agents(int $mairieId)
    {
        return User::where('mairie_id', $mairieId)->where('role', 'user')
            ->orderBy('nom')->orderBy('prenom')->get();
    }

    private function mairieGeree()
    {
        $this->verifierGestion();
        $mairie = auth()->user()->mairie;
        abort_unless($mairie, 403);

        return $mairie;
    }

    private function verifierGestion(): void
    {
        abort_unless(auth()->user()->aDroit('planning_gestion'), 403);
    }

    private function verifierMairie(Planning $planning): void
    {
        abort_unless($planning->mairie_id === auth()->user()->mairie_id, 403);
    }
}
