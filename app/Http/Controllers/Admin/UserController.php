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
        return view('admin.users.create', ['mairies' => Mairie::orderBy('nom')->get()]);
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
            'droit'               => 'nullable|in:' . implode(',', array_keys(Referentiel::DROITS)),
            'fonction'            => 'nullable|string|max:150',
            'email'               => 'nullable|email|unique:users,email',
            'telephone_indicatif' => 'nullable|string|max:8',
            'telephone'           => 'nullable|string|max:20',
            'password'            => 'required|min:8',
        ]);

        $estAdmin = $data['role'] === 'admin';

        $user = User::create([
            'prenom'                   => $data['prenom'],
            'nom'                      => $data['nom'],
            'username'                 => User::genererUsername($data['prenom'], $data['nom']),
            'email'                    => $data['email'] ?: null,
            'password'                 => Hash::make($data['password']),
            'temp_password'            => $estAdmin ? null : $data['password'],
            'temp_password_expires_at' => $estAdmin ? null : now()->addHours(48),
            'must_change_password'     => ! $estAdmin,
            'role'                     => $data['role'],
            'mairie_id'                => $estAdmin ? null : $data['mairie_id'],
            'service'                  => $estAdmin ? null : (int) $data['service'],
            'grade'                    => $estAdmin ? null : (int) $data['grade'],
            'droit'                    => $estAdmin ? null : ($data['droit'] ?? null),
            'fonction'                 => (! $estAdmin && (int) $data['grade'] === Referentiel::GRADE_EMPLOYE) ? ($data['fonction'] ?? null) : null,
            'reference'                => $estAdmin ? null : User::genererReference((int) $data['mairie_id'], (int) $data['service']),
            'telephone_indicatif'      => $data['telephone_indicatif'] ?: '+33',
            'telephone'                => $data['telephone'] ?: null,
        ]);

        ActivityLogger::user('CREATE', "Utilisateur créé par admin : \"{$user->username}\" (role : {$user->role}, mairie : " . ($user->mairie?->nom ?? '—') . ')');

        return $estAdmin
            ? redirect()->route('users.index')->with('success', 'Administrateur créé.')
            : redirect()->route('users.courrier', $user->id);
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', ['user' => $user, 'mairies' => Mairie::orderBy('nom')->get()]);
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
            'droit'               => 'nullable|in:' . implode(',', array_keys(Referentiel::DROITS)),
            'fonction'            => 'nullable|string|max:150',
            'email'               => 'nullable|email|unique:users,email,' . $user->id,
            'telephone_indicatif' => 'nullable|string|max:8',
            'telephone'           => 'nullable|string|max:20',
            'password'            => 'nullable|min:8',
        ]);

        $estAdmin  = $data['role'] === 'admin';
        $nomChange = $data['prenom'] !== $user->prenom || $data['nom'] !== $user->nom;
        $serviceChange = ! $estAdmin
            && ((int) $data['service'] !== (int) $user->service || (int) $data['mairie_id'] !== (int) $user->mairie_id);

        $user->fill([
            'prenom'              => $data['prenom'],
            'nom'                 => $data['nom'],
            'email'               => $data['email'] ?: null,
            'role'                => $data['role'],
            'mairie_id'           => $estAdmin ? null : $data['mairie_id'],
            'service'             => $estAdmin ? null : (int) $data['service'],
            'grade'               => $estAdmin ? null : (int) $data['grade'],
            'droit'               => $estAdmin ? null : ($data['droit'] ?? null),
            'fonction'            => (! $estAdmin && (int) $data['grade'] === Referentiel::GRADE_EMPLOYE) ? ($data['fonction'] ?? null) : null,
            'telephone_indicatif' => $data['telephone_indicatif'] ?: '+33',
            'telephone'           => $data['telephone'] ?: null,
        ]);

        if ($nomChange) {
            $user->username = User::genererUsername($data['prenom'], $data['nom'], $user->id);
        }
        if ($serviceChange) {
            $user->reference = User::genererReference((int) $data['mairie_id'], (int) $data['service']);
        }
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
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
