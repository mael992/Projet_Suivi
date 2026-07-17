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
            'user_id'                 => 'required|exists:users,id',
            'date_butoir'             => 'required|date|after_or_equal:today',
            'photo_avant'             => 'nullable|image|max:8192',
            'photo_apres'             => 'nullable|image|max:8192',
            'description_instruction' => 'nullable|string|max:5000',
        ]);

        $mairieId = $user->isAdmin() ? (int) $data['mairie_id'] : $user->mairie_id;

        // Le responsable chargé de la tâche doit appartenir à la mairie et au service choisis
        $assigne = User::findOrFail($data['user_id']);
        if ($assigne->mairie_id !== $mairieId || $assigne->service !== (int) $data['service']) {
            return back()->withInput()->withErrors(['user_id' => "L'utilisateur sélectionné n'appartient pas à ce service."]);
        }

        $tache = new Tache([
            'service'                 => (int) $data['service'],
            'user_id'                 => (int) $data['user_id'],
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

        ActivityLogger::log('TACHE', 'CREATE', "Tâche {$tache->reference} créée (mairie #{$mairieId}, service {$tache->service_label}, responsable : {$assigne->username})");
        TacheNotifier::notifierAffectation($tache, $assigne);

        return redirect()->route('dashboard')->with('success', "Tâche {$tache->reference} créée.");
    }

    public function show(Tache $tache)
    {
        $this->autoriserVue($tache);

        $user = auth()->user();

        // Employés proposés pour la substitution (même mairie, même service)
        $employes = collect();
        $estResponsable = $user->id === $tache->user_id;
        if ($estResponsable && ($tache->enAttentePriseEnCharge() || $tache->prise_en_charge === 'substitution')) {
            $employes = $this->employesDuService($tache);
        }

        return view('taches.show', compact('tache', 'employes'));
    }

    /** Le responsable change la personne substituée (crayon sur la page Voir). */
    public function changerSubstitut(Request $request, Tache $tache)
    {
        $user = auth()->user();
        abort_unless($user->id === $tache->user_id, 403);
        abort_unless($tache->prise_en_charge === 'substitution' && ! $tache->estFaite(), 403);

        $data = $request->validate([
            'substitut_id' => 'required|exists:users,id',
        ]);

        $substitut = User::findOrFail($data['substitut_id']);
        if ($substitut->mairie_id !== $tache->mairie_id) {
            return back()->withErrors(['substitut_id' => "L'employé sélectionné n'appartient pas à cette mairie."]);
        }

        if ($substitut->id !== $tache->substitut_id) {
            $tache->update(['substitut_id' => $substitut->id]);
            ActivityLogger::log('TACHE', 'UPDATE', "Tâche {$tache->reference} : substitution changée pour {$substitut->username}");
            TacheNotifier::notifierAffectation($tache, $substitut);
        }

        return redirect()->route('taches.show', $tache)
            ->with('success', "Substitution mise à jour : {$substitut->username} a été prévenu par email.");
    }

    /** Le responsable prend en charge la tâche ou la substitue à un employé. */
    public function prendreEnCharge(Request $request, Tache $tache)
    {
        $user = auth()->user();
        abort_unless($user->id === $tache->user_id, 403);
        abort_unless($tache->enAttentePriseEnCharge(), 403);

        $data = $request->validate([
            'mode'         => 'required|in:responsable,substitution',
            'substitut_id' => 'required_if:mode,substitution|nullable|exists:users,id',
        ]);

        if ($data['mode'] === 'substitution') {
            $substitut = User::findOrFail($data['substitut_id']);
            if ($substitut->mairie_id !== $tache->mairie_id) {
                return back()->withErrors(['substitut_id' => "L'employé sélectionné n'appartient pas à cette mairie."]);
            }

            $tache->update([
                'prise_en_charge' => 'substitution',
                'substitut_id'    => $substitut->id,
                'statut'          => Referentiel::STATUT_EN_COURS,
            ]);

            ActivityLogger::log('TACHE', 'UPDATE', "Tâche {$tache->reference} substituée à {$substitut->username} par {$user->username}");
            TacheNotifier::notifierAffectation($tache, $substitut);

            return redirect()->route('taches.show', $tache)
                ->with('success', "Tâche substituée à {$substitut->username} — un email lui a été envoyé.");
        }

        $tache->update([
            'prise_en_charge' => 'responsable',
            'statut'          => Referentiel::STATUT_EN_COURS,
        ]);

        ActivityLogger::log('TACHE', 'UPDATE', "Tâche {$tache->reference} prise en charge par {$user->username}");

        return redirect()->route('taches.show', $tache)->with('success', 'Vous avez pris en charge cette tâche.');
    }

    /** Clôture : commentaire obligatoire (non vide) + photo si exigée. */
    public function cloturer(Request $request, Tache $tache)
    {
        $user = auth()->user();
        abort_unless($tache->peutEtreClotureePar($user), 403);

        $data = $request->validate([
            'description_cloture' => 'required|string|max:5000',
            'photo_apres'         => 'nullable|image|max:8192',
        ], [
            'description_cloture.required' => 'Le commentaire de clôture est obligatoire.',
        ]);

        if ($tache->photo_avant && ! $tache->photo_apres && ! $request->hasFile('photo_apres')) {
            return back()->withErrors(['photo_apres' => 'La photo de la tâche une fois finie est obligatoire (une photo « à faire » existe).']);
        }

        if ($request->hasFile('photo_apres')) {
            if ($tache->photo_apres) {
                Storage::disk('public')->delete($tache->photo_apres);
            }
            $tache->photo_apres = $request->file('photo_apres')->store('taches', 'public');
        }

        $tache->description_cloture = $data['description_cloture'];
        $tache->statut              = Referentiel::STATUT_FAIT;
        $tache->date_cloture        = now();
        $tache->save();

        ActivityLogger::log('TACHE', 'UPDATE', "Tâche {$tache->reference} clôturée par {$user->username}");
        TacheNotifier::notifierCloture($tache);

        return redirect()->route('dashboard')->with('success', "Tâche {$tache->reference} clôturée.");
    }

    public function edit(Tache $tache)
    {
        $this->autoriserVue($tache);
        $this->autoriserCreateur($tache);

        $user = auth()->user();

        return view('taches.edit', [
            'tache'          => $tache,
            'usersService'   => $this->usersParService($tache->mairie_id),
            'employesService' => $this->employesDuService($tache),
            'employeSeul'    => ! $user->peutGererTaches(),
        ]);
    }

    public function update(Request $request, Tache $tache)
    {
        $this->autoriserVue($tache);
        $this->autoriserCreateur($tache);

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
                'user_id'                 => 'required|exists:users,id',
                'substitut_id'            => 'nullable|exists:users,id',
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

            // Le créateur peut changer la personne substituée (si une substitution est en cours)
            $nouveauSubstitut = null;
            if ($tache->prise_en_charge === 'substitution' && ! empty($data['substitut_id'])
                && (int) $data['substitut_id'] !== (int) $tache->substitut_id) {
                $nouveauSubstitut = User::findOrFail($data['substitut_id']);
                if ($nouveauSubstitut->mairie_id !== $tache->mairie_id) {
                    return back()->withErrors(['substitut_id' => "L'employé sélectionné n'appartient pas à cette mairie."]);
                }
                $tache->substitut_id = $nouveauSubstitut->id;
            }

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
        if (isset($nouveauSubstitut) && $nouveauSubstitut) {
            TacheNotifier::notifierAffectation($tache, $nouveauSubstitut);
        }

        return redirect()->route('dashboard')->with('success', "Tâche {$tache->reference} mise à jour.");
    }

    public function destroy(Tache $tache)
    {
        $this->autoriserVue($tache);
        $this->autoriserCreateur($tache);

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

    /** Seul le créateur (ou un admin) peut modifier / supprimer une tâche */
    private function autoriserCreateur(Tache $tache): void
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $tache->created_by === $user->id, 403);
    }

    /** Employés du service de la tâche (candidats à la substitution) */
    private function employesDuService(Tache $tache)
    {
        return User::where('mairie_id', $tache->mairie_id)
            ->where('service', $tache->service)
            ->where('grade', Referentiel::GRADE_EMPLOYE)
            ->where('id', '!=', $tache->user_id)
            ->orderBy('nom')->orderBy('prenom')
            ->get();
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
