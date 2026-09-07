<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\CourrierIdentifiants;
use App\Models\Mairie;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\Referentiel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Gestion des utilisateurs côté admin : on voit tout, toutes mairies,
 * avec les colonnes "mairie" et "équipe" en plus.
 */
class UserController extends Controller
{
    public function index()
    {
        $users = User::with('mairie')
            ->orderByRaw("CASE role WHEN 'admin' THEN 0 ELSE 1 END")
            ->orderBy('mairie_id')
            ->orderBy('grade')
            ->orderBy('nom')
            ->get();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create', [
            'mairies'          => Mairie::orderBy('nom')->get(),
            'binomesPossibles' => collect(), // choisi ensuite dans la gestion de la mairie
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'prenom'              => 'required|string|max:100',
            'nom'                 => 'required|string|max:100',
            'role'                => 'required|in:admin,user',
            'mairie_id'           => 'required_if:role,user|nullable|exists:mairies,id',
            'service'             => 'required_if:role,user|nullable|integer|in:' . implode(',', array_keys(Referentiel::SERVICES)),
            'grade'               => 'required_if:role,user|nullable|integer|in:' . implode(',', array_keys(Referentiel::GRADES)),
            'droits'              => 'nullable|array',
            'droits.*'            => 'string|in:' . implode(',', array_keys(Referentiel::DROITS)),
            'fonction'            => 'nullable|string|max:150',
            // Case unique « Réceptionner les messages extérieurs »
            'communication'       => 'nullable|array',
            'communication.*'     => 'string|in:inconnu',
            'binome_id'           => 'nullable|exists:users,id',
            'email'               => 'nullable|email|unique:users,email',
            'telephone_indicatif' => 'nullable|string|max:8',
            'telephone'           => 'nullable|string|max:20',
            // Seul un compte administrateur garde un mot de passe choisi :
            // les agents de mairie recoivent un provisoire tire au sort.
            'password'            => 'required_if:role,admin|nullable|min:8',
        ]);

        $estAdmin = $data['role'] === 'admin';

        if (! $estAdmin && ! in_array((int) $data['grade'], Referentiel::gradesAutorises((int) $data['service']), true)) {
            return back()->withInput()->withErrors(['grade' => 'Ce statut n\'est pas autorisé pour ce service.']);
        }

        $motDePasse = User::genererMotDePasseProvisoire();

        $user = User::create([
            'prenom'                   => $data['prenom'],
            'nom'                      => $data['nom'],
            'username'                 => User::genererUsername($data['prenom'], $data['nom']),
            'email'                    => ($data['email'] ?? null) ?: null,
            'password'                 => $estAdmin ? $data['password'] : $motDePasse,
            'temp_password'            => $estAdmin ? null : $motDePasse,
            'temp_password_expires_at' => $estAdmin ? null : now()->addHours(User::HEURES_TEMP_PASSWORD),
            'must_change_password'     => ! $estAdmin,
            'role'                     => $data['role'],
            'mairie_id'                => $estAdmin ? null : $data['mairie_id'],
            'service'                  => $estAdmin ? null : (int) $data['service'],
            'grade'                    => $estAdmin ? null : (int) $data['grade'],
            'droits'                   => $estAdmin ? null : array_values(array_unique($data['droits'] ?? [])),
            'fonction'                 => (! $estAdmin && (int) $data['grade'] === Referentiel::GRADE_EMPLOYE) ? ($data['fonction'] ?? null) : null,
            'communication'            => $estAdmin ? null : array_values(array_unique($data['communication'] ?? [])),
            'binome_id'                => $estAdmin ? null : ($data['binome_id'] ?? null),
            'reference'                => $estAdmin ? null : User::genererReference((int) $data['mairie_id'], (int) $data['service']),
            'telephone_indicatif'      => ($data['telephone_indicatif'] ?? null) ?: '+33',
            'telephone'                => ($data['telephone'] ?? null) ?: null,
        ]);

        ActivityLogger::user('CREATE', "Utilisateur créé par admin : \"{$user->username}\" (role : {$user->role}, mairie : " . ($user->mairie?->nom ?? '—') . ')');

        return $estAdmin
            ? redirect()->route('users.index')->with('success', 'Administrateur créé.')
            : redirect()->route('users.index')
                ->with('success', "Utilisateur « {$user->username} » créé.")
                ->with('courrier_id', $user->id);
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', [
            'user'             => $user,
            'mairies'          => Mairie::orderBy('nom')->get(),
            'binomesPossibles' => $user->mairie_id
                ? User::where('mairie_id', $user->mairie_id)->where('role', 'user')
                    ->where('id', '!=', $user->id)->orderBy('nom')->get()
                : collect(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'prenom'              => 'required|string|max:100',
            'nom'                 => 'required|string|max:100',
            'role'                => 'required|in:admin,user',
            'mairie_id'           => 'required_if:role,user|nullable|exists:mairies,id',
            'service'             => 'required_if:role,user|nullable|integer|in:' . implode(',', array_keys(Referentiel::SERVICES)),
            'grade'               => 'required_if:role,user|nullable|integer|in:' . implode(',', array_keys(Referentiel::GRADES)),
            'droits'              => 'nullable|array',
            'droits.*'            => 'string|in:' . implode(',', array_keys(Referentiel::DROITS)),
            'fonction'            => 'nullable|string|max:150',
            // Case unique « Réceptionner les messages extérieurs »
            'communication'       => 'nullable|array',
            'communication.*'     => 'string|in:inconnu',
            'binome_id'           => 'nullable|exists:users,id',
            'email'               => 'nullable|email|unique:users,email,' . $user->id,
            'telephone_indicatif' => 'nullable|string|max:8',
            'telephone'           => 'nullable|string|max:20',
            'password'            => 'nullable|min:8',   // administrateur uniquement
            'reinitialiser_mdp'   => 'nullable|boolean',
        ]);

        $estAdmin  = $data['role'] === 'admin';

        if (! $estAdmin && ! in_array((int) $data['grade'], Referentiel::gradesAutorises((int) $data['service']), true)) {
            return back()->withInput()->withErrors(['grade' => 'Ce statut n\'est pas autorisé pour ce service.']);
        }

        $nomChange = $data['prenom'] !== $user->prenom || $data['nom'] !== $user->nom;
        $serviceChange = ! $estAdmin
            && ((int) $data['service'] !== (int) $user->service || (int) $data['mairie_id'] !== (int) $user->mairie_id);

        $user->fill([
            'prenom'              => $data['prenom'],
            'nom'                 => $data['nom'],
            'email'               => ($data['email'] ?? null) ?: null,
            'role'                => $data['role'],
            'mairie_id'           => $estAdmin ? null : $data['mairie_id'],
            'service'             => $estAdmin ? null : (int) $data['service'],
            'grade'               => $estAdmin ? null : (int) $data['grade'],
            'droits'              => $estAdmin ? null : array_values(array_unique($data['droits'] ?? [])),
            'fonction'            => (! $estAdmin && (int) $data['grade'] === Referentiel::GRADE_EMPLOYE) ? ($data['fonction'] ?? null) : null,
            'communication'       => $estAdmin ? null : array_values(array_unique($data['communication'] ?? [])),
            'binome_id'           => $estAdmin ? null : ($data['binome_id'] ?? null),
            'telephone_indicatif' => ($data['telephone_indicatif'] ?? null) ?: '+33',
            'telephone'           => ($data['telephone'] ?? null) ?: null,
        ]);

        if ($nomChange) {
            $user->username = User::genererUsername($data['prenom'], $data['nom'], $user->id);
        }
        if ($serviceChange) {
            $user->reference = User::genererReference((int) $data['mairie_id'], (int) $data['service']);
        }
        // Un administrateur choisit son mot de passe ; un agent de mairie
        // reçoit un provisoire tiré au sort.
        if ($user->role === 'admin' && ! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        } elseif ($user->role !== 'admin' && $request->boolean('reinitialiser_mdp')) {
            $user->attribuerMotDePasseProvisoire();
        }

        $user->save();

        ActivityLogger::user('UPDATE', "Utilisateur modifié par admin : \"{$user->username}\"");

        return redirect()->route('users.index')->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->withErrors(['delete' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        }

        $username = $user->username;
        $user->delete();

        ActivityLogger::user('DELETE', "Utilisateur supprimé par admin : \"{$username}\"");

        return redirect()->route('users.index')->with('success', 'Utilisateur supprimé.');
    }

    public function courrier(User $user)
    {
        if (! $user->must_change_password || $user->role === 'admin') {
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
}
