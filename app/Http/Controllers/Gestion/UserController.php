<?php

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Mail\CourrierIdentifiants;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\Referentiel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Gestion des utilisateurs de SA mairie (responsables / sous-responsables).
 * Chaque mairie ne voit que ses utilisateurs, jamais ceux de la commune voisine.
 */
class UserController extends Controller
{
    public function index()
    {
        $mairie = $this->mairie();

        // Tri : grade (chef → employé) puis ordre alphabétique sans tenir compte des accents
        $users = User::where('mairie_id', $mairie->id)
            ->where('role', 'user')
            ->get()
            ->sortBy([
                fn ($a, $b) => ($a->grade ?? 99) <=> ($b->grade ?? 99),
                fn ($a, $b) => strcasecmp(Str::ascii($a->nom . $a->prenom), Str::ascii($b->nom . $b->prenom)),
            ])
            ->values();

        return view('gestion.utilisateurs.index', compact('users', 'mairie'));
    }

    public function create()
    {
        return view('gestion.utilisateurs.create', ['mairie' => $this->mairie()]);
    }

    public function store(Request $request)
    {
        $mairie = $this->mairie();

        $data = $request->validate([
            'prenom'              => 'required|string|max:100',
            'nom'                 => 'required|string|max:100',
            'service'             => 'required|integer|in:' . implode(',', array_keys(Referentiel::SERVICES)),
            'grade'               => 'required|integer|in:' . implode(',', array_keys(Referentiel::GRADES)),
            'droit'               => 'nullable|in:' . implode(',', array_keys(Referentiel::DROITS)),
            'fonction'            => 'nullable|string|max:150',
            'email'               => 'nullable|email|unique:users,email',
            'telephone_indicatif' => 'nullable|string|max:8',
            'telephone'           => 'nullable|string|max:20',
            'password'            => 'required|min:8',
        ]);

        if (! in_array((int) $data['grade'], Referentiel::gradesAutorises((int) $data['service']), true)) {
            return back()->withInput()->withErrors(['grade' => 'Ce statut n\'est pas autorisé pour ce service.']);
        }

        $user = User::create([
            'prenom'                   => $data['prenom'],
            'nom'                      => $data['nom'],
            'username'                 => User::genererUsername($data['prenom'], $data['nom']),
            'email'                    => $data['email'] ?: null,
            'password'                 => Hash::make($data['password']),
            'temp_password'            => $data['password'],
            'temp_password_expires_at' => now()->addHours(48),
            'must_change_password'     => true,
            'role'                     => 'user',
            'mairie_id'                => $mairie->id,
            'service'                  => (int) $data['service'],
            'grade'                    => (int) $data['grade'],
            'droit'                    => $data['droit'] ?? null,
            'fonction'                 => (int) $data['grade'] === Referentiel::GRADE_EMPLOYE ? ($data['fonction'] ?? null) : null,
            'reference'                => User::genererReference($mairie->id, (int) $data['service']),
            'telephone_indicatif'      => $data['telephone_indicatif'] ?: '+33',
            'telephone'                => $data['telephone'] ?: null,
        ]);

        ActivityLogger::user('CREATE', "Utilisateur créé : \"{$user->username}\" (mairie : {$mairie->nom}, service : {$user->service_label}, grade : {$user->grade_label})");

        return redirect()->route('gestion.utilisateurs.index')
            ->with('success', "Utilisateur « {$user->username} » créé.")
            ->with('courrier_id', $user->id);
    }

    public function edit(User $user)
    {
        $this->verifierMairie($user);

        return view('gestion.utilisateurs.edit', ['user' => $user, 'mairie' => $this->mairie()]);
    }

    public function update(Request $request, User $user)
    {
        $this->verifierMairie($user);

        $data = $request->validate([
            'prenom'              => 'required|string|max:100',
            'nom'                 => 'required|string|max:100',
            'service'             => 'required|integer|in:' . implode(',', array_keys(Referentiel::SERVICES)),
            'grade'               => 'required|integer|in:' . implode(',', array_keys(Referentiel::GRADES)),
            'droit'               => 'nullable|in:' . implode(',', array_keys(Referentiel::DROITS)),
            'fonction'            => 'nullable|string|max:150',
            'email'               => 'nullable|email|unique:users,email,' . $user->id,
            'telephone_indicatif' => 'nullable|string|max:8',
            'telephone'           => 'nullable|string|max:20',
            'password'            => 'nullable|min:8',
        ]);

        if (! in_array((int) $data['grade'], Referentiel::gradesAutorises((int) $data['service']), true)) {
            return back()->withInput()->withErrors(['grade' => 'Ce statut n\'est pas autorisé pour ce service.']);
        }

        $serviceChange = (int) $data['service'] !== (int) $user->service;
        $nomChange     = $data['prenom'] !== $user->prenom || $data['nom'] !== $user->nom;

        $user->prenom  = $data['prenom'];
        $user->nom     = $data['nom'];
        $user->service  = (int) $data['service'];
        $user->grade    = (int) $data['grade'];
        $user->droit    = $data['droit'] ?? null;
        $user->fonction = (int) $data['grade'] === Referentiel::GRADE_EMPLOYE ? ($data['fonction'] ?? null) : null;
        $user->email    = $data['email'] ?: null;
        $user->telephone_indicatif = $data['telephone_indicatif'] ?: '+33';
        $user->telephone           = $data['telephone'] ?: null;

        if ($nomChange) {
            $user->username = User::genererUsername($data['prenom'], $data['nom'], $user->id);
        }
        if ($serviceChange) {
            $user->reference = User::genererReference($user->mairie_id, (int) $data['service']);
        }
        if (! empty($data['password'])) {
            $user->password                 = Hash::make($data['password']);
            $user->temp_password            = $data['password'];
            $user->temp_password_expires_at = now()->addHours(48);
            $user->must_change_password     = true;
        }

        $user->save();

        ActivityLogger::user('UPDATE', "Utilisateur modifié : \"{$user->username}\" (service : {$user->service_label}, grade : {$user->grade_label})");

        return redirect()->route('gestion.utilisateurs.index')->with('success', __('Utilisateur mis à jour.'));
    }

    public function destroy(User $user)
    {
        $this->verifierMairie($user);

        if ($user->id === auth()->id()) {
            return redirect()->route('gestion.utilisateurs.index')
                ->withErrors(['delete' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        }

        $username = $user->username;
        $user->delete();

        ActivityLogger::user('DELETE', "Utilisateur supprimé : \"{$username}\"");

        return redirect()->route('gestion.utilisateurs.index')->with('success', 'Utilisateur supprimé.');
    }

    /** Courrier PDF avec les identifiants provisoires (comme PlanEx). */
    public function courrier(User $user)
    {
        $this->verifierMairie($user);

        if (! $user->must_change_password) {
            abort(403);
        }

        $pdf       = Pdf::loadView('users.courrier', ['user' => $user])->setPaper('a4', 'portrait');
        $pdfBinary = $pdf->output();

        if ($user->email) {
            try {
                Mail::to($user->email)->send(new CourrierIdentifiants($user, $pdfBinary));
            } catch (\Exception $e) {
                report($e);
            }
        }

        return response($pdfBinary, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="Courrier_MGDS_' . $user->username . '.pdf"');
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function mairie()
    {
        $mairie = auth()->user()->mairie;
        abort_unless($mairie !== null, 403);

        return $mairie;
    }

    private function verifierMairie(User $user): void
    {
        abort_if($user->mairie_id !== auth()->user()->mairie_id, 403);
        abort_if($user->role === 'admin', 403);
    }
}
