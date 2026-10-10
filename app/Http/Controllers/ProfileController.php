<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\DoubleAuthentification as A2F;
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
        // Double authentification : un code, même depuis un appareil de
        // confiance, envoyé à l'adresse ACTUELLE (avant toute modification)
        $emailChange = $request->validated('email') !== $request->user()->email;
        if ($emailChange && ! A2F::confirmeRecemment($request)) {
            return A2F::exigerConfirmation($request, route('profile.edit'));
        }

        $request->user()->fill($request->safe()->only('email'));

        if ($emailChange) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        if ($emailChange) {
            A2F::oublierAppareils($request->user());
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        if (! A2F::confirmeRecemment($request)) {
            return A2F::exigerConfirmation($request, route('profile.edit'));
        }

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
