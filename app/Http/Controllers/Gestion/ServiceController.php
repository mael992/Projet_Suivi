<?php

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Models\MairieService;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\Referentiel;
use Illuminate\Http\Request;

/**
 * Services (équipes) de la mairie : chaque commune adapte la liste à son
 * organisation — renommer, désactiver, ou ajouter ses propres services.
 */
class ServiceController extends Controller
{
    public function index()
    {
        $mairie = $this->mairie();

        // Nombre d'agents par service, pour prévenir avant désactivation
        $effectifs = User::where('mairie_id', $mairie->id)
            ->where('role', 'user')
            ->selectRaw('service, COUNT(*) as total')
            ->groupBy('service')
            ->pluck('total', 'service')
            ->all();

        return view('gestion.services.index', [
            'mairie'    => $mairie,
            'defaut'    => Referentiel::SERVICES,
            'perso'     => $mairie->servicesPersonnalises()->get()->keyBy('numero'),
            'effectifs' => $effectifs,
            'services'  => $mairie->libellesServices(),
        ]);
    }

    /** Enregistre en une fois les noms et l'activation de tous les services. */
    public function update(Request $request)
    {
        $mairie = $this->mairie();

        $data = $request->validate([
            'services'          => 'required|array',
            'services.*.nom'    => 'nullable|string|max:100',
            'services.*.actif'  => 'nullable|boolean',
        ]);

        foreach ($data['services'] as $numero => $ligne) {
            $numero = (int) $numero;
            $nom    = trim($ligne['nom'] ?? '') ?: (Referentiel::SERVICES[$numero] ?? null);
            $actif  = (bool) ($ligne['actif'] ?? false);

            if ($nom === null) {
                continue;
            }

            MairieService::updateOrCreate(
                ['mairie_id' => $mairie->id, 'numero' => $numero],
                ['nom' => $nom, 'actif' => $actif],
            );
        }

        ActivityLogger::log('MAIRIE', 'SERVICES', "Services de la mairie mis à jour ({$mairie->nom})");

        return redirect()->route('gestion.services.index')->with('success', 'Services mis à jour.');
    }

    /** Ajoute un service propre à la commune. */
    public function store(Request $request)
    {
        $mairie = $this->mairie();

        $data = $request->validate([
            'nom' => 'required|string|max:100',
        ]);

        // Numéro libre à partir de 20 (au-delà du référentiel par défaut)
        $numero = max(20, ((int) $mairie->servicesPersonnalises()->max('numero')) + 1);
        $numero = max($numero, 20);

        MairieService::create([
            'mairie_id' => $mairie->id,
            'numero'    => $numero,
            'nom'       => $data['nom'],
            'actif'     => true,
        ]);

        return redirect()->route('gestion.services.index')
            ->with('success', "Service « {$data['nom']} » ajouté (n° {$numero}).");
    }

    private function mairie()
    {
        $user = auth()->user();
        abort_unless($user->peutGererMairie(), 403);

        $mairie = $user->mairie;
        abort_unless($mairie !== null, 403);

        return $mairie;
    }
}
