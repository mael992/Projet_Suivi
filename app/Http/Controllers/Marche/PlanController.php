<?php

namespace App\Http\Controllers\Marche;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Marche\Concerns\ResolveMairie;
use App\Models\Commercant;
use App\Models\Mairie;
use App\Models\MarcheAxe;
use App\Models\MarcheEmplacement;
use App\Models\MarchePlan;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    use ResolveMairie;

    /** Mini-onglet 🗺️ Plan : plans datés + rendu 2D des axes */
    public function index(Request $request)
    {
        $mairie = $this->mairieCourante($request);

        $plans = MarchePlan::where('mairie_id', $mairie->id)
            ->orderByDesc('date')
            ->get();

        $plan = $request->filled('plan')
            ? $plans->firstWhere('id', $request->integer('plan'))
            : $plans->first();

        $plan?->load(['axes.emplacements.commercant', 'emplacements.commercant']);

        $commercants = Commercant::where('mairie_id', $mairie->id)
            ->orderBy('nom')->orderBy('prenom')
            ->get();

        $mairies = $request->user()->isAdmin() ? Mairie::orderBy('nom')->get(['id', 'nom']) : collect();

        return view('marche.plan', compact('mairie', 'plans', 'plan', 'commercants', 'mairies'));
    }

    // ── Plans (datés, modifiables) ───────────────────────────────

    public function storePlan(Request $request)
    {
        $mairie = $this->mairieCourante($request);
        $this->verifierEdition();

        $data = $request->validate([
            'nom'  => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        $plan = MarchePlan::create([
            'mairie_id' => $mairie->id,
            'nom'       => $data['nom'],
            'date'      => $data['date'],
        ]);

        ActivityLogger::log('MARCHE', 'CREATE', "Plan de marché créé : \"{$plan->nom}\" du {$plan->date->format('d/m/Y')} ({$mairie->nom})");

        return $this->retourPlan($request, $plan, 'Plan créé. Ajoutez maintenant les axes (trottoirs, allées…).');
    }

    public function updatePlan(Request $request, MarchePlan $plan)
    {
        $this->verifierPlan($request, $plan);
        $this->verifierEdition();

        $data = $request->validate([
            'nom'  => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        $plan->update($data);

        return $this->retourPlan($request, $plan, 'Plan mis à jour.');
    }

    public function destroyPlan(Request $request, MarchePlan $plan)
    {
        $this->verifierPlan($request, $plan);
        $this->verifierEdition();

        $nom = $plan->nom;
        $plan->delete();

        ActivityLogger::log('MARCHE', 'DELETE', "Plan de marché supprimé : \"{$nom}\"");

        return redirect()->route('marche.plan', $this->paramsMairie($request))
            ->with('success', 'Plan supprimé.');
    }

    // ── Axes ─────────────────────────────────────────────────────

    public function storeAxe(Request $request, MarchePlan $plan)
    {
        $this->verifierPlan($request, $plan);
        $this->verifierEdition();

        $data = $request->validate([
            'nom'      => 'required|string|max:255',
            'longueur' => 'required|numeric|min:1|max:9999',
        ]);

        $plan->axes()->create($data);

        return $this->retourPlan($request, $plan, "Axe « {$data['nom']} » ajouté ({$data['longueur']} m).");
    }

    public function destroyAxe(Request $request, MarcheAxe $axe)
    {
        $this->verifierPlan($request, $axe->plan);
        $this->verifierEdition();

        $plan = $axe->plan;
        $axe->delete();

        return $this->retourPlan($request, $plan, 'Axe supprimé.');
    }

    // ── Emplacements (placement des exposants) ───────────────────

    public function storeEmplacement(Request $request, MarcheAxe $axe)
    {
        $this->verifierPlan($request, $axe->plan);
        $this->verifierEdition();

        $data = $request->validate([
            'commercant_id' => 'required|exists:commercants,id',
            'longueur'      => 'nullable|numeric|min:0.5|max:999',
            'position'      => 'nullable|numeric|min:0',
            'montant'       => 'nullable|numeric|min:0',
        ]);

        $commercant = Commercant::findOrFail($data['commercant_id']);
        abort_if($commercant->mairie_id !== $axe->plan->mairie_id, 403);

        $axe->load('emplacements');

        $longueur = (float) ($data['longueur'] ?? $commercant->longueur_defaut);
        $position = array_key_exists('position', $data) && $data['position'] !== null
            ? (float) $data['position']
            : $axe->finDernierStand();

        $emplacement = $axe->emplacements()->create([
            'commercant_id' => $commercant->id,
            'position'      => $position,
            'longueur'      => $longueur,
            'montant'       => $data['montant'] ?? null,
        ]);

        ActivityLogger::log('MARCHE', 'PLACE', "Exposant placé : {$commercant->full_name} sur \"{$axe->nom}\" ({$longueur} m, plan du {$axe->plan->date->format('d/m/Y')})");

        // Espace restant à gauche / à droite du nouvel exposant
        [$gauche, $droite] = $this->espacesAutour($axe->fresh('emplacements'), $emplacement->fresh());

        $messages = ["{$commercant->full_name} placé sur « {$axe->nom} » : il reste {$gauche} m à gauche et {$droite} m à droite."];

        // ⚠️ mini warning si ça dépasse un peu d'un côté
        $fin = $position + $longueur;
        if ($fin > (float) $axe->longueur) {
            $depassement = round($fin - (float) $axe->longueur, 2);
            session()->flash('warning', "⚠️ Attention : ça dépasse de {$depassement} m au bout de « {$axe->nom} ».");
        }
        if ($this->chevauche($axe->fresh('emplacements'), $emplacement->fresh())) {
            session()->flash('warning', '⚠️ Attention : ce stand chevauche un autre emplacement, vérifiez les positions.');
        }

        return $this->retourPlan($request, $axe->plan, implode(' ', $messages));
    }

    public function updateEmplacement(Request $request, MarcheEmplacement $emplacement)
    {
        $this->verifierPlan($request, $emplacement->axe->plan);
        $this->verifierEdition();

        $data = $request->validate([
            'position' => 'required|numeric|min:0',
            'longueur' => 'required|numeric|min:0.5|max:999',
            'montant'  => 'nullable|numeric|min:0',
        ]);

        $emplacement->update($data);

        return $this->retourPlan($request, $emplacement->axe->plan, 'Emplacement mis à jour.');
    }

    // ── Emplacements 2D (posés sur le fond de plan) ──────────────

    /** Changer le fond de plan (image de la place) */
    public function storeImage(Request $request, MarchePlan $plan)
    {
        $this->verifierPlan($request, $plan);
        $this->verifierEdition();

        $request->validate(['image' => 'required|image|max:10240']);

        if ($plan->image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($plan->image);
        }

        $plan->update(['image' => $request->file('image')->store('plans', 'public')]);

        return $this->retourPlan($request, $plan, 'Fond de plan mis à jour.');
    }

    /** Poser un carré (emplacement) sur le fond de plan */
    public function storeStand(Request $request, MarchePlan $plan)
    {
        $this->verifierPlan($request, $plan);
        $this->verifierEdition();

        $data = $request->validate([
            'commercant_id' => 'nullable|exists:commercants,id',
            'label'         => 'nullable|string|max:50',
            'pos_x'         => 'required|numeric|min:0|max:100',
            'pos_y'         => 'required|numeric|min:0|max:100',
            'largeur_pct'   => 'nullable|numeric|min:0.5|max:100',
            'hauteur_pct'   => 'nullable|numeric|min:0.5|max:100',
            'couleur'       => 'nullable|string|max:20',
            'rotation'      => 'nullable|numeric|min:-360|max:360',
            'electricite'   => 'nullable|boolean',
            'montant'       => 'nullable|numeric|min:0',
        ]);

        if (! empty($data['commercant_id'])) {
            $commercant = Commercant::findOrFail($data['commercant_id']);
            abort_if($commercant->mairie_id !== $plan->mairie_id, 403);
        }

        $emplacement = $plan->emplacements()->create([
            'commercant_id' => $data['commercant_id'] ?? null,
            'label'         => $data['label'] ?? null,
            'pos_x'         => $data['pos_x'],
            'pos_y'         => $data['pos_y'],
            'largeur_pct'   => $data['largeur_pct'] ?? 6,
            'hauteur_pct'   => $data['hauteur_pct'] ?? 4,
            'couleur'       => $data['couleur'] ?? '#e6a23c',
            'rotation'      => (float) ($data['rotation'] ?? 0),
            'electricite'   => (bool) ($data['electricite'] ?? false),
            'montant'       => $data['montant'] ?? null,
        ]);

        ActivityLogger::log('MARCHE', 'PLACE', 'Emplacement "' . ($emplacement->label ?: $emplacement->commercant?->full_name ?: '#' . $emplacement->id) . "\" posé sur le plan du {$plan->date->format('d/m/Y')}");

        return $this->retourPlan($request, $plan, 'Emplacement posé sur le plan.');
    }

    /** Modifier les infos d'un carré (commerçant, label, couleur, élec, montant) */
    public function updateStand(Request $request, MarcheEmplacement $emplacement)
    {
        $plan = $emplacement->planParent();
        $this->verifierPlan($request, $plan);
        $this->verifierEdition();

        $data = $request->validate([
            'commercant_id' => 'nullable|exists:commercants,id',
            'label'         => 'nullable|string|max:50',
            'couleur'       => 'nullable|string|max:20',
            'rotation'      => 'nullable|numeric|min:-360|max:360',
            'electricite'   => 'nullable|boolean',
            'montant'       => 'nullable|numeric|min:0',
        ]);

        if (! empty($data['commercant_id'])) {
            $commercant = Commercant::findOrFail($data['commercant_id']);
            abort_if($commercant->mairie_id !== $plan->mairie_id, 403);
        }

        $emplacement->update([
            'commercant_id' => $data['commercant_id'] ?? null,
            'label'         => $data['label'] ?? null,
            'couleur'       => $data['couleur'] ?? $emplacement->couleur,
            'rotation'      => (float) ($data['rotation'] ?? 0),
            'electricite'   => (bool) ($data['electricite'] ?? false),
            'montant'       => $data['montant'] ?? null,
        ]);

        return $this->retourPlan($request, $plan, 'Emplacement mis à jour.');
    }

    /** Sauvegarde groupée des positions/tailles après déplacement à la souris */
    public function savePositions(Request $request, MarchePlan $plan)
    {
        $this->verifierPlan($request, $plan);
        $this->verifierEdition();

        $data = $request->validate([
            'stands'                 => 'required|array',
            'stands.*.id'            => 'required|integer',
            'stands.*.pos_x'         => 'required|numeric|min:0|max:100',
            'stands.*.pos_y'         => 'required|numeric|min:0|max:100',
            'stands.*.largeur_pct'   => 'required|numeric|min:0.5|max:100',
            'stands.*.hauteur_pct'   => 'required|numeric|min:0.5|max:100',
        ]);

        foreach ($data['stands'] as $s) {
            $plan->emplacements()->whereKey($s['id'])->update([
                'pos_x'       => $s['pos_x'],
                'pos_y'       => $s['pos_y'],
                'largeur_pct' => $s['largeur_pct'],
                'hauteur_pct' => $s['hauteur_pct'],
            ]);
        }

        ActivityLogger::log('MARCHE', 'UPDATE', "Positions du plan du {$plan->date->format('d/m/Y')} enregistrées (" . count($data['stands']) . ' emplacements)');

        return response()->json(['ok' => true]);
    }

    /** Le commerçant n'est finalement pas venu → on le retire du plan */
    public function destroyEmplacement(Request $request, MarcheEmplacement $emplacement)
    {
        $this->verifierPlan($request, $emplacement->planParent());
        $this->verifierEdition();

        $plan = $emplacement->planParent();
        $nom  = $emplacement->commercant?->full_name ?? '—';
        $emplacement->delete();

        ActivityLogger::log('MARCHE', 'RETIRE', "Exposant retiré du plan : {$nom}");

        return $this->retourPlan($request, $plan, "{$nom} retiré du plan.");
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function verifierPlan(Request $request, MarchePlan $plan): void
    {
        $mairie = $this->mairieCourante($request);
        abort_if($plan->mairie_id !== $mairie->id, 403);
    }

    private function paramsMairie(Request $request): array
    {
        return $request->user()->isAdmin() && $request->filled('mairie')
            ? ['mairie' => $request->integer('mairie')]
            : [];
    }

    private function retourPlan(Request $request, MarchePlan $plan, string $message)
    {
        return redirect()->route('marche.plan', $this->paramsMairie($request) + ['plan' => $plan->id])
            ->with('success', $message);
    }

    /** [gauche, droite] : mètres libres autour d'un emplacement sur son axe */
    private function espacesAutour(MarcheAxe $axe, MarcheEmplacement $emp): array
    {
        $autres = $axe->emplacements->where('id', '!=', $emp->id);

        $precedent = $autres->filter(fn ($e) => $e->position + $e->longueur <= $emp->position + 0.001)
            ->sortBy('position')->last();
        $suivant = $autres->filter(fn ($e) => $e->position >= $emp->position + $emp->longueur - 0.001)
            ->sortBy('position')->first();

        $gauche = $emp->position - ($precedent ? $precedent->position + $precedent->longueur : 0);
        $droite = ($suivant ? $suivant->position : (float) $axe->longueur) - ($emp->position + $emp->longueur);

        return [round(max($gauche, 0), 2), round(max($droite, 0), 2)];
    }

    private function chevauche(MarcheAxe $axe, MarcheEmplacement $emp): bool
    {
        return $axe->emplacements
            ->where('id', '!=', $emp->id)
            ->contains(fn ($e) => $emp->position < $e->position + $e->longueur - 0.001
                && $e->position < $emp->position + $emp->longueur - 0.001);
    }
}
