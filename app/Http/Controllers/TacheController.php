<?php

namespace App\Http\Controllers;

use App\Models\Mairie;
use App\Models\Tache;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\TacheNotifier;
use App\Support\Referentiel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TacheController extends Controller
{
    /**
     * Tableau des anomalies de la mairie (filtré selon le rôle).
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Tache::with(['assigne', 'createur', 'mairie'])
            ->visiblesPar($user);

        // Filtres
        if ($request->filled('statut') && array_key_exists($request->statut, Referentiel::STATUTS)) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('service') && ($user->voitTousLesServices() || $user->isAdmin())) {
            $query->where('service', (int) $request->service);
        }

        if ($request->filled('mairie') && $user->isAdmin()) {
            $query->where('mairie_id', (int) $request->mairie);
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('reference', 'like', "%{$q}%")
                    ->orWhere('description_instruction', 'like', "%{$q}%")
                    ->orWhereHas('assigne', function ($u) use ($q) {
                        $u->where('username', 'like', "%{$q}%")
                          ->orWhere('nom', 'like', "%{$q}%")
                          ->orWhere('prenom', 'like', "%{$q}%");
                    });
            });
        }

        // Tri : statut (ouvert → en cours → fait) puis plus récent d'abord
        $taches = $query
            ->orderByRaw("CASE statut WHEN 'ouvert' THEN 0 WHEN 'en_cours' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->get();

        $mairies = $user->isAdmin() ? Mairie::orderBy('nom')->get(['id', 'nom']) : collect();

        return view('taches.index', compact('taches', 'mairies'));
    }

    public function create()
    {
        $user = auth()->user();
        abort_unless($user->peutGererTaches(), 403);

        return view('taches.create', [
            'mairies'      => $user->isAdmin() ? Mairie::orderBy('nom')->get() : collect(),
            'usersService' => $this->usersParService($user->isAdmin() ? null : $user->mairie_id),
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->peutGererTaches(), 403);

        $data = $request->validate([
            'mairie_id'               => $user->isAdmin() ? 'required|exists:mairies,id' : 'nullable',
            'service'                 => 'required|integer|in:' . implode(',', array_keys(Referentiel::SERVICES)),
            'user_id'                 => 'nullable|exists:users,id',
            'date_butoir'             => 'required|date|after_or_equal:today',
            'photo_avant'             => 'nullable|image|max:8192',
            'photo_apres'             => 'nullable|image|max:8192',
            'description_instruction' => 'nullable|string|max:5000',
        ]);

        $mairieId = $user->isAdmin() ? (int) $data['mairie_id'] : $user->mairie_id;

        // L'employé assigné doit appartenir à la mairie et au service choisis
        if (!empty($data['user_id'])) {
            $assigne = User::findOrFail($data['user_id']);
            if ($assigne->mairie_id !== $mairieId || $assigne->service !== (int) $data['service']) {
                return back()->withInput()->withErrors(['user_id' => "L'utilisateur sélectionné n'appartient pas à ce service."]);
            }
        }

        $tache = new Tache([
            'service'                 => (int) $data['service'],
            'user_id'                 => $data['user_id'] ?? null,
            'created_by'              => $user->id,
            'statut'                  => Referentiel::STATUT_OUVERT,
            'date_butoir'             => $data['date_butoir'],
            'description_instruction' => $data['description_instruction'] ?? null,
        ]);
        $tache->mairie_id = $mairieId;
        $tache->reference = Tache::genererReference($mairieId, (int) $data['service']);

        if ($request->hasFile('photo_avant')) {
            $tache->photo_avant = $request->file('photo_avant')->store('taches', 'public');
        }
        if ($request->hasFile('photo_apres')) {
            $tache->photo_apres = $request->file('photo_apres')->store('taches', 'public');
        }

        $tache->save();

        ActivityLogger::log('TACHE', 'CREATE', "Tâche {$tache->reference} créée (mairie #{$mairieId}, service {$tache->service_label})");
        TacheNotifier::notifierCreation($tache);

        return redirect()->route('dashboard')->with('success', "Tâche {$tache->reference} créée.");
    }

    public function show(Tache $tache)
    {
        $this->autoriserVue($tache);

        return view('taches.show', compact('tache'));
    }

    public function edit(Tache $tache)
    {
        $this->autoriserVue($tache);

        $user = auth()->user();

        return view('taches.edit', [
            'tache'        => $tache,
            'usersService' => $this->usersParService($tache->mairie_id),
            'employeSeul'  => ! $user->peutGererTaches(),
        ]);
    }

    public function update(Request $request, Tache $tache)
    {
        $this->autoriserVue($tache);

        $user        = auth()->user();
        $gestion     = $user->peutGererTaches();
        $ancienStatut = $tache->statut;

        $rules = [
            'statut'              => 'required|in:' . implode(',', array_keys(Referentiel::STATUTS)),
            'description_cloture' => 'nullable|string|max:5000',
            'photo_apres'         => 'nullable|image|max:8192',
        ];

        if ($gestion) {
            $rules += [
                'user_id'                 => 'nullable|exists:users,id',
                'date_butoir'             => 'required|date',
                'photo_avant'             => 'nullable|image|max:8192',
                'description_instruction' => 'nullable|string|max:5000',
            ];
        }

        $data = $request->validate($rules);

        // Un employé ne peut pas rouvrir une tâche déjà faite
        if (! $gestion && $ancienStatut === Referentiel::STATUT_FAIT && $data['statut'] !== Referentiel::STATUT_FAIT) {
            return back()->withErrors(['statut' => "Seul un grade supérieur peut rouvrir une tâche terminée."]);
        }

        // Photo "une fois fini" obligatoire pour clôturer si une photo "à faire" existe
        $passeAFait = $data['statut'] === Referentiel::STATUT_FAIT && $ancienStatut !== Referentiel::STATUT_FAIT;
        if ($passeAFait && $tache->photo_avant && ! $tache->photo_apres && ! $request->hasFile('photo_apres')) {
            return back()->withErrors(['photo_apres' => "La photo de la tâche une fois finie est obligatoire (une photo « à faire » existe)."]);
        }

        if ($gestion) {
            if (array_key_exists('user_id', $data) && $data['user_id']) {
                $assigne = User::findOrFail($data['user_id']);
                if ($assigne->mairie_id !== $tache->mairie_id || $assigne->service !== $tache->service) {
                    return back()->withErrors(['user_id' => "L'utilisateur sélectionné n'appartient pas à ce service."]);
                }
            }
            $nouvelAssigne = ($data['user_id'] ?? null) && (int) $data['user_id'] !== (int) $tache->user_id;

            $tache->user_id                 = $data['user_id'] ?? null;
            $tache->date_butoir             = $data['date_butoir'];
            $tache->description_instruction = $data['description_instruction'] ?? $tache->description_instruction;

            if ($request->hasFile('photo_avant')) {
                if ($tache->photo_avant) {
                    Storage::disk('public')->delete($tache->photo_avant);
                }
                $tache->photo_avant = $request->file('photo_avant')->store('taches', 'public');
            }
        } else {
            $nouvelAssigne = false;
        }

        if ($request->hasFile('photo_apres')) {
            if ($tache->photo_apres) {
                Storage::disk('public')->delete($tache->photo_apres);
            }
            $tache->photo_apres = $request->file('photo_apres')->store('taches', 'public');
        }

        if (array_key_exists('description_cloture', $data)) {
            $tache->description_cloture = $data['description_cloture'];
        }

        // Date et heure de clôture automatiques dès que le statut passe à "fait"
        $tache->statut = $data['statut'];
        if ($passeAFait) {
            $tache->date_cloture = now();
        } elseif ($tache->statut !== Referentiel::STATUT_FAIT) {
            $tache->date_cloture = null;
        }

        $tache->save();

        ActivityLogger::log('TACHE', 'UPDATE', "Tâche {$tache->reference} modifiée (statut : {$ancienStatut} → {$tache->statut})");

        if ($passeAFait) {
            TacheNotifier::notifierCloture($tache);
        } elseif ($nouvelAssigne) {
            TacheNotifier::notifierAssignation($tache);
        }

        return redirect()->route('dashboard')->with('success', "Tâche {$tache->reference} mise à jour.");
    }

    public function destroy(Tache $tache)
    {
        $this->autoriserVue($tache);
        abort_unless(auth()->user()->peutGererTaches(), 403);

        foreach (['photo_avant', 'photo_apres'] as $photo) {
            if ($tache->$photo) {
                Storage::disk('public')->delete($tache->$photo);
            }
        }

        $ref = $tache->reference;
        $tache->delete();

        ActivityLogger::log('TACHE', 'DELETE', "Tâche {$ref} supprimée");

        return redirect()->route('dashboard')->with('success', "Tâche {$ref} supprimée.");
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function autoriserVue(Tache $tache): void
    {
        $visible = Tache::visiblesPar(auth()->user())->whereKey($tache->id)->exists();
        abort_unless($visible, 403);
    }

    /** Utilisateurs groupés par service (pour la liste dépendante du formulaire) */
    private function usersParService(?int $mairieId): array
    {
        $query = User::where('role', 'user')->orderBy('nom')->orderBy('prenom');

        if ($mairieId) {
            $query->where('mairie_id', $mairieId);
        }

        return $query->get()
            ->groupBy(fn ($u) => ($mairieId ? '' : $u->mairie_id . ':') . $u->service)
            ->map(fn ($users) => $users->map(fn ($u) => [
                'id'    => $u->id,
                'label' => $u->username . ' (' . $u->grade_label . ')',
            ])->values())
            ->toArray();
    }
}
