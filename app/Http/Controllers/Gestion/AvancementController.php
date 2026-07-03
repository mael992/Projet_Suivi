<?php

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Support\Referentiel;
use Illuminate\Http\Request;

/**
 * Avancement des tâches de travail : taux de charge par service
 * (en cours de réalisation / déjà réalisées et clôturées / à venir),
 * avec filtrage par période.
 */
class AvancementController extends Controller
{
    public function index(Request $request)
    {
        $mairie = auth()->user()->mairie;
        abort_unless($mairie !== null, 403);

        $query = $mairie->taches();

        if ($request->filled('date_debut')) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }

        $taches = $query->get(['service', 'statut']);

        $stats = [];
        foreach (Referentiel::SERVICES as $numero => $label) {
            $duService = $taches->where('service', $numero);
            $total     = $duService->count();

            $stats[] = [
                'service'  => $label,
                'total'    => $total,
                'en_cours' => $duService->where('statut', Referentiel::STATUT_EN_COURS)->count(),
                'fait'     => $duService->where('statut', Referentiel::STATUT_FAIT)->count(),
                'a_venir'  => $duService->where('statut', Referentiel::STATUT_OUVERT)->count(),
            ];
        }

        return view('gestion.avancement.index', compact('mairie', 'stats'));
    }
}
