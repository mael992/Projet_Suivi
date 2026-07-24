<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /** Préférences de réception des messages externes (droit communication extérieur). */
    public function communication(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->isAdmin(), 403);

        $data = $request->validate([
            'categories'   => 'nullable|array',
            'categories.*' => 'string|in:inconnu,' . implode(',', array_keys(\App\Support\Referentiel::SERVICES)),
        ]);

        // Toujours enregistrer un tableau explicite (même vide = ne rien recevoir)
        $user->communication = array_values(array_unique($data['categories'] ?? []));
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'communication-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
