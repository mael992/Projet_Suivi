<?php

namespace App\Http\Controllers\Marche;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Marche\Concerns\ResolveMairie;
use App\Models\Commercant;
use App\Models\Mairie;
use App\Models\MarcheEmplacement;
use Illuminate\Http\Request;

/**
 * Mini-onglet 🏦 Registre : qui est venu, quand, combien de fois,
 * et combien chaque commerçant a rapporté à la mairie.
 * Filtrable par activité et par période (ex : les fleuristes en juillet).
 */
class RegistreController extends Controller
{
    use ResolveMairie;

    public function index(Request $request)
    {
        $mairie = $this->mairieCourante($request);

        $activite  = $request->input('activite');
        $dateDebut = $request->input('date_debut');
        $dateFin   = $request->input('date_fin');

        // Venues = emplacements (sur un axe OU posés en 2D) de plans datés de la mairie
        $condPlan = function ($q) use ($mairie, $dateDebut, $dateFin) {
            $q->where('mairie_id', $mairie->id);
            if ($dateDebut) {
                $q->whereDate('date', '>=', $dateDebut);
            }
            if ($dateFin) {
                $q->whereDate('date', '<=', $dateFin);
            }
        };

        $venues = MarcheEmplacement::with(['commercant', 'axe.plan', 'plan'])
            ->where(function ($q) use ($condPlan) {
                $q->whereHas('axe.plan', $condPlan)
                  ->orWhereHas('plan', $condPlan);
            })
            ->when($activite, fn ($q) => $q->whereHas('commercant', fn ($c) => $c->where('activite', $activite)))
            ->get()
            ->sortByDesc(fn ($v) => $v->planParent()?->date)
            ->values();

        // Statistiques par commerçant sur la période (hors emplacements libres)
        $stats = $venues->filter(fn ($v) => $v->commercant_id)->groupBy('commercant_id')->map(function ($groupe) {
            $commercant = $groupe->first()->commercant;

            return [
                'commercant'     => $commercant,
                'nb_venues'      => $groupe->count(),
                'total_montant'  => round((float) $groupe->sum('montant'), 2),
                'derniere_venue' => $groupe->map(fn ($v) => $v->planParent()?->date)->filter()->max(),
            ];
        })->sortByDesc('total_montant')->values();

        // Répartition par activité (ex : 4 fleuristes en juillet)
        $parActivite = $venues->groupBy(fn ($v) => $v->commercant?->activite ?? '—')
            ->map(fn ($g) => [
                'venues'      => $g->count(),
                'commercants' => $g->pluck('commercant_id')->unique()->count(),
                'montant'     => round((float) $g->sum('montant'), 2),
            ])->sortByDesc('venues');

        $activites = Commercant::where('mairie_id', $mairie->id)
            ->distinct()->orderBy('activite')->pluck('activite');

        $mairies = $request->user()->isAdmin() ? Mairie::orderBy('nom')->get(['id', 'nom']) : collect();

        return view('marche.registre', compact('mairie', 'venues', 'stats', 'parActivite', 'activites', 'mairies'));
    }
}
